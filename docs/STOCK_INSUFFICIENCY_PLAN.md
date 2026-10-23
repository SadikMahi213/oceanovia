# Stock-Insufficiency ("Held Item") Workflow — Final Implementation Plan

Status: **finalized for approval. No application code or tests changed yet.**

Author context: continues the investigation in `docs/STOCK_INSUFFICIENCY_PLAN.md`
(previous revision) and the codebase inspection of the checkout, inventory,
procurement, notification, payment, cancellation and refund paths.

---

## 0. Decisions (locked)

1. **Always create an order** when the customer submits a valid cart, even if
   some or all requested stock is unavailable.
2. Split order items into **fulfillable** and **stock-held**. Held items use the
   order-item status **`awaiting_stock`**.
3. Requested 5 with 3 available → **reserve at most 3 and track the remaining 2
   as held**. Never mark all 5 reserved/fulfillable.
4. **Notify the responsible seller/supplier and the actual ordering customer**
   when the shortage is identified or confirmed. Reuse `UserNotification`,
   `EmailLog`, and the email job, with **database-enforced idempotency**.
5. **Do not charge through Stripe until the payable amount and fulfilment
   quantities are stable.** A zero-total or fully held order must **not** trigger
   a Stripe charge. Existing Stripe transitions are documented in §6.
6. Shipping, tax, discounts and totals are **recalculated consistently** when a
   held item is later confirmed or declined. **A previously charged amount is
   never silently changed** (§7).
7. **Web and API checkout stay consistent** (§5.6).
8. On decline, use the **existing cancellation and refund services**; release
   **only** stock that was actually reserved; **never** duplicate refunds,
   ledger entries, or inventory mutations (§8).
9. **Preserve every pre-existing tracked and untracked change.** No reset,
   clean, stash, overwrite, commit, push, or deploy.

### 0.1 Consequence accepted explicitly

"Totals = confirmed items only" **and** "always create the order" together mean
an all-held order has `subtotal = 0`, `total = 0`. This is a valid, intentional
state:

- `payment_status` stays `pending`; nothing is charged; no COD collection is
  opened; no commission exists for held items.
- Stripe: a zero-total order is **never** handed to Stripe (zero-amount
  sessions are invalid). The web checkout redirects to the order page with an
  "awaiting stock confirmation" message; the API returns the order without a
  payment URL handed to the gateway.
- COD: `finaliseCodOrder` runs only for the confirmed portion. With no
  confirmed portion the order stays `pending` and no COD collection is created.

---

## 1. Current behaviour (verified in code)

- **Inventory commit** `app/Services/InventoryService.php:29-95`
  `reserveAndDecrementForCart(Cart)` → `SourceStockService::reserveForCart`
  (supplier rows, available = `stock_quantity - reserved_quantity`,
  `app/Services/SourceStockService.php:28-76`) then locks local `inventory`
  rows in ascending id order and decrements. On shortfall it throws
  `ValidationException('cart')` → whole transaction rolls back.
- **Web checkout** `app/Http/Controllers/CheckoutController.php:136-291`
  Transaction: lock cart (+items) → create `Order` (`status=pending`,
  `payment_status=pending`) → create `OrderItem`s → `CommissionService::
  createCommissions` → `InventoryService::reserveAndDecrementForCart` → delete
  cart → COD: `CheckoutService::finaliseCodOrder`. Post-commit:
  `CheckoutService::dispatchPostCommitEffects` (emits `OrderPlaced`, queues
  `ProcessOrder`). Stripe path redirects to `checkout.stripe`.
- **API checkout** `app/Http/Controllers/Api/CheckoutController.php:43-177`
  Same shape, plus `assertCartItemsAvailable` (published check only), no stock
  check in that method. Returns `payment_url` for the web `checkout.index`.
- **Order/OrderItem** `app/Models/Order.php`, `app/Models/OrderItem.php`
  `order_items.status` exists (`varchar(30)`, default `pending`,
  `2026_06_27_000005_add_status_to_order_items.php`). No `held_quantity` today.
- **COD** `app/Services/CodCollectionService.php` `createForOrder` (only when
  `payment_method = cod`), `expectedAmountForItem` = line subtotal + a
  proportional share of `(shipping + tax - discount)`.
