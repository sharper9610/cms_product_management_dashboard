# 2Game CMS Output Layer — Project Blueprint

## Purpose

This repository is the existing Laravel CMS/control system. It already contains products, prices, R2 JSON generation, orders, wallet endpoints, key redemption, parent SKU resolution, and supplier order processors.

The next phase is **not** to rebuild the CMS or checkout from scratch.

The next phase is to create a clean, versioned **Floating JSON output layer** that sits outside the sensitive checkout/payment/key logic but connects to the CMS as the commercial source of truth.

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

## Current Existing System

The current CMS already has:

- Laravel CMS/admin dashboard.
- Product and price database models.
- R2 product JSON upload.
- R2 price JSON upload.
- Product API endpoints.
- Price API endpoints.
- Store/market matrix currently named `shopify.store_matrix`.
- Order API endpoints.
- Storefront order endpoint.
- Wallet endpoints.
- Key redeem endpoint.
- Parent SKU resolver.
- Supplier order processors for Ztorm, InComm, Point Nexus, and Genba.
- Activity logging, roles, permissions, and 2FA.

## Target Direction

The target is to create a new `v4` JSON output layer while preserving the existing working system.

### CMS remains responsible for private truth

The CMS/database remains responsible for:

- Product identity.
- SKU/source mapping.
- Trusted pricing.
- Supplier cost and routing.
- Wallet balances and transactions.
- Checkout validation.
- Payment/fraud/3DS decisions.
- Orders and order items.
- Supplier fulfilment.
- Key delivery/redeem.
- Support/debugging information.

### Floating JSON becomes public rendering layer

R2 JSON should power:

- Homepage.
- Product pages.
- Category pages.
- Search documents.
- Content/sentiment sections.
- Public display prices.
- Public channel/feed outputs where safe.

R2 JSON must not become checkout truth.

## Public vs Private Rule

### Public R2 may contain

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

### Public R2 must never contain

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

## Vercel Commerce Role

Vercel Commerce may be used as the frontend shell/UI, but it must not be the commerce authority.

Allowed:

- Product page layout.
- Cart drawer UI.
- Checkout UI container.
- Performance patterns.

Not allowed:

- Trusted cart totals.
- Product/pricing truth.
- Supplier routing.
- Key release.
- Wallet ledger.
- Fraud/3DS fulfilment release decision.

## Checkout Boundary

The frontend may render public price data from R2.

But checkout must send only intent data, ideally:

```json
{
  "market": "GB",
  "items": [
    { "sku": "363750", "quantity": 1 }
  ]
}
```

The CMS/checkout system must calculate trusted price, tax, discount, supplier route, and fraud/payment flow from the private database.

## Main Build Focus

The initial build focus is:

1. Document current flow.
2. Define v4 JSON schemas.
3. Generate local v4 artifacts.
4. Add public-safety validation.
5. Publish v4 artifacts to staging R2.
6. Create JSON contracts for frontend consumption.
7. Write order/checkout flow specification for the checkout team.

Do not touch supplier fulfilment or payment logic in early milestones unless explicitly requested.
