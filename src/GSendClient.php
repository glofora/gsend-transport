<?php

namespace Glofora\GSend;

use Exception;
use Glofora\GSend\Contracts\GSendClientInterface;
use Glofora\GSend\Exceptions\GSendApiException;
use Glofora\GSend\Exceptions\GSendException;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class GSendClient implements GSendClientInterface
{
    public const DEFAULT_ENDPOINT = 'https://gsend.glofora.com/api/v1/send-mail';
    public const DEFAULT_TIMEOUT = 15;

    protected ?ClientInterface $httpClient;
    protected LoggerInterface $logger;

    /**
     * @param  string|null  $apiKey
     * @param  string  $endpoint
     * @param  int  $timeout
     * @param  ClientInterface|null  $httpClient
     * @param  LoggerInterface|null  $logger
     */
    public function __construct(
        protected ?string $apiKey = null,
        protected string $endpoint = self::DEFAULT_ENDPOINT,
        protected int $timeout = self::DEFAULT_TIMEOUT,
        ?ClientInterface $httpClient = null,
        ?LoggerInterface $logger = null
    ) {
        $this->httpClient = $httpClient;
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * {@inheritdoc}
     */
    public function send(array $payload): array
    {
        if ($this->isSimulated()) {
            $this->logger->warning('[gSend] GSEND_API_KEY is not set in environment. Mail dispatch simulated.', [
                'to' => $payload['to'] ?? null,
                'subject' => $payload['subject'] ?? null,
            ]);

            return [
                'status' => 'simulated',
                'simulated' => true,
                'message' => 'Mail dispatch simulated because API key is empty.',
                'payload' => $payload,
            ];
        }

        if ($this->httpClient !== null || class_exists(GuzzleClient::class)) {
            return $this->sendViaGuzzle($payload);
        }

        return $this->sendViaCurl($payload);
    }

    /**
     * {@inheritdoc}
     */
    public function sendMail(
        string|array $to,
        string $subject,
        string $content,
        ?string $fromName = null,
        array $attachments = []
    ): array {
        $payload = [
            'to' => $to,
            'subject' => $subject,
            'custom_content' => $content,
            'attachments' => $attachments,
        ];

        if (! empty($fromName)) {
            $payload['from_name'] = $fromName;
        }

        return $this->send($payload);
    }

    /**
     * {@inheritdoc}
     */
    public function isSimulated(): bool
    {
        return empty($this->apiKey);
    }

    /**
     * {@inheritdoc}
     */
    public function getApiKey(): ?string
    {
        return $this->apiKey;
    }

    /**
     * {@inheritdoc}
     */
    public function getEndpoint(): string
    {
        return $this->endpoint;
    }

    /**
     * Send payload via Guzzle HTTP client.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     *
     * @throws GSendApiException
     * @throws GSendException
     */
    protected function sendViaGuzzle(array $payload): array
    {
        $client = $this->httpClient ?? new GuzzleClient([
            RequestOptions::TIMEOUT => $this->timeout,
        ]);

        try {
            $response = $client->request('POST', $this->endpoint, [
                RequestOptions::HEADERS => [
                    'Authorization' => 'Bearer '.$this->apiKey,
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ],
                RequestOptions::JSON => $payload,
                RequestOptions::HTTP_ERRORS => false,
            ]);

            $statusCode = $response->getStatusCode();
            $body = (string) $response->getBody();
            $decoded = json_decode($body, true);

            if ($statusCode >= 400) {
                $errorMsg = is_array($decoded)
                    ? ($decoded['error'] ?? $decoded['message'] ?? $body)
                    : $body;

                $this->logger->error('[gSend] Dispatch failed: '.$errorMsg, [
                    'status' => $statusCode,
                    'endpoint' => $this->endpoint,
                ]);

                throw new GSendApiException(
                    '[gSend] Mail gateway returned error: '.$errorMsg,
                    $statusCode,
                    $decoded ?? $body
                );
            }

            $this->logger->info('[gSend] Mail delivered successfully via gSend API.', [
                'to' => $payload['to'] ?? null,
                'subject' => $payload['subject'] ?? null,
                'response' => $decoded ?? $body,
            ]);

            return is_array($decoded) ? $decoded : ['status' => 'success', 'raw' => $body];
        } catch (GSendApiException $e) {
            throw $e;
        } catch (GuzzleException|Exception $e) {
            $this->logger->error('[gSend] Network exception: '.$e->getMessage(), [
                'exception' => $e,
            ]);

            throw new GSendException(
                '[gSend] Could not connect to mail gateway: '.$e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Fallback HTTP sender using native PHP cURL when Guzzle is not present.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     *
     * @throws GSendApiException
     * @throws GSendException
     */
    protected function sendViaCurl(array $payload): array
    {
        if (! extension_loaded('curl')) {
            throw new GSendException('[gSend] Neither GuzzleHttp nor the PHP cURL extension is available.');
        }

        $ch = curl_init($this->endpoint);
        if ($ch === false) {
            throw new GSendException('[gSend] Failed to initialize cURL.');
        }

        $jsonPayload = json_encode($payload);

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $jsonPayload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer '.$this->apiKey,
                'Accept: application/json',
                'Content-Type: application/json',
            ],
        ]);

        $body = curl_exec($ch);
        $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($body === false) {
            $this->logger->error('[gSend] Network exception: '.$curlError);
            throw new GSendException('[gSend] Could not connect to mail gateway: '.$curlError);
        }

        $decoded = json_decode((string) $body, true);

        if ($statusCode >= 400) {
            $errorMsg = is_array($decoded)
                ? ($decoded['error'] ?? $decoded['message'] ?? $body)
                : $body;

            $this->logger->error('[gSend] Dispatch failed: '.$errorMsg, [
                'status' => $statusCode,
                'endpoint' => $this->endpoint,
            ]);

            throw new GSendApiException(
                '[gSend] Mail gateway returned error: '.$errorMsg,
                $statusCode,
                $decoded ?? $body
            );
        }

        $this->logger->info('[gSend] Mail delivered successfully via gSend API.', [
            'to' => $payload['to'] ?? null,
            'subject' => $payload['subject'] ?? null,
            'response' => $decoded ?? $body,
        ]);

        return is_array($decoded) ? $decoded : ['status' => 'success', 'raw' => $body];
    }
}