- **Commission** `app/Services/CommissionService.php` `createCommissions`
  creates one commission per **all** order items from `item->subtotal`.
- **Procurement** `app/Services/ProcurementService.php` `createForOrder`
  (sourced items only) and `syncProcurement`, whose `aggregateStatus()`
  (`:473-503`) maps `order_item.status` → procurement status. `awaiting_stock`
  is **not** in its rank map (would default to `pending`) — must be handled.
- **Shipping** `app/Services/ShippingService.php` groups cart items by
  vendor, adds per-vendor cost. **Tax** `app/Services/TaxService.php` =
  `round(subtotal * rate)` by state. **Coupon** `app/Services/CouponService.php`
  discount from `order->subtotal`.
- **Cancellation** `app/Services/OrderCancellationService.php` restores local
  inventory only for non-sourced items and delegates
  `ProcurementService::cancelForOrder`; guards paid orders and collected COD.
- **Refunds** `app/Services/RefundService.php` + `app/Support/RefundCalculator.php`
  item-scoped/proportional, ledgered reversals; `requestRefund` only for
  `confirmed|processing|shipped|delivered`.
- **Notifications** `app/Listeners/SendOrderPlacedNotification.php` +
  `app/Jobs/SendAdminEmail.php` + `app/Models/EmailLog.php`: idempotency via
  `email_logs.idempotency_key` UNIQUE (`2026_10_21_000001_...`). In-app
  `UserNotification` created by `ProcurementService::notifyUser:515-527`.
- **Existing contradicting test** `tests/Feature/CheckoutTest.php:377`
  `test_new_addresses_roll_back_when_order_transaction_fails` asserts full
  rollback + `SessionHasErrors('cart')` on insufficient stock. **To be
  replaced/reworded** (not yet).

---

## 2. Data model

### 2.1 `order_items` (migration `<ts>_add_stock_hold_to_order_items.php`)
- Reuse existing `status` (`varchar(30)`); new value **`awaiting_stock`**.
- New columns:
  - `held_quantity` — `unsignedInteger`, default `0`. Units ordered but **not**
    reserved (the shortfall). Invariant: `reserved/confirmed` portion =
    `quantity - held_quantity`.
  - `stock_held_at` — `timestamp`, nullable.
  - `stock_confirmed_at` — `timestamp`, nullable.
  - `stock_declined_at` — `timestamp`, nullable.
- Index on `status` already exists; hold queries filter `status = 'awaiting_stock'`.
- `down()` drops the four columns.

### 2.2 No new tables
Held-item notifications reuse `EmailLog` (email) and `UserNotification`
(in-app), idempotently keyed. No schema changes to those tables.

### 2.3 Model changes
- `OrderItem`: add the four columns to `$fillable`; cast timestamps to
  `datetime`, `held_quantity` to `integer`; helpers `isStockHeld()`,
  `confirmedQuantity()` (`quantity - held_quantity`).
- `Order`: add `awaiting_stock`-aware helpers only if a display aggregate is
  needed; a **new order status value is NOT introduced** (order stays
  `pending`/`confirmed` as today) to avoid breaking status consumers. Order-level
  "has held items" is derived (`items.status = awaiting_stock`).
- `order_items.status` consumers to update for the new value:
  - `ProcurementService::aggregateStatus()` rank map (see §5.5).
  - `CodCollectionService::recordCollection` guard already rejects
    `cancelled`; ensure a held item has no COD collection to collect (§5.4).
  - Seller/supplier/customer order views: render an "Awaiting stock" badge.

---

## 3. Status transitions (authoritative)

| Stage | `order_items.status` | `order.status` | `payment_status` | stock effect |
| --- | --- | --- | --- | --- |
| Fulfillable item at checkout | `pending` | `pending` (Stripe) / `confirmed` (COD, via finalise) | `pending` | reserved/decremented for confirmed qty |
| Held item at checkout | `awaiting_stock` | same as order | `pending` | reserve available portion only; `held_quantity` = shortfall |
| Held item, seller **confirms** (stock now suffices) | `pending` | unchanged | unchanged | reserve remaining; `stock_confirmed_at`; totals recomputed (Stripe: see §7) |
| Held item, seller **confirms** (still short) | `awaiting_stock` | unchanged | unchanged | reserve what is available; reduce `held_quantity` |
| Held item, seller **declines** | `cancelled` | `cancelled` if no active items remain | unchanged unless paid→refund path | release the reserved portion only; `stock_declined_at` |
| Customer cancels whole order | item `cancelled` | `cancelled` | unchanged (paid→refund guard blocks, §8) | via `OrderCancellationService` |
| Window | — | — | — | — |

