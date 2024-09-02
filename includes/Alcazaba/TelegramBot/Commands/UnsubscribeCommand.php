<?php

namespace Longman\TelegramBot\Commands\UserCommands;

use Logger;
use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\ServerResponse;

class UnsubscribeCommand extends UserCommand
{
    protected $name = 'unsuscribeupdates';
    protected $usage = '/unsuscribeupdates';
    protected $description = 'A command to unsubscribe from updates';
    protected $version = '1.0.0';

    public function execute(): ServerResponse
    {
        Logger::info('Unsubscribe command called');
        return $this->replyToChat('Te has desinscrito de actualizaciones de la página web. Ya no te mandaremos mensajes :)');
    }
}
