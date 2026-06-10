# Decisions Log — 2Game CMS / Floating JSON Project

This file records important decisions so future chats, developers, and Codex tasks do not need to rely on conversation history.

---

## 2026-06-10 — Project Structure Decision

Decision:

Use the existing `cms_product_management_dashboard` repo as the CMS/output-layer project foundation.

Reason:

The repo already contains products, prices, R2 JSON generation, orders, wallet endpoints, key redeem, parent SKU resolver, and supplier processors. We should not rebuild these blindly.

---

## 2026-06-10 — Floating JSON Scope

Decision:

The Floating JSON system should initially focus on public rendering/output:

- Product JSON.
- Public display price JSON.
- Homepage JSON.
- Category JSON.
- Search JSONL.
- Content/sentiment JSON.
- Channel/feed outputs.

Checkout/payment/order/private fulfilment remain handled by the existing CMS/dev team flow.

---

## 2026-06-10 — CMS Source of Truth

Decision:

The existing CMS/database remains the trusted source for:

- Product identity.
- Pricing.
- Supplier mapping.
- Supplier cost.
- Checkout validation.
- Orders.
- Wallet.
- Fraud/risk decisions.
- Supplier fulfilment.
- Key delivery.

R2 JSON is not checkout truth.

---

## 2026-06-10 — Public R2 Boundary

Decision:

Anything written to public R2 must be safe if downloaded by anyone.

Public R2 must not include:

- Supplier cost.
- Margin.
- Supplier credentials.
- Private supplier route.
- Key IDs or actual keys.
- Customer PII.
- Wallet balance.
- Payment metadata.
- Fraud/risk score.
- Checkout metadata.
- Internal admin notes.

---

## 2026-06-10 — Vercel Commerce Role

Decision:

Vercel Commerce may be used as the frontend shell/UI, but not as the commerce authority.

Allowed:

- Layouts.
- Cart drawer UI.
- Product page UI.
- Checkout UI container.
- Performance patterns.

Not allowed:

- Trusted product data.
- Trusted price.
- Trusted cart total.
- Wallet ledger.
- Order/fulfilment authority.
- Key release authority.

---

## 2026-06-10 — Shopify Role

Decision:

Shopify should not control products, checkout, wallet, orders, fulfilment, key delivery, or customer library.

If Shopify is used at all, it is optional final ledger export only.

If ledger-only Shopify export is awkward, drop Shopify.

---

## 2026-06-10 — Checkout Boundary

Decision:

Frontend should eventually send only intent data:

```json
{
  "market": "GB",
  "items": [
    { "sku": "363750", "quantity": 1 }
  ]
}
```

CMS/checkout layer calculates trusted price, discount, tax, supplier route, payment amount, and fraud/risk decisions.

Reason:

Current storefront request accepts prices/totals from the request. It does validate against active prices, but target architecture should move to a stronger CheckoutIntent model.

---

## 2026-06-10 — Fraud / Forter / 3DS

Decision:

Forter should be included as a future private risk/payment optimization adapter.

Forter does not release keys. 2Game releases keys.

Fulfilment requires:

- Trusted CheckoutIntent.
- Successful payment authorization.
- Acceptable Forter/internal risk decision.
- Acceptable 3DS result where required.
- Successful capture if manual capture is used.

If risk/3DS is missing or unacceptable:

- Hold fulfilment.
- Cancel authorization if uncaptured.
- Refund if captured.
- Do not reveal keys.
- Write an order event.

---

## 2026-06-10 — Wallet Rules

Decision:

Wallet target rules:

- Global.
- Multi-currency.
- Default payment method.
- Fixed top-up amounts.
- No withdrawals.
- No expiry.
- No wallet-to-wallet transfer.

Note:

The current repo exposes a wallet withdrawal endpoint. This conflicts with the target rule and should be disabled/restricted in a later milestone.

---

## 2026-06-10 — Supplier Source Registry

Current known source IDs from `config/services.php`:

```text
1 = Ztorm
2 = InComm
3 = Point Nexus
4 = Genba
```

Need confirmation/new IDs for:

- Lootbar.
- FunGroup.
- VaultN.

---

## 2026-06-10 — R2 v4 Strategy

Decision:

Build v4 outputs without breaking v3.

Current v3 paths:

```text
product-json/v3/products/{sku}.json
product-json/v3/prices/{sku}.json
```

Target staging v4 paths:

```text
staging/product-json/v4/products/{sku}.json
staging/product-json/v4/prices/{sku}.json
staging/product-json/v4/markets/{market}/home.json
staging/product-json/v4/markets/{market}/categories.json
staging/product-json/v4/markets/{market}/search/products.jsonl
```

Production v4 publishing requires explicit approval.

---

## 2026-06-10 — Chat/Project Continuity

Decision:

Conversation history is too heavy to rely on. Repo docs are the project memory.

Future chats should start from:

- `docs/HANDOVER_FOR_NEXT_CHAT.md`
- `docs/PROJECT_BLUEPRINT.md`
- `docs/MILESTONE_PLAN.md`
- `docs/CODEX_PLAYBOOK.md`
- `docs/DECISIONS_LOG.md`

Every future session should update one or more of:

- `docs/HANDOVER_FOR_NEXT_CHAT.md`
- `docs/DECISIONS_LOG.md`
- `docs/OPEN_QUESTIONS.md`
- `docs/MILESTONE_PLAN.md`