Order-placement post-commit side effects (`OrderPlaced`, `ProcessOrder`) must
**not** treat held items as fulfilled (§5.5, §6).

---

## 4. `InventoryService` — partition instead of throw

New value object `app/Support/StockPartition.php`:
```
StockPartition {
  array<int,int> $confirmed;   // cartItemId => reserved qty
  array<int,int> $held;        // cartItemId => shortfall qty
  bool isEmpty(): bool
  bool allHeld(): bool
}
```

New method (kept internal to preserve the locked-row design):
```
InventoryService::partitionForCart(Cart $cart): StockPartition
```
- Same deterministic locking order (source stock first, then local `inventory`
  ascending by id) and the same **re-check against the locked rows**
  (`SourceStockService`/`InventoryService` primitives reused).
- For each item: reserve `min(quantity, available)`; record the shortfall in
  `held`. **Never** reserve the shortfall; never mark the whole quantity
  fulfillable when only part is available.
- Supplier-sourced items reserve source stock; local items decrement their
  inventory row; products with no inventory row are not stock-managed
  (unchanged semantics).
- `reserveAndDecrementForCart()` is reimplemented on top of the same primitive
  so the locking protocol stays single-sourced (its throwing behaviour is
  retained for any caller that still wants strict semantics, if any remain).

> MySQL caveat: real oversell/deadlock behaviour **cannot** be validated on the
> local SQLite suite. A documented MySQL 8.0 concurrency test is specified
> (§10) but will be skipped locally, consistent with the existing suite.

---

## 5. Checkout behaviour (web + API)

### 5.1 Web (`CheckoutController::store`)
Inside the existing transaction, after order + items are created, replace the
`reserveAndDecrementForCart` call with:
1. `$partition = InventoryService::partitionForCart($cart)`.
2. For each cart item:
   - confirmed → `OrderItem.status = 'pending'`, `held_quantity = 0`,
     quantity = confirmed qty, subtotal = qty × unit_price.
   - held → `OrderItem.status = 'awaiting_stock'`,
     `held_quantity = shortfall`, `quantity = ordered qty`,
     subtotal = **confirmed qty × unit_price** (only the fillable part is
     payable), `stock_held_at = now()`.
3. Recompute `subtotal/shipping/tax/discount/total` from confirmed items only
   via `OrderTotalsService` (§5.3).
4. Commissions: only for confirmed items (`CommissionService` scoped, §5.5).
5. COD: `finaliseCodOrder` only when ≥1 confirmed item; otherwise leave the
   order `pending` with no COD collection.
6. Stripe: only redirect to `checkout.stripe` when `total > 0` **and** ≥1
   confirmed item; else redirect to `orders.show` with an "awaiting stock"
   message. (The `stripe()` action is also hardened to refuse a zero-total /
   all-held order, §6.)
7. Delete **all** cart items (confirmed and held now live on the order).
8. Post-commit: for each held item dispatch `StockHoldService::notifyHeldItem`
   (isolated + logged like `dispatchPostCommitEffects`).

### 5.2 API (`Api/CheckoutController::store`)
Mirror §5.1 exactly so web and API produce identical order/item state, totals,
commissions and notifications. For a zero-total/all-held order the JSON has no
gateway hand-off (`payment_url` points at the order page, marked
"awaiting stock confirmation"). Keep `assertCartItemsAvailable` (published
guard) — it is independent of stock.

### 5.3 `OrderTotalsService`
New service with one method `recompute(Order $order): void`:
- Reloads active items (`status` not in `cancelled/returned/awaiting_stock`).
- `subtotal` = Σ active item subtotals; **shipping** recalculated from active
  items (vendor grouping identical to checkout); **tax** = `round(subtotal ×
  state rate)`; **discount** recalculated from the applied coupon (if any) on
  the new subtotal, capped as `CouponService` does; `total` = subtotal +
  shipping + tax − discount.
