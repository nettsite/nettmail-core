<?php

namespace Nettsite\NettMail\Core\Tests\Fakes;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class FakeHttpClient implements ClientInterface
{
    public ?RequestInterface $lastRequest = null;

    public function __construct(
        private readonly ResponseInterface $response,
    ) {
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->lastRequest = $request;

        return $this->response;
    }
}
