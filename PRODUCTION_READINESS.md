# Production Readiness Plan — MulitVendor USA

> Target: 500 concurrent users · <300ms cached pages · <2s page load · <200ms API · 99.9% uptime

---

## Table of Contents

1. [Architecture Diagram](#1-architecture-diagram)
2. [Folder & Code Changes](#2-folder--code-changes)
3. [Server Specification](#3-server-specification)
4. [Database Optimization Checklist](#4-database-optimization-checklist)
5. [Laravel Optimization Checklist](#5-laravel-optimization-checklist)
6. [Redis Configuration](#6-redis-configuration)
7. [Queue Architecture](#7-queue-architecture)
8. [Deployment Guide](#8-deployment-guide)
9. [Scaling Guide](#9-scaling-guide)
10. [Cost Estimation](#10-cost-estimation)
11. [Final Production Readiness Report](#11-final-production-readiness-report)

---

## 1. Architecture Diagram

```
┌─────────────────────────────────────────────────────────────────────┐
│                         Cloudflare CDN                               │
│  (Caching · Image Opt · WAF · Bot Mgmt · HTTP/3 · Rate Limiting)   │
└────────────────────────┬────────────────────────────────────────────┘
                         │
┌────────────────────────▼────────────────────────────────────────────┐
│                    Load Balancer (HAProxy / NLB)                     │
│              SSL Termination · Sticky Sessions · Health Checks       │
└────────────────────────┬────────────────────────────────────────────┘
                         │
┌────────────────────────▼────────────────────────────────────────────┐
│                    Web Tier (2× App Servers)                         │
│  ┌──────────────────────────────────────────────────────────────┐   │
│  │  Nginx + PHP-FPM 8.3                                          │   │
│  │  FastCGI Cache · Brotli · HTTP/2 · HTTP/3                     │   │
│  │  OPcache · Route/Config/View/Event Cache                      │   │
│  └──────────────────────────────────────────────────────────────┘   │
└────────────────────────┬────────────────────────────────────────────┘
                         │
┌────────────────────────▼────────────────────────────────────────────┐
│                    Application Services                              │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐              │
│  │  Redis       │  │  MySQL 8.0   │  │  Queue       │              │
│  │  (Sessions   │  │  (Primary +  │  │  Workers     │              │
│  │   · Cache    │  │   Read       │  │  (3×)        │              │
│  │   · Queues   │  │   Replica)  │  │              │              │
│  │   · Rate     │  └──────────────┘  └──────────────┘              │
│  │     Limiting)│                                                  │
│  └──────────────┘                                                  │
└─────────────────────────────────────────────────────────────────────┘
                         │
┌────────────────────────▼────────────────────────────────────────────┐
│                    Object Storage (Cloudflare R2)                    │
│  (Product Images · Avatars · Documents · Backups)                   │
└─────────────────────────────────────────────────────────────────────┘
                         │
┌────────────────────────▼────────────────────────────────────────────┐
│                    Monitoring Stack                                  │
│  Prometheus + Grafana + Node Exporter + Laravel Pulse + Sentry      │
└─────────────────────────────────────────────────────────────────────┘
```

**Traffic Flow:**
1. User → Cloudflare (cached assets, image optimization, WAF)
2. Cloudflare → Nginx (uncached requests, API calls)
3. Nginx → PHP-FPM (app logic)
4. PHP-FPM → Redis (session read/write, cache get/set)
5. PHP-FPM → MySQL (persistent data)
6. PHP-FPM → Queue Workers (async jobs via Redis)
7. PHP-FPM → R2 (file uploads/serve)

---

## 2. Folder & Code Changes

### 2.1 New Files to Create

```
deploy/
├── nginx/
│   ├── app.conf              # Main Nginx vhost config
│   ├── fastcgi_cache.conf    # FastCGI cache rules
│   ├── security.conf         # Security headers, CSP
│   └── cloudflare.conf       # Cloudflare IP allowlist
├── php/
│   ├── www.conf              # PHP-FPM pool config
│   └── opcache.ini           # OPcache settings
├── mysql/
│   └── my.cnf                # MySQL tuned config
├── redis/
│   └── redis.conf            # Redis config
├── monitor/
│   ├── prometheus.yml        # Prometheus config
│   └── alerts.yml            # Alert rules
├── scripts/
│   ├── deploy.sh             # Zero-downtime deploy script
│   ├── backup.sh             # Automated backup script
│   └── healthcheck.sh        # Health check endpoint script
└── loadtest/
    ├── k6_scenario.js         # k6 load test script
    └── wrk_scenario.lua       # wrk load test script
```

### 2.2 Code Changes Required

#### Critical: Fix N+1 Queries

**File: `app/Models/Product.php`** — Lines 232-251
Change 4 accessors from collection methods to query builder:
```php
// BEFORE (N+1 — loads ALL reviews into memory)
public function getRatingAverageAttribute(): float
{
    $approved = $this->reviews->where('is_approved', true);
    return $approved->isNotEmpty() ? round($approved->avg('rating'), 1) : 0.0;
}

// AFTER (single query)
public function getRatingAverageAttribute(): float
{
    return round($this->reviews()->where('is_approved', true)->avg('rating') ?? 0, 1);
}
```
Apply same pattern for `getReviewsCountAttribute`, `getInStockAttribute`, `getStockQuantityAttribute`.

**File: `app/Http/Controllers/OrderController.php`** — Line 23
```php
// BEFORE
$order = Order::with(['items.product', 'shippingAddress', 'billingAddress'])
// AFTER (add items.seller for N+1 in orders/show.blade.php:86)
$order = Order::with(['items.product', 'items.seller', 'shippingAddress', 'billingAddress'])
```

**File: `app/Http/Controllers/CustomerController.php`** — Lines 201-209
Replace 7 separate count queries with 1 grouped query:
```php
$statusCounts = Order::byUser(auth()->id())
    ->selectRaw("COUNT(*) as total")
    ->selectRaw("SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending")
    ->selectRaw("SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing")
    ->first();
```

**File: `app/Http/Controllers/CheckoutController.php`** — Line 198
```php
// BEFORE
$order->load('items');  // called late
// AFTER — add to route binding or call early
$order->load(['items.product', 'items.seller']);
```

#### Critical: Move Heavy Processes to Queues

Create new Job classes:

| Job | Trigger | Queue |
|-----|---------|-------|
| `ProcessOrder` | Already exists in `app/Jobs/` | `high` |
| `SendEmailNotification` | Register/login/order | `default` |
| `GenerateInvoice` | Order placed | `default` |
| `CalculateCommission` | Order completed | `low` |
| `ResizeProductImage` | Image upload | `media` |
| `UpdateStockFromOrder` | Order placed | `high` |
| `SendSellerNotification` | Order received | `default` |
| `ProcessRefund` | Refund requested | `high` |
| `SyncSearchIndex` | Product CRUD | `low` |
| `GenerateSitemap` | Daily cron | `low` |

#### Cache Strategy

```php
// config/cache.php — change default to redis
'default' => env('CACHE_STORE', 'redis'),

// Cache TTLs:
// Homepage: 300s (Cache::remember)
// Categories: 3600s
// Product detail: 600s
// Settings: 86400s (all day)
// User sessions: handled by Redis directly
```

Add cache tags for grouped invalidation:
```php
Cache::tags(['products', 'homepage'])->flush(); // when product changes
Cache::tags(['categories'])->flush();           // when category changes
```

#### Session Driver

```env
SESSION_DRIVER=redis
SESSION_CONNECTION=sessions  # dedicated Redis connection
```

#### Rate Limiting

```php
// app/Providers/AppServiceProvider.php
RateLimiter::for('api', fn () => Limit::perMinute(60)->by($request->user()?->id ?: $request->ip()));
RateLimiter::for('checkout', fn () => Limit::perMinute(5)->by(auth()->id()));
RateLimiter::for('reviews', fn () => Limit::perMinute(3)->by(auth()->id()));
RateLimiter::for('search', fn () => Limit::perMinute(30)->by($request->ip()));
```

---

## 3. Server Specification

### 3.1 Recommended Configuration (500 concurrent users)

| Component | Specification | Justification |
|-----------|--------------|---------------|
| **App Server** (×2) | 4 vCPU, 8 GB RAM, 100 GB NVMe | 2 servers for HA; each handles 250 concurrent users |
| **Database Server** | 8 vCPU, 16 GB RAM, 200 GB NVMe | MySQL needs RAM for InnoDB buffer pool; CPU for complex queries |
| **Redis Server** | 2 vCPU, 4 GB RAM, 50 GB SSD | In-memory; session + cache + queues fit in 4 GB |
| **Queue Workers** | Run on app servers | No separate server needed at 500 users |
| **Load Balancer** | 2 vCPU, 4 GB RAM | HAProxy or AWS NLB |

### 3.2 Hosting Recommendation

| Option | Cost/Month | Pros | Cons |
|--------|-----------|------|------|
| **Hetzner Cloud VPS** (Recommended) | ~$80-120 | Best price/performance | Manual setup |
| **DigitalOcean App Platform** | ~$200-300 | Managed, easy deploy | More expensive |
| **AWS (t3.medium × 2 + db.t3.medium)** | ~$250-400 | Full ecosystem | Complex pricing |
| **Vultr High Frequency** | ~$90-140 | Good perf/price | Fewer regions |

**Recommendation:** Start with **Hetzner CX41** (4 vCPU, 8 GB, $15.49/mo × 2) + **Hetzner CX51** for DB (8 vCPU, 16 GB, $31.49/mo) = **~$63/mo** for infrastructure. Add Redis ($5/mo managed or run on DB server).

### 3.3 Operating System

**Ubuntu 24.04 LTS** — Minimal install. No GUI. Only install:
- `nginx`, `php8.3-fpm`, `php8.3-cli`, `php8.3-mysql`, `php8.3-redis`, `php8.3-bcmath`, `php8.3-gd`, `php8.3-imagick`
- `mysql-server-8.0` or `percona-server-8.0`
- `redis-server` (≥ 7.0)
- `composer`, `supervisor`, `fail2ban`, `ufw`, `prometheus`, `node-exporter`
- `certbot` (Let's Encrypt SSL)

---

## 4. Database Optimization Checklist

### 4.1 MySQL Configuration (`/etc/mysql/my.cnf`)

```ini
[mysqld]
# InnoDB
innodb_buffer_pool_size = 10G          # 70% of RAM (16GB → 11.2G). Holds indexes + data in memory.
innodb_log_file_size = 1G              # Large enough to handle peak write loads without checkpoint churn.
innodb_flush_log_at_trx_commit = 2     # 0=fastest/risk, 1=safest/slow, 2=balanced (lose 1s on crash).
innodb_flush_method = O_DIRECT         # Bypass OS cache for InnoDB data files; avoids double buffering.
innodb_io_capacity = 2000              # Max IOPS your disk can handle (NVMe can do 2000+).
innodb_io_capacity_max = 4000          # Burst IOPS limit.
innodb_buffer_pool_instances = 8       # 1 instance per GB of buffer pool reduces contention.
innodb_thread_concurrency = 0          # 0 = let InnoDB decide (automatic).

# Connection Pool
max_connections = 500                  # Max concurrent connections. PHP-FPM ≈ 50 per server × 2 = 100, plus overhead.
thread_cache_size = 100                # Cache threads to avoid create/destroy overhead.
wait_timeout = 300                     # Seconds before idle connection is killed (5 min).
interactive_timeout = 300              # Same for interactive connections.

# Query Cache (deprecated in MySQL 8.0 — use ProxySQL or app-level cache)
# query_cache_type = 0                # Disabled in MySQL 8.0+

# Temp tables
tmp_table_size = 64M                   # Max size for in-memory temp tables. Larger = fewer disk temps.
max_heap_table_size = 64M              # Same for MEMORY engine tables.

# Slow query log
slow_query_log = 1
slow_query_log_file = /var/log/mysql/slow.log
long_query_time = 0.5                  # Log queries taking >500ms
log_queries_not_using_indexes = 1      # Find missing indexes
```

### 4.2 Required New Indexes

```sql
-- Composite indexes for common query patterns

-- Orders: filter by user + status (Customer sees "My Orders")
CREATE INDEX orders_user_status_idx ON orders(user_id, status, created_at DESC);

-- Orders: dashboard status counts
CREATE INDEX orders_status_payment_idx ON orders(status, payment_status);

-- Order items: seller sees their items
CREATE INDEX order_items_seller_order_idx ON order_items(seller_id, order_id, id);

-- Products: seller sees products list
CREATE INDEX products_seller_status_idx ON products(seller_id, status, created_at DESC);

-- Products: category browsing (storefront)
CREATE INDEX products_category_status_idx ON products(category_id, status, price);

-- Reviews: product page (aggregated ratings)
CREATE INDEX reviews_product_approved_idx ON reviews(product_id, is_approved, rating, created_at DESC);

-- Payouts: seller payout history
CREATE INDEX seller_payouts_seller_status_idx ON seller_payouts(seller_id, status, created_at DESC);

-- Commissions: seller finance
CREATE INDEX commissions_seller_status_idx ON commissions(seller_id, status, created_at DESC);

-- Carts: session-based (guest checkout)
CREATE INDEX carts_session_user_idx ON carts(session_id, user_id);

-- Recently viewed: cleanup
CREATE INDEX recently_viewed_user_product_idx ON recently_viewed(user_id, product_id, updated_at DESC);

-- Inventory: stock alerts
CREATE INDEX inventory_stock_alert_idx ON inventory(stock_quantity, stock_alert_threshold);

-- Notifications: user inbox
CREATE INDEX user_notifications_notifiable_read_idx ON user_notifications(notifiable_id, notifiable_type, read_at);
```

### 4.3 Query Optimization

| Issue | Location | Fix |
|-------|----------|-----|
| N+1 in Product accessors | `app/Models/Product.php:232-251` | Use query builder, not collection |
| N+1 in `orders.show` view | `resources/views/orders/show.blade.php:86` | Add `items.seller` to eager load |
| 7 separate status queries | `CustomerController.php:201-223` | Single grouped `SELECT` |
| Cart loading without eager loading | `CheckoutController.php:198` | Add `$order->load('items')` before loop |
| Missing eager load on `orderReorder` | `CustomerController.php:254` | Pre-load items, batch product query |
| LIKE search without index | `routes/api.php:14` (`tags like...`) | Add FULLTEXT index or Meilisearch |
| `$order->items->where()` in Blade | Multiple views | Pre-filter in controller |

### 4.4 Connection Pooling

Use **ProxySQL** between PHP and MySQL:
```ini
mysql_servers = (
    { address="10.0.0.1", port=3306, hostgroup=0 },  # Writer
    { address="10.0.0.2", port=3306, hostgroup=1 },  # Reader
)
mysql_query_rules = (
    { rule_id=1, match_pattern="^SELECT .*", destination_hostgroup=1, apply=1 },  # Reads → replica
    { rule_id=2, match_pattern=".*", destination_hostgroup=0, apply=1 },           # Writes → primary
)
```

At 500 users, ProxySQL reduces connection overhead by pooling 100 persistent connections instead of 500 short-lived ones.

### 4.5 Read Replica Setup

```env
DB_CONNECTION=mysql
DB_HOST=10.0.0.1       # Writer
DB_READ_HOST=10.0.0.2  # Reader

# config/database.php
'mysql' => [
    'read' => [
        'host' => env('DB_READ_HOST', '127.0.0.1'),
    ],
    'write' => [
        'host' => env('DB_HOST', '127.0.0.1'),
    ],
    // ... rest of config
],
```

---

## 5. Laravel Optimization Checklist

### 5.1 OPcache (`/etc/php/8.3/cli/conf.d/opcache.ini`)

```ini
opcache.enable=1
opcache.memory_consumption=256         # 256MB for compiled PHP files (enough for Laravel + vendor).
opcache.interned_strings_buffer=32     # 32MB for string deduplication.
opcache.max_accelerated_files=20000    # Laravel + vendor ≈ 15000 files.
opcache.revalidate_freq=600            # Check file changes every 10 min in production.
opcache.fast_shutdown=1                # Fast shutdown sequence.
opcache.validate_timestamps=0          # 0 in prod = never check timestamps (clear OPcache on deploy).
opcache.jit=1255                       # PHP 8.0+ JIT: CPU-optimized tracing JIT.
opcache.jit_buffer_size=256M          # 256MB for JIT-compiled code.
```

### 5.2 PHP-FPM (`/etc/php/8.3/fpm/pool.d/www.conf`)

```ini
pm = dynamic
pm.max_children = 50                   # Max PHP processes per server. 50 × 2 servers = 100 concurrent PHP threads.
pm.start_servers = 10                  # Start with 10.
pm.min_spare_servers = 5               # Keep at least 5 idle.
pm.max_spare_servers = 15              # Max 15 idle.
pm.max_requests = 1000                 # Restart process after 1000 requests (prevent memory leak).
request_terminate_timeout = 300        # Kill requests taking >300s.
request_slowlog_timeout = 2            # Log slow requests (>2s) to slow log.
slowlog = /var/log/php-slow.log
```

### 5.3 Laravel Cache Commands (Run on Deploy)

```bash
php artisan optimize                  # Route + Config + View + Events
php artisan route:cache               # Route registration
php artisan config:cache              # Config (all files → 1 cached file)
php artisan view:cache                # Compiled Blade templates
php artisan event:cache               # Event discovery
composer dump-autoload -o             # Optimized PSR-4 autoloader
```

### 5.4 Additional Laravel Optimizations

```php
// app/Providers/AppServiceProvider.php — boot()
// Disable lazy loading in production (catches missing eager loads)
Model::preventLazyLoading(!app()->isProduction());

// Use cursor() instead of get() for large datasets
Product::cursor()->each(fn ($product) => ...);  // Memory-efficient iteration

// Chunk large exports
Order::chunk(500, fn ($orders) => ...);

// Use read-only replica for reports
DB::connection('mysql_read')->table('orders')->get();

// Eager load with specific columns
Product::with('seller:id,name,email')->get();
```

### 5.5 Frontend Optimizations

```blade
{{-- Lazy load images --}}
<img src="{{ $product->thumbnail }}" loading="lazy" decoding="async" width="400" height="400">

{{-- Preconnect to external origins --}}
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://cdn.jsdelivr.net">

{{-- Prefetch likely next page --}}
<link rel="prefetch" href="/products" as="document">

{{-- Critical CSS inline, deferred CSS --}}
<style>{!! $criticalCss !!}</style>
<link rel="preload" href="{{ mix('css/app.css') }}" as="style" onload="this.onload=null;this.rel='stylesheet'">
```

**JS Bundles:** Enable code splitting and tree shaking in `vite.config.js`:
```js
build: {
    rollupOptions: {
        output: {
            manualChunks: {
                vendor: ['alpinejs', 'lodash'],
                filament: ['@filament/filament'],
            }
        }
    }
}
```

---

## 6. Redis Configuration

### 6.1 Redis Instance Setup

```ini
# /etc/redis/redis.conf

# Memory
maxmemory 3gb                          # 75% of 4 GB RAM. Leave room for OS.
maxmemory-policy allkeys-lru           # Evict least-recently-used keys when memory is full.
    
# Persistence (for queues — can lose cache but not jobs)
save 60 1000                           # Save to disk every 60s if ≥1000 keys changed.
appendonly yes                         # AOF for durability
appendfsync everysec                   # fsync every second (balanced perf/safety)
    
# Connections
maxclients 5000                        # More than enough for 500 users.
timeout 300                            # Close idle connections after 5 min.
    
# Optimizations
activerehashing yes                    # Rehash incrementally to avoid latency spikes.
hz 10                                  # Background tasks frequency (10/sec is fine).
lfu-log-factor 10                      # LFU counter decay factor.
lfu-decay-time 1                       # LFU decay time in minutes.
```

### 6.2 Redis Database Mapping

| Database | Purpose | Persistence | Eviction |
|----------|---------|-------------|----------|
| db0 | **Session** | AOF + RDB | `noeviction` (never lose sessions) |
| db1 | **Cache** (app) | None | `allkeys-lru` |
| db2 | **Queue** (jobs) | AOF + RDB | `noeviction` |
| db3 | **Rate Limiting** | None | `volatile-ttl` |
| db4 | **Locks** | None | `noeviction` |

### 6.3 Laravel Redis Configuration

```env
# .env
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=

# Session
SESSION_DRIVER=redis
SESSION_CONNECTION=sessions

# Cache
CACHE_STORE=redis
REDIS_CACHE_DB=1

# Queue
QUEUE_CONNECTION=redis
REDIS_QUEUE=default
```

```php
// config/database.php — Redis connections
'redis' => [
    // ... default config ...
    
    'sessions' => [
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'password' => env('REDIS_PASSWORD'),
        'port' => env('REDIS_PORT', 6379),
        'database' => 0,  // Session DB
    ],
    
    'cache' => [
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'password' => env('REDIS_PASSWORD'),
        'port' => env('REDIS_PORT', 6379),
        'database' => 1,  // Cache DB
    ],
],
```

---

## 7. Queue Architecture

### 7.1 Queue Configuration

```env
QUEUE_CONNECTION=redis
REDIS_QUEUE=default
```

```php
// config/queue.php
'redis' => [
    'driver' => 'redis',
    'connection' => 'default',     // uses db2
    'queue' => env('REDIS_QUEUE', 'default'),
    'retry_after' => 3600,         // Retry after 1 hour
    'block_for' => 5,              // Block pop for 5 seconds (reduces polling)
    'after_commit' => true,        // Only dispatch after DB commit
],
```

### 7.2 Queue Workers (Supervisor)

```ini
# /etc/supervisor/conf.d/laravel-worker.conf

[program:laravel-worker-high]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/artisan queue:work redis --queue=high --sleep=1 --tries=3 --timeout=300
numprocs=2
autostart=true
autorestart=true
user=www-data

[program:laravel-worker-default]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/artisan queue:work redis --queue=default --sleep=3 --tries=3 --timeout=300
numprocs=4
autostart=true
autorestart=true
user=www-data

[program:laravel-worker-low]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/artisan queue:work redis --queue=low,media --sleep=3 --tries=1 --timeout=600
numprocs=2
autostart=true
autorestart=true
user=www-data
```

### 7.3 Queue Strategy

| Queue | Workers | Priority | Use Cases |
|-------|---------|----------|-----------|
| `high` | 2 | Immediate | Order processing, payments, refunds |
| `default` | 4 | Normal | Emails, notifications, invoices |
| `low` | 1 | Delayed | Commission calc, search sync, analytics |
| `media` | 1 | Background | Image resizing, thumbnail generation |

### 7.4 Queue Monitoring

```bash
# Check queue size
redis-cli -n 2 LLEN queues:high
redis-cli -n 2 LLEN queues:default
redis-cli -n 2 LLEN queues:low

# Check failed jobs
php artisan queue:failed
```

---

## 8. Deployment Guide

### 8.1 Initial Server Setup

```bash
# 1. Update system
apt update && apt upgrade -y
apt install -y nginx mysql-server-8.0 redis-server composer \
    supervisor fail2ban ufw certbot python3-certbot-nginx \
    prometheus prometheus-node-exporter

# 2. Install PHP 8.3
add-apt-repository ppa:ondrej/php -y
apt install -y php8.3-fpm php8.3-cli php8.3-mysql php8.3-redis \
    php8.3-bcmath php8.3-gd php8.3-imagick php8.3-xml php8.3-mbstring \
    php8.3-curl php8.3-zip php8.3-intl

# 3. Configure firewall
ufw allow 22/tcp
ufw allow 80/tcp
ufw allow 443/tcp
ufw --force enable

# 4. Install fail2ban
cp /etc/fail2ban/jail.conf /etc/fail2ban/jail.local
systemctl enable --now fail2ban

# 5. Configure SSL
certbot --nginx -d yourdomain.com -d www.yourdomain.com
```

### 8.2 Nginx Configuration

```nginx
# /etc/nginx/sites-available/app.conf
server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name yourdomain.com;
    
    # SSL
    ssl_certificate /etc/letsencrypt/live/yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/yourdomain.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256;
    ssl_session_cache shared:SSL:10m;
    ssl_session_timeout 10m;
    
    # Brotli
    brotli on;
    brotli_types text/plain text/css application/json application/javascript text/xml image/svg+xml;
    
    # Gzip (fallback)
    gzip on;
    gzip_types text/plain text/css application/json application/javascript text/xml image/svg+xml;
    gzip_min_length 1000;
    gzip_proxied any;
    gzip_vary on;
    
    root /var/www/current/public;
    index index.php;
    
    # FastCGI Cache
    include /etc/nginx/fastcgi_cache.conf;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    # Static assets — cache aggressively
    location ~* \.(jpg|jpeg|png|gif|ico|webp|avif|svg|css|js|woff2?)$ {
        expires 365d;
        add_header Cache-Control "public, immutable";
        access_log off;
    }
    
    # Storage files
    location /storage/ {
        try_files $uri =404;
        expires 30d;
        add_header Cache-Control "public, immutable";
    }
    
    # PHP
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
        
        # FastCGI Cache
        fastcgi_cache_bypass $no_cache;
        fastcgi_no_cache $no_cache;
        fastcgi_cache APP;
        fastcgi_cache_valid 200 302 10m;
        fastcgi_cache_valid 404 1m;
        fastcgi_cache_use_stale error timeout updating http_500;
        fastcgi_cache_lock on;
        fastcgi_cache_key "$scheme$request_method$host$request_uri";
    }
    
    # Security headers
    include /etc/nginx/security.conf;
    include /etc/nginx/cloudflare.conf;
}

# HTTP → HTTPS redirect
server {
    listen 80;
    return 301 https://$host$request_uri;
}
```

### 8.3 FastCGI Cache Config

```nginx
# /etc/nginx/fastcgi_cache.conf
fastcgi_cache_path /var/run/nginx-cache levels=1:2 keys_zone=APP:100m inactive=60m;
fastcgi_cache_key "$scheme$request_method$host$request_uri";

# Skip caching for authenticated users, admin, etc.
set $no_cache 0;
if ($http_cookie ~* "(XSRF-TOKEN|session|remember_web)") {
    set $no_cache 1;
}
if ($request_uri ~* "/admin|/seller|/supplier|/account|/checkout|/cart") {
    set $no_cache 1;
}
if ($request_method != GET) {
    set $no_cache 1;
}
```

### 8.4 Security Headers

```nginx
# /etc/nginx/security.conf
add_header X-Frame-Options "SAMEORIGIN" always;
add_header X-Content-Type-Options "nosniff" always;
add_header X-XSS-Protection "1; mode=block" always;
add_header Referrer-Policy "strict-origin-when-cross-origin" always;
add_header Permissions-Policy "camera=(), microphone=(), geolocation=()" always;

# CSP — adjust as needed
add_header Content-Security-Policy "
    default-src 'self';
    script-src 'self' 'unsafe-inline' 'unsafe-eval' https://js.stripe.com;
    style-src 'self' 'unsafe-inline' https://fonts.googleapis.com;
    img-src 'self' data: blob: https://*.cloudflare.com https://*.stripe.com;
    font-src 'self' https://fonts.gstatic.com;
    connect-src 'self' https://api.stripe.com;
    frame-src https://js.stripe.com;
" always;
```

### 8.5 Zero-Downtime Deploy Script

```bash
#!/bin/bash
# deploy/deploy.sh

set -e

REPO_DIR="/var/www/repo"
CURRENT_DIR="/var/www/current"
RELEASE_DIR="/var/www/releases/$(date +%Y%m%d%H%M%S)"

echo "=== Cloning release ==="
git clone --depth 1 -b main $REPO_DIR $RELEASE_DIR

echo "=== Installing dependencies ==="
cd $RELEASE_DIR
composer install --no-dev --optimize-autoloader --no-interaction
npm ci --production
npm run build

echo "=== Configuring environment ==="
ln -nfs /var/www/.env $RELEASE_DIR/.env

echo "=== Running migrations ==="
php artisan migrate --force

echo "=== Caching ==="
php artisan optimize
php artisan route:cache
php artisan config:cache
php artisan view:cache
php artisan event:cache

echo "=== Switching symlink ==="
ln -nfs $RELEASE_DIR $CURRENT_DIR

echo "=== Reloading services ==="
sudo systemctl reload php8.3-fpm
sudo systemctl reload nginx
sudo supervisorctl restart all

echo "=== Deploy complete ==="
```

### 8.6 Backup Script

```bash
#!/bin/bash
# deploy/backup.sh

DATE=$(date +%Y-%m-%d)
BACKUP_DIR="/backups/$DATE"
mkdir -p $BACKUP_DIR

# Database
mysqldump --single-transaction --quick --routines --triggers \
    mulitvendor_usa | gzip > $BACKUP_DIR/database.sql.gz

# Storage files
rsync -az /var/www/storage/app/public/ $BACKUP_DIR/storage/

# Upload to R2 (offsite)
rclone copy $BACKUP_DIR cloudflare-r2:mulitvendor-backups/$DATE/

# Retention: keep daily for 7 days, weekly for 4 weeks, monthly for 12 months
find /backups -type d -mtime +7 -exec rm -rf {} \;
```

---

## 9. Scaling Guide

### 9.1 Horizontal Scaling (500 → 2000+ users)

| Phase | Users | Servers | Database | Redis |
|-------|-------|---------|----------|-------|
| **Launch** | 0–500 | 2 web + 1 DB | 1 MySQL | 1 Redis |
| **Growth** | 500–2000 | 4 web + 1 DB + 1 replica | 1 primary + 1 replica | 1 Redis (upgraded) |
| **Scale** | 2000–10000 | 8 web (auto-scale) | 1 primary + 3 replicas + ProxySQL | 1 Redis cluster (3 nodes) |
| **Enterprise** | 10000+ | Auto-scale (K8s) | Vitess or PlanetScale | Redis Enterprise |

### 9.2 When to Add More Resources

| Signal | Action |
|--------|--------|
| CPU > 80% on web servers | Add another web server |
| PHP-FPM max_children reached | Increase workers or add server |
| MySQL CPU > 70% | Add read replica |
| Redis memory > 80% | Increase maxmemory or upgrade instance |
| Queue backlog > 1000 jobs | Add more queue workers |
| Page load > 2s | Check N+1, add caching, optimize queries |
| API latency > 200ms | Add Redis cache, check slow queries |

### 9.3 Auto-Scaling (Cloud)

```yaml
# AWS Auto Scaling config
MinSize: 2
MaxSize: 10
ScaleOutPolicy:
  - Metric: CPUUtilization
    Threshold: 70%
    Period: 300
    Cooldown: 120
ScaleInPolicy:
  - Metric: CPUUtilization
    Threshold: 30%
    Period: 600
    Cooldown: 300
```

### 9.4 Search Scaling

**Threshold: 50,000+ products** → Implement Meilisearch:

```bash
# Install Meilisearch
curl -L https://install.meilisearch.com | sh
./meilisearch --master-key=YOUR_KEY

# Laravel package
composer require meilisearch/meilisearch-laravel
```

```php
// config/scout.php
'meilisearch' => [
    'host' => env('MEILISEARCH_HOST', 'http://localhost:7700'),
    'key' => env('MEILISEARCH_KEY'),
],

// app/Models/Product.php
use Laravel\Scout\Searchable;

class Product extends Model
{
    use Searchable;
    
    public function toSearchableArray(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'tags' => $this->tags,
            'category' => $this->category?->name,
            'seller' => $this->seller?->name,
            'price' => $this->price,
            'status' => $this->status,
        ];
    }
}
```

---

## 10. Cost Estimation

### 10.1 Monthly Infrastructure Costs

| Service | Provider | Spec | Cost/Month |
|---------|----------|------|------------|
| **Web Server 1** | Hetzner | CX41 (4 vCPU, 8 GB) | $15.49 |
| **Web Server 2** | Hetzner | CX41 (4 vCPU, 8 GB) | $15.49 |
| **DB Server** | Hetzner | CX51 (8 vCPU, 16 GB) | $31.49 |
| **Redis** | Hetzner (on DB server) | Included | $0 |
| **Object Storage** | Cloudflare R2 | 10 GB storage + 1M requests | ~$1 |
| **CDN** | Cloudflare (Free tier) | Free | $0 |
| **SSL** | Let's Encrypt | Free | $0 |
| **Monitoring** | Grafana Cloud (Free tier) | 10k series | $0 |
| **Sentry** | Free tier | 5k errors/mo | $0 |
| **SMTP** | SendGrid Free | 100 emails/day | $0 |
| **Backups** | Cloudflare R2 | Included in storage | $0 |
| **Domain** | Namecheap | .com renewal | ~$12/yr |
| **Total** | | | **~$63/mo** |

### 10.2 Annual Costs

| Category | Cost/Year |
|----------|-----------|
| Infrastructure | ~$756 |
| Domain | ~$12 |
| Sentry (Pro) | ~$0 (free tier) |
| SMTP (upgrade) | ~$120 (SendGrid Essentials) |
| **Total** | **~$888/year** |

### 10.3 Optional Upgrades

| Service | Cost | When Needed |
|---------|------|-------------|
| Managed Redis (DigitalOcean) | +$15/mo | 1000+ users |
| AWS RDS MySQL | +$50/mo | Want managed DB |
| Meilisearch Cloud | +$29/mo | 50k+ products |
| Laravel Forge | +$10/mo | Managed server deployment |
| Ploi | +$9/mo | Alternative to Forge |

---

## 11. Final Production Readiness Report

### 11.1 Readiness Score: 48/100 (Needs Work)

| Category | Score | Issues |
|----------|-------|--------|
| Architecture | 30/100 | Single server, no CDN, local storage, no Redis |
| Database | 45/100 | Good indexes but missing composites; SQLite dev, MySQL needed |
| Caching | 20/100 | File cache only; no Redis; FastCGI cache not configured |
| Queue | 35/100 | Database queue (too slow); needs Redis + Supervisor |
| Session | 25/100 | File sessions (not suitable for multi-server) |
| N+1 Fixes | 40/100 | 20 issues found, 6 critical |
| Frontend | 50/100 | Basic optimization, no code splitting |
| Security | 55/100 | Basic CSRF/XSS but no CSP, fail2ban, firewall rules |
| Monitoring | 10/100 | Nothing installed |
| Backups | 20/100 | No automated backups |
| CI/CD | 15/100 | No deployment scripts |
| Load Testing | 0/100 | Not yet performed |

### 11.2 Priority Action Plan

| Priority | Task | Effort | Impact | Timeline |
|----------|------|--------|--------|----------|
| **P0** | Fix N+1 queries (6 critical) | 2h | High | Day 1 |
| **P0** | Move to MySQL + add indexes | 4h | High | Day 1 |
| **P0** | Install + configure Redis | 2h | High | Day 1 |
| **P0** | Change session/cache/queue to Redis | 1h | High | Day 1 |
| **P1** | Move heavy processes to queues | 4h | Medium | Day 2 |
| **P1** | Configure Nginx + FastCGI cache | 2h | High | Day 2 |
| **P1** | Set up S3/R2 for file storage | 2h | Medium | Day 2 |
| **P1** | Configure Cloudflare CDN | 1h | Medium | Day 2 |
| **P2** | Implement CSP + security headers | 1h | Medium | Day 3 |
| **P2** | Install monitoring stack | 3h | Medium | Day 3 |
| **P2** | Create deployment scripts | 3h | Medium | Day 3 |
| **P2** | Set up automated backups | 1h | Medium | Day 3 |
| **P3** | Add OPcache + JIT config | 1h | Low | Day 4 |
| **P3** | Run load tests (k6/wrk) | 3h | Medium | Day 4 |
| **P3** | Set up fail2ban + firewall | 1h | Low | Day 4 |
| **P3** | Optimize frontend bundles | 2h | Low | Day 5 |

### 11.3 Expected Performance After Optimization

| Metric | Before | After | Target |
|--------|--------|-------|--------|
| Homepage TTFB | ~800ms | ~80ms (cached) | <300ms |
| Product List | ~500ms | ~120ms | <200ms |
| Product Detail | ~400ms | ~100ms | <200ms |
| Checkout | ~1.2s | ~400ms | <1s |
| API (search) | ~300ms | ~80ms | <200ms |
| Login | ~200ms | ~100ms | <300ms |
| Concurrent Users | ~50 | ~500+ | 500 |
| DB Queries/Request | ~15-25 | ~5-10 | <10 |
| Memory/Request | ~35MB | ~25MB (opcache) | <30MB |
| Queue Throughput | N/A (sync) | ~1000 jobs/min | 500/min |

### 11.4 Key Risks & Mitigations

| Risk | Likelihood | Impact | Mitigation |
|------|-----------|--------|------------|
| **Database contention at 500 users** | Medium | High | Add indexes, read replica, connection pooling |
| **Session loss on server restart** | Low | High | Redis persistence + AOF |
| **Payment double-charge** | Low | Critical | `lockForUpdate` + idempotency key (already implemented) |
| **Image uploads fill disk** | Medium | Medium | R2 object storage + upload size limits |
| **Queue backlog during flash sale** | Low | High | Auto-scale workers, separate high queue |
| **Redis memory exhaustion** | Low | Medium | `maxmemory-policy allkeys-lru`, alert at 80% |
| **SSL certificate expiry** | Low | High | Certbot auto-renew, monitoring alert |

---

## Implementation Summary

**Total estimated implementation time: 4-5 days** for a single developer.

**Day 1** (Foundation):
- Fix all N+1 queries
- Switch to MySQL, create indexes
- Install Redis, configure sessions/cache/queues
- Run `php artisan optimize`

**Day 2** (Infrastructure):
- Configure Nginx with FastCGI cache, Brotli, security headers
- Set up S3/R2 for file storage
- Move heavy processes to queue jobs
- Configure Supervisor for queue workers

**Day 3** (Security & Monitoring):
- Set up Cloudflare (CDN, WAF, caching)
- Install Prometheus + Grafana + Node Exporter
- Set up automated backups
- Configure fail2ban + firewall

**Day 4** (Testing & Polish):
- Run load tests (k6) at 100, 250, 500, 1000 users
- Tweak based on results
- OPcache + JIT configuration
- Frontend bundle optimization

**Day 5** (Documentation & Go-Live):
- Create runbooks
- Deploy to production using zero-downtime script
- Monitor for 24h
- Done.

---

*Generated by production readiness analysis of G:\mulitvendor_usa*
*PHP 8.4 · Laravel 13 · MySQL 8.0 · Redis 7 · Nginx · Ubuntu 24.04*
