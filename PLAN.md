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

### Stage 2 — Templates ✅
- `Domain/Templates/TemplateCompiler.php` — compiles HTML into a `CompiledTemplate` (html + auto-generated plain text), validates `{{unsubscribe_url}}` present for `TemplateType::Broadcast`
- `Domain/Templates/PlainTextConverter.php` — DOM-based HTML → plain text (block elements → newlines, links rendered as `text (url)`, entities decoded)
- `Domain/Templates/MergeTagRenderer.php` — `{{first_name}}` etc. substitution, unknown tags left untouched
- `Domain/Templates/TemplateType.php` (enum), `CompiledTemplate.php` (value object), `MissingUnsubscribeLinkException.php`
- Tests: merge tag replacement (incl. whitespace/repeats), plain-text conversion (headings, links, entities, blank-line collapsing), unsubscribe-link validation for broadcast vs transactional

Note: design JSON persistence + Unlayer-specific storage belongs to `StorageAdapterContract`/adapters (Stage 3+), not core's compiler — core only compiles/validates HTML.

### Stage 3 — Contacts & Lists (Phase 2 core portion) ✅
- `Domain/Contacts/Contact.php`, `MailingList.php`, `ListMembership.php`, `Suppression.php` — entities/value objects
- `Domain/Contacts/BounceType.php`, `MembershipStatus.php`, `SuppressionReason.php` (enums), `EmailNormalizer.php`
- Dedup logic: email normalization (lowercase, trim) applied in `Contact`'s constructor and used for lookups
- Global suppression rules on `Contact::isSuppressed()`: hard bounce / complaint / global unsubscribe → exempt only operational transactional
- `Contracts/ContactSourceContract.php` — as defined in spec
- Extended `StorageAdapterContract` with contact/list/membership CRUD methods
- Tests: dedup, suppression checks, `InMemoryStorageAdapter` (in `tests/Fakes/`) exercised as a contract conformance fake

### Stage 4 — Bounces (Phase 1 + 4) ✅
- `Contracts/BounceParserContract.php` — `parse(rawMessage): ?ParsedBounce`
- `Domain/Bounces/BounceClassifier.php` — hard/soft/complaint classification, soft-bounce counter → auto-hard after N (default 3, configurable), resets on successful delivery
- `Domain/Bounces/DsnParser.php` — RFC 3464 `Final-Recipient`/`Status` parsing + heuristic subject/body fallback for non-standard bounces
- `Domain/Bounces/ParsedBounce.php` — value object (recipient, bounceType, statusCode)
- Tests: classifier state transitions (hard/soft/complaint, escalation, reset), DSN parsing fixtures under `tests/Fixtures/Bounces/` (RFC 3464 hard/soft, heuristic hard/soft, unrecognised)

