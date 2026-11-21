# gSend Mail Transport for Laravel & PHP

[![Latest Version on Packagist](https://img.shields.io/packagist/v/glofora/gsend-transport.svg?style=flat-square)](https://packagist.org/packages/glofora/gsend-transport)
[![Total Downloads](https://img.shields.io/packagist/dt/glofora/gsend-transport.svg?style=flat-square)](https://packagist.org/packages/glofora/gsend-transport)
[![PHP Version](https://img.shields.io/packagist/dependency-v/glofora/gsend-transport/php?style=flat-square)](https://packagist.org/packages/glofora/gsend-transport)
[![License](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)](LICENSE.md)

A mail transport driver and standalone client for sending emails through the **gSend** REST API (`https://gsend.glofora.com/api/v1/send-mail`).

Official Packagist Package: [packagist.org/packages/glofora/gsend-transport](https://packagist.org/packages/glofora/gsend-transport)

---

## Features

- **Seamless Laravel Integration**: Register as `gsend` mailer driver with auto-discovery.
- **Symfony Mailer Support**: Extends `Symfony\Component\Mailer\Transport\AbstractTransport`.
- **Standalone PHP Client**: Use `GSendClient` directly without framework dependencies.
- **Attachments Support**: Automatically encodes and transports file attachments.
- **Simulation Mode**: Gracefully simulates delivery in local and testing environments when `GSEND_API_KEY` is not set.
- **Custom Sender Name**: Automatically extracts sender display name from email or falls back to application config.
- **Robust Error Handling**: Clear exceptions and PSR-3 logger integration for debugging.

---

## Requirements

- PHP **8.1** or higher
- Composer

---

## Installation

Install the package via [Packagist](https://packagist.org/packages/glofora/gsend-transport) using Composer:

```bash
composer require glofora/gsend-transport
```

---

## Laravel Quick Start

### 1. Environment Variables

Add your gSend credentials to your `.env` file:

```dotenv
MAIL_MAILER=gsend

GSEND_API_KEY=your-gsend-api-key-here
GSEND_ENDPOINT=https://gsend.glofora.com/api/v1/send-mail
```

### 2. Configure Mailer (`config/mail.php`)

Add the `gsend` transport configuration to your `mailers` array:

```php
'mailers' => [
    // ...
    'gsend' => [
        'transport' => 'gsend',
        'api_key' => env('GSEND_API_KEY'),
        'endpoint' => env('GSEND_ENDPOINT', 'https://gsend.glofora.com/api/v1/send-mail'),
        'from_name' => env('GSEND_FROM_NAME', env('APP_NAME')),
    ],
],
```

### 3. Optional: Publish Configuration

If you wish to customize configuration options:

```bash
php artisan vendor:publish --tag=gsend-config
```

This creates `config/gsend.php`:

```php
return [
    'api_key' => env('GSEND_API_KEY'),
    'endpoint' => env('GSEND_ENDPOINT', 'https://gsend.glofora.com/api/v1/send-mail'),
    'timeout' => (int) env('GSEND_TIMEOUT', 15),
    'from_name' => env('GSEND_FROM_NAME', env('APP_NAME')),
];
```

### 4. Sending Mail in Laravel

Send emails using standard Laravel mailables or notifications:

```php
use Illuminate\Support\Facades\Mail;
use App\Mail\WelcomeEmail;

Mail::to('user@example.com')->send(new WelcomeEmail());
```

Or using the `GSend` Facade for direct sending:

```php
use Glofora\GSend\Facades\GSend;

$result = GSend::sendMail(
    to: 'customer@example.com',
    subject: 'Order Confirmation',
    content: '<h1>Your order is confirmed!</h1>',
    fromName: 'Glofora Store'
);
```

---

## Standalone PHP / Symfony Mailer

### Option A: Using Symfony Mailer

```php
use Glofora\GSend\GSendTransport;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Email;

$transport = new GSendTransport(
    apiKey: 'your-gsend-api-key',
    endpoint: 'https://gsend.glofora.com/api/v1/send-mail'
);

$mailer = new Mailer($transport);

$email = (new Email())
    ->from('Orders <support@mywebsite.com>')
    ->to('customer@example.com')
    ->subject('Welcome to Our Service')
    ->html('<p>Thank you for signing up.</p>')
    ->attachFromPath('/path/to/invoice.pdf', 'invoice.pdf');

$mailer->send($email);
```

### Option B: Using Direct `GSendClient`

For simple scripts or non-Symfony PHP projects:

```php
use Glofora\GSend\GSendClient;

$client = new GSendClient('your-gsend-api-key');

// Method 1: Helper method
$response = $client->sendMail(
    to: 'user@example.com',
    subject: 'System Alert',
    content: '<p>Disk usage reached 85%.</p>',
    fromName: 'Monitoring Bot'
);

// Method 2: Raw payload array
$response = $client->send([
    'to' => ['admin1@example.com', 'admin2@example.com'],
    'subject' => 'Weekly Report',
    'custom_content' => '<p>Attached is your weekly report.</p>',
    'from_name' => 'Report Engine',
    'attachments' => [
        [
            'filename' => 'report.csv',
            'content_type' => 'text/csv',
            'content' => base64_encode(file_get_contents('report.csv')),
        ],
    ],
]);
```

---

## Attachments Format

Attachments are automatically formatted and encoded when sent through Symfony Mailer or Laravel Mailables.

When building raw payloads manually for `GSendClient::send()`:

```php
[
    'filename' => 'contract.pdf',
    'content_type' => 'application/pdf',
    'content' => base64_encode($fileBytes),
]
```

---

## Local Simulation Mode

When `api_key` is null or empty, `GSendTransport` and `GSendClient` automatically switch to **simulation mode**:
- Network calls are skipped.
- A warning is logged: `[gSend] GSEND_API_KEY is not set in environment. Mail dispatch simulated.`
- No exceptions are thrown, allowing tests and local development to run smoothly without live credentials.

---

## Testing

Run unit tests with PHPUnit:

```bash
composer test
```

---

## License

This package is open-sourced software licensed under the [MIT license](LICENSE.md).
