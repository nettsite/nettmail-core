<?php

namespace Nettsite\NettMail\Core\Drivers;

use Nettsite\NettMail\Core\Contracts\MailDriverContract;
use Nettsite\NettMail\Core\Drivers\Support\AddressFormatter;
use Nettsite\NettMail\Core\Drivers\Support\AttachmentReader;
use Nettsite\NettMail\Core\Mail\EmailAddress;
use Nettsite\NettMail\Core\Mail\EmailMessage;
use Nettsite\NettMail\Core\Mail\SendResult;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class MailersendDriver implements MailDriverContract
{
    public function __construct(
        private readonly string $apiKey,
        private readonly ClientInterface $httpClient,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly StreamFactoryInterface $streamFactory,
        private readonly string $baseUrl = 'https://api.mailersend.com/v1',
    ) {
    }

    public function send(EmailMessage $message): SendResult
    {
        $request = $this->requestFactory
            ->createRequest('POST', "{$this->baseUrl}/email")
            ->withHeader('Authorization', "Bearer {$this->apiKey}")
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Accept', 'application/json')
            ->withBody($this->streamFactory->createStream(json_encode($this->payload($message), JSON_THROW_ON_ERROR)));

        try {
            $response = $this->httpClient->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
            return SendResult::failure($e->getMessage());
        }

        if ($response->getStatusCode() >= 300) {
            $body = json_decode((string) $response->getBody(), true) ?? [];

            return SendResult::failure($body['message'] ?? "Mailersend API error ({$response->getStatusCode()})");
        }

        return SendResult::success($response->getHeaderLine('X-Message-Id'));
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(EmailMessage $message): array
    {
        $payload = [
            'from' => self::formatAddress($message->from),
            'to' => array_map(self::formatAddress(...), $message->to),
            'subject' => $message->subject,
        ];

        if ($message->html !== null) {
            $payload['html'] = $message->html;
        }

        if ($message->text !== null) {
            $payload['text'] = $message->text;
        }

        if ($message->cc !== []) {
            $payload['cc'] = array_map(self::formatAddress(...), $message->cc);
        }

        if ($message->bcc !== []) {
            $payload['bcc'] = array_map(self::formatAddress(...), $message->bcc);
        }

        if ($message->replyTo !== null) {
            $payload['reply_to'] = self::formatAddress($message->replyTo);
        }

        if ($message->attachments !== []) {
            $payload['attachments'] = array_map(
                fn (array $attachment): array => [
                    'filename' => $attachment['name'],
                    'content' => base64_encode(AttachmentReader::read($attachment['path'])),
                    'disposition' => 'attachment',
                ],
                $message->attachments,
            );
        }

        if ($message->headers !== []) {
            $payload['headers'] = array_map(
                fn (string $name, string $value): array => ['name' => $name, 'value' => $value],
                array_keys($message->headers),
                array_values($message->headers),
            );
        }

        return $payload;
    }

    /**
     * @return array<string, string>
     */
    private static function formatAddress(EmailAddress $address): array
    {
        $formatted = ['email' => $address->email];

        $name = AddressFormatter::sanitizeName($address->name);

        if ($name !== null) {
            $formatted['name'] = $name;
        }

        return $formatted;
    }
}