### Stage 5 — Provider Webhooks (Phase 1, moved into core) ✅
- Framework-agnostic — both `nettmail/laravel` and `nettmail/wordpress` need the same signature verification + payload parsing, so it lives here rather than in the Laravel adapter.
- `Contracts/WebhookHandlerContract.php` — `verify(rawBody, headers, secret): bool` + `parse(payload): NormalizedEvent[]` (header keys lowercased by caller)
- `Domain/Webhooks/EventType.php` (enum), `NormalizedEvent.php` (type, provider message ID, occurred-at, raw payload)
- Per-provider handlers: `Drivers/Webhooks/ResendWebhookHandler.php` (Svix signature), `MailersendWebhookHandler.php` (HMAC-SHA256 `Signature` header), `MailgunWebhookHandler.php` (HMAC of `timestamp+token` from payload's `signature` object), `PostmarkWebhookHandler.php` (no native signing — optional shared-secret header) — each maps provider event shapes to `NormalizedEvent`
- Normalized events feed `BounceClassifier` (Stage 4) and `Domain/Tracking/EventRecorder` (Stage 6)
- Adapters (`nettmail/laravel`, `nettmail/wordpress`) only need: a route/REST endpoint that stores the raw payload, calls the matching handler, and persists the result via `StorageAdapterContract`
- Tests: signature verification (valid/invalid) and payload→event mapping per provider

### Stage 6 — Campaigns & Segmentation (Phase 3) ✅
- `Domain/Campaigns/Campaign.php` — status state machine (`draft → scheduled → sending → sent|failed|paused`), `CampaignStatus` enum, `InvalidCampaignTransitionException`
- `Domain/Campaigns/CampaignSender.php` — suppression check (`shouldSend`) + per-contact merge tag rendering of subject/html/text
- `Domain/Campaigns/MergeTag.php` — merge tag definitions for the UI picker, with `defaults()`
- `Domain/Campaigns/Segmentation/` — `SegmentCondition`, `SegmentGroup`, `SegmentOperator`, `SegmentLogic` (enum), `SegmentEvaluator` — AND/OR with one level of nesting, full operator set from spec (`is`, `is not`, `contains`, `does not contain`, `starts with`, `is blank`/`is not blank`, `>`/`<`/`between`, `before`/`after`/`within last N days`)
- Tests: campaign state machine transitions (valid + invalid), campaign sender suppression/rendering, segment evaluator (AND/OR, nesting, all operators)

### Stage 7 — Tracking (Phase 3) ✅
- `Domain/Tracking/PixelGenerator.php` — builds `{baseUrl}/nettmail/track/open/{send_token}`, `<img>` tag, and inserts it before `</body>` (or appends if absent)
- `Domain/Tracking/LinkRewriter.php` — DOM-based, rewrites `<a href>` to `{baseUrl}/nettmail/track/click/{send_token}/{link_hash}`; skips the `{{unsubscribe_url}}` merge tag and any explicitly passed `skipUrls`
- `Domain/Tracking/EventRecorder.php` + `TrackingEvent.php` — builds open/click `TrackingEvent` records (reuses `Domain/Webhooks/EventType`); `isFirstOpen()` for the first-open-wins rule
- Tests: pixel URL generation and HTML insertion, link rewriting (preserves unsubscribe links unwrapped, respects explicit skip list, ignores anchors without href), event recorder

### Stage 8 — Remaining Drivers (Phase 3) ✅
- `Drivers/Support/MultipartFormBuilder.php` — builds multipart/form-data bodies (fields + file attachments) for Mailgun
- `Drivers/MailgunDriver.php` — multipart POST to `{baseUrl}/{domain}/messages`, Basic auth `api:{apiKey}`; success keyed off `body['id']`
- `Drivers/PostmarkDriver.php` — JSON POST to `{baseUrl}/email` with `X-Postmark-Server-Token` header; failure when `ErrorCode !== 0`
- `Drivers/Support/SesV2Signer.php` — from-scratch AWS SigV4 signer (no AWS SDK dependency), returns `Authorization`/`X-Amz-Date`/`X-Amz-Content-Sha256` headers
- `Drivers/SesDriver.php` — signed POST to SES v2 `outbound-emails` endpoint; uses `Content.Simple` for plain html/text, falls back to `Content.Raw.Data` (base64 MIME via `SymfonyEmailFactory`) when attachments are present
- `Drivers/Webhooks/SesWebhookHandler.php` — verifies SNS notifications by checking `TopicArn` against the configured secret, and (when an HTTP client is supplied) verifies the SNS message signature against the certificate at `SigningCertURL`; maps SES `eventType` (Send/Delivery/Open/Click/Bounce/Complaint) to `EventType`, with Bounce split into Hard/SoftBounced via `bounce.bounceType`
- All drivers follow the same `MailDriverContract` + `SendResult` pattern as Stage 1
- Tests: signed-request assembly, raw MIME fallback with attachments, API error handling for each driver, SES SNS signature verification (valid + invalid), SES event mapping

---

**Notes:**
- `StorageAdapterContract` grows incrementally per stage rather than being fully designed upfront — avoids guessing methods before the domain logic that needs them exists.
- PHPStan level 5 + CS Fixer run per stage, not just at the end.
