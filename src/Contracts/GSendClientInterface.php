<?php

namespace Glofora\GSend\Contracts;

interface GSendClientInterface
{
    /**
     * Send an email payload directly to the gSend REST API.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     *
     * @throws \Glofora\GSend\Exceptions\GSendApiException
     * @throws \Glofora\GSend\Exceptions\GSendException
     */
    public function send(array $payload): array;

    /**
     * Helper to send an email with individual parameters.
     *
     * @param  string|array<int, string>  $to
     * @param  string  $subject
     * @param  string  $content
     * @param  string|null  $fromName
     * @param  array<int, array<string, string>>  $attachments
     * @return array<string, mixed>
     *
     * @throws \Glofora\GSend\Exceptions\GSendApiException
     * @throws \Glofora\GSend\Exceptions\GSendException
     */
    public function sendMail(
        string|array $to,
        string $subject,
        string $content,
        ?string $fromName = null,
        array $attachments = []
    ): array;

    /**
     * Check if client is running in simulation mode (e.g. empty API key).
     */
    public function isSimulated(): bool;

    /**
     * Get the configured API key.
     */
    public function getApiKey(): ?string;

    /**
     * Get the configured API endpoint.
     */
    public function getEndpoint(): string;
}
