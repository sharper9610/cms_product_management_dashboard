# Milestone Plan — 2Game Floating JSON Output Layer

## Milestone Rules

Each milestone should be small, reviewable, and safe.

Rules:

- One milestone = one branch = one PR = one report.
- Do not change checkout/payment/supplier fulfilment unless the milestone explicitly says so.
- Public JSON changes must include a public-safety check.
- Any output written to R2 must first be generated locally or to staging.
- Do not expose keys, costs, customer data, wallet data, fraud scores, or supplier credentials in public JSON.

---

## Phase 0 — Project Alignment

### M0.1 — Current System Flow Documentation

Goal: Document how the current CMS works today.

Deliverables:

- `docs/CURRENT_SYSTEM_FLOW.md`
- Product flow.
- Price flow.
- R2 v3 upload flow.
- Storefront order flow.
- Parent SKU resolution flow.
- Wallet flow.
- Key redeem flow.
- Supplier processor flow.

No functional code changes.

### M0.2 — Target Architecture Documentation

Goal: Document the target CMS -> Floating JSON -> Frontend architecture.

Deliverables:

- `docs/TARGET_ARCHITECTURE.md`
- `docs/R2_V4_JSON_PLAN.md`
- `docs/ORDER_FLOW_TARGET.md`

No functional code changes.

---

## Phase 1 — R2 v4 JSON Foundation

### M1 — Artifact Envelope and Public Safety Rules

Goal: Define the standard metadata envelope for all generated JSON artifacts.

Deliverables:

- Artifact envelope schema/class.
- Public/private field rules.
- Forbidden public keys list.
- Example valid artifact.
- Example invalid artifact.

### M2 — Public Product View v4

Goal: Create a new public product JSON shape for frontend rendering.

Deliverables:

- `PublicProductViewV4` resource/service.
- Local output generation for `products/{sku}.json`.
- Uses existing Product/ProductResourceV3 data where appropriate.
- Excludes sensitive fields.

### M3 — Public Price View v4

Goal: Create a new public display-price JSON shape.

Deliverables:

- `PublicPriceViewV4` resource/service.
- Local output generation for `prices/{sku}.json`.
- Includes display price, discount, market/currency fields.
- Excludes supplier cost, margin, route, checkout metadata.

### M4 — Local v4 Artifact Generator

Goal: Generate v4 artifacts locally before any R2 publishing.

Deliverables:

- Command or service for local generation.
- Output under `storage/app/artifacts/v4/...`.
- Supports single SKU and bulk mode.
- Summary report.

### M5 — Public Safety Validator

Goal: Prevent sensitive fields from entering public JSON.

Deliverables:

- Validator service.
- Rejects known forbidden keys.
- Validates product/price artifact shapes.
- Generates validation report.

### M6 — Staging R2 v4 Publisher

Goal: Upload validated v4 artifacts to staging R2 only.

Deliverables:

- Staging R2 path: `staging/product-json/v4/...`.
- Publish report.
- No production publishing yet.

---

## Phase 2 — Page-Level JSON

### M7 — Market Config Abstraction

Goal: Introduce a market config independent of the Shopify naming.

Deliverables:

- New `config/markets.php` or compatible service.
- Migration path from `config('shopify.store_matrix.stores')`.
- No breaking change to old v3 logic.

### M8 — Homepage JSON v4

Goal: Generate homepage JSON with three main sections.

Sections:

1. Categories.
2. Content/sentiment.
3. Products.

Deliverables:

- `home.json` per market.
- Static/sample content initially allowed.
- Product rails generated from CMS products/prices.

### M9 — Category JSON v4

Goal: Generate category and product rail outputs.

Deliverables:

- `categories.json`.
- `categories/{slug}.json`.
- Product references by SKU.
- Market-aware display prices where available.

### M10 — Search JSONL v4

Goal: Generate search documents for Typesense or future search provider.

Deliverables:

- `search/products.jsonl` per market.
- Public-safe fields only.
- SKU, title, slug, platform, publisher, categories, display price.

---

## Phase 3 — Frontend Contract and Integration Support

### M11 — Frontend JSON Contract

Goal: Produce a contract for frontend developers.

Deliverables:

- `docs/FRONTEND_JSON_CONTRACT.md`
- URLs/paths for all v4 JSON files.
- Example payloads.
- Required/optional fields.
- Cache expectations.

### M12 — R2 Publish Dashboard Updates

Goal: Update admin R2 upload page for v4 generation/publishing.

Deliverables:

- v4 generation option.
- staging-only publish button.
- validation report display.
- publish report display.

---

## Phase 4 — Order Flow Specification for Dev Team

### M13 — CheckoutIntent Spec

Goal: Write the target checkout/pricing flow for the checkout team.

Deliverables:

- `docs/CHECKOUT_INTENT_SPEC.md`
- Browser sends SKU/quantity/market.
- CMS calculates trusted price.
- Payment provider receives trusted amount.
- R2 display price is not checkout truth.

### M14 — Fraud/Forter/3DS Flow Spec

Goal: Define fulfilment gate rules.

Deliverables:

- `docs/FRAUD_3DS_FORTER_FLOW.md`
- Forter as adapter.
- Payment provider risk as adapter.
- 3DS result normalization.
- Manual capture/cancel/refund rules.
- No key release without risk approval.

### M15 — Internal Order Timeline Spec

Goal: Define structured order events for support/debugging.

Deliverables:

- `docs/ORDER_EVENT_TIMELINE_SPEC.md`
- Proposed `order_events` schema.
- Failure codes.
- Support-safe summary shape.

---

## Later Phases

Future work after JSON v4 and order specs:

- Internal order event timeline implementation.
- CheckoutIntent implementation if required in this repo.
- Wallet withdrawal disable/lockdown.
- New supplier source registry entries: Lootbar, FunGroup, VaultN.
- Wholesale/API output JSON.
- Eneba/G2A/Kinguin output JSON.
- Sentiment/trending engine.
- Agent-readable reports.
