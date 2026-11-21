<?php

namespace Glofora\GSend\Tests;

use Glofora\GSend\Exceptions\GSendApiException;
use Glofora\GSend\GSendClient;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

class GSendClientTest extends TestCase
{
    public function test_it_identifies_simulation_mode_when_api_key_is_missing(): void
    {
        $client = new GSendClient(null);

        $this->assertTrue($client->isSimulated());

        $result = $client->send([
            'to' => 'test@example.com',
            'subject' => 'Hello',
            'custom_content' => 'Test body',
        ]);

        $this->assertSame('simulated', $result['status']);
        $this->assertTrue($result['simulated']);
    }

    public function test_it_sends_email_successfully_via_http(): void
    {
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'success' => true,
                'message' => 'Email sent',
            ])),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $httpClient = new GuzzleClient(['handler' => $handlerStack]);

        $client = new GSendClient(
            apiKey: 'test_token',
            httpClient: $httpClient
        );

        $response = $client->sendMail(
            to: 'recipient@example.com',
            subject: 'Welcome',
            content: '<p>Welcome!</p>',
            fromName: 'Glofora Team'
        );

        $this->assertTrue($response['success']);
        $this->assertSame('Email sent', $response['message']);
    }

    public function test_it_throws_api_exception_on_http_error(): void
    {
        $mock = new MockHandler([
            new Response(422, ['Content-Type' => 'application/json'], json_encode([
                'error' => 'Invalid email address',
            ])),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $httpClient = new GuzzleClient(['handler' => $handlerStack]);

        $client = new GSendClient(
            apiKey: 'test_token',
            httpClient: $httpClient
        );

        $this->expectException(GSendApiException::class);
        $this->expectExceptionMessage('Invalid email address');

        $client->send([
            'to' => 'invalid-email',
            'subject' => 'Test',
            'custom_content' => 'Hi',
        ]);
    }

    public function test_it_creates_attachment_from_raw_data(): void
    {
        $attachment = GSendClient::createAttachmentFromData(
            data: 'Hello World Attachment',
            filename: 'test.txt',
            contentType: 'text/plain'
        );

        $this->assertSame('test.txt', $attachment['filename']);
        $this->assertSame('text/plain', $attachment['content_type']);
        $this->assertSame(base64_encode('Hello World Attachment'), $attachment['content']);
    }

    public function test_it_creates_attachment_from_file(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'gsend_test_');
        file_put_contents($tempFile, 'Sample content in temp file');

        try {
            $attachment = GSendClient::createAttachment($tempFile, 'custom-name.txt');

            $this->assertSame('custom-name.txt', $attachment['filename']);
            $this->assertNotEmpty($attachment['content_type']);
            $this->assertSame(base64_encode('Sample content in temp file'), $attachment['content']);
        } finally {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }
}
