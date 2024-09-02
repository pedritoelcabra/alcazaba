<?php

require_once __DIR__ . '/Commands/SubscribeCommand.php';
require_once __DIR__ . '/Commands/UnsubscribeCommand.php';

use Longman\TelegramBot\Commands\UserCommands\SubscribeCommand;
use Longman\TelegramBot\Commands\UserCommands\UnsubscribeCommand;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Telegram;

class TelegramBot
{
    public static function execute(): void
    {
        if (($_REQUEST['activate'] ?? false) !== false) {
            self::activate();

            return;
        }

        $telegram = self::telegram();

        try {
            $telegram->handle();
            Logger::info('Telegram hook called ', [$telegram->getCommandClasses()]);
        } catch (Throwable $e) {
            Logger::info('Telegram handle error');
            Logger::info($e->getMessage());
        }
    }

    private static function activate(): void
    {
        $hook_url = 'https://alcazabadejuegos.es/wp-admin/admin-ajax.php?action=telegram_bot';

        try {
            // Create Telegram API object
            $telegram = self::telegram();

            // Set webhook
            $result = $telegram->setWebhook($hook_url);
            if ($result->isOk()) {
                echo $result->getDescription();
            }
        } catch (TelegramException $e) {
            Logger::info('Telegram activation error');
            Logger::info($e->getMessage());
        }
    }

    private static function telegram(): Telegram
    {
        $credentials = GameCron::getTelegramCredentials();
        $apiKey = $credentials['bot'];
        $userName = $credentials['user_name'];

        try {
            // Create Telegram API object
            $telegram = new Telegram($apiKey, $userName);

            $telegram->addCommandClass(SubscribeCommand::class);
            $telegram->addCommandClass(UnsubscribeCommand::class);

            return $telegram;
        } catch (TelegramException $e) {
            Logger::info('Telegram init error');
            Logger::info($e->getMessage());
        }

        throw new RuntimeException('Telegram error');
    }
}
