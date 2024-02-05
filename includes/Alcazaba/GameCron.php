<?php

class GameCron
{
    public static function cron(): void
    {
        Logger::info('Executing cron ' . time());

        self::googleSyncCron();
        self::bggSyncCron();
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

    private static function bggSyncCron(): void
    {
        $repo = new GameRepository();
        foreach ($repo->getAllGamesPendingBggSync() as $game) {
            $repo->setPendingBggSync($game->id, false);

            Logger::info('Syncing from bgg: ' . $game->id);
            $url = sprintf('https://boardgamegeek.com/xmlapi2/thing?id=%s&stats=1', $game->bggId);
            $xml = file_get_contents($url);

            if ($xml === false) {
                Logger::info('Failed getting: ' . $url);
            }

            $data = simplexml_load_string($xml);
            if ($data === false) {
                Logger::info('Failed decoding xml from: ' . $url);
            }

            $dataArray = json_decode(json_encode($data), true);

            $weight = $dataArray['item']['statistics']['ratings']['averageweight']['@attributes']['value'] ?? null;
            $thumbnail = $dataArray['item']['thumbnail'] ?? '';

            $repo->setGameWeight($game->id, floatval($weight));

            if ($thumbnail !== '' && ! $game->hasThumbnail()) {
                $content = file_get_contents($thumbnail);
                $fp = fopen($game->getThumbnailPath(), "w");
                fwrite($fp, $content);
                fclose($fp);
            }
        }
    }
}
