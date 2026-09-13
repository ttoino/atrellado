<?php

namespace App\Support\Mailer;

use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\RawMessage;
use WorkersPHP\SendEmailBinding;

// Symfony Mailer transport over the Worker's send_email binding. The
// binding takes a structured message (no raw MIME), so only the first
// From address is used and Bcc is dropped (Cloudflare rejects it).
class WorkersEmailTransport implements TransportInterface
{
    public function __construct(private readonly SendEmailBinding $binding) {}

    public function send(RawMessage $message, ?\Symfony\Component\Mailer\Envelope $envelope = null): ?SentMessage
    {
        if (!$message instanceof Email) {
            throw new \LogicException('workers-email only supports Symfony Email messages.');
        }

        $this->binding->send([
            'from' => $message->getFrom()[0]->getAddress(),
            'to' => array_map(fn (Address $a) => $a->getAddress(), $message->getTo()),
            'subject' => (string) $message->getSubject(),
            'html' => $message->getHtmlBody(),
            'text' => $message->getTextBody(),
        ]);

        return null;
    }

    public function __toString(): string
    {
        return 'workers-email';
    }
}
