<?php

class GameCron
{
    public static function cron(): void
    {
        Logger::info('Executing cron ' . time());

        self::googleSyncCron();
    }

    private static function googleSyncCron(): void
    {
        $repo = new GameRepository();
        foreach ($repo->getAllGamesPendingGcalSync() as $game) {
            $repo->setPendingGcalSync($game->id, false);

            Logger::info('Sending to gcal: ' . $game->id);
            if ($game->gcalId === null) {
                $gcalId = GoogleSync::createInCalendar($game);
                $repo->setGcalId($game->id, $gcalId);
            } else {
                GoogleSync::updateInCalendar($game);
            }
        }
    }
}
