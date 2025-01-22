<?php

use App\Services\NotificationService;
use Dotenv\Dotenv;
use Longman\TelegramBot\Exception\TelegramException;

/**
 * bootstrap.php
 *
 * Loads environment, sets up DB connection, error handlers, etc.
 */

// 1. Load Composer autoload (if not already done in index.php).
require __DIR__ . '/vendor/autoload.php';

// 2. Load environment variables (.env)
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

// 4. Initialize a NotificationService for error alerts (optional)
$botToken    = $_ENV['TELEGRAM_BOT_TOKEN']            ?? '';
$botUsername = $_ENV['TELEGRAM_BOT_USERNAME']         ?? '';
$errorChatId = $_ENV['ERROR_NOTIFICATION_TELEGRAM_ID'] ?? '';

try {
    $notificationService = new NotificationService($botToken, $botUsername);
} catch (TelegramException $e) {
    error_log('NotificationServiceException: ' . $e->getMessage());
}

/**
 * 5. Exception Handler
 */
set_exception_handler(/**
 * @throws TelegramException
 */ function (\Throwable $throwable) use ($notificationService, $errorChatId) {
    // Build a message
    $message = "[EXCEPTION] " . $throwable->getMessage() . "\n"
        . "File: " . $throwable->getFile() . "\n"
        . "Line: " . $throwable->getLine();

    // Send to Telegram (if you want immediate error notifications)
    $notificationService->notifyUser($errorChatId, $message);

    // Also log to PHP error log
    error_log($message);

    // Optionally re-throw or exit
    // throw $throwable;
    // exit;
});

/**
 * 6. Error Handler (convert PHP warnings/notices to the same flow)
 */
set_error_handler(/**
 * @throws TelegramException
 */ function ($severity, $message, $file, $line) use ($notificationService, $errorChatId) {
    // Classify the error type
    $errorType = match ($severity) {
        E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR   => 'Fatal Error',
        E_WARNING, E_USER_WARNING                             => 'Warning',
        E_NOTICE, E_USER_NOTICE                               => 'Notice',
        E_DEPRECATED, E_USER_DEPRECATED                       => 'Deprecated',
        E_STRICT                                              => 'Strict',
        default                                               => 'Unknown Error',
    };

    $errorMessage = "[PHP $errorType] $message\nFile: $file\nLine: $line\n";

    // Send to Telegram
    $notificationService->notifyUser($errorChatId, $errorMessage);

    // Log to PHP error log
    error_log($errorMessage);

    // Return false to let normal PHP error handling proceed if needed
    return false;
});

/**
 * 7. Shutdown Function for fatal errors (e.g., E_ERROR)
 */
register_shutdown_function(/**
 * @throws TelegramException
 */ function () use ($notificationService, $errorChatId) {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
        $message = "[FATAL ERROR] {$error['message']}\n"
            . "File: {$error['file']}\n"
            . "Line: {$error['line']}\n";
        $notificationService->notifyUser($errorChatId, $message);
        error_log($message);
    }
});
