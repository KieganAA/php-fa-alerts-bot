<?php

namespace App\Bot;

use Longman\TelegramBot\Telegram;
use Longman\TelegramBot\Exception\TelegramException;
use RuntimeException;

/**
 * Class BotHandler
 *
 * Central class that initializes and handles incoming updates from Telegram.
 */
class BotHandler
{
    /**
     * @var Telegram
     */
    protected Telegram $telegram;

    /**
     * BotHandler constructor.
     *
     * @param string $botToken The Telegram bot token.
     */
    public function __construct(string $botToken)
    {
        $botUsername = $_ENV['TELEGRAM_BOT_USERNAME'] ?? '';

        try {
            $this->telegram = new Telegram($botToken, $botUsername);
            $this->telegram->addCommandsPath(__DIR__ . '/Commands');
        } catch (TelegramException $e) {
            error_log("TelegramException in BotHandler::__construct: " . $e->getMessage());
            throw new RuntimeException($e->getMessage());
        }
    }

    /**
     * Handle incoming update (webhook).
     */
    public function handle(): void
    {
        try {
            $this->telegram->handle();
        } catch (TelegramException $e) {
            error_log("TelegramException in BotHandler::handle: " . $e->getMessage());
        }
    }
}
