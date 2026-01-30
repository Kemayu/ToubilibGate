<?php
declare(strict_types=1);

namespace toubilib\core\application\ports\spi;

interface MailerInterface
{
    public function send(string $to, string $subject, string $body, ?string $from = null): void;
}