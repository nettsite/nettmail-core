# NettMail Core

[![Latest Version on Packagist](https://img.shields.io/packagist/v/nettmail/core.svg?style=flat-square)](https://packagist.org/packages/nettmail/core)
[![Tests](https://github.com/nettsite/nettmail-core/actions/workflows/run-tests.yml/badge.svg)](https://github.com/nettsite/nettmail-core/actions/workflows/run-tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/nettmail/core.svg?style=flat-square)](https://packagist.org/packages/nettmail/core)

Framework-agnostic PHP 8.2+ core for **NettMail** — a composable email package handling transactional delivery, broadcast campaigns, drag-and-drop template authoring (Unlayer), contact list management, segmentation, bounce processing, and provider webhook ingestion.

This package contains all domain logic, contracts, and drivers. It has no dependency on Laravel or WordPress — those are thin adapters built on top of `nettmail/core`:

- [`nettmail/laravel`](https://github.com/nettsite/nettmail-laravel) — Laravel adapter (Eloquent models, Livewire admin UI, queued jobs)
- `nettmail/wordpress` — WordPress plugin adapter
- [`nettmail/filament`](https://github.com/nettsite/nettmail-filament) — Filament UI adapter (planned)

## Status

Early development. See [PLAN.md](PLAN.md) for the build roadmap.

## Installation

```bash
composer require nettmail/core
```

## Usage

```php
use Nettsite\NettMail\Core\NettMail;
use Nettsite\NettMail\Core\Mail\EmailAddress;
use Nettsite\NettMail\Core\Mail\EmailMessage;

$nettmail = new NettMail($driver); // $driver implements MailDriverContract

$result = $nettmail->send(new EmailMessage(
    from: new EmailAddress('sender@example.com', 'Sender'),
    to: [new EmailAddress('recipient@example.com')],
    subject: 'Hello',
    html: '<p>Hello world</p>',
));
```

## Testing

```bash
composer test
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
