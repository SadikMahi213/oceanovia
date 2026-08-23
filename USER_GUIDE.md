# MulitVendor USA — Complete User Guide

A multi-vendor marketplace connecting customers, sellers, suppliers, and admins on one platform. This guide explains every screen and feature for every type of user.

- **Customers** buy products from sellers and suppliers.
- **Sellers** list their own products, manage inventory, and fulfill orders.
- **Suppliers** stock wholesale inventory, ship bulk orders, and manage shipping zones.
- **Admins** run the whole marketplace: users, products, payouts, refunds, content, and reports.

**Roles & login:** a single account has one role (`customer`, `seller`, `supplier`, or `admin`). After login you are taken straight to your own dashboard. Suspended or inactive accounts cannot log in.

**Demo accounts (after seeding):**

| Role | Email | Password |
|---|---|---|
| Admin | `admin@mulitvendor.com` | `Password@123` |
| Seller | `seller@mulitvendor.com` | `Password@123` |
| Supplier | `supplier@mulitvendor.com` | `Password@123` |
| Customer | `customer@mulitvendor.com` | `Password@123` |

**Currency:** all prices are shown in US Dollars (`$`, 2 decimals).

---

## Table of Contents

1. [Registration & Accounts](#1-registration--accounts)
2. [Storefront (Public / Guests)](#2-storefront-public--guests)
3. [Shopping, Cart & Checkout](#3-shopping-cart--checkout)
4. [Customer Account](#4-customer-account)
5. [Refunds & Returns (Customer)](#5-refunds--returns-customer)
6. [Seller Dashboard](#6-seller-dashboard)
7. [Supplier Dashboard](#7-supplier-dashboard)
8. [Admin Panel](#8-admin-panel)
9. [Payments, Commissions & Payouts (Behind the Scenes)](#9-payments-commissions--payouts)
10. [Notifications, Messages & Reviews](#10-notifications-messages--reviews)
11. [Developer Setup & Common Tasks](#11-developer-setup--common-tasks)
12. [Troubleshooting & FAQ](#12-troubleshooting--faq)

---

## 1. Registration & Accounts

### Creating an account

1. Click **Register** in the top bar.
2. Fill in name, email, phone (optional), and a password.
3. Choose your **account type**:
   - **Customer** — shop and buy.
   - **Seller** — sell your own products (your profile is reviewed by an admin before you can list).
   - **Supplier** — supply wholesale inventory and ship bulk orders.
4. Submit. You are logged in automatically.

> **Password rules:** at least 8 characters and must include uppercase, lowercase, a number, and a symbol (e.g. `Password@123`).

### Email verification

If email verification is enabled, you'll see a "verify your email" screen until you click the link sent to your inbox. Ordering, selling, and most account features require a verified email.

### Logging in / out

- **Login:** use your email + password. You are redirected to your role's dashboard (`/dashboard`, `/seller/dashboard`, `/supplier/dashboard`, or `/admin/dashboard`).
- **Logout:** from the account menu (top right).

### Becoming a seller or supplier later

From your customer dashboard you can switch roles:
- **Become a Seller** → creates a pending seller profile; an admin must approve it before you can list products.
- **Become a Supplier** → creates a pending supplier profile (with KYC documents to submit).

Once switched, you can't go back to being a pure customer with the same account (admin accounts cannot convert).

### Forgot password

Use **Forgot your password?** on the login screen → enter your email → click the reset link → choose a new password.

---

## 2. Storefront (Public / Guests)

Anyone can browse the store without an account.

### Homepage

- **Featured products** (curated picks).
- **Shop by category** with product counts.
- **Banners** (promotions/announcements).
- Search bar and the full navigation menu (desktop mega-menu or mobile menu).

### Products / catalog

Open **Shop** or **All Products**:

- **Filters:** category (parent + children), seller, price range.
- **Search:** keyword search (a live dropdown suggests products as you type).
- **Sort:** newest, price (low→high / high→low), name, best sellers, top rated.
- Results paginate 12 at a time.

### Product detail page

- Image gallery, price, **discount badge** when `compare_price` is higher.
- **Stock / availability** status.
- **Description, variants** (color/size options with their own price & stock) if the seller configured them.
- **Reviews** with a 1–5 star breakdown and recent written reviews.
- **Add to Cart**, **Add to Wishlist**, **share** actions.
- **Related products** and **recently viewed** (remembered locally for 20 items).

### Deals

The **Daily Deals** page lists every product currently on sale (`compare_price > price`).

### Featured Sellers

Browse approved sellers and their product counts.

### Categories

Parent categories have child categories. A category page lists products from that category **and** its children.

### Other public pages

- **FAQ** — answers grouped by topic (shopping, shipping, returns, payments, account).
- **Contact Us** — send a message to the marketplace team (stored and reviewed by admins).
- **About / Privacy / Terms / Help Center** — CMS pages published by admins.
- **Sitemap** at `/sitemap.xml`.

---

## 3. Shopping, Cart & Checkout

### Cart

- Add products to your cart from product pages.
- Update quantities or remove items in the cart.
- Guests: cart is temporary (kept in the browser). **Log in** to save it and merge it when you return.
- A cart summary shows item totals, shipping estimate, and the final total.

### Shipping estimate

Enter your ZIP code (and weight/dimensions when available) to see carrier options. Shipping is:
- **Free** on orders **≥ $50**.
- A **$3 surcharge** applies to orders over 5 lb.

### Checking out (requires login + verified email)

1. Go to **Checkout** from the cart.
2. **Shipping address:** choose a saved address or enter a new one.
3. **Billing address:** can match shipping or be entered separately.
4. **Apply a coupon** if you have one (a discount and maximum discount cap apply).
5. Choose a payment method:
   - **Card (Stripe):** you're redirected to a secure Stripe payment page. After paying you're returned to the confirmation screen.
   - **Cash on Delivery (COD):** the order is confirmed and payment is marked pending until delivery.
6. Confirm the order. You'll see an order confirmation with your order number (`ORD-XXXXX...`).

### After ordering

- The order is processed in the background (inventory is reserved/decremented, seller commissions recorded).
- You can track the order from your account → **Orders**.
- Emails/notifications are sent for key events if mail is configured.

### Cancelling an order

Orders in **pending** or **confirmed** status can be cancelled from the order detail page (a reason is optional). Paid orders cannot be cancelled by the customer — request a refund instead.

### Reordering

From an old order, click **Reorder** to add its products back to your cart.

### Invoice

Every order has a printable **Invoice** view.

---

## 4. Customer Account

Your customer area lives under `/account` (or **Dashboard** after login).

| Section | What you can do |
|---|---|
| **Dashboard** | Order counts, recent orders, recently viewed products, unread notifications, recommended products, available coupons. |
| **Profile** | Edit name, username, phone, date of birth, gender, country/city/state/postal, avatar (≤2 MB), cover image (≤5 MB). |
| **Change password** | Requires your current password and the strong-password rules. |
| **Addresses** | Add / edit / delete shipping & billing addresses; mark one as default. |
| **Orders** | Filter by status (all / pending / processing / shipped / delivered / cancelled), view details, invoice, cancel (when eligible), reorder. |
| **Wishlist** | Products you saved — view and add to cart. |
| **Notifications** | Read notifications; mark single or all as read. |
| **Reviews** | Edit or delete reviews you have written. |
| **Coupons** | See available, used, and expired coupons. |
| **Recently viewed** | Full history of products you viewed. |
| **Wallet** | See your refund credit history and recent non-cancelled orders. |
| **Settings** | Notification preferences, language, timezone. |
| **Security** | Security overview and session information. |

---

## 5. Refunds & Returns (Customer)

### Requesting a refund

1. Open the order you want refunded (**Orders → order detail**).
2. Click **Request a Refund / Return**.
3. Choose whether to refund **one item** (select the item) or the **whole order**.
4. Add a reason (up to 2,000 characters) and submit.

**Eligibility:** orders in `confirmed`, `processing`, `shipped`, or `delivered` status. Pending or already-cancelled orders are not eligible.

### What happens next

- The request goes to **pending**.
- An **admin** (or the **seller**, for seller-sold items) approves or rejects it.
- If **approved**, the payment is refunded (via Stripe if paid by card), the order is marked `refunded`, and seller commissions/earnings are reversed accordingly.
- If **rejected**, you'll see the rejection reason.

---

## 6. Seller Dashboard

Sellers sell their own products and fulfill their own orders. Access: **Seller Dashboard** (`/seller/dashboard`). Requires an **approved seller profile**.

### Dashboard overview

- Total products, orders, and revenue.
- Order status counts and recent orders.
- **Low stock** and **out of stock** alerts.
- Top 5 best-selling products.
- Available balance and pending commission.
- 6-month monthly sales chart.
- Recent reviews, notifications, and orders.

### Products

**List:** search/filter your products, sort, edit status inline.

**Create / edit a product:**
- Title, slug, description, category, brand.
- Price, compare price (sale price), cost.
- Images (≤2 MB each).
- **Variants:** SKU, price, stock, color, size, weight.
- Status: `published` / `draft` / `archived`.
- **Featured** flag and SEO title/description.
- Scheduling fields (publish/unpublish dates).

> You must have an approved seller profile to create products.

### Orders

- List of orders that contain your items.
- Order detail shows the items from your store.
- Update **your items'** fulfillment status: `processing` → `shipped` → `delivered` (or `cancelled`).
- The parent order status updates automatically when all seller items reach the same state.

### Analytics

- 12-month monthly sales.
- Top 10 products by revenue.
- Order status breakdown.
- Revenue vs order count over 6 months.

### Inventory

- View your stock levels.
- **Adjust stock:** add, remove, or set stock, with a reason.
- Per-product **inventory logs** (a full history).

### Payouts

- View your available balance (net of commission).
- **Request payout:** choose a method (bank, PayPal, or Stripe) and enter account details. Minimum $1.
- Manage your **withdrawal methods** (add / delete).

### Returns

- View return/refund requests against your products.
- **Approve** (triggers the refund) or **reject**.

### Messages

- Read and reply to order-related messages from customers.

### Coupons

Create your own coupons:
- Code, type (% or fixed amount), value.
- Minimum order amount and **maximum discount**.
- Usage limit and per-user limit.
- Valid date range.

### Reviews

- See reviews on your products.
- **Reply** to a review (one reply per review).

### Wallet

- Balance, pending/paid commissions, and your last 10 transactions (ledger).

### Reports

- Sales / products / orders reports with a date range.
- **Export as CSV.**

### Settings

- Tax & invoice settings.
- Language, timezone, currency (per-seller).
- Change password.

---

## 7. Supplier Dashboard

Suppliers stock wholesale inventory and ship bulk orders. Access: **Supplier Dashboard** (`/supplier/dashboard`). Requires an approved supplier profile and completed KYC.

### Dashboard overview

- Inventory counts (total / low / out of stock).
- Order counts by status.
- Revenue and balance.
- Low-stock items.
- 12-month monthly sales, top products/categories.
- Recent inventory activity.

### Profile & KYC

- Company/brand info, warehouse/pickup/return addresses, contact person, website, trade license, VAT number, logo/banner.
- **KYC verification:** upload identity and business documents (national ID, passport, business license, tax certificate, company registration, bank & address verification). Accepted formats: JPEG/PNG/JPG/PDF, ≤5 MB each.
- Submit KYC → status becomes **pending** until an admin verifies it.

### Inventory

- Edit stock, threshold, warehouse, batch, expiry.
- **Adjust** (adjustment / damage / return / transfer).
- **Transfer stock** between your warehouses.
- Per-product inventory logs.

### Orders (fulfillment workflow)

Dedicated lists per state: **New → Accepted → Packed → Ready for pickup → Shipped → Delivered**, plus **Returned** and **Cancelled**.

| Action | What it does |
|---|---|
| **Accept** | Marks the order as processing. |
| **Mark packed** | Order item status → packed. |
| **Ready for pickup** | Status → ready_for_pickup. |
| **Fulfill** | Status → shipped (parent order ships when all items ship). |
| **Reject** | Cancels the order; a reason is required. |
| **Add note** | Appends an internal note to the order. |
| **Invoice** | Opens a printable invoice. |

### Shipping

- **Zones:** countries / states / cities / ZIP codes.
- **Rates** per zone: flat, weight-based, or order-total-based (or free), with carrier, rate, weight/order-total bounds, and ETA days.

### Returns

- Approve / reject / refund / replace return requests.
- Approved/refunded items are marked `returned`.

### Reviews

- Reviews on products you supply.
- **Reply** to a review.
- **Report a fake review** (recorded in the audit log).

### Messages

- Threaded messages from sellers/admins, with the ability to attach files to replies.

### Wallet & Finance

- Balance and payout history.
- **Request payout:** methods include bank, PayPal, Stripe, Wise, Payoneer.
  - A **2% platform fee** and **1% tax** are deducted.
- **Ledger** of transactions.

### Settlements

- Full payout history plus your current balance.

### Customers

- List of customers who ordered your products.
- Per-customer: their orders and total spent.

### Reports

- Types: sales, revenue, inventory, orders, products, settlements, tax.
- Date range and **CSV or PDF** export.

### Settings

- Working hours, holiday calendar, shipping preferences, payment settings, bank account, notification email.
- Language, timezone, currency.
- Change password.

---

## 8. Admin Panel

Admins run the entire platform. Access: **Admin Dashboard** (`/admin/dashboard`).

### Dashboard

- Orders by status, paid revenue.
- Pending payouts (count + total), pending refunds.
- Published products, out-of-stock, pending reviews.
- User counts by type, active coupons.
- Recent orders, payouts, refunds, reviews.

### Products

- Search by name/SKU; filter by category, status, stock level (low/out/in), featured.
- Sort by name, price, status, sold, created.
- **Inline status edit** directly on the list.

### Categories

- Search, filter by status/parent.
- Product counts.
- **Inline edit** of category name/status.

### Users

- List users, filter by role & status, search.
- Sortable table.
- **Inline edit** of role and status (active / inactive / suspended).

### Tax Rates

- CRUD tax rates per US state (2-letter code, rate 0–100%).
- Toggle active/inactive. Used automatically at checkout based on shipping state (cached 24h).

### Coupons

- Create/edit coupons: code, % or fixed, value, minimum order, maximum discount, usage limit, per-user limit, date range, active flag.

### Announcements

- Create/edit announcements (type: info / warning / success / alert).

### Banners

- Create/edit banners: image + mobile image, link, text/background colors, button text, sort order, section (hero / promo / featured), status.

### CMS Pages

- Create/edit pages (title, slug, HTML content, meta title/description, status: draft/published). These power the public **About / Privacy / Terms / Help** pages.

### Brands

- Create/edit brands (logo, website, sort order, active).

### FAQs

- Create/edit FAQ items (question, answer, category, sort order, active). These power the public FAQ page.

### Contact Messages

- View messages submitted from the public **Contact Us** form.
- Mark messages as read.

### Orders

- List all orders (status filter, search by order # or user).
- Order detail: user, items, addresses, payment info.

### Reviews

- Filter by status (approved / pending / rejected) and search by product.
- **Approve** or **reject** reviews.

### Payouts

- List & view payout requests.
- **Approve** → **Complete** (marks related commissions as paid) → or **Reject** (returns the balance to the seller).

### Refunds

- Pending refunds first.
- **Approve:** processes the Stripe refund (if applicable), marks the order refunded, reverses commissions and seller balance.
- **Reject:** requires a reason.

### KYC Verifications

- Review supplier verification documents.
- **Approve** (records who/when) or **Reject** (requires admin notes).

### Shipping Methods

- CRUD shipping methods (base rate, rate per kg, free shipping threshold, ETA days, zones).

### Settings

- Central app settings grouped by topic (general, payments, shipping, commission, appearance, etc.).

### Support Tickets

- View tickets, change status (open / in progress / resolved / closed) and priority, assign, close.

### Audit Logs

- Full history of important actions (role changes, review reports, etc.), filtered and paginated.

### Commissions

- List commissions (filter by status/seller, search by order).
- Detail: order, item, seller, linked payout.
- **Mark paid** (usually happens automatically when a payout completes).

### Reports

- **Sales:** revenue, orders, average order value, commission, daily breakdown.
- **Sellers:** revenue / orders / commission per seller.
- **Products:** units sold, revenue, rating per product.
- **Orders:** status & payment-method breakdown, 30-day daily order counts.

---

## 9. Payments, Commissions & Payouts

Understanding the money flow helps everyone use the platform correctly.

### Customer side

- You pay the **order total** shown at checkout (subtotal + shipping + tax − discount).
- Stripe charges exactly the displayed total (an "adjustments" line reconciles any rounding/tax difference).
- COD orders are confirmed immediately; payment is marked `pending` until delivery.

### Seller side

- Each of your products carries a **commission rate** (set on your seller profile by an admin).
- When an order is paid: `commission = item subtotal × rate%`.
- You are **credited the net amount** (item subtotal − commission) into your seller wallet.
- When you **request a payout**, an admin reviews it; on completion the amount is marked paid and removed from your balance.
- Refunds reverse the net credit and the commission.

### Supplier side

- Suppliers earn from their wholesale inventory.
- Payout requests deduct a **2% platform fee** and **1% tax**.
- A ledger of all transactions is shown in **Wallet** and **Settlements**.

### Admin side

- Approve payouts → complete them (commissions auto-marked paid) → or reject (balance returned).
- Approve refunds to reverse money and commissions.

---

## 10. Notifications, Messages & Reviews

### Notifications

Each role has a notification bell/dashboard section:
- View notifications, open a notification, **mark one read** or **mark all read**.

### Messages (sellers & suppliers)

- Order-related conversations between sellers and customers (sellers), and threaded messages between suppliers and their counterparts.
- Reply inline; suppliers can attach files.

### Reviews

- **Customers** write reviews for products they have ordered (a review is auto-approved when the writer has a **delivered** order for that product). Reviews from shoppers who haven't had the product delivered are held (not approved) until an admin approves them.
- **Sellers/suppliers** can reply to reviews.
- **Admins** approve or reject reviews and can see a pending queue.
- Suppliers can **report a fake review** (goes to the audit log).

---

## 11. Developer Setup & Common Tasks

### Requirements

- PHP **8.4+** (the lock file requires ≥ 8.4.1)
- Composer 2.x
- Node.js 22+ and npm
- MySQL 8 / MariaDB (or SQLite for local dev)
- Redis (for sessions/cache/queue in production; optional locally)

### Local install

```bash
git clone https://github.com/SadikMahi213/oceanovia.git
cd oceanovia
cp .env.example .env
php artisan key:generate

# configure .env (DB_CONNECTION, DB_DATABASE, DB_USERNAME, DB_PASSWORD)

composer install
npm install
npm run build          # or: npm run dev (Vite hot reload)

php artisan migrate --seed
php artisan serve
```

Demo login: `admin@mulitvendor.com` / `Password@123` (also seller/supplier/customer variants).

### Running tests

```bash
php artisan test
```

### Production deploy (Contabo VPS)

Scripts live in `deploy/`:

```bash
# One-time server provisioning (as root on a fresh Ubuntu 24.04)
bash deploy/server-setup.sh

# Deploy / update the app
bash deploy/deploy.sh
```

Deploy essentials (if doing manually):
```bash
git pull
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan storage:link
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

Production `.env` should set: `APP_ENV=production`, `APP_DEBUG=false`, a real `APP_URL`, `DB_*` credentials, Redis drivers, and real Stripe/mail keys.

### Queue workers

Queues handle order processing, emails, notifications, and commission jobs (`high`, `default`, `low`, `media`). Run via Supervisor (see `deploy/queue-worker.conf`) or locally:

```bash
php artisan queue:work redis --queue=high,default,low,media
```

### Useful Artisan commands

```bash
php artisan migrate            # run migrations
php artisan db:seed            # seed roles + demo data + CMS pages
php artisan optimize           # cache config/routes/views/events
php artisan storage:link       # expose uploaded files
php artisan schedule:run       # (cron: every minute) — for scheduled jobs
php artisan queue:monitor      # queue health
php artisan about              # app info / environment
```

### Key directories

| Path | What's there |
|---|---|
| `app/Models/` | Eloquent models (Order, Product, SellerProfile, SupplierProfile, Commission, Refund, Transaction, …) |
| `app/Http/Controllers/` | Public, customer, seller, supplier, admin, API controllers |
| `app/Services/` | Business logic: Commission, Payout, Refund, Coupon, Tax, Audit |
| `resources/views/` | Blade templates grouped by role (`customer/`, `seller/`, `supplier/`, `admin/`, `products/`, …) |
| `routes/web.php` `routes/api.php` | Web + API routes |
| `database/migrations/` `database/seeders/` | Schema + seeders |
| `deploy/` | Server setup, deploy, nginx, queue worker, backup scripts |
| `tests/` | Feature + unit tests |

---

## 12. Troubleshooting & FAQ

**Q: I can't log in.**
Check your email is verified and your account status is active (suspended/inactive accounts are blocked). Use "Forgot your password?" to reset.

**Q: My password is rejected when changing/registering.**
Passwords must be 8+ characters with uppercase, lowercase, a number, and a symbol.

**Q: I requested a payout but it's not in my bank yet.**
Payouts go through admin review (approve → complete). The amount shows as pending until then.

**Q: My product isn't showing on the storefront.**
Products must be `published` and your seller profile must be `approved`. Draft/archived products are hidden. Check your inventory isn't out of stock.

**Q: Why is my refund taking a while?**
Refunds need admin/seller approval. Approved refunds are processed via Stripe (if card) and can take a few business days to appear.

**Q: I see a commission/payout mismatch.**
Sellers are credited net of commission (item subtotal − commission). Refunds reverse the same net amount. Check your **Wallet → transactions** ledger.

**Q: Where do uploaded images/files live?**
`storage/app/public` (symlinked to `public/storage`). Make sure `php artisan storage:link` has been run.

**Q: The site is slow or jobs aren't running.**
Check the queue workers (`supervisorctl status` / `php artisan queue:monitor`) and that Redis is up. Homepage/catalog caches can be cleared with `php artisan optimize:clear`.

**Q: I get a 419 error on forms.**
This is a session/CSRF expiry — refresh the page and try again; it usually means your session expired or the session driver isn't persisting.

**Q: How do I deploy an update?**
Run the steps in [Developer Setup & Common Tasks](#11-developer-setup--common-tasks) (git pull → composer → build → migrate → cache). On the VPS: `bash deploy/deploy.sh`.
