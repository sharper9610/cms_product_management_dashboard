# Handover for Next Chat — 2Game CMS / Floating JSON Project

## How to Continue

In a new chat, start with:

```text
We are working on the 2Game CMS / Floating JSON output layer in GitHub repo sharper9610/cms_product_management_dashboard.
Please read:
- docs/HANDOVER_FOR_NEXT_CHAT.md
- docs/PROJECT_BLUEPRINT.md
- docs/MILESTONE_PLAN.md
- docs/CODEX_PLAYBOOK.md
- docs/DECISIONS_LOG.md
Then continue from the next open milestone.
```

## Repository

```text
sharper9610/cms_product_management_dashboard
```

## Project Summary

The existing repo is a Laravel CMS/admin backend with Vite/Materio-style admin assets.

It already contains a lot of the old production logic:

- Product models and APIs.
- Price models and APIs.
- R2 product JSON upload.
- R2 price JSON upload.
- Store/market matrix currently named `shopify.store_matrix`.
- Storefront order endpoint.
- Parent SKU resolver.
- Order/order item models.
- Supplier processors for Ztorm, InComm, Point Nexus, and Genba.
- Wallet endpoints.
- Key redeem endpoint.
- Activity logs, roles, permissions, and 2FA.

The next work is not a full rebuild. The next work is to create a clean, external-facing **Floating JSON v4 output layer** that uses the existing CMS as the source but safely feeds the new frontend and channels.

## Core Architecture

```text
Unify / supplier sync
  -> existing CMS database
  -> Floating JSON compiler/output layer
  -> public R2 JSON
  -> Vercel Commerce / Next.js frontend rendering

Checkout path:
Frontend cart intent
  -> CMS checkout/payment system
  -> trusted CMS pricing
  -> fraud / 3DS / wallet / payment provider
  -> order processing
  -> supplier fulfilment
  -> key/customer library
```

## Locked Decisions

- Existing CMS/database remains the trusted commercial/private source.
- Floating JSON is for public rendering, page composition, search, feeds, and channel outputs.
- R2 JSON must not be checkout truth.
- Vercel Commerce may be used as the frontend shell/UI but not as product/cart/pricing truth.
- Checkout and sensitive private logic stay with the developer team/CMS backend.
- Shopify should not drive products, checkout, wallet, orders, or fulfilment.
- Shopify, if ever used, is only optional final ledger export.
- If Shopify ledger export is awkward, drop it.
- Wallet is global, multi-currency, default payment method, no withdrawals, no expiry, no wallet-to-wallet transfer.
- Forter should be included in the private fraud/payment optimization flow as an adapter, not public JSON.
- Payment authorization is not enough to release keys. Fulfilment requires payment + fraud/3DS/risk approval.

## Public R2 Rule

Public R2 can contain:

- SKU / canonical product identifier.
- Slug.
- Title.
- Product type.
- Platform / activation / DRM.
- Publisher / developer.
- Release date.
- Public status.
- Media URLs.
- Public display price.
- Discount percent.
- Category placement.
- Public content/sentiment metadata.

Public R2 must never contain:

- Supplier credentials.
- Supplier cost or margin.
- Private supplier route.
- Key IDs or actual keys.
- Customer PII.
- Wallet balance.
- Payment metadata.
- Fraud/risk score.
- Checkout metadata.
- Internal admin notes.

## Existing Code Findings

Important files already inspected:

- `routes/web.php`
  - Contains admin routes including `/product/r2-json-upload` and parent SKU sync.
- `routes/api.php`
  - Contains product, price, order, storefront order, wallet, bonus, and key redeem APIs.
- `app/Services/Json/ProductJsonUploadService.php`
  - Generates current v3 product and price JSON and uploads to R2.
- `app/Http/Resources/ProductResourceV3.php`
  - Current product JSON shape.
- `app/Models/Product.php`
  - Product model, relationships, source IDs, pricing relationship.
- `app/Models/Price.php`
  - Pricing model with price, steam_price, cost_estimate, discount fields, activity logs.
- `config/shopify.php`
  - Current market/store matrix. Rename conceptually later to markets/channel markets.
- `config/services.php`
  - Current source IDs: Ztorm = 1, InComm = 2, Point Nexus = 3, Genba = 4.
- `app/Http/Controllers/Api/OrderControllerV2.php`
  - Current order/storefront order flow.
- `app/Services/SkuMapping/ParentSkuResolver.php`
  - Resolves parent SKU to child/source SKU by country/currency/price matching.
- `app/Services/OrderProcessing/OrderProcessor.php`
  - Dispatches order items by source to supplier processors.
- `app/Services/OrderProcessing/ZtormProcessor.php`
  - Current Ztorm order processor.
- `app/Http/Controllers/Api/WalletController.php`
  - Wallet endpoints, including a withdrawal endpoint that conflicts with locked wallet rule.

## Important Current Flow Details

### R2 v3

Current R2 upload writes:

```text
product-json/v3/products/{sku}.json
product-json/v3/prices/{sku}.json
```

Non-production uses:

```text
staging/product-json/v3/...
```

Target v4 should use:

```text
staging/product-json/v4/...
product-json/v4/...
```

### Current pricing

Current price JSON is built from the `prices` table and store matrix. It includes display price fields but the public R2 builder does not currently select `cost_estimate`.

### Current storefront order issue

Current `ProcessStorefrontOrderRequest` accepts incoming totals and prices from the request. It validates through parent SKU/price matching, but target architecture should move toward a `CheckoutIntent` flow where the CMS calculates the trusted price.

Target:

```text
Frontend sends SKU + quantity + market.
CMS calculates trusted price.
Payment provider receives trusted amount.
Order is created from CheckoutIntent.
```

### Current fulfilment issue

Current storefront order path creates an order and immediately calls `OrderProcessor`. Target architecture should gate fulfilment behind payment + fraud/3DS/risk approval.

## Created Docs So Far

- `docs/PROJECT_BLUEPRINT.md`
- `docs/MILESTONE_PLAN.md`
- `docs/CODEX_PLAYBOOK.md`
- `docs/HANDOVER_FOR_NEXT_CHAT.md`
- `docs/DECISIONS_LOG.md`

## Next Recommended Milestone

### M0.1 — Current System Flow Documentation

Create:

```text
docs/CURRENT_SYSTEM_FLOW.md
```

Should document:

- Product flow.
- Price flow.
- R2 v3 upload flow.
- Storefront order flow.
- Parent SKU resolution flow.
- Wallet flow.
- Key redeem flow.
- Supplier processor flow.
- Current Shopify naming/dependencies.

No functional code changes.

## Then Continue With

### M0.2 — Target Architecture Documentation

Create:

```text
docs/TARGET_ARCHITECTURE.md
docs/R2_V4_JSON_PLAN.md
docs/ORDER_FLOW_TARGET.md
```

## Things Not To Touch Yet

Do not modify these areas until a specific milestone asks for it:

- Supplier processors.
- Payment provider logic.
- Wallet mutation logic.
- Key redemption.
- Order processing execution.
- Production R2 publishing.

## Immediate Questions To Ask Team

- Is this repo the correct long-term place for the v4 R2 output layer?
- Does Unify write all supplier data into this CMS database?
- What are the source IDs for Lootbar, FunGroup, and VaultN?
- Is `products.sku` the stable identifier across CMS/R2/checkout/order/fulfilment?
- What frontend JSON fields are required by the Vercel Commerce team for home, category, product, search, and content sections?
- Should wallet withdrawal be disabled now or in a later milestone?

