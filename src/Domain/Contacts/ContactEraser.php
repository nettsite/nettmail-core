<?php

namespace Nettsite\NettMail\Core\Domain\Contacts;

/**
 * POPIA right-to-erasure: anonymises a contact's PII in place while
 * preserving its id, so aggregate send statistics keyed by contact_id
 * remain intact.
 */
final class ContactEraser
{
    public function erase(Contact $contact): void
    {
        $contact->email = 'erased-'.hash('sha256', $contact->email).'@erased.invalid';
        $contact->firstName = null;
        $contact->lastName = null;
        $contact->phone = null;
        $contact->metadata = [];
        $contact->sourceType = null;
        $contact->sourceId = null;
    }
}
