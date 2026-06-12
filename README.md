# NettMail Core

[![Latest Version on Packagist](https://img.shields.io/packagist/v/nettsite/nettmail-core.svg?style=flat-square)](https://packagist.org/packages/nettsite/nettmail-core)
[![Tests](https://github.com/nettsite/nettmail-core/actions/workflows/run-tests.yml/badge.svg)](https://github.com/nettsite/nettmail-core/actions/workflows/run-tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/nettsite/nettmail-core.svg?style=flat-square)](https://packagist.org/packages/nettsite/nettmail-core)

Framework-agnostic PHP 8.2+ core for **NettMail** — a composable email package handling transactional delivery, broadcast campaigns, drag-and-drop template authoring (Unlayer), contact list management, segmentation, bounce processing, and provider webhook ingestion.

This package contains all domain logic, contracts, and drivers. It has no dependency on Laravel or WordPress — those are thin adapters built on top of `nettsite/nettmail-core`:

- [`nettmail/laravel`](https://github.com/nettsite/nettmail-laravel) — Laravel adapter (Eloquent models, Livewire admin UI, queued jobs)
- `nettmail/wordpress` — WordPress plugin adapter
- [`nettmail/filament`](https://github.com/nettsite/nettmail-filament) — Filament UI adapter (planned)

## Features

- **Seven mail drivers** behind a single `MailDriverContract`: PHP `mail()`/sendmail, SMTP, Resend, Mailersend, Mailgun, Postmark, and Amazon SES (hand-rolled SigV4 — no AWS SDK). HTTP drivers accept any PSR-18 client.
- **Custom headers** on every driver — including the RFC 8058 `List-Unsubscribe` / `List-Unsubscribe-Post` one-click unsubscribe headers required by Gmail and Yahoo for bulk senders.
- **Webhook ingestion** for Resend, Mailersend, Mailgun, Postmark, and SES/SNS — signature verification (with replay-timestamp tolerance), payload parsing into normalized events.
- **Bounce handling** — RFC 3464 DSN parsing with heuristic fallback, hard/soft/complaint classification, auto-suppression after N consecutive soft bounces.
- **Contacts, lists, and segmentation** — email normalization and deduplication, per-list membership status, AND/OR condition groups with string, numeric, and date operators.
- **Campaigns** — status state machine (draft → scheduled → sending → sent/failed/paused), merge-tag rendering, suppression-aware send filtering.
- **Open & click tracking** — tracking-pixel insertion and link rewriting done once per campaign (with a send-token placeholder), producing a link hash → URL map for the redirect endpoint.
- **Templates** — compilation with enforced unsubscribe links for broadcast templates and auto-generated plain-text fallback.
- **Injection-safe addressing** — display names are RFC 5322 quoted and emails validated centrally, on every driver.
- **IMAP bounce-mailbox polling** — `BouncePoller` fetches unseen messages via an injectable `MailboxContract`, classifies them with the same DSN parser/heuristics as webhooks, and files them into "Processed"/"Unrecognised" folders.
- **POPIA right-to-erasure** — `NettMail::eraseContact()` anonymises a contact's PII while preserving its id, so aggregate send statistics remain intact.
- **Suppression list export** — `NettMail::exportSuppressions()` produces a CSV of hard-bounced, complained, and globally unsubscribed contacts.
- **Double opt-in tokens** — `OptInTokenGenerator` issues and verifies framework-agnostic, HMAC-signed, expiring confirmation tokens shared by both Laravel and WordPress adapters.

## Status

Feature-complete for the current delivery phases, including all Phase 4 compliance primitives (IMAP bounce polling, right-to-erasure, suppression export, double opt-in tokens).

## Installation

```bash
composer require nettsite/nettmail-core
```

## Usage

```php
use Nettsite\NettMail\Core\Drivers\ResendDriver;
use Nettsite\NettMail\Core\Mail\EmailAddress;
use Nettsite\NettMail\Core\Mail\EmailMessage;
use Nettsite\NettMail\Core\NettMail;

$nettmail = new NettMail(
    new ResendDriver($apiKey, $httpClient, $requestFactory, $streamFactory),
    $storage, // your StorageAdapterContract implementation
);

$result = $nettmail->send(new EmailMessage(
    from: new EmailAddress('sender@example.com', 'Sender'),
    to: [new EmailAddress('recipient@example.com')],
    subject: 'Hello',
    html: '<p>Hello world</p>',
    headers: ['List-Unsubscribe' => '<https://example.com/unsubscribe/abc>'],
));

$result->success;   // bool
$result->messageId; // provider message id, normalized for webhook correlation
```

Any class implementing `MailDriverContract` can be passed to `NettMail` — swap providers without touching calling code. Persistence is left to the host application via `StorageAdapterContract`; the adapters above provide Eloquent and `$wpdb` implementations.

## Testing

```bash
composer test     # Pest, 164 tests
composer phpstan  # PHPStan level 5
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](https://github.com/spatie/.github/blob/main/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Nettsite](https://github.com/nettsite)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
