<?php

namespace App\Support\Mailer;

use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\RawMessage;

// Symfony Mailer transport over the worker's email endpoint, which takes a
// structured message (no raw MIME). Only the first From address is used
// and Bcc is dropped (Cloudflare rejects it).
class HttpMailTransport implements TransportInterface
{
    public function __construct(private readonly string $endpoint) {}

    public function send(RawMessage $message, ?\Symfony\Component\Mailer\Envelope $envelope = null): ?SentMessage
    {
        if (!$message instanceof Email) {
            throw new \LogicException('http-mail only supports Symfony Email messages.');
        }

        $ch = curl_init($this->endpoint . '/send');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode([
                'from' => $message->getFrom()[0]->getAddress(),
                'to' => array_map(fn (Address $a) => $a->getAddress(), $message->getTo()),
                'subject' => (string) $message->getSubject(),
                'html' => $message->getHtmlBody(),
                'text' => $message->getTextBody(),
            ]),
        ]);
        $raw = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        if ($raw === false || $status >= 400) {
            throw new TransportException('Email endpoint error (HTTP ' . $status . '): ' . substr((string) $raw, 0, 500));
        }

        return null;
    }

    public function __toString(): string
    {
        return 'http-mail';
    }
}
