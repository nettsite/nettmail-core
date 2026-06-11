<?php

namespace Nettsite\NettMail\Core\Domain\Contacts;

use DateTimeImmutable;

/**
 * Builds a CSV of globally suppressed contacts (hard bounce, complaint,
 * global unsubscribe) for upload to external provider suppression lists.
 */
final class SuppressionExporter
{
    /**
     * @param array<int, Contact> $contacts
     */
    public function export(array $contacts): string
    {
        $stream = fopen('php://temp', 'r+');

        fputcsv($stream, ['email', 'reason', 'suppressed_at'], escape: '');

        foreach ($contacts as $contact) {
            $suppression = $this->toSuppression($contact);

            if ($suppression === null) {
                continue;
            }

            fputcsv($stream, [
                $suppression->email,
                $suppression->reason->value,
                $suppression->suppressedAt->format(DATE_ATOM),
            ], escape: '');
        }

        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);

        return $csv;
    }

    private function toSuppression(Contact $contact): ?Suppression
    {
        if ($contact->bounceType === BounceType::Complaint) {
            return new Suppression($contact->email, SuppressionReason::Complaint, $contact->bouncedAt ?? new DateTimeImmutable());
        }

        if ($contact->bounceType === BounceType::Hard) {
            return new Suppression($contact->email, SuppressionReason::HardBounce, $contact->bouncedAt ?? new DateTimeImmutable());
        }

        if ($contact->globalUnsubscribedAt !== null) {
            return new Suppression($contact->email, SuppressionReason::Unsubscribed, $contact->globalUnsubscribedAt);
        }

        return null;
    }
}
