<?php

namespace Glofora\GSend;

use Exception;
use Glofora\GSend\Contracts\GSendClientInterface;
use Glofora\GSend\Exceptions\GSendApiException;
use Glofora\GSend\Exceptions\GSendException;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\MessageConverter;

class GSendTransport extends AbstractTransport
{
    public const DEFAULT_ENDPOINT = 'https://gsend.glofora.com/api/v1/send-mail';

    protected GSendClientInterface $client;
    protected LoggerInterface $logger;

    /**
     * Create a new gSend transport instance.
     *
     * @param  string|null  $apiKey
     * @param  string  $endpoint
     * @param  GSendClientInterface|null  $client
     * @param  LoggerInterface|null  $logger
     * @param  string|null  $defaultFromName
     * @param  EventDispatcherInterface|null  $dispatcher
     */
    public function __construct(
        protected ?string $apiKey = null,
        protected string $endpoint = self::DEFAULT_ENDPOINT,
        ?GSendClientInterface $client = null,
        ?LoggerInterface $logger = null,
        protected ?string $defaultFromName = null,
        ?EventDispatcherInterface $dispatcher = null
    ) {
        parent::__construct($dispatcher, $logger);

        $this->logger = $logger ?? new NullLogger();
        $this->client = $client ?? new GSendClient(
            apiKey: $this->apiKey,
            endpoint: $this->endpoint,
            logger: $this->logger
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function doSend(SentMessage $message): void
    {
        $email = MessageConverter::toEmail($message->getOriginalMessage());

        if (empty($this->apiKey)) {
            $this->logger->warning('[gSend] GSEND_API_KEY is not set in environment. Mail dispatch simulated.', [
                'to' => array_map(fn ($addr) => $addr->getAddress(), $email->getTo()),
                'subject' => $email->getSubject(),
            ]);

            return;
        }

        $recipients = array_map(fn ($addr) => $addr->getAddress(), $email->getTo());
        $to = count($recipients) === 1 ? $recipients[0] : $recipients;

        $fromAddress = $email->getFrom()[0] ?? null;
        $fromName = ($fromAddress && ! empty($fromAddress->getName()))
            ? $fromAddress->getName()
            : $this->resolveFallbackFromName();

        $subject = $email->getSubject() ?: 'Notification';
        $html = $email->getHtmlBody();
        $text = $email->getTextBody();
        $bodyContent = $html ?: $text ?: '';

        // Parse attachments
        $attachments = [];
        foreach ($email->getAttachments() as $attachment) {
            $attachments[] = [
                'filename' => $attachment->getFilename(),
                'content_type' => $attachment->getContentType(),
                'content' => base64_encode($attachment->getBody()),
            ];
        }

        $payload = [
            'to' => $to,
            'subject' => $subject,
            'custom_content' => $bodyContent,
            'attachments' => $attachments,
        ];

        if (! empty($fromName)) {
            $payload['from_name'] = $fromName;
        }

        try {
            $this->client->send($payload);
        } catch (GSendApiException $e) {
            throw new TransportException($e->getMessage(), $e->getStatusCode(), $e);
        } catch (GSendException $e) {
            throw new TransportException($e->getMessage(), $e->getCode(), $e);
        } catch (Exception $e) {
            throw new TransportException('[gSend] Could not connect to mail gateway: '.$e->getMessage(), 0, $e);
        }
    }

    /**
     * Resolve default from name across Laravel / Environment / options.
     */
    protected function resolveFallbackFromName(): ?string
    {
        if (! empty($this->defaultFromName)) {
            return $this->defaultFromName;
        }

        if (class_exists(\Illuminate\Container\Container::class)) {
            $container = \Illuminate\Container\Container::getInstance();
            if ($container && $container->bound('config')) {
                try {
                    $name = $container->make('config')->get('app.name');
                    if (! empty($name)) {
                        return $name;
                    }
                } catch (\Throwable) {
                    // Fall through safely if container resolution fails
                }
            }
        }

        if (function_exists('env') && ($name = env('APP_NAME'))) {
            return $name;
        }

        return $_ENV['APP_NAME'] ?? getenv('APP_NAME') ?: null;
    }

    /**
     * Get underlying client instance.
     */
    public function getClient(): GSendClientInterface
    {
        return $this->client;
    }

    /**
     * Get string representation of transport.
     */
    public function __toString(): string
    {
        return 'gsend';
    }
}
