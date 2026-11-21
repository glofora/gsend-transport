# gSend Mail Transport for Laravel & PHP

[![Latest Version on Packagist](https://img.shields.io/packagist/v/glofora/gsend-transport.svg?style=flat-square)](https://packagist.org/packages/glofora/gsend-transport)
[![Total Downloads](https://img.shields.io/packagist/dt/glofora/gsend-transport.svg?style=flat-square)](https://packagist.org/packages/glofora/gsend-transport)
[![PHP Version](https://img.shields.io/packagist/dependency-v/glofora/gsend-transport/php?style=flat-square)](https://packagist.org/packages/glofora/gsend-transport)
[![License](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)](LICENSE.md)

A mail transport driver and standalone client for sending emails through the **gSend** REST API (`https://gsend.glofora.com/api/v1/send-mail`).

- **Official Website & Dashboard**: [https://gsend.glofora.com/](https://gsend.glofora.com/)
- **Packagist Package**: [glofora/gsend-transport](https://packagist.org/packages/glofora/gsend-transport)

---

## Getting Your API Key

To send emails using this package, you need a gSend account and API key:

1. **Sign Up / Log In**: Visit [https://gsend.glofora.com/](https://gsend.glofora.com/) to register or sign in to your dashboard.
2. **Generate API Key**: Head to your account settings or API section to generate your unique `GSEND_API_KEY`.
3. **Verify Sender Domain**: Configure and verify your sender domain/email on the dashboard to ensure optimal deliverability.
4. **Endpoint**: The standard mail API endpoint is `https://gsend.glofora.com/api/v1/send-mail`.

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

Add your gSend credentials from [https://gsend.glofora.com/](https://gsend.glofora.com/) to your `.env` file:

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

#### Using Laravel Mailables (with Attachments)

Send emails using standard Laravel mailables or notifications. Attachments added via `attach()` or `attachFromStorage()` are automatically extracted, base64-encoded, and sent via gSend:

```php
use Illuminate\Support\Facades\Mail;
use App\Mail\InvoiceMail;

Mail::to('user@example.com')->send(new InvoiceMail());
```

Or via inline `Mail::send()`:

```php
use Illuminate\Support\Facades\Mail;

Mail::send([], [], function ($message) {
    $message->to('user@example.com')
            ->subject('Monthly Statement')
            ->html('<h1>Your statement is ready</h1>')
            ->attach('/path/to/statement.pdf');
});
```

#### Using the `GSend` Facade

For fast, direct sending without mailables:

```php
use Glofora\GSend\Facades\GSend;
use Glofora\GSend\GSendClient;

$response = GSend::sendMail(
    to: 'customer@example.com',
    subject: 'Order Confirmation',
    content: '<h1>Your order is confirmed!</h1>',
    fromName: 'Glofora Store',
    attachments: [
        GSendClient::createAttachment('/path/to/invoice.pdf'),
    ]
);

// Access response details from gSend API:
$logId = $response['log_id'] ?? null;
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

## Attachments Support

**Yes! Sending email attachments is fully supported** across all transport modes.

### 1. In Laravel Mailables / Mail Notifications
When using standard Laravel mail, attach files normally. `GSendTransport` automatically converts them into the base64 format required by the gSend API:

```php
// In a Mailable's attachments() method (Laravel 9+ / 10+ / 11+ / 12+):
public function attachments(): array
{
    return [
        \Illuminate\Mail\Mailables\Attachment::fromPath('/path/to/invoice.pdf')
            ->as('invoice.pdf')
            ->withMime('application/pdf'),
    ];
}

// Or in older Mailable build() method:
public function build()
{
    return $this->view('emails.invoice')
        ->attach('/path/to/invoice.pdf');
}
```

### 2. Using `GSendClient` Helper Methods
Use built-in helper methods `createAttachment()` or `createAttachmentFromData()`:

```php
use Glofora\GSend\Facades\GSend;
use Glofora\GSend\GSendClient;

$response = GSend::sendMail(
    to: 'customer@example.com',
    subject: 'Your Documents',
    content: '<p>Attached are your documents.</p>',
    fromName: 'Glofora Support',
    attachments: [
        // From file path (MIME type detected automatically)
        GSendClient::createAttachment(storage_path('app/invoice.pdf'), 'invoice.pdf'),

        // Or directly from raw string/binary data:
        GSendClient::createAttachmentFromData(
            data: $generatedPdfBytes,
            filename: 'statement.pdf',
            contentType: 'application/pdf'
        ),
    ]
);

// Response contains the delivery status and log_id:
// ['success' => true, 'log_id' => 12345, ...]
$logId = $response['log_id'] ?? null;
```

### 3. Raw REST API Payload Format
If you are assembling raw payload arrays for `GSendClient::send()` or interacting with the API directly:

```json
{
  "to": "recipient@example.com",
  "from_name": "Yink",
  "subject": "Direct Custom Mail Notice",
  "custom_content": "<h1>Direct Notification</h1><p>This is raw HTML content.</p>",
  "attachments": [
    {
      "filename": "invoice.pdf",
      "content_type": "application/pdf",
      "content": "JVBERi0xLjQKJ..."
    }
  ]
}
```

When no attachments are needed, `attachments` can be left empty (`[]`).

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
