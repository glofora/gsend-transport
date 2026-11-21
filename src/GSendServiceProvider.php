<?php

namespace Glofora\GSend;

use Glofora\GSend\Contracts\GSendClientInterface;
use Illuminate\Support\ServiceProvider;

class GSendServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/gsend.php', 'gsend');

        $this->app->singleton(GSendClientInterface::class, function ($app) {
            $config = $app['config']->get('gsend', []);

            $apiKey = $config['api_key']
                ?? $app['config']->get('services.gsend.api_key')
                ?? env('GSEND_API_KEY');

            $endpoint = $config['endpoint']
                ?? $app['config']->get('services.gsend.endpoint', GSendTransport::DEFAULT_ENDPOINT);

            $timeout = (int) ($config['timeout'] ?? 15);

            return new GSendClient(
                apiKey: $apiKey,
                endpoint: $endpoint,
                timeout: $timeout,
                logger: $app->bound('log') ? $app->make('log') : null
            );
        });

        $this->app->alias(GSendClientInterface::class, GSendClient::class);
        $this->app->alias(GSendClientInterface::class, 'gsend');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $targetPath = function_exists('config_path')
                ? config_path('gsend.php')
                : (method_exists($this->app, 'configPath') ? $this->app->configPath('gsend.php') : $this->app->basePath('config/gsend.php'));

            $this->publishes([
                __DIR__.'/../config/gsend.php' => $targetPath,
            ], 'gsend-config');
        }

        // Register custom mail transport driver with Laravel's mail manager
        if ($this->app->bound('mail.manager')) {
            $this->app->make('mail.manager')->extend('gsend', function (array $config = []) {
                $apiKey = $config['api_key']
                    ?? $this->app['config']->get('gsend.api_key')
                    ?? $this->app['config']->get('services.gsend.api_key')
                    ?? env('GSEND_API_KEY');

                $endpoint = $config['endpoint']
                    ?? $this->app['config']->get('gsend.endpoint')
                    ?? $this->app['config']->get('services.gsend.endpoint', GSendTransport::DEFAULT_ENDPOINT);

                $fromName = $config['from_name']
                    ?? $this->app['config']->get('gsend.from_name')
                    ?? $this->app['config']->get('app.name')
                    ?? env('APP_NAME');

                $timeout = (int) ($config['timeout']
                    ?? $this->app['config']->get('gsend.timeout', 15));

                $logger = $this->app->bound('log') ? $this->app->make('log') : null;

                $client = new GSendClient(
                    apiKey: $apiKey,
                    endpoint: $endpoint,
                    timeout: $timeout,
                    logger: $logger
                );

                return new GSendTransport(
                    apiKey: $apiKey,
                    endpoint: $endpoint,
                    client: $client,
                    logger: $logger,
                    defaultFromName: $fromName
                );
            });
        }
    }
}
