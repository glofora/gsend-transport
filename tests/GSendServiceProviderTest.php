<?php

namespace Glofora\GSend\Tests;

use Glofora\GSend\Contracts\GSendClientInterface;
use Glofora\GSend\Facades\GSend;
use Glofora\GSend\GSendClient;
use Glofora\GSend\GSendServiceProvider;
use Glofora\GSend\GSendTransport;
use Illuminate\Support\Facades\Mail;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

class GSendServiceProviderTest extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            GSendServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('mail.default', 'gsend');
        $app['config']->set('mail.mailers.gsend', [
            'transport' => 'gsend',
            'api_key' => 'env_token_key',
            'endpoint' => 'https://gsend.glofora.com/api/v1/send-mail',
            'from_name' => 'Laravel App',
        ]);
        $app['config']->set('gsend.api_key', 'config_api_key');
    }

    public function test_it_registers_client_in_container(): void
    {
        $client = $this->app->make(GSendClientInterface::class);

        $this->assertInstanceOf(GSendClient::class, $client);
        $this->assertSame('config_api_key', $client->getApiKey());
    }

    public function test_it_registers_facade(): void
    {
        $this->assertSame('config_api_key', GSend::getApiKey());
    }

    public function test_it_registers_gsend_mail_transport(): void
    {
        $transport = Mail::getSymfonyTransport();

        $this->assertInstanceOf(GSendTransport::class, $transport);
        $this->assertSame('gsend', (string) $transport);
    }
}
