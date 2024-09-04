<?php

namespace Longman\TelegramBot\Commands\UserCommands;

use Logger;
use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use TelegramBot;

class SubscribeCommand extends UserCommand
{
    protected $name = 'subscribeupdates';
    protected $usage = '/subscribeupdates';
    protected $description = 'A command to subscribe to updates';
    protected $version = '1.0.0';

    public function execute(): ServerResponse
    {
        Logger::info('Subscribe command called');

        $id = TelegramBot::getUserIdFromTelegramId($this->getMessage()->getFrom()->getId());
        if ($id === null) {
            return $this->replyToChat('No hemos podido encontrar una cuenta en la web asociada a tu cuenta de Telegram.');
        }

        if (TelegramBot::userIsSubscribed($id)) {
            return $this->replyToChat('Ya estabas suscrito. Si no te están llegando actualizaciones, contacta con el administrador del bot.');
        }

        TelegramBot::subscribeUser($id, $this->getMessage()->getChat()->getId());

        return $this->replyToChat('Te has suscrito a actualizaciones de la web. Te avisamos de cualquier cambio en tus partidas!');
    }
}
