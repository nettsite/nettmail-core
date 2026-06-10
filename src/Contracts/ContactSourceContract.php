<?php

namespace Nettsite\NettMail\Core\Contracts;

interface ContactSourceContract
{
    /** Human-readable name shown in the NettMail UI. */
    public function label(): string;

    /** Unique key used as source_type on nettmail_contacts. */
    public function key(): string;

    /**
     * Return contacts as an iterable/generator to avoid memory issues.
     *
     * @return iterable<array{email: string, first_name?: string, last_name?: string, metadata?: array<string, mixed>}>
     */
    public function contacts(): iterable;

    /**
     * @return array{email: string, first_name?: string, last_name?: string, metadata?: array<string, mixed>}|null
     */
    public function findContact(string|int $sourceId): ?array;
}
