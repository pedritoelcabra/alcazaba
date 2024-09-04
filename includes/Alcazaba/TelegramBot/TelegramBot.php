<?php

require_once __DIR__ . '/Commands/SubscribeCommand.php';
require_once __DIR__ . '/Commands/UnsubscribeCommand.php';

use Longman\TelegramBot\Commands\UserCommands\SubscribeCommand;
use Longman\TelegramBot\Commands\UserCommands\UnsubscribeCommand;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Telegram;

class TelegramBot
{
    public const META_KEY = 'wp_bot_subscription';

    public static function execute(): void
    {
        if (($_REQUEST['activate'] ?? false) !== false) {
            self::activate();

            return;
        }

        Logger::info('Telegram hook called');

        $telegram = self::telegram();

        try {
            $telegram->handle();
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

    public static function userIsSubscribed(int $userId): bool
    {
        $meta = get_user_meta($userId, self::META_KEY, true);

        return is_string($meta) && $meta !== '';
    }

    public static function subscribeUser(int $userId, int $chatId): void
    {
        update_user_meta($userId, self::META_KEY, (string) $chatId);
    }

    public static function unsubscribeUser(int $userId): void
    {
        update_user_meta($userId, self::META_KEY, '');
    }

    public static function getUserIdFromTelegramId(string $telegramId): ?int
    {
        global $wpdb;

        $sql = <<<EOF
SELECT user_id
FROM wp_usermeta
WHERE meta_value = '$telegramId'
  AND meta_key = 'wptelegram_user_id';
EOF;

        $result = $wpdb->get_row($sql);
        if ($result === null) {
            return null;
        }

        return (int)$result->user_id;
    }

    public static function sendMessageToTelegramUser(): void
    {
        $telegram = self::telegram();
    }
}
