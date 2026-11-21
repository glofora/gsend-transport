<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Glofora\GSend\GSendClient;
use Glofora\GSend\GSendTransport;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Email;

echo "=== 1. Testing Standalone GSendClient in Simulation Mode ===\n";

$client = new GSendClient(null); // No API key = simulation
$result = $client->sendMail(
    to: 'test@example.com',
    subject: 'Local Simulation Test',
    content: '<h1>Hello from Local Test!</h1>',
    fromName: 'Glofora Local'
);

echo "Simulation Response:\n";
print_r($result);

echo "\n=== 2. Testing Symfony Mailer GSendTransport in Simulation Mode ===\n";

$transport = new GSendTransport(null); // No API key = simulation
$mailer = new Mailer($transport);

$email = (new Email())
    ->from('sender@example.com')
    ->to('recipient@example.com')
    ->subject('Testing Symfony Mailer with GSend')
    ->html('<p>This is a simulated mail dispatch via GSendTransport.</p>')
    ->attach('Dummy PDF content', 'sample.pdf', 'application/pdf');

$mailer->send($email);

echo "Transport Name: " . (string) $transport . "\n";
echo "Successfully processed email dispatch (simulated)!\n";

echo "\n=== 3. How to test live sending ===\n";
echo "Set your API key in code or run:\n";
echo "  \$liveClient = new GSendClient('YOUR_GSEND_API_KEY');\n";
echo "  \$liveClient->sendMail('you@example.com', 'Subject', '<p>Live test</p>');\n";
