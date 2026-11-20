
# PromptEdit Studio — Pixelforge Take-Home Assessment

A full-stack AI image-generation application built with **Laravel 12, Vue 3 and Inertia.js**, integrating with the Pixelforge API.

The application demonstrates asynchronous AI image generation, accurate usage-based billing, provider cost reconciliation, secure webhook processing and a responsive user experience.

It was built as part of the Senior Product Engineer take-home assessment for [PromptEdit](https://promptedit.com/).

## Live Application

- **Application:** https://mytestdodo.pro/whoami/login
- **GitHub Repository:** https://github.com/saints23githubbeck/promptedit-assessment
- **Technology:** Laravel 12, Vue 3, Inertia.js, MySQL
- **AI Provider:** [Pixelforge](https://pixelforge.promptedit.dev/)

The deployed application uses a dedicated Pixelforge API key and webhook signing secret, separate from local development credentials.

## Features

### 1. AI Image Generation

- Generate images through the Pixelforge API.
- Configure image size and generation quality.
- Support optional reference image URLs.
- Display a live price estimate before submission.
- Handle asynchronous generation requests.
- Track queued, processing, succeeded and failed states.
- Display generated images when processing completes.
- Provide clear error feedback for rejected or failed requests.

### 2. Usage-Based Pricing

The application charges users the actual Pixelforge generation cost plus a 25% markup.

- Estimate generation costs before submission.
- Snapshot provider pricing rates when a generation is created.
- Calculate final costs from actual token usage.
- Charge only after successful generation.
- Failed generations incur no customer charge.
- Preserve precision for fractional-cent provider costs.

### 3. Generation History

Users can view their previous generations, including:

- Original prompts.
- Generation status.
- Image previews.
- Image size and quality.
- Estimated generation cost.
- Actual customer charge.
- Provider generation errors.

History automatically refreshes while generations are pending.

### 4. Secure Webhook Processing

- Verify Pixelforge webhook signatures using HMAC-SHA256.
- Validate webhook timestamps against a five-minute replay tolerance.
- Persist webhook event IDs to prevent duplicate processing.
- Handle duplicate and out-of-order deliveries.
- Process webhook events transactionally.
- Protect terminal generation states from being overwritten.
- Recover generations when webhook delivery is missed.

### 5. Admin Usage & Profit Report

An operator dashboard provides financial visibility across the application.

It includes:

- Daily customer credit spend.
- Daily Pixelforge provider expenditure.
- Daily profit.
- Provider generation counts.
- User-level financial breakdowns.
- Local provider-cost totals.
- Reconciliation differences against Pixelforge usage.

The provider's `GET /v1/usage` endpoint is treated as the source of truth for daily provider expenditure.

## Technology Stack

| Component | Technology |
|---|---|
| Backend | Laravel 12 |
| Frontend | Vue 3 |
| SPA / Server communication | Inertia.js |
| Database | MySQL 8+ |
| Local automated tests | SQLite |
| Build tooling | Vite |
| Package management | Composer and npm |
| Authentication | Laravel session authentication |
| AI image generation | Pixelforge API |
| Webhook security | HMAC-SHA256 |

## Requirements

- PHP 8.2+ with `mbstring`, `pdo_mysql` (or `pdo_sqlite` for tests), `openssl`, `xml`, `dom` and `xmlwriter`.
- Composer 2.
- Node.js 20+ and npm.
- MySQL 8+ for deployment.
- SQLite for automated tests.

## Local Development Setup

### 1. Clone the repository

```bash
git clone https://github.com/saints23githubbeck/promptedit-assessment.git

cd promptedit-assessment
```


### 2. Install dependencies

```bash
composer install

npm ci
```

### 3. Configure the environment

```bash
cp .env.example .env

php artisan key:generate
```

Create a MySQL database named `promptedit` and configure the database connection in `.env`.

### 4. Configure Pixelforge

Create a local-development Pixelforge account and API key:

[Create Pixelforge Account](https://pixelforge.promptedit.dev/register)

Add the API credentials to `.env`:

```dotenv
PIXELFORGE_BASE_URL=https://pixelforge.promptedit.dev/v1
PIXELFORGE_API_KEY=your_local_pixelforge_api_key
PIXELFORGE_WEBHOOK_SECRET=your_local_webhook_signing_secret
PIXELFORGE_WEBHOOK_TOLERANCE=300
```

Use a separate API key and webhook signing secret for production.

Never commit `.env` or API credentials to the repository.

### 5. Run migrations

```bash
php artisan migrate
```

### 6. Start the application

Run Laravel:

```bash
php artisan serve
```

Run Vite in a separate terminal:

```bash
npm run dev
```

The application will be available at:

```text
http://localhost:8000
```

### 7. Run the scheduler

In another terminal:

```bash
php artisan schedule:work
```

The scheduler runs the pending-generation reconciliation command.

### 8. Configure webhooks

Pixelforge requires a publicly reachable webhook URL.

During local development, expose your Laravel application using ngrok, Expose or Cloudflare Tunnel.

Configure the local Pixelforge API key with:

```text
https://YOUR_PUBLIC_HOST/webhooks/pixelforge
```

Copy the signing secret associated with that exact API key into `.env`.

The webhook endpoint is registered outside Laravel's browser CSRF middleware and uses signature verification for authentication.

## Demo Accounts

The following demo accounts are available when the demo seeder has been executed.

| Email | Account | Starting balance |
|---|---|---:|
| `admin@promptedit.test` | Administrator | $25.00 |
| `demo@promptedit.test` | Regular user | $10.00 |
| `sam@promptedit.test` | Regular user | $5.00 |

**Demo password:** `password`

Demo accounts are intended for demonstration purposes. Change or disable these credentials before exposing a production environment publicly.

Demo account balances are initialized through ledger purchase entries. No fake generation records are inserted into generation history.

## Production Deployment

The application is deployed under:

```text
https://mytestdodo.pro/whoami/
```

It runs as a subdirectory application, allowing the existing application hosted at the domain root to remain unaffected.

### 1. Configure production environment variables

Configure the following values using the server's environment configuration.

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://mytestdodo.pro/whoami
ASSET_URL=https://mytestdodo.pro/whoami

VITE_APP_BASE_PATH=/whoami/

PIXELFORGE_BASE_URL=https://pixelforge.promptedit.dev/v1
PIXELFORGE_API_KEY=your_production_pixelforge_api_key
PIXELFORGE_WEBHOOK_SECRET=your_production_webhook_signing_secret
PIXELFORGE_WEBHOOK_TOLERANCE=300
```

Configure the database credentials and `APP_KEY` separately.

The production Pixelforge key and webhook secret must be different from local development credentials.

### 2. Install production dependencies and build assets

```bash
composer install --no-dev --prefer-dist --optimize-autoloader

npm ci

npm run build
```

Run database migrations:

```bash
php artisan migrate --force
```

Optimize Laravel:

```bash
php artisan optimize
```

### 3. Configure web server routing

The web server points to the Laravel application's `public/` directory.

The `/whoami/` path is routed to the PromptEdit application while the existing root application continues to operate independently.

Vite uses environment-specific base paths:

- Local development: `/`
- Production deployment: `/whoami/`

The shared Vue navigation utility derives its base path from Vite's `BASE_URL`, avoiding hardcoded environment-specific paths in individual components.

### 4. Configure production webhooks

Configure the production Pixelforge API key with:

```text
https://mytestdodo.pro/whoami/webhooks/pixelforge
```

Use the signing secret belonging to the deployed API key.

### 5. Configure Laravel scheduling

Add a cron entry to execute Laravel's scheduler every minute:

```cron
* * * * * cd /var/www/whoami && php artisan schedule:run >> /dev/null 2>&1
```

The scheduled `pixelforge:poll-pending` command recovers uncertain creation responses and missed webhook deliveries.

No long-running queue worker is required by this implementation.

### 6. Grant administrator access

Register the intended operator account, then grant administrator access:

```bash
php artisan tinker
```

```php
App\Models\User::where(
    'email',
    'operator@example.com'
)->update([
    'is_admin' => true,
]);
```

The admin report is available at:

```text
/admin/report
```

It requires an authenticated administrator account.

## Accounting Model

### Monetary precision

All USD amounts are stored as integer picodollars:

```text
1 USD = 1,000,000,000,000 picodollars
```

This avoids binary floating-point arithmetic for provider costs, customer charges and markup calculations.

### Provider cost

Pixelforge publishes token-based pricing but does not return a final monetary cost for each generation.

The application snapshots the applicable pricing rates when a generation is created.

Once generation completes, provider expenditure is calculated from actual token usage and the snapshotted rates.

### Customer charge

The customer charge is:

```text
Customer Charge = Actual Provider Cost × 1.25
```

The application uses integer arithmetic to preserve precision.

The configured rates make the 25% markup exactly representable at the stored precision. The implementation fails loudly rather than silently rounding if a future rate violates that invariant.

### Upfront estimates

Before submission, the application estimates the expected generation cost using Pixelforge's pricing endpoint and token-estimation guidance.

The estimate incorporates:

- Unicode prompt character counts.
- Configured characters-per-token conversion.
- Documented 15% uncertainty allowance.
- Reference-image token counts.
- Output token counts.
- Midpoint of the quality-specific reasoning range.

The estimate is displayed before the user submits the generation.

### Final billing

The final customer charge is calculated from actual usage only after a generation succeeds.

- Successful generation: charge actual provider cost plus 25%.
- Failed generation: no customer charge.
- Duplicate webhook delivery: no duplicate charge.

## Webhook and Recovery Architecture

The integration is designed to handle asynchronous provider behavior and unreliable webhook delivery.

### Signature verification

Each webhook signature is verified using:

- `Pixelforge-Signature`.
- The event timestamp.
- The raw request body.
- HMAC-SHA256.
- A five-minute replay tolerance.

Invalid or expired signatures are rejected.

### Event idempotency

Webhook event IDs are persisted with a unique database constraint.

Events are processed transactionally to prevent duplicate processing.

Customer ledger entries also use a unique `(generation_id, type)` constraint to ensure a generation cannot be charged more than once.

### State transitions

The application supports:

```text
queued
  |
  v
processing
  |
  +------> succeeded
  |
  +------> failed
```

Terminal states are protected.

A generation that has succeeded or failed cannot be moved back into a non-terminal state by a delayed or duplicate webhook.

### Out-of-order webhooks

A webhook may arrive before the original generation creation response.

In this case, the application uses:

```text
metadata.local_generation_id
```

to locate the local generation, associate the provider generation ID and process the event.

Unmatched events remain pending and can be replayed once the provider ID becomes available.

### Uncertain creation responses

If a generation creation request times out after it may have been accepted by Pixelforge, the application retries using the same idempotency key.

The scheduled reconciliation process:

- Caps repeated submission attempts.
- Polls known pending provider generations.
- Recovers missed webhook deliveries.
- Updates local generation state.

This provides an additional recovery path when webhook delivery is delayed or unavailable.

## Testing Provider Scenarios

Pixelforge documents prompt keywords that deliberately trigger different provider behaviors.

The following scenarios can be tested manually:

| Prompt tag | Expected behavior |
|---|---|
| `[succeed]` | Accepted and completes successfully; exactly one charge |
| `[fail]` | Accepted, later fails; no customer charge |
| `[slow]` | Remains pending longer; history updates through webhook or polling |
| `[duplicate]` | Duplicate webhook deliveries; exactly one charge |
| `[blocked]` | Rejected at submission; visible failed record, no charge |
| `[overloaded]` | Temporary provider overload; bounded retry and a clear error if still unavailable |

These scenarios help verify the application's handling of provider failures, asynchronous processing, billing and event idempotency.

## Admin Usage Reconciliation

The admin report compares local generation records with Pixelforge's official usage endpoint:

```text
GET /v1/usage
```

The report includes:

- Provider-reported daily costs.
- Local generation provider-cost totals.
- Reconciliation differences.
- Customer credit spend.
- Profit.
- Per-user financial breakdowns.

The provider's usage endpoint is treated as the source of truth for daily provider expenditure.

All comparisons use the same production API key and UTC completion-date range.

The local reconciliation delta is displayed rather than hidden, making discrepancies visible to the operator.

## Automated Tests

Run the application test suite:

```bash
php artisan test
```

Build the frontend:

```bash
npm ci

npm run build
```

The test suite covers:

- Exact token-cost arithmetic.
- The 25% customer markup.
- Webhook signature verification.
- Duplicate webhook delivery.
- Failed-generation billing.
- Terminal-state monotonicity.
- Webhook-before-create-response race conditions.

SQLite is configured for automated tests to provide an isolated testing environment.

## Security and Reliability

The application incorporates:

- Laravel session-based authentication.
- Hashed user passwords.
- Session regeneration during authentication.
- IP/email-based login and registration rate limits.
- Authenticated admin reporting.
- Signed webhook verification.
- Event and ledger idempotency.
- Transactional webhook processing.
- Environment-based secret management.
- Separate Pixelforge credentials for local and production environments.

## Scope Decisions

The assessment leaves some implementation choices open.

The following decisions were made:

1. **Image storage:** Generated images use Pixelforge's signed image URLs. These may stop loading after the provider's 24-hour URL expiry, as permitted by the assessment.

2. **Credit balances:** The optional fake credit purchase and balance-management feature is intentionally not partially implemented. The core generation pricing and actual usage billing flows remain the focus.

3. **Generation history:** No fake generation records are inserted. History contains generations that pass through the real provider pipeline, preserving the integrity of the admin report and provider reconciliation.

4. **Recovery:** Scheduled polling complements webhook processing so the application can recover from missed deliveries and uncertain creation responses.

5. **Money precision:** Integer picodollars are used instead of floating-point monetary calculations to preserve exact provider costs and customer charges.

6. **Deployment:** Production uses a dedicated Pixelforge API key, webhook signing secret and environment-specific asset base path.

## Assessment Submission

**Project:** PromptEdit Studio

**GitHub Repository:**
https://github.com/saints23githubbeck/promptedit-assessment

**Live Application:**
https://mytestdodo.pro/whoami/login

**Pixelforge Account:** Use the email address associated with the Pixelforge account when completing the application form.

---

Built with Laravel, Vue, Inertia.js and Pixelforge.
