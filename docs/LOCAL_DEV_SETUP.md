# Local Development Setup — 2Game CMS / Floating JSON Project

## Purpose

This guide defines the local terminal setup for working on the CMS/Floating JSON output layer safely.

The immediate project focus is:

- Reading the existing Laravel CMS code.
- Documenting the current system flow.
- Building v4 JSON generation locally.
- Validating public-safe JSON.
- Publishing only to staging R2 when approved.

Checkout, payments, fraud, order processing, and supplier fulfilment are not the first development target unless a milestone explicitly says so.

---

## Required Tools

### Core

- Git
- GitHub access to `sharper9610/cms_product_management_dashboard`
- PHP 8.2+
- Composer
- Node.js 20+
- npm or pnpm
- MySQL/MariaDB access, or a safe local/staging database dump
- Redis if queue/cache features are needed locally

### AI / Coding

- Codex CLI or Codex via ChatGPT/GitHub integration
- VS Code or preferred IDE

### Deployment / Platform

- Cloudflare R2 staging credentials for JSON publishing tests
- Vercel CLI only when working with the frontend/Vercel repo
- Supabase CLI only if we later decide to add a separate Supabase-backed JSON control service

---

## What We Do Not Need Yet

### Supabase

Supabase is not required for the current CMS repo unless the architecture changes.

Reason:

- The current repo is Laravel/PHP.
- Existing data lives in the CMS database.
- The first goal is v4 JSON generation from CMS data, not building a new database layer.

Add Supabase later only if we decide to build a separate external JSON control plane with its own state, approval workflow, or agent memory.

### Vercel

Vercel CLI is not required for this Laravel CMS repo.

Use Vercel CLI when working on the frontend/Vercel Commerce repo.

---

## Recommended Local Folder Structure

```text
~/Projects/2game/
  cms_product_management_dashboard/
  frontend-vercel-commerce/        # when available
  json-output-experiments/         # optional scratch repo, if needed
```

---

## Clone Repo

```bash
git clone git@github.com:sharper9610/cms_product_management_dashboard.git
cd cms_product_management_dashboard
```

Create a milestone branch:

```bash
git checkout -b milestone-0-current-system-flow
```

---

## Install PHP Dependencies

```bash
composer install
```

Copy environment file:

```bash
cp .env.example .env
php artisan key:generate
```

If `.env.example` is not complete, request a sanitized `.env.staging.example` from the dev team.

Never commit real secrets.

---

## Install Node Dependencies

```bash
npm install
```

or, if the team standardizes on pnpm:

```bash
pnpm install
```

---

## Run Local Dev Server

The repo already has a Composer dev command that runs Laravel, queue listener, logs, and Vite together.

```bash
composer dev
```

Alternative manual commands:

```bash
php artisan serve
npm run dev
```

---

## Database Setup

Request from the dev team:

- Safe local/staging database access, or
- Sanitized database dump, or
- Seed data that includes products, prices, media, localizations, SKU mappings, and sample orders.

Minimum data needed for v4 JSON work:

- `products`
- `prices`
- `product_media`
- `localizations`
- `ratings`
- `tags`
- `sku_mappings` if testing parent SKU flows

Do not use production customer/order data locally unless it is sanitized.

---

## R2 Staging Setup

Request staging-only R2 credentials from the dev team:

```env
R2_ACCESS_KEY_ID=
R2_SECRET_ACCESS_KEY=
R2_REGION=auto
R2_BUCKET=
R2_RESOURCE_BUCKET=
R2_ENDPOINT=
```

Use staging paths only until approved:

```text
staging/product-json/v4/...
```

Never publish to production paths from local development without explicit approval.

---

## Codex Setup

Use Codex with the project docs as the operating memory.

Before giving Codex a task, tell it to read:

```text
docs/HANDOVER_FOR_NEXT_CHAT.md
docs/PROJECT_BLUEPRINT.md
docs/MILESTONE_PLAN.md
docs/CODEX_PLAYBOOK.md
docs/DECISIONS_LOG.md
```

Codex tasks should be milestone-scoped.

Example:

```text
You are working in repo sharper9610/cms_product_management_dashboard.
Read the docs listed above.
Work only on M0.1: create docs/CURRENT_SYSTEM_FLOW.md.
Do not change application code.
Do not modify checkout, wallet, supplier processors, key redemption, or R2 publishing logic.
```

---

## Vercel CLI Setup

Install only on the machine that will work with the frontend repo:

```bash
npm i -g vercel
```

Then authenticate:

```bash
vercel login
```

Use Vercel CLI for:

- Linking the frontend project.
- Pulling frontend environment variables.
- Running frontend builds locally.
- Preview deployments.

Do not use Vercel CLI for this Laravel CMS unless there is a separate deployment target.

---

## Supabase CLI Setup

Not required now.

If later required, use the supported Supabase approach:

```bash
npm install supabase --save-dev
npx supabase --help
```

or install globally via Homebrew/Scoop/standalone binary, not `npm install -g supabase`.

Use Supabase only if we create a separate JSON control service.

---

## First Local Verification Commands

```bash
php artisan --version
php artisan route:list
npm run build
```

For documentation-only milestones, no database is required.

For JSON generation milestones, database and staging R2 configuration will be required.

---

## Milestone Workflow

1. Read project docs.
2. Create milestone branch.
3. Make small changes.
4. Run checks.
5. Update docs/report.
6. Commit.
7. Open PR.

Branch example:

```bash
git checkout -b milestone-1-r2-v4-product-json
```

Commit example:

```bash
git add .
git commit -m "Build milestone 1 R2 v4 product JSON foundation"
git push -u origin milestone-1-r2-v4-product-json
```

---

## Environment Secrets Rule

Never commit:

- `.env`
- API passwords
- R2 credentials
- supplier credentials
- payment provider keys
- Forter credentials
- customer/order production data

If a sample is needed, create:

```text
.env.example
.env.staging.example
```

with placeholder values only.
