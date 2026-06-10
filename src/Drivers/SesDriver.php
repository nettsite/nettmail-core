<?php

namespace Nettsite\NettMail\Core\Drivers;

use DateTimeImmutable;
use DateTimeZone;
use Nettsite\NettMail\Core\Contracts\MailDriverContract;
use Nettsite\NettMail\Core\Drivers\Support\SesV2Signer;
use Nettsite\NettMail\Core\Drivers\Support\SymfonyEmailFactory;
use Nettsite\NettMail\Core\Mail\EmailAddress;
use Nettsite\NettMail\Core\Mail\EmailMessage;
use Nettsite\NettMail\Core\Mail\SendResult;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Sends via the SES v2 SendEmail API, signed with AWS Signature V4.
 * Messages with attachments are sent as raw MIME (built via Symfony
 * Mailer); plain messages use the simpler `Simple` content shape.
 */
final class SesDriver implements MailDriverContract
{
    public function __construct(
        private readonly string $accessKeyId,
        private readonly string $secretAccessKey,
        private readonly string $region,
        private readonly ClientInterface $httpClient,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly StreamFactoryInterface $streamFactory,
        private readonly ?SesV2Signer $signer = null,
    ) {
    }

    public function send(EmailMessage $message): SendResult
    {
        $host = "email.{$this->region}.amazonaws.com";
        $path = '/v2/email/outbound-emails';
        $body = json_encode($this->payload($message), JSON_THROW_ON_ERROR);

        $signer = $this->signer ?? new SesV2Signer($this->accessKeyId, $this->secretAccessKey, $this->region);
        $signedHeaders = $signer->sign('POST', $host, $path, $body, new DateTimeImmutable('now', new DateTimeZone('UTC')));

        $request = $this->requestFactory
            ->createRequest('POST', "https://{$host}{$path}")
            ->withHeader('Host', $host)
            ->withHeader('Content-Type', 'application/json');

        foreach ($signedHeaders as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        $request = $request->withBody($this->streamFactory->createStream($body));

        try {
            $response = $this->httpClient->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
            return SendResult::failure($e->getMessage());
        }

        $responseBody = json_decode((string) $response->getBody(), true) ?? [];

        if ($response->getStatusCode() >= 300) {
            return SendResult::failure($responseBody['message'] ?? "SES API error ({$response->getStatusCode()})");
        }

        return SendResult::success($responseBody['MessageId'] ?? '');
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(EmailMessage $message): array
    {
        if ($message->attachments !== []) {
            $email = SymfonyEmailFactory::make($message);

            return [
                'FromEmailAddress' => self::formatAddress($message->from),
                'Destination' => $this->destination($message),
                'Content' => ['Raw' => ['Data' => base64_encode($email->toString())]],
            ];
        }

        $body = [];

        if ($message->html !== null) {
            $body['Html'] = ['Data' => $message->html];
        }

        if ($message->text !== null) {
            $body['Text'] = ['Data' => $message->text];
        }

        $payload = [
            'FromEmailAddress' => self::formatAddress($message->from),
            'Destination' => $this->destination($message),
            'Content' => [
                'Simple' => [
                    'Subject' => ['Data' => $message->subject],
                    'Body' => $body,
                ],
            ],
        ];

        if ($message->replyTo !== null) {
            $payload['ReplyToAddresses'] = [self::formatAddress($message->replyTo)];
        }

        return $payload;
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function destination(EmailMessage $message): array
    {
        $destination = [
            'ToAddresses' => array_map(self::formatAddress(...), $message->to),
        ];

        if ($message->cc !== []) {
            $destination['CcAddresses'] = array_map(self::formatAddress(...), $message->cc);
        }

        if ($message->bcc !== []) {
            $destination['BccAddresses'] = array_map(self::formatAddress(...), $message->bcc);
        }

        return $destination;
    }

    private static function formatAddress(EmailAddress $address): string
    {
        return $address->name !== null
            ? "{$address->name} <{$address->email}>"
            : $address->email;
    }
}
