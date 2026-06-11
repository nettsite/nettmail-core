# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Repo

This is `nettmail/core` — one repo within the `nettmail/` workspace (see `../CLAUDE.md` for the full multi-package picture). Pure PHP 8.2+, zero framework dependencies. Bootstrapped from `spatie/package-skeleton-php`. This repo has its own GitHub remote — verify with `git remote -v` before pushing.

## Commands

```bash
vendor/bin/pest                                  # run all tests
vendor/bin/pest tests/Drivers/SesDriverTest.php  # run a single test file
vendor/bin/pest --filter "test name"             # run tests matching a name
vendor/bin/pest --coverage                       # with coverage (build/coverage)
vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.dist.php --allow-risky=yes
```

`composer test` and `composer format` wrap the above. PHPStan level 5 minimum applies (per workspace conventions) — run it per-stage, not just at the end.

## Architecture

### Build order

The spec (`../email-package-spec.html`, v0.4) is the authoritative source for requirements and remaining work — see its Delivery Phases section. Stages 0–8 (foundation, drivers, templates, contacts/lists, bounces, webhooks, campaigns/segmentation, tracking, remaining drivers) are complete; the stage-by-stage build log lives in git history. Follow the established pattern for new work: contracts → implementation → Pest tests → commit.

### Layout

- `src/Mail/` — core value objects: `EmailAddress`, `EmailMessage`, `SendResult`.
- `src/NettMail.php` — facade-like entry point (per spec naming: Herald→NettMail rename).
- `src/Contracts/` — the framework-agnostic interfaces adapters (`nettmail/laravel`, `nettmail/wordpress`) implement:
  - `MailDriverContract` — `send(EmailMessage): SendResult`, implemented by every driver in `src/Drivers/`.
  - `StorageAdapterContract` — grows incrementally per stage, only adding methods the domain actually needs (currently contacts/lists/memberships). Don't pre-design new methods speculatively — add them when a stage requires them.
  - `BounceParserContract`, `WebhookHandlerContract`, `ContactSourceContract`.
- `src/Domain/` — business logic, organized by bounded context: `Bounces`, `Campaigns` (incl. `Campaigns/Segmentation`), `Contacts`, `Templates`, `Tracking`, `Webhooks`. Each is independent of the others and of any driver.
- `src/Drivers/` — `MailDriverContract` implementations (Php, Smtp, Resend, Mailersend, Mailgun, Postmark, Ses) plus `Drivers/Webhooks/` (one handler per provider mapping provider payloads → `Domain/Webhooks/NormalizedEvent`) and `Drivers/Support/` (shared helpers: `SymfonyEmailFactory`, `MultipartFormBuilder`, `SesV2Signer`).

### Key cross-cutting patterns

- **Webhooks → events → domain**: each provider's webhook handler (`Drivers/Webhooks/*`) verifies the signature and parses the payload into `Domain/Webhooks/NormalizedEvent[]`. These feed `Domain/Bounces/BounceClassifier` (hard/soft/complaint state machine with auto-suppress after N soft bounces) and `Domain/Tracking/EventRecorder` (open/click).
- **Email normalization & suppression**: `Domain/Contacts/EmailNormalizer` is used everywhere contacts are deduped/looked up. `Contact::isSuppressed()` is the single source of truth for whether a send should proceed (hard bounce / complaint / global unsubscribe exempt only operational transactional mail) — `Domain/Campaigns/CampaignSender::shouldSend()` relies on this.
- **Templates**: `TemplateCompiler` validates `{{unsubscribe_url}}` is present for `TemplateType::Broadcast` (throws `MissingUnsubscribeLinkException` otherwise) and auto-generates plain text via `PlainTextConverter`. `MergeTagRenderer` substitutes `{{tag}}` placeholders, leaving unknown tags untouched.
- **HTTP-API drivers are PSR-18 injectable**: Resend, Mailersend, Mailgun, Postmark, Ses drivers take a PSR-18 client constructor argument so tests can inject `tests/Fakes/FakeHttpClient.php` instead of hitting the network.
- **SES is hand-rolled SigV4**: `Drivers/Support/SesV2Signer.php` signs requests without the AWS SDK; `SesDriver` falls back to raw MIME (`Content.Raw.Data`, base64 via `SymfonyEmailFactory`) when attachments are present, otherwise uses `Content.Simple`.

### Testing conventions

- Pest, with `tests/Fakes/` providing `FakeHttpClient`, `FakeMailDriver`, and `InMemoryStorageAdapter` (a contract-conformance fake for `StorageAdapterContract`, exercised by its own test).
- Bounce-parsing fixtures (RFC 3464 + heuristic .eml samples) live in `tests/Fixtures/Bounces/`.
- `tests/ArchTest.php` enforces no `dd`/`dump`/`ray` debugging calls in `src/`.
- `failOnWarning`/`failOnRisky`/`failOnEmptyTestSuite` are all enabled in `phpunit.xml.dist` — tests must not be empty or emit warnings.

### Code style

PHP CS Fixer (`@PSR12` + short array syntax, alpha-sorted imports, no unused imports, one blank line between class members, fully multiline method args when wrapped). Also follow workspace-wide Spatie conventions in `~/.claude/laravel-php-guidelines.md` (typed properties, no docblocks on fully-typed methods, happy-path-last, early returns).
