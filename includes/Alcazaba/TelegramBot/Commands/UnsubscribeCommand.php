<?php

namespace Longman\TelegramBot\Commands\UserCommands;

use Logger;
use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use TelegramBot;

class UnsubscribeCommand extends UserCommand
{
    protected $name = 'unsuscribeupdates';
    protected $usage = '/unsuscribeupdates';
    protected $description = 'A command to unsubscribe from updates';
    protected $version = '1.0.0';

    public function execute(): ServerResponse
    {
        Logger::info('Unsubscribe command called');

        $id = TelegramBot::getUserIdFromTelegramId($this->getMessage()->getFrom()->getId());
        if ($id === null) {
            return $this->replyToChat('No hemos podido encontrar una cuenta en la web asociada a tu cuenta de Telegram.');
        }

        if (! TelegramBot::userIsSubscribed($id)) {
            return $this->replyToChat('No estás suscrito.');
        }

        TelegramBot::unsubscribeUser($id);

        return $this->replyToChat('Te has desinscrito de actualizaciones de la web. Ya no te mandaremos mensajes :)');
    }
}
