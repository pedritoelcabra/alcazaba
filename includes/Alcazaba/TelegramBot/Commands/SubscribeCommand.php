<?php

namespace Longman\TelegramBot\Commands\UserCommands;

use Logger;
use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\ServerResponse;

class SubscribeCommand extends UserCommand
{
    protected $name = 'subscribeupdates';
    protected $usage = '/subscribeupdates';
    protected $description = 'A command to subscribe to updates';
    protected $version = '1.0.0';

    public function execute(): ServerResponse
    {
        Logger::info('Subscribe command called');
        return $this->replyToChat('Te has suscrito a actualizaciones de la página web. Te avisamos de cualquier cambio en tus partidas!');
    }
}
