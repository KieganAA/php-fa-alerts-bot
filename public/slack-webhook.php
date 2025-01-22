<?php
declare(strict_types=1);

use App\Services\SlackService;
use App\Services\NotificationService;
use Longman\TelegramBot\Exception\TelegramException;

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/../vendor/autoload.php';

// 1) Only accept POST from Slack
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

// 2) Grab the raw POST body & decode JSON
$rawInput = file_get_contents('php://input');
$payload  = json_decode($rawInput, true);

if (!$payload) {
    http_response_code(400);
    exit('Invalid JSON or empty payload.');
}

// 3) Slack Event API "challenge" check (used for Event Subscriptions)
if (isset($payload['challenge'])) {
    header('Content-Type: application/json');
    echo json_encode(['challenge' => $payload['challenge']]);
    exit;
}

// 4) Slack Outgoing Webhook (or older slash command) token verification
//    (This prevents unauthorized/spoofed requests)
$expectedToken = $_ENV['SLACK_OUTGOING_WEBHOOK_TOKEN'] ?? '';
$receivedToken = $payload['token'] ?? '';
if ($receivedToken !== $expectedToken) {
    http_response_code(401);
    exit('Unauthorized - invalid Slack token');
}

// 5) Create SlackService & parse
$slackService  = new SlackService();
$parsedMessage = $slackService->parseSlackMessage($payload);

// 6) If parseSlackMessage returns non-null, forward message to Telegram
if ($parsedMessage !== null) {
    $botToken    = $_ENV['TELEGRAM_BOT_TOKEN']     ?? '';
    $botUsername = $_ENV['TELEGRAM_BOT_USERNAME']  ?? '';
    $chatId      = $_ENV['ALERTS_CHAT_TELEGRAM_ID'] ?? ''; // your Telegram group or user ID

    try {
        $notificationService = new NotificationService($botToken, $botUsername);
        $notificationService->notifyUser($chatId, $parsedMessage);
    } catch (TelegramException $e) {
        error_log('[Telegram Error] ' . $e->getMessage());
    }
}

// 7) Always respond with 200 OK so Slack doesn’t retry
http_response_code(200);
echo 'OK';
