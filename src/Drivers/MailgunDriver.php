<?php

namespace Nettsite\NettMail\Core\Drivers;

use Nettsite\NettMail\Core\Contracts\MailDriverContract;
use Nettsite\NettMail\Core\Drivers\Support\MultipartFormBuilder;
use Nettsite\NettMail\Core\Mail\EmailAddress;
use Nettsite\NettMail\Core\Mail\EmailMessage;
use Nettsite\NettMail\Core\Mail\SendResult;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class MailgunDriver implements MailDriverContract
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $domain,
        private readonly ClientInterface $httpClient,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly StreamFactoryInterface $streamFactory,
        private readonly string $baseUrl = 'https://api.mailgun.net/v3',
    ) {
    }

    public function send(EmailMessage $message): SendResult
    {
        $form = new MultipartFormBuilder();

        $form->addField('from', self::formatAddress($message->from));

        foreach ($message->to as $address) {
            $form->addField('to', self::formatAddress($address));
        }

        foreach ($message->cc as $address) {
            $form->addField('cc', self::formatAddress($address));
        }

        foreach ($message->bcc as $address) {
            $form->addField('bcc', self::formatAddress($address));
        }

        $form->addField('subject', $message->subject);

        if ($message->html !== null) {
            $form->addField('html', $message->html);
        }

        if ($message->text !== null) {
            $form->addField('text', $message->text);
        }

        if ($message->replyTo !== null) {
            $form->addField('h:Reply-To', self::formatAddress($message->replyTo));
        }

        foreach ($message->attachments as $attachment) {
            $form->addFile('attachment', $attachment['name'], file_get_contents($attachment['path']));
        }

        $request = $this->requestFactory
            ->createRequest('POST', "{$this->baseUrl}/{$this->domain}/messages")
            ->withHeader('Authorization', 'Basic '.base64_encode("api:{$this->apiKey}"))
            ->withHeader('Content-Type', "multipart/form-data; boundary={$form->boundary()}")
            ->withBody($this->streamFactory->createStream($form->build()));

        try {
            $response = $this->httpClient->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
            return SendResult::failure($e->getMessage());
        }

        $body = json_decode((string) $response->getBody(), true) ?? [];

        if ($response->getStatusCode() >= 300) {
            return SendResult::failure($body['message'] ?? "Mailgun API error ({$response->getStatusCode()})");
        }

        return SendResult::success($body['id'] ?? '');
    }

    private static function formatAddress(EmailAddress $address): string
    {
        return $address->name !== null
            ? "{$address->name} <{$address->email}>"
            : $address->email;
    }
}