- Used at creation, on confirm, and on decline → one code path, no drift.
- Coupon edge case: if a coupon was applied and the new subtotal drops below
  `min_order_amount`, keep the coupon but cap the discount at the new subtotal
  (never negative total). Documented; no silent refund of a charged amount
  (§7).

### 5.4 COD
- Held items get **no** `CodCollection` (their subtotal is 0 until confirmed).
- When a held item is confirmed (`held_quantity → 0`), create its `CodCollection`
  via `CodCollectionService::createForOrder` (idempotent `firstOrCreate`).
- When declined, no COD collection exists to cancel.

### 5.5 Commission & procurement for held items
- **Commission**: `CommissionService::createCommissions` must skip
  `awaiting_stock` items at checkout. On confirm, create the commission for the
  newly-confirmed item (`firstOrCreate` on `order_item_id` is already
  idempotent).
- **Procurement**: `createForOrder` only creates POs for sourced items; it must
  **exclude `awaiting_stock` items**. Add `awaiting_stock` to
  `aggregateStatus()`'s rank map handling so a held item cannot silently
  surface as a `pending` procurement. On confirm, run
  `ProcurementService::createForOrder`/`syncForOrder` for the item (idempotent
  via existing `firstOrCreate`/`updateOrCreate`).

### 5.6 Consistency guarantee
A shared `CheckoutService` helper (e.g. `applyStockPartition(Order, Cart,
StockPartition)`) is called by both controllers so the split, totals, and
commission/procurement scoping cannot diverge.

---

## 6. Stripe payment-state transitions (documented before implementation)

Existing states/transitions (verified):
- Checkout creates `Order` with `payment_status=pending`; web redirects to
  `checkout.stripe`; `stripe()` creates the Stripe session and stores
  `stripe_session_id`.
- `success()` / webhook `checkout.session.completed`: lock order
  `where payment_status != 'paid'` → set `status=confirmed`,
  `payment_status=paid`, `confirmed_at`, `stripe_payment_intent_id`;
  `creditSellers`; `ProcurementService::createForOrder`.
- `charge.refunded`: `RefundService::recordProviderRefund` per provider refund;
  order → `payment_status=refunded`.

Required changes for held items:
- **Never** create a Stripe session for a zero-total or all-held order
  (guard in `stripe()`; message + redirect to `orders.show`).
- **Stripe order with confirmed + held items**: the session is created for the
  **confirmed-only** `total` (already how `stripe()` builds line items from
  `order->items` and `order->total`). The confirmation/webhook path must not
  mark the whole order `paid` if it contains `awaiting_stock` items — it marks
  the order `paid` for the confirmed amount and leaves held items
  `awaiting_stock` (no seller credit / procurement for held items).
- **Held item confirmed later on an already-paid Stripe order**: this increases
  the payable amount. **We do not silently re-charge.** The order is flagged
  for a follow-up payment (documented manual/admin step) rather than an
  automatic gateway charge; the seller/supplier confirmation records the item
  as confirmed, recomputes totals, and notifies the customer of the additional
  amount due. This is the "stable payable amount" rule (§0.5).
- **Held item declined on a Stripe order**: no charge was taken for it;
  recompute totals (held item excluded already). If the order was already paid
  on the confirmed portion only, no refund is due for the declined held unit.
- **Held item declined after the whole order was charged (edge: charged before
  a decline)** → refund the affected amount via `RefundService` (item-scoped),
  never a manual ledger write.

> Open point resolved by decision #5: automatic re-charge on late confirmation
> is **out of scope**; documented as a follow-up-payment state. (Flagged in §11.)

---

## 7. Totals, shipping, tax, discounts — consistency rules

- Totals are always produced by `OrderTotalsService::recompute` (§5.3) from
  active items; every confirm/decline path calls it.
- **No silent change of a charged amount**: when recompute would change an
  amount that was already charged (Stripe paid), the difference is surfaced as
  a follow-up payment / refund via the documented paths (§6, §8) — never an
  in-place edit of a charged total.
