<?php

namespace Nettsite\NettMail\Core\Domain\Campaigns;

final readonly class MergeTag
{
    public function __construct(
        public string $key,
        public string $label,
        public ?string $defaultValue = null,
    ) {
    }

    /**
     * @return array<int, self>
     */
    public static function defaults(): array
    {
        return [
            new self('first_name', 'First Name'),
            new self('last_name', 'Last Name'),
            new self('email', 'Email Address'),
            new self('company', 'Company'),
            new self('unsubscribe_url', 'Unsubscribe Link'),
        ];
    }
}
