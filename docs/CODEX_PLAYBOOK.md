# Codex Playbook — 2Game CMS Output Layer

## Purpose

Codex should help us build the Floating JSON output layer safely and incrementally.

The CMS already contains sensitive systems: pricing, wallet, order processing, key redemption, and supplier processors. Codex must not casually modify those areas.

## Golden Rules

1. Never expose sensitive data in public JSON.
2. Never treat R2/display JSON as checkout truth.
3. Never modify checkout, payment, wallet, key, fraud, or supplier execution logic unless the milestone explicitly requires it.
4. Public JSON must be generated from CMS data, validated, and published only after passing public-safety checks.
5. Early milestones must use local artifacts before publishing to R2.
6. Staging R2 first. Production R2 only after explicit approval.
7. Do not remove existing v3 behavior while building v4.
8. Avoid breaking existing routes, resources, or processors.
9. Prefer additive changes: new v4 resources/services/configs over rewriting v3 logic.
10. Every milestone should produce docs or a report explaining what changed.

## Sensitive Fields Forbidden in Public R2

The following must never appear in public artifacts:

- `cost_estimate`
- `cost_price`
- `cost_price_euro`
- `margin`
- `supplier_cost`
- `supplier_route`
- `supplier_credentials`
- `password`
- `secret`
- `key_id`
- actual game/license keys
- `wallet_balance`
- customer email or PII
- fraud/risk score
- payment metadata
- checkout metadata
- raw gateway payloads
- internal admin notes

## Current System Facts

- Current product R2 path uses `product-json/v3/products/{sku}.json`.
- Current price R2 path uses `product-json/v3/prices/{sku}.json`.
- Current R2 upload is handled by `ProductJsonUploadService`.
- Current market mapping is named `shopify.store_matrix.stores`.
- Current source IDs include Ztorm, InComm, Point Nexus, and Genba.
- Current storefront order flow accepts pricing/totals from the incoming payload and validates through parent SKU/price matching.
- Target architecture should move toward CheckoutIntent where CMS calculates trusted price.

## Branch and PR Rules

Use one branch per milestone.

Branch naming:

```text
milestone-<number>-<short-name>
```

Each PR should include:

- Summary.
- Files changed.
- Tests/checks run.
- Risk notes.
- Rollback notes.
- Screenshots or sample JSON if relevant.

## Safe First Milestones

Good Codex tasks:

- Add docs.
- Add v4 schemas/resources.
- Add local JSON generation.
- Add validators.
- Add staging-only R2 output.
- Add reports.

Risky Codex tasks:

- Change supplier processors.
- Change wallet mutation logic.
- Change key redemption.
- Change payment webhook handling.
- Change checkout order creation.

Risky tasks require explicit approval and a dedicated milestone.

## Definition of Done

A milestone is complete only when:

- Code builds or affected tests pass.
- Public safety rules are respected.
- Existing v3 behavior is not broken unless explicitly intended.
- New docs or reports are updated.
- The PR explains exactly how to verify the change.