- COD `expected_amount` per item is derived from `order->subtotal/shipping/tax/
  discount` at collection-creation time; because held items carry subtotal 0,
  their eventual `CodCollection` uses the recomputed order values.

---

## 8. Cancellation & refund on decline (no duplicates)

`StockHoldService::decline(OrderItem $item, int $actorId)`:
- Transaction, lock the item. Guard: must be `awaiting_stock`; otherwise no-op
  (idempotent).
- Release **only the reserved portion** (`quantity - held_quantity`):
  - sourced → `SourceStockService` release of the reserved units;
  - local → increment `inventory.stock_quantity` by the reserved units.
  - **Never** release the held (unreserved) units.
- Set `status=cancelled`, `stock_declined_at=now()`.
- Reverse the item's **pending** commission, cancel its **pending**
  COD collection, and cancel/sync its procurement — reuse
  `OrderCancellationService`'s item-scoped logic where possible, or the same
  primitives it uses (commission→reversed, cod→cancelled, procurement sync).
- Recompute order totals; if no active items remain → `order.status=cancelled`
  (reuse `OrderCancellationService` whole-order semantics).
- **If the order was paid** and money was actually taken for this item, create a
  refund via `RefundService` (item-scoped). The existing services guarantee
  proportional allocation, capped reversals, and ledger idempotency — no
  duplicate refunds/ledger entries. A declined held item that was never
  charged creates **no** refund.
- Notify the customer (and echo to seller/supplier if relevant) via
  `StockHoldService` with idempotent keys.

Idempotency: every decline/confirm is guarded by the item's status under a row
lock; repeated clicks no-op. Stock release is bounded by the actually-reserved
quantity read under the lock.

---

## 9. Notifications (`StockHoldService` + DB idempotency)

New `app/Services/StockHoldService.php`:

- `notifyHeldItem(OrderItem $item): void`
  - **Seller** (`item.seller_id`): in-app `UserNotification`
    (`type=order_item_stock_hold`) + `EmailLog` email linking to the seller
    order page.
  - **Supplier** (`item.supplier_id` when set): same for the supplier.
  - **Customer** (`order.user_id`): in-app `UserNotification` + `EmailLog`
    email ("we're checking availability with the seller/supplier").
- `confirm(OrderItem $item, int $actorId)` and `decline(OrderItem $item, int $actorId)`
  as in §8, each notifying the customer of the outcome.
- **Email idempotency** (DB-enforced, mirrors `SendOrderPlacedNotification`):
  `email_logs.idempotency_key` values
  `stock-hold:{orderItemId}:seller|supplier|customer`,
  `stock-confirmed:{orderItemId}:customer`,
  `stock-declined:{orderItemId}:customer`.
  Insert inside try/catch `UniqueConstraintViolationException`, treat only the
  `idempotency_key` index as a duplicate (helper copied from
  `SendOrderPlacedNotification::isIdempotencyConflict`).
- **In-app idempotency**: deterministic UUIDv5 `id` derived from the same key so
  a duplicate insert collides on the primary key and is a no-op.
- Notification failures are isolated + logged (never fail checkout), exactly
  like `dispatchPostCommitEffects`.
- Recipients are always resolved from the `OrderItem`/`Order` (never from the
  request), so the wrong user can never be notified.

### 9.1 Dashboard actions (routes + controllers + views)
Role-gated on the existing groups in `routes/web.php`:
```
POST seller/orders/{order}/items/{item}/stock/confirm      seller.stock.confirm
POST seller/orders/{order}/items/{item}/stock/decline      seller.stock.decline
POST supplier/orders/{order}/items/{item}/stock/confirm    supplier.stock.confirm
POST supplier/orders/{order}/items/{item}/stock/decline    supplier.stock.decline
```
- `SellerController` / `SupplierController` methods authorize that the item
  belongs to an order scoped to the actor (seller_id/supplier_id match), then
  call `StockHoldService`.
- Views (seller/supplier order show) render an "Awaiting stock" badge and
  Confirm/Decline buttons only while the item is `awaiting_stock`.
- Customer order view + `OrderItem` render a customer-facing "Awaiting stock
  confirmation" label.

---

## 10. Tests (regression specification — not written yet)

