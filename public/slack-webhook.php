<?php
declare(strict_types=1);

use App\Services\SlackService;
use App\Services\NotificationService;
use Longman\TelegramBot\Exception\TelegramException;

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/../vendor/autoload.php';

// Only accept POST from Slack
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

$rawInput = file_get_contents('php://input');
$payload  = json_decode($rawInput, true);

/**
 * If using Slack's Event Subscription, Slack will first send a "challenge" to verify your endpoint.
 * You must echo the challenge back:
 */
if (isset($payload['challenge'])) {
    header('Content-Type: application/json');
    echo json_encode(['challenge' => $payload['challenge']]);
    exit;
}

// Create SlackService
$slackService = new SlackService();
// Try to parse a relevant alert
$parsedMessage = $slackService->parseSlackMessage($payload);

if ($parsedMessage !== null) {
    // If parseSlackMessage returns non-null, we have a message for Telegram
    $botToken    = $_ENV['TELEGRAM_BOT_TOKEN'] ?? '';
    $botUsername = $_ENV['TELEGRAM_BOT_USERNAME'] ?? '';
    $chatId      = $_ENV['ALERTS_CHAT_TELEGRAM_ID'] ?? ''; // your Telegram target (group/user)

    try {
        $notificationService = new NotificationService($botToken, $botUsername);
        // Send the formatted message to Telegram
        $notificationService->notifyUser($chatId, $parsedMessage);
    } catch (TelegramException $e) {
        error_log('[Telegram Error] ' . $e->getMessage());
    }
}

// Always respond with 200 OK so Slack doesn't retry
http_response_code(200);
echo 'OK';
