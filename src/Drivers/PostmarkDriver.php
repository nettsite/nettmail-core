<?php

namespace Nettsite\NettMail\Core\Drivers;

use Nettsite\NettMail\Core\Contracts\MailDriverContract;
use Nettsite\NettMail\Core\Drivers\Support\AddressFormatter;
use Nettsite\NettMail\Core\Drivers\Support\AttachmentReader;
use Nettsite\NettMail\Core\Mail\EmailMessage;
use Nettsite\NettMail\Core\Mail\SendResult;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class PostmarkDriver implements MailDriverContract
{
    public function __construct(
        private readonly string $serverToken,
        private readonly ClientInterface $httpClient,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly StreamFactoryInterface $streamFactory,
        private readonly string $baseUrl = 'https://api.postmarkapp.com',
    ) {
    }

    public function send(EmailMessage $message): SendResult
    {
        $request = $this->requestFactory
            ->createRequest('POST', "{$this->baseUrl}/email")
            ->withHeader('X-Postmark-Server-Token', $this->serverToken)
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Accept', 'application/json')
            ->withBody($this->streamFactory->createStream(json_encode($this->payload($message), JSON_THROW_ON_ERROR)));

        try {
            $response = $this->httpClient->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
            return SendResult::failure($e->getMessage());
        }

        $body = json_decode((string) $response->getBody(), true) ?? [];

        if ($response->getStatusCode() >= 300 || ($body['ErrorCode'] ?? 0) !== 0) {
            return SendResult::failure($body['Message'] ?? "Postmark API error ({$response->getStatusCode()})");
        }

        return SendResult::success($body['MessageID'] ?? '');
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(EmailMessage $message): array
    {
        $payload = [
            'From' => AddressFormatter::format($message->from),
            'To' => implode(', ', array_map(AddressFormatter::format(...), $message->to)),
            'Subject' => $message->subject,
        ];

        if ($message->html !== null) {
            $payload['HtmlBody'] = $message->html;
        }

        if ($message->text !== null) {
            $payload['TextBody'] = $message->text;
        }

        if ($message->cc !== []) {
            $payload['Cc'] = implode(', ', array_map(AddressFormatter::format(...), $message->cc));
        }

        if ($message->bcc !== []) {
            $payload['Bcc'] = implode(', ', array_map(AddressFormatter::format(...), $message->bcc));
        }

        if ($message->replyTo !== null) {
            $payload['ReplyTo'] = AddressFormatter::format($message->replyTo);
        }

        if ($message->attachments !== []) {
            $payload['Attachments'] = array_map(
                fn (array $attachment): array => [
                    'Name' => $attachment['name'],
                    'Content' => base64_encode(AttachmentReader::read($attachment['path'])),
                    'ContentType' => 'application/octet-stream',
                ],
                $message->attachments,
            );
        }

        if ($message->headers !== []) {
            $payload['Headers'] = array_map(
                fn (string $name, string $value): array => ['Name' => $name, 'Value' => $value],
                array_keys($message->headers),
                array_values($message->headers),
            );
        }

        return $payload;
    }
}
