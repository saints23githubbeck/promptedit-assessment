# PromptEdit Studio — Pixelforge take-home assessment

A Laravel 12, Vue 3 and Inertia application for asynchronous Pixelforge image generation. It focuses on exact USD accounting, provider-rate snapshots, authenticated webhooks, event idempotency, monotonic generation states and polling recovery.

## Requirements

- PHP 8.3+ with `mbstring`, `pdo_mysql` (or `pdo_sqlite` for tests), `openssl`, `xml`, `dom` and `xmlwriter`
- Composer 2
- Node.js 20+ and npm
- MySQL 8+ for deployment (SQLite is configured for automated tests)

## Local setup

1. Create a MySQL database named `promptedit`.
2. Install dependencies and configure the environment:

   ```bash
   composer install
   cp .env.example .env
   php artisan key:generate
   npm ci
   ```

3. Add a **local-development** Pixelforge API key and its matching webhook signing secret to `.env`. Do not commit `.env`; use a separate API key for deployment.

   ```dotenv
   PIXELFORGE_BASE_URL=https://pixelforge.promptedit.dev/v1
   PIXELFORGE_API_KEY=your_local_pixelforge_api_key
   PIXELFORGE_WEBHOOK_SECRET=your_local_webhook_signing_secret
   PIXELFORGE_WEBHOOK_TOLERANCE=300
   ```

4. Run migrations and start the app:

   ```bash
   php artisan migrate
   php artisan serve
   npm run dev
   ```

5. In another terminal, run the scheduler while developing:

   ```bash
   php artisan schedule:work
   ```

6. Pixelforge requires a publicly reachable webhook URL. Expose the Laravel server with ngrok, Expose or Cloudflare Tunnel, then set the local Pixelforge key's webhook URL to:

   ```text
   https://YOUR_PUBLIC_HOST/webhooks/pixelforge
   ```

   Copy that key's signing secret into `.env`. The webhook endpoint is registered outside Laravel's browser/CSRF middleware.

## Production deployment

1. Deploy the repository to a PHP 8.3+ host with MySQL and HTTPS.
2. Configure environment variables in the host's secret/environment manager. At minimum set `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL`, database credentials, `APP_KEY`, `PIXELFORGE_BASE_URL`, `PIXELFORGE_API_KEY` and `PIXELFORGE_WEBHOOK_SECRET`.
3. Install dependencies and build assets:

   ```bash
   composer install --no-dev --prefer-dist --optimize-autoloader
   npm ci
   npm run build
   php artisan migrate --force
   php artisan optimize
   ```

4. Point the web root at Laravel's `public/` directory. Configure the deployed Pixelforge API key's webhook URL as `https://YOUR_DOMAIN/webhooks/pixelforge` and use that key's own signing secret. Do not reuse the local-development key.
5. Add a cron entry to run Laravel's scheduler every minute:

   ```cron
   * * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
   ```

   The scheduled `pixelforge:poll-pending` command recovers uncertain create responses and missed webhook deliveries. No long-running queue worker is required by this implementation.
6. Register an account, then grant operator access to the intended report account:

   ```bash
   php artisan tinker
   ```

   ```php
   App\Models\User::where('email', 'operator@example.com')->update(['is_admin' => true]);
   ```

   The admin report is available at `/admin/report` and requires an authenticated admin user.

## Accounting model

USD amounts are stored as integer picodollars (`1 USD = 10^12 units`). No binary floating-point arithmetic is used for provider cost or markup. Pixelforge's published rates are snapshotted on each generation. Provider cost is the sum of each actual token count multiplied by its snapshotted USD-per-million rate. The customer charge is exactly 125% of provider cost. The configured rates make this markup exactly representable at the stored precision; the code fails loudly rather than silently rounding if a future rate violates that invariant.

The upfront estimate uses Pixelforge's pricing endpoint and token-estimation guidance: Unicode prompt characters divided by the configured characters-per-token value, with the documented 15% uncertainty allowance, exact reference-image token counts, exact output token counts and the midpoint of the quality-specific reasoning range. The final charge is calculated only from actual usage on a succeeded generation. Failed generations have no charge.

## Webhook and recovery model

- Verify `Pixelforge-Signature` against the timestamp and raw request body using HMAC-SHA256 and a five-minute replay tolerance.
- Persist event IDs under a unique database constraint and process events transactionally.
- Create the customer charge through a unique `(generation_id, type)` ledger constraint, so duplicate deliveries cannot create a second charge.
- Never move a terminal generation (`succeeded` or `failed`) back to a non-terminal state.
- If a webhook arrives before the create response, use the signed event's `metadata.local_generation_id` to find the local row, link the provider ID and process the event immediately. Unmatched events remain pending and can be replayed once the provider ID is known.
- If the create request times out after it may have been accepted, retry with the same idempotency key. The scheduled reconciler caps repeated submission attempts and polls known pending provider generations.

## Manual provider scenarios

Use these documented prompt tags to exercise provider behavior:

| Prompt tag | Expected behavior |
|---|---|
| `[succeed]` | Accepted and completes successfully; exactly one charge |
| `[fail]` | Accepted, later fails; no charge |
| `[slow]` | Remains pending longer; history status updates through webhook/polling |
| `[duplicate]` | Duplicate webhook deliveries; exactly one charge |
| `[blocked]` | Rejected at submission; visible failed record, no charge |
| `[overloaded]` | Temporary provider overload; bounded retry and a clear error if still unavailable |

The admin report compares local per-generation provider costs with Pixelforge's `GET /v1/usage` totals for the same API key and UTC completion-date range. Pixelforge's usage endpoint is the source of truth for daily provider totals; the report shows the local sum and reconciliation delta alongside it.

## Test and build

```bash
php artisan test
npm ci
npm run build
```

The test suite covers exact token-cost arithmetic, the 25% markup, signature verification, duplicate webhook delivery, failed-generation billing, terminal-state monotonicity and the webhook-before-create-response race.

## Scope decisions

- Generated images use Pixelforge's signed image URL and may stop loading after the provider's 24-hour expiry, as allowed by the assessment.
- The required generation, pricing, history, webhook and deployment flows are implemented. The optional fake credit balance/purchase feature is intentionally not partially implemented.
- Registration and login use Laravel sessions, hashed passwords, session regeneration and IP/email-based rate limits.
