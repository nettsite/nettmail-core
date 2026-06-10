# Build Plan — `nettmail/core`

Order by dependency. Each stage: contracts → implementation → Pest tests → commit/push. Mirrors spec's `src/` structure (renamed Herald→NettMail).

### Stage 0 — Foundation
- Rename `src/CoreClass.php` → `src/NettMail.php` (facade-like entry point, per spec structure)
- Value objects: `EmailAddress`, `EmailMessage` (to/from/subject/html/text/attachments), `SendResult` (provider message ID, status)
- `Contracts/MailDriverContract.php` — `send(EmailMessage): SendResult`
- `Contracts/StorageAdapterContract.php` — stub interface, methods added incrementally as domains need persistence (Eloquent/WP adapters implement later)

### Stage 1 — Drivers (Phase 1 scope) ✅
- `Drivers/PhpMailDriver.php` — wraps `mail()`/sendmail via Symfony Mailer's `SendmailTransport`
- `Drivers/SmtpDriver.php` — Symfony Mailer `EsmtpTransport`/`Smtps` via `Transport::fromDsn()`
- `Drivers/ResendDriver.php`, `Drivers/MailersendDriver.php` — HTTP API via PSR-18 client (injectable for testing)
- `Drivers/Support/SymfonyEmailFactory.php` — shared `EmailMessage` → Symfony `Email` conversion for Php/Smtp drivers
- Tests: mock HTTP client (`FakeHttpClient` + `nyholm/psr7`) for Resend/Mailersend; connection-failure paths for Php/Smtp drivers; `SymfonyEmailFactory` mapping

### Stage 2 — Templates
- `Domain/Templates/TemplateCompiler.php` — stores Unlayer design JSON + compiled HTML, validates unsubscribe block present
- `Domain/Templates/PlainTextConverter.php` — HTML → plain text fallback
- Merge-tag substitution (`{{first_name}}` etc.) — shared by transactional + broadcast
- Tests: merge tag replacement, plain-text conversion, missing-unsubscribe-block validation

### Stage 3 — Contacts & Lists (Phase 2 core portion)
- `Domain/Contacts/Contact.php`, `MailingList.php`, `ListMembership.php`, `Suppression.php` — entities/value objects
- Dedup logic: email normalization (lowercase, trim)
- Global suppression rules: hard bounce / complaint / global unsubscribe → exempt only operational transactional
- `Contracts/ContactSourceContract.php` — as defined in spec
- Extend `StorageAdapterContract` with contact/list CRUD methods
- Tests: dedup, suppression checks, contract conformance (in-memory adapter for testing)

### Stage 4 — Bounces (Phase 1 + 4)
- `Contracts/BounceParserContract.php`
- `Domain/Bounces/BounceClassifier.php` — hard/soft/complaint classification, soft-bounce counter → auto-hard after N (default 3, configurable)
- `Domain/Bounces/DsnParser.php` — RFC 3464 parsing + heuristic subject-line fallback
- Tests: classifier state transitions, DSN parsing fixtures (sample bounce emails)

### Stage 5 — Provider Webhooks (Phase 1, moved into core)
- Framework-agnostic — both `nettmail/laravel` and `nettmail/wordpress` need the same signature verification + payload parsing, so it lives here rather than in the Laravel adapter.
- `Contracts/WebhookHandlerContract.php` — `verify(rawBody, headers, secret): bool` + `parse(payload): NormalizedEvent[]`
- Per-provider handlers: `Drivers/Webhooks/ResendWebhookHandler.php`, `MailersendWebhookHandler.php`, `MailgunWebhookHandler.php`, `PostmarkWebhookHandler.php` — each verifies its own signature scheme and maps provider event shapes to a normalized `Event` DTO (type, provider message ID, timestamp, raw payload)
- Normalized events feed `BounceClassifier` (Stage 4) and `Domain/Tracking/EventRecorder` (Stage 6)
- Adapters (`nettmail/laravel`, `nettmail/wordpress`) only need: a route/REST endpoint that stores the raw payload, calls the matching handler, and persists the result via `StorageAdapterContract`
- Tests: signature verification (valid/invalid) and payload→event mapping per provider, using recorded sample payloads

### Stage 6 — Campaigns & Segmentation (Phase 3)
- `Domain/Campaigns/Campaign.php` — status state machine (`draft → scheduled → sending → sent|failed|paused`)
- `Domain/Campaigns/CampaignSender.php`, `MergeTag.php`
- Segment condition evaluator: AND/OR, one level of nesting, full operator set from spec (`is`, `contains`, `between`, `within last N days`, etc.)
- Tests: state machine transitions, segment evaluator against fixture contact sets

### Stage 7 — Tracking (Phase 3)
- `Domain/Tracking/PixelGenerator.php`, `LinkRewriter.php`, `EventRecorder.php`
- Link rewriting must skip unsubscribe links
- Tests: pixel URL generation, link rewriting (preserves unsubscribe links unwrapped)

### Stage 8 — Remaining Drivers (Phase 3)
- `Drivers/MailgunDriver.php`, `PostmarkDriver.php`, SES driver
- Same `MailDriverContract` + `SendResult` pattern as Stage 1
- Add corresponding webhook handlers (Stage 5 pattern) for SES (via SNS) if not already covered

---

**Notes:**
- `StorageAdapterContract` grows incrementally per stage rather than being fully designed upfront — avoids guessing methods before the domain logic that needs them exists.
- PHPStan level 5 + CS Fixer run per stage, not just at the end.
