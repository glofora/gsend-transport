<?php

namespace Glofora\GSend\Tests;

use Glofora\GSend\Contracts\GSendClientInterface;
use Glofora\GSend\GSendTransport;
use Mockery;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\RawMessage;

class GSendTransportTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_simulates_when_api_key_is_empty(): void
    {
        $clientMock = Mockery::mock(GSendClientInterface::class);
        $clientMock->shouldNotReceive('send');

        $transport = new GSendTransport(
            apiKey: null,
            client: $clientMock
        );

        $email = (new Email())
            ->from('sender@example.com')
            ->to('recipient@example.com')
            ->subject('Hello Test')
            ->text('Hello Plain Text');

        $sentMessage = new SentMessage($email, new \Symfony\Component\Mailer\Envelope($email->getFrom()[0], $email->getTo()));

        // Should return early and not call send
        $reflection = new \ReflectionClass($transport);
        $doSend = $reflection->getMethod('doSend');
        $doSend->setAccessible(true);
        $doSend->invoke($transport, $sentMessage);

        $this->assertSame('gsend', (string) $transport);
    }

    public function test_it_formats_payload_and_dispatches_mail(): void
    {
        $clientMock = Mockery::mock(GSendClientInterface::class);
        $clientMock->shouldReceive('send')
            ->once()
            ->with(Mockery::on(function ($payload) {
                return $payload['to'] === 'user@example.com'
                    && $payload['subject'] === 'Test Subject'
                    && $payload['custom_content'] === '<b>Hello World</b>'
                    && $payload['from_name'] === 'Sender Person'
                    && count($payload['attachments']) === 1
                    && $payload['attachments'][0]['filename'] === 'invoice.pdf';
            }))
            ->andReturn(['success' => true]);

        $transport = new GSendTransport(
            apiKey: 'key_123',
            client: $clientMock
        );

        $email = (new Email())
            ->from('Sender Person <sender@example.com>')
            ->to('user@example.com')
            ->subject('Test Subject')
            ->html('<b>Hello World</b>')
            ->attach('fake-pdf-content', 'invoice.pdf', 'application/pdf');

        $sentMessage = new SentMessage($email, new \Symfony\Component\Mailer\Envelope($email->getFrom()[0], $email->getTo()));

        $reflection = new \ReflectionClass($transport);
        $doSend = $reflection->getMethod('doSend');
        $doSend->setAccessible(true);
        $doSend->invoke($transport, $sentMessage);

        $this->addToAssertionCount(1);
    }

    public function test_it_handles_multiple_recipients(): void
    {
        $clientMock = Mockery::mock(GSendClientInterface::class);
        $clientMock->shouldReceive('send')
            ->once()
            ->with(Mockery::on(function ($payload) {
                return is_array($payload['to'])
                    && count($payload['to']) === 2
                    && in_array('one@example.com', $payload['to'])
                    && in_array('two@example.com', $payload['to']);
            }))
            ->andReturn(['success' => true]);

        $transport = new GSendTransport(
            apiKey: 'key_123',
            client: $clientMock
        );

        $email = (new Email())
            ->from('sender@example.com')
            ->to('one@example.com', 'two@example.com')
            ->subject('Multiple')
            ->text('Text Body');

        $sentMessage = new SentMessage($email, new \Symfony\Component\Mailer\Envelope($email->getFrom()[0], $email->getTo()));

        $reflection = new \ReflectionClass($transport);
        $doSend = $reflection->getMethod('doSend');
        $doSend->setAccessible(true);
        $doSend->invoke($transport, $sentMessage);

        $this->addToAssertionCount(1);
    }

    public function test_it_wraps_client_api_exception_into_transport_exception(): void
    {
        $clientMock = Mockery::mock(GSendClientInterface::class);
        $clientMock->shouldReceive('send')
            ->once()
            ->andThrow(new \Glofora\GSend\Exceptions\GSendApiException('Gateway error', 400));

        $transport = new GSendTransport(
            apiKey: 'key_123',
            client: $clientMock
        );

        $email = (new Email())
            ->from('sender@example.com')
            ->to('fail@example.com')
            ->subject('Fail Test')
            ->text('Fail content');

        $sentMessage = new SentMessage($email, new \Symfony\Component\Mailer\Envelope($email->getFrom()[0], $email->getTo()));

        $this->expectException(\Symfony\Component\Mailer\Exception\TransportException::class);
        $this->expectExceptionMessage('Gateway error');

        $reflection = new \ReflectionClass($transport);
        $doSend = $reflection->getMethod('doSend');
        $doSend->setAccessible(true);
        $doSend->invoke($transport, $sentMessage);
    }
}