### 10.1 Replace/reword
- `CheckoutTest::test_new_addresses_roll_back_when_order_transaction_fails`
  (`tests/Feature/CheckoutTest.php:377`) → assert the **new** behaviour: order
  created, OOS item `awaiting_stock` with `held_quantity`, no rollback, cart
  consumed. (Not modified until plan approved.)
- Keep an **explicit** strict-rollback test only if a strict path remains;
  otherwise remove the stale assertion with a clear rationale.

### 10.2 New `tests/Feature/StockHoldWorkflowTest.php`
1. Mixed cart (1 OOS + 1 in-stock) → order created; in-stock item `pending`,
   stock decremented; OOS item `awaiting_stock`; cart cleared.
2. Total = confirmed items only (held item excluded).
3. **Partial qty**: order 5 with 3 in stock → reserve 3, `held_quantity = 2`,
   item `awaiting_stock`; inventory decremented by exactly 3.
4. All items OOS → order still created, `total = 0`, `payment_status = pending`,
   no COD collection, no commission.
5. Sold-out sourced item → `SupplierProductStock.reserved_quantity` unchanged
   for the held portion.
6. Held **sourced** item notifies the **supplier**; held **local** item notifies
   the **seller**; correct `EmailLog.idempotency_key` each.
7. Customer receives exactly one held-item notification per item.
8. Duplicate `notifyHeldItem` → no second `EmailLog` / `UserNotification`; no
   error logged.
9. Wrong user cannot confirm/decline (403).
10. Seller confirm, stock now available → item `pending`, `held_quantity = 0`,
    totals recomputed, commission + procurement created **once**, customer
    notified (key `stock-confirmed:{id}:customer`).
11. Seller confirm, still short → stays `awaiting_stock`, `held_quantity`
    reduced, no double reservation.
12. Seller decline → item `cancelled`, **only reserved** stock released
    (inventory/source-stock exactly restored), totals recomputed, customer
    notified; order `cancelled` when no active items remain.
13. Decline on COD (never charged) → **no** refund created.
14. Decline on a **paid** order → `RefundService` item-scoped refund created;
    no duplicate refund/ledger entry on repeat.
15. Repeated confirm/decline clicks → idempotent (no duplicate reservations,
    ledger entries, refunds, or notifications).
16. Notification/dispatch failure during checkout does not fail the checkout or
    roll back the order (mirrors `PostCommitNotificationTest`).

### 10.3 Web + API parity
- New `tests/Feature/ApiStockHoldWorkflowTest.php`: mixed cart, all-held cart,
  held-item notification keys, zero-total no-gateway behaviour — asserting the
  API produces the same order/item/total/notification state as web.

### 10.4 Payment & Stripe
- `tests/Feature/StockHoldStripeTest.php`:
  - all-held/zero-total order → `stripe()` refuses, no session, redirect +
    message; webhook does not mark `paid`.
  - mixed order → session total equals confirmed-only total; `checkout.session.
    completed` marks `paid` without confirming held items and without crediting
    sellers for held items.
  - held item confirmed on a paid order → no automatic second charge; totals
    recomputed; follow-up-payment state recorded (assert the chosen marker).
  - declined held item after charge (edge) → item-scoped `RefundService` refund.

### 10.5 Notification idempotency (DB-level)
- Extend patterns from `tests/Feature/PostCommitNotificationTest.php`: assert
  `EmailLog.idempotency_key` uniqueness, duplicate → no-op, unrelated unique
  violation not misclassified.

### 10.6 MySQL 8.0 concurrency (skipped locally, documented)
- `tests/Feature/StockHoldConcurrencyMySqlTest.php` (guarded like
  `MoneyPathConcurrencyMySqlTest`): concurrent checkout-vs-confirm and
  confirm-vs-confirm must not oversell; skipped without a real MySQL 8.0
  instance. **No MySQL concurrency-safety claim** until run against real MySQL.

### 10.7 Regression suites to keep green
`CheckoutTest`, `CheckoutTransactionIntegrityTest`, `CheckoutTransactionRetryTest`,
`ApiCodCheckoutTest`, `OrderPlacedNotificationTest`, `PostCommitNotificationTest`,
`ProcurementTest`, `ProcurementSettlement*`, `Refund*`, `SupplierPayout*`,
`MoneyPathRetryTest`.

