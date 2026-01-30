<?php
declare(strict_types=1);

namespace toubilib\infra\mailer;

use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Email;
use toubilib\core\application\ports\spi\MailerInterface;

final class SymfonyMailerAdapter implements MailerInterface
{
    private Mailer $mailer;
    private ?string $defaultFrom;

    public function __construct(string $dsn, ?string $defaultFrom = null)
    {
        $transport = Transport::fromDsn($dsn);
        $this->mailer = new Mailer($transport);
        $this->defaultFrom = $defaultFrom;
    }

    public function send(string $to, string $subject, string $body, ?string $from = null): void
    {
        $email = (new Email())
            ->from($from ?: ($this->defaultFrom ?: 'no-reply@toubilib.local'))
            ->to($to)
            ->subject($subject)
            ->text($body);

        $this->mailer->send($email);
    }
}