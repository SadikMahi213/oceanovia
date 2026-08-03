// k6 Load Test — MulitVendor USA
// Run: k6 run --vus 100 --duration 5m --out json=results.json k6_scenario.js

import http from 'k6/http';
import { check, sleep, group } from 'k6';
import { Rate, Trend, Counter } from 'k6/metrics';

const BASE_URL = __ENV.BASE_URL || 'https://yourdomain.com';

// Custom metrics
const pageLoadTime = new Trend('page_load_time');
const apiLatency = new Trend('api_latency');
const errorRate = new Rate('error_rate');
const cacheHitRate = new Rate('cache_hit_rate');

export const options = {
    stages: [
        { duration: '2m', target: 100 },   // Ramp up to 100 users
        { duration: '3m', target: 250 },    // Ramp to 250
        { duration: '2m', target: 500 },    // Ramp to 500
        { duration: '3m', target: 500 },    // Stay at 500
        { duration: '2m', target: 0 },      // Ramp down
    ],
    thresholds: {
        http_req_duration: ['p(95)<2000'],  // 95% of requests under 2s
        error_rate: ['rate<0.05'],          // <5% error rate
    },
};

export default function () {
    // ─── Public Pages ───────────────────────────────────
    group('Homepage', () => {
        const res = http.get(`${BASE_URL}/`);
        check(res, { 'homepage status 200': (r) => r.status === 200 });
        pageLoadTime.add(res.timings.duration);
        errorRate.add(res.status !== 200);
        if (res.headers['X-Cache'] === 'HIT') cacheHitRate.add(1);
        sleep(1);
    });

    group('Products List', () => {
        const res = http.get(`${BASE_URL}/products`);
        check(res, { 'products status 200': (r) => r.status === 200 });
        pageLoadTime.add(res.timings.duration);
        sleep(2);
    });

    group('Categories', () => {
        const res = http.get(`${BASE_URL}/categories`);
        check(res, { 'categories status 200': (r) => r.status === 200 });
        pageLoadTime.add(res.timings.duration);
        sleep(1);
    });

    // ─── Search / API ───────────────────────────────────
    group('Live Search API', () => {
        const res = http.get(`${BASE_URL}/api/search/live?q=test`);
        check(res, { 'search status 200': (r) => r.status === 200 });
        apiLatency.add(res.timings.duration);
        errorRate.add(res.status !== 200);
        sleep(1);
    });

    group('Product Detail', () => {
        // Visit a few different products
        const slugs = ['product-1', 'product-2', 'product-3'];
        const slug = slugs[Math.floor(Math.random() * slugs.length)];
        const res = http.get(`${BASE_URL}/products/${slug}`);
        check(res, { 'product detail 200': (r) => r.status === 200 });
        pageLoadTime.add(res.timings.duration);
        sleep(3);
    });

    // ─── Auth Pages (simulated login) ───────────────────
    group('Login Page', () => {
        const res = http.get(`${BASE_URL}/login`);
        check(res, { 'login page 200': (r) => r.status === 200 });
        pageLoadTime.add(res.timings.duration);
        sleep(1);
    });

    // ─── Health Check ───────────────────────────────────
    group('Health', () => {
        const res = http.get(`${BASE_URL}/health`);
        check(res, { 'health ok': (r) => r.json('status') === 'ok' });
        apiLatency.add(res.timings.duration);
    });
}
