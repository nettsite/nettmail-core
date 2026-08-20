# Changelog

All notable changes to `core` will be documented in this file.

## v0.1.3 - 2026-08-20

### Fixed

- `DsnParser` now decodes quoted-printable soft line breaks and unfolds RFC 2822 header continuations before matching bounce fields. Previously, DSN fields (`Status`, `Final-Recipient`, `Diagnostic-Code`) or heuristic keyword matches split across lines by folding or QP encoding — common on Exchange/Outlook NDRs — could be missed.
