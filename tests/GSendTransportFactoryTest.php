<?php

namespace Glofora\GSend\Tests;

use Glofora\GSend\GSendTransport;
use Glofora\GSend\GSendTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;

class GSendTransportFactoryTest extends TestCase
{
    public function test_it_creates_transport_from_dsn(): void
    {
        $factory = new GSendTransportFactory();
        $dsn = new Dsn('gsend', 'default', 'my_secret_token');

        $this->assertTrue($factory->supports($dsn));

        $transport = $factory->create($dsn);

        $this->assertInstanceOf(GSendTransport::class, $transport);
        $this->assertSame('gsend', (string) $transport);
    }
}
