<?php

class GameCron
{
    public static function cron(): void
    {
        Logger::info('Executing cron ' . time());

        self::googleSyncCron();
        self::bggSyncCron();
        self::bggGameRegisterSyncCron();
        self::bggMetadataSync();
        self::telegramSync();
    }

    public static function dailyCron(): void
    {
        Logger::info('Executing daily cron ' . time());

        self::telegramDailyUpdate();
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

    private static function telegramSync(): void
    {
        $repo = new GameRepository();
        foreach ($repo->getAllGamesPendingTelegramSync() as $game) {
            $repo->setPendingTelegramSync($game->id, false);

            Logger::info('Sending to telegram: ' . $game->id);

            self::sendToTelegram(self::telegramMessage($game));
        }
    }

    private static function telegramDailyUpdate(): void
    {
        $repo = new BoardgameRepository();
        $overDueGames = $repo->getGamesOverDue();
        if ($overDueGames === []) {
            Logger::info('No overdue games!');
            return;
        }
        Logger::info('Sending overdue message to telegram.');

        self::sendToTelegram(self::overDueGamesMessage($overDueGames));
    }

    private static function sendToTelegram(string $message): void
    {
        $credentials = self::getTelegramCredentials();
        if ($credentials === []) {
            return;
        }

        $url = sprintf(
            'https://api.telegram.org/bot%s/sendMessage?parse_mode=HTML&chat_id=%s&text=%s',
            $credentials['bot'],
            $credentials['channel'],
            urlencode($message)
        );

        file_get_contents($url);
    }

    private static function getTelegramCredentials(): array
    {
        try {
            $credentials = json_decode(file_get_contents(plugin_dir_path(__FILE__) . 'telegram.json'), 1);
        } catch (Throwable $e) {
            Logger::info($e->getMessage());

            return [];
        }

        if (empty($credentials['bot']) || empty($credentials['channel'])) {
            Logger::info('Invalid telegram credentials');

            return [];
        }

        return $credentials;
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

            if ($thumbnail !== '' && !$game->hasThumbnail()) {
                $content = file_get_contents($thumbnail);
                $fp = fopen($game->getThumbnailPath(), "w");
                fwrite($fp, $content);
                fclose($fp);
            }

            // 1 item per cron run to avoid overloading BGG API
            break;
        }
    }

    private static function bggMetadataSync(): void
    {
        $repo = new BggDataRepository();
        $bggId = $repo->getFirstBggGameNeedingSync();
        if ($bggId === null) {
            echo 'no sync needed';
            return;
        }

        $dataArray = self::getBggXml($bggId);

        $repo->saveBggMetadata(
            $dataArray['item']['name'][0]['@attributes']['value'] ?? $bggId,
            $bggId,
            json_encode($dataArray)
        );
    }

    private static function bggGameRegisterSyncCron(): void
    {
        $repo = new BoardgameRepository();
        foreach ($repo->getAll('pending_bgg_sync = 1') as $game) {
            $repo->setPendingBggSync($game->id, false);

            if ($game->hasThumbnail()) {
                continue;
            }

            Logger::info('Syncing ludoteca from bgg: ' . $game->id);
            $dataArray = self::getBggXml($game->bggId);
            if ($dataArray === []) {
                continue;
            }

            $thumbnail = $dataArray['item']['thumbnail'] ?? '';

            if ($thumbnail !== '') {
                $content = file_get_contents($thumbnail);
                $fp = fopen($game->getThumbnailPath(), "w");
                fwrite($fp, $content);
                fclose($fp);
            }

            // 1 item per cron run to avoid overloading BGG API
            break;
        }
    }

    private static function getBggXml(string $bggId): array
    {
        Logger::info('Syncing ludoteca from bgg: ' . $bggId);
        $url = sprintf('https://boardgamegeek.com/xmlapi2/thing?id=%s&stats=1', $bggId);
        $xml = file_get_contents($url);

        if ($xml === false) {
            Logger::info('Failed getting: ' . $url);

            return [];
        }

        $data = simplexml_load_string($xml);
        if ($data === false) {
            Logger::info('Failed decoding xml from: ' . $url);

            return [];
        }

        return json_decode(json_encode($data), true);
    }

    private static function telegramMessage(Game $game): string
    {
        $name = $game->name;
        $formatter = new IntlDateFormatter(
            'es',
            IntlDateFormatter::LONG,
            IntlDateFormatter::SHORT,
            'Europe/Madrid',
        );
        $formatter->setPattern("eeee, dd 'de' MMMM 'a las' HH:mm");
        $day = ucfirst($formatter->format($game->startTime));
        $bggLink = ($game->bggId !== null && $game->bggId > 0) ? sprintf(
            PHP_EOL . '<a target="_blank" href="%s">Ver en BGG</a>',
            $game->bggLink()
        ) : '';
        $createdBy = $game->createdByName;
        $description = $game->telegramSafeDescription();
        if ($description !== '') {
            $description = PHP_EOL . '<strong>Descripción</strong>' . PHP_EOL . $description;
        }
        $abierta = $game->joinable ? PHP_EOL . sprintf('Partida abierta para %s jugadores', $game->maxPlayers) : '';
        $alcazabaLink = 'https://alcazabadejuegos.es/lista-de-partidas/';
        $joinLink = $game->joinable ? PHP_EOL . sprintf('<a target="_blank" href="%s">Unirse a la partida</a>', $alcazabaLink) : '';

        return <<<EOF
<i>Nueva partida publicada en la web:</i>

<strong>-- $name --</strong>
<strong>$day</strong>$bggLink
Creada por: {$createdBy}{$abierta}{$joinLink}{$description}
EOF;
    }

    /** @param $games Boardgame[] */
    private static function overDueGamesMessage(array $games): string
    {
        $list = '';
        foreach ($games as $game) {
            $list .= sprintf(' - %s, %s%s', $game->name, $game->loanedText(), PHP_EOL);
        }

        return <<<EOF
<strong>Recordatorio Ludoteca!</strong>

$list
Sabemos que son juegazos difíciles de soltar 😜
EOF;
    }
}
