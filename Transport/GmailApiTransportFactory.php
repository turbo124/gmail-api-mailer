<?php

namespace Symfony\Component\Mailer\Bridge\GmailApi\Transport;

use Symfony\Component\Mailer\Exception\UnsupportedSchemeException;
use Symfony\Component\Mailer\Transport\AbstractTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\TransportInterface;

final class GmailApiTransportFactory extends AbstractTransportFactory
{
    public function create(Dsn $dsn): TransportInterface
    {
        $transport = null;
        $scheme = $dsn->getScheme();
        $user = $this->getUser($dsn);

        if ('gmailapi' === $scheme) {

            $host = 'default' === $dsn->getHost() ? null : $dsn->getHost();
            $port = $dsn->getPort();

            $transport = (new GmailApiTransport($user, $this->client, $this->dispatcher, $this->logger))->setHost($host)->setPort($port);

            return $transport;

        }

        throw new UnsupportedSchemeException($dsn, 'gmailapi', $this->getSupportedSchemes());
    }

    protected function getSupportedSchemes(): array
    {
        return ['gmailapi'];
    }
}
