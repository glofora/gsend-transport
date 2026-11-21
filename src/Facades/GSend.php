<?php

namespace Glofora\GSend\Facades;

use Glofora\GSend\Contracts\GSendClientInterface;
use Illuminate\Support\Facades\Facade;

/**
 * @method static array send(array $payload)
 * @method static array sendMail(string|array $to, string $subject, string $content, ?string $fromName = null, array $attachments = [])
 * @method static array createAttachment(string $filePath, ?string $filename = null, ?string $contentType = null)
 * @method static array createAttachmentFromData(string $data, string $filename, string $contentType = 'application/octet-stream')
 * @method static bool isSimulated()
 * @method static string|null getApiKey()
 * @method static string getEndpoint()
 *
 * @see \Glofora\GSend\GSendClient
 */
class GSend extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return GSendClientInterface::class;
    }
}