---

## 11. Migration & backward-compatibility concerns

- `order_items` new columns are additive, nullable/defaulted → safe `up`/`down`:
  existing rows get `held_quantity=0`, null timestamps, and are unaffected.
- New **status value** `awaiting_stock` is additive to an existing
  `varchar(30)`; no enum/check constraint exists (verified) → no schema break.
  Risk is **application-level**: every `order_item.status` consumer must treat
  `awaiting_stock` correctly (audited in §1: `ProcurementService::aggregateStatus`,
  `OrderCancellationService`, COD guard, views, and any status map). The plan
  updates each.
- Order-level status is **not** changed, so order-status consumers are safe;
  "has held items" is derived from items.
- Existing orders/items unaffected; no data backfill needed.
- New `EmailLog.idempotency_key` values are nullable-free and flow-scoped, so
  they never collide with existing admin mailer behaviour (verified in
  `2026_10_21_000001_...`).
- API: response shape for all-held orders adds no new required field but the
  `payment_url`/message semantics change — documented as an intentional
  behaviour change.

---

## 12. Implementation file list (concise)

**New**
- `app/Support/StockPartition.php`
- `app/Services/OrderTotalsService.php`
- `app/Services/StockHoldService.php`
- `database/migrations/<ts>_add_stock_hold_to_order_items.php`
- Tests: `StockHoldWorkflowTest`, `ApiStockHoldWorkflowTest`,
  `StockHoldStripeTest`, `StockHoldConcurrencyMySqlTest`

**Modified**
- `app/Http/Controllers/CheckoutController.php` (partition, held items, totals,
  COD/Stripe gating, hold notifications, `stripe()` zero-total guard)
- `app/Http/Controllers/Api/CheckoutController.php` (parity)
- `app/Services/InventoryService.php` (`partitionForCart`)
- `app/Services/SourceStockService.php` (partial-reserve helper if needed)
- `app/Services/CommissionService.php` (skip held items at checkout)
- `app/Services/ProcurementService.php` (`createForOrder` skip held;
  `aggregateStatus` handle `awaiting_stock`)
- `app/Services/CheckoutService.php` (shared partition/effects helper)
- `app/Models/OrderItem.php` (columns, casts, helpers)
- `app/Http/Controllers/SellerController.php`, `SupplierController.php`
  (confirm/decline actions; item authorization)
- `routes/web.php` (confirm/decline routes)
- Seller/supplier/customer order views (badge + buttons)
- `tests/Feature/CheckoutTest.php` (replace the rollback assertion — after approval)

**Preserved untouched**: all Phase 0 payout/refund files and every pre-existing
tracked/untracked change (§0.9).

---

## 13. Test-case list (concise)

- Web: mixed, partial-qty, all-held, totals, statuses, cart cleared.
- API: parity for the same cases.
- Inventory: reserve-available-only; held portion not reserved (local + source).
- Notifications: correct recipient; idempotent keys; duplicate no-op; customer.
- Payment: zero-total no Stripe; mixed Stripe confirmed-only total; no paid on
  held items; no silent re-charge; follow-up-payment marker.
- Refunds: decline COD = no refund; decline paid = item-scoped refund; no
  duplicates.
- Cancellation: order cancelled when all items decline.
- Idempotency: repeated confirm/decline; duplicate notifications.
- Concurrency (MySQL, skipped locally): checkout-vs-confirm, confirm-vs-confirm.
- Regression: existing checkout/refund/payout/notification suites green.

---

## 14. Open points flagged for decision (non-blocking; sensible defaults chosen)

1. **Late confirmation on a paid Stripe order** — default chosen: **no automatic
   re-charge**; record a documented follow-up-payment state and notify the
   customer (§6). Confirm acceptable.
2. **Zero-total Stripe UX copy/route** — default: redirect to `orders.show`
   with an "awaiting stock confirmation" message. Confirm wording/route.
3. **No auto-cancel timeout** on unconfirmed held items (dashboard-button model
   chosen). Confirm no SLA/timeout job is wanted.
4. **Partial hold vs whole-item hold** — decision #3 fixes this as **partial**
   (`held_quantity`). No further input needed.

Stop here. No application code or tests will be changed until the plan is
explicitly approved.
