<?php

namespace Nettsite\NettMail\Core\Drivers\Support;

final class MultipartFormBuilder
{
    private readonly string $boundary;

    private string $body = '';

    public function __construct()
    {
        $this->boundary = '----NettMailBoundary'.bin2hex(random_bytes(16));
    }

    public function boundary(): string
    {
        return $this->boundary;
    }

    public function addField(string $name, string $value): void
    {
        $this->body .= "--{$this->boundary}\r\n";
        $this->body .= "Content-Disposition: form-data; name=\"{$name}\"\r\n\r\n";
        $this->body .= $value."\r\n";
    }

    public function addFile(string $name, string $filename, string $content): void
    {
        $this->body .= "--{$this->boundary}\r\n";
        $this->body .= "Content-Disposition: form-data; name=\"{$name}\"; filename=\"{$filename}\"\r\n";
        $this->body .= "Content-Type: application/octet-stream\r\n\r\n";
        $this->body .= $content."\r\n";
    }

    public function build(): string
    {
        return $this->body."--{$this->boundary}--\r\n";
    }
}
