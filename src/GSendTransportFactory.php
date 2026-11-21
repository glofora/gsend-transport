<?php

namespace Glofora\GSend;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Exception\UnsupportedSchemeException;
use Symfony\Component\Mailer\Transport\AbstractTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\TransportInterface;

class GSendTransportFactory extends AbstractTransportFactory
{
    /**
     * @return string[]
     */
    protected function getSupportedSchemes(): array
    {
        return ['gsend', 'gsend+api', 'gsend+https'];
    }

    /**
     * {@inheritdoc}
     */
    public function create(Dsn $dsn): TransportInterface
    {
        $scheme = $dsn->getScheme();

        if (! in_array($scheme, $this->getSupportedSchemes(), true)) {
            throw new UnsupportedSchemeException($dsn, 'gsend', $this->getSupportedSchemes());
        }

        $apiKey = $this->getUser($dsn);
        $host = 'default' === $dsn->getHost() ? null : $dsn->getHost();
        $port = $dsn->getPort();

        $endpoint = GSendTransport::DEFAULT_ENDPOINT;
        if ($host) {
            $path = $dsn->getOption('path', '/api/v1/send-mail');
            $endpoint = ($port ? "https://{$host}:{$port}" : "https://{$host}").$path;
        }

        return new GSendTransport(
            apiKey: $apiKey,
            endpoint: $endpoint,
            logger: $this->logger,
            dispatcher: $this->dispatcher
        );
    }
}
