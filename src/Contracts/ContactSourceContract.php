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
     * @return iterable<array{email: string, first_name?: string, last_name?: string, phone?: string, metadata?: array<string, mixed>, source_id?: string|int}>
     */
    public function contacts(): iterable;

    /**
     * @return array{email: string, first_name?: string, last_name?: string, phone?: string, metadata?: array<string, mixed>, source_id?: string|int}|null
     */
    public function findContact(string|int $sourceId): ?array;
}
