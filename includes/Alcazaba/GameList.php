<?php

class GameList
{
    private static function redirectIfNotLoggedIn()
    {
        if (wp_get_current_user()->ID === 0) {
            wp_redirect('/area-de-socios');
            exit;
        }
    }

    private static function checkOwnerPermissions(int $gameId)
    {
        if (current_user_can('administrator')) {
            return;
        }

        $game = self::gameRepo()->get($gameId);

        if ($game === null || $game->createdBy !== wp_get_current_user()->ID) {
            self::unauthorized();
        }
    }

    private static function unauthorized(): void
    {
        wp_redirect('/lista-de-partidas?message=unauthorized');
        exit;
    }

    public static function createGameForm(): string
    {
        self::redirectIfNotLoggedIn();

        return TemplateParser::fetchTemplate('create', []);
    }

    public static function delete(): string
    {
        self::redirectIfNotLoggedIn();
        $id = (int)$_REQUEST['id'];
        self::checkOwnerPermissions($id);

        $game = self::gameRepo()->get($id);
        if ($game !== null && $game->gcalId !== null) {
            GoogleSync::deleteFromCalendar($game->gcalId);
        }
        self::gameRepo()->delete($id);

        wp_redirect('/lista-de-partidas');
        exit;
    }

    public static function join(): string
    {
        self::redirectIfNotLoggedIn();
        $gameId = (int)$_REQUEST['id'];
        $playerId = wp_get_current_user()->ID;

        $game = self::gameRepo()->get($gameId);

        if ($game->playerInGame($playerId)) {
            self::unauthorized();
        }

        if (!$game->hasFreeSlots()) {
            self::unauthorized();
        }

        self::playerRepo()->joinGame($gameId, $playerId);
        self::queueGameForSync($game);

        wp_redirect('/lista-de-partidas');
        exit;
    }

    public static function leave(): string
    {
        self::redirectIfNotLoggedIn();
        $gameId = (int)$_REQUEST['id'];
        $playerId = wp_get_current_user()->ID;

        $game = self::gameRepo()->get($gameId);

        if (!$game->playerInGame($playerId)) {
            self::unauthorized();
        }

        self::playerRepo()->leaveGame($gameId, $playerId);
        self::queueGameForSync($game);

        wp_redirect('/lista-de-partidas');
        exit;
    }

    public static function plus(): string
    {
        self::redirectIfNotLoggedIn();
        $gameId = (int)$_REQUEST['id'];
        $playerId = wp_get_current_user()->ID;

        $game = self::gameRepo()->get($gameId);

        if (!$game->playerInGame(wp_get_current_user()->ID)) {
            self::unauthorized();
        }

        if (!$game->hasFreeSlots()) {
            self::unauthorized();
        }

        self::playerRepo()->increaseAmount($gameId, $playerId);
        self::queueGameForSync($game);

        wp_redirect('/lista-de-partidas');
        exit;
    }

    public static function minus(): string
    {
        self::redirectIfNotLoggedIn();
        $gameId = (int)$_REQUEST['id'];
        $playerId = wp_get_current_user()->ID;

        $game = self::gameRepo()->get($gameId);

        if (!$game->playerHasOthers(wp_get_current_user()->ID)) {
            self::unauthorized();
        }

        self::playerRepo()->decreaseAmount($gameId, $playerId);
        self::queueGameForSync($game);

        wp_redirect('/lista-de-partidas');
        exit;
    }

    public static function save(): string
    {
        self::redirectIfNotLoggedIn();

        $data = ['error' => ''];
        try {
            $game = Game::fromPost($_POST);

            $gameId = self::gameRepo()->create($game);
            self::playerRepo()->joinGame($gameId, wp_get_current_user()->ID);

            $game = self::gameRepo()->get($gameId);
            if ($game !== null) {
                self::queueGameForSync($game);

                if (($_REQUEST['game-publish'] ?? false) !== false) {
                    self::gameRepo()->setPendingTelegramSync($game->id, true);
                }
            }

            wp_redirect('/lista-de-partidas');
            exit;
        } catch (Throwable $e) {
            $data['sent'] = $_POST;
            $data['error'] = $e->getMessage();
        }

        return TemplateParser::fetchTemplate('create', $data);
    }

    public static function update(): string
    {
        self::redirectIfNotLoggedIn();
        $gameId = (int)$_REQUEST['id'];
        self::checkOwnerPermissions($gameId);

        $data = ['error' => ''];
        try {
            $game = self::gameRepo()->get($gameId);
            $updatedGame = $game->updateFromPost($_POST);
            self::gameRepo()->update($updatedGame);

            self::queueGameForSync($game);

            wp_redirect('/lista-de-partidas');
            exit;
        } catch (Throwable $e) {
            $data['sent'] = $_POST;
            $data['id'] = $gameId;
            $data['error'] = $e->getMessage();
        }

        return TemplateParser::fetchTemplate('create', $data);
    }

    public static function edit(): string
    {
        self::redirectIfNotLoggedIn();
        $id = (int)$_REQUEST['id'];
        self::checkOwnerPermissions($id);

        $data = ['error' => ''];

        $game = self::gameRepo()->get($id);

        $description = str_replace('<br/>', PHP_EOL, $game->description ?? '');

        $data['game-name'] = $game->name;
        $data['game-description'] = $description;
        $data['game-id'] = $game->bggId;
        $data['game-datetime'] = $game->startTime->format('Y-m-d H:i');
        $data['game-endtime'] = $game->endTime ? $game->endTime->format('Y-m-d H:i') : '';
        $data['game-players'] = $game->maxPlayers;
        if ($game->joinable) {
            $data['game-open'] = 1;
        }

        return TemplateParser::fetchTemplate('create', ['sent' => $data, 'id' => $game->id]);
    }

    public static function ajaxListGames(): void
    {
        $query = $_POST['query'];

        $ch = curl_init();

        curl_setopt(
            $ch,
            CURLOPT_URL,
            "https://boardgamegeek.com/search/boardgame?nosession=1&showcount=20&q=" . urlencode($query)
        );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
        ]);

        $data = curl_exec($ch);
        curl_close($ch);

        $decoded = json_decode($data, true);

        $cleanGameNames = [];
        foreach ($decoded['items'] as $item) {
            $cleanGameNames[] = [
                'id' => $item['id'],
                'label' => sprintf('%s (%s)', $item['name'], $item['yearpublished']),
                'value' => sprintf('%s (%s)', $item['name'], $item['yearpublished']),
                'url' => sprintf('https://boardgamegeek.com%s', $item['href']),
            ];
        }

        wp_send_json($cleanGameNames);
        exit;
    }

    private static function queueGameForSync(Game $game): void
    {
        self::gameRepo()->setPendingGcalSync($game->id, true);
        self::gameRepo()->setPendingBggSync($game->id, true);
    }

    private static function gameRepo(): GameRepository
    {
        return new GameRepository();
    }

    private static function playerRepo(): GamePlayerRepository
    {
        return new GamePlayerRepository();
    }

    public static function scheduleCron(): void
    {
        if (!wp_next_scheduled('al_cron_hook')) {
            wp_schedule_event(time(), 'minutely', 'al_cron_hook');
        }
        if (!wp_next_scheduled('al_cron_hook_daily')) {
            wp_schedule_event(time(), 'daily', 'al_cron_hook');
        }
    }

    public static function listGames(): string
    {
        self::redirectIfNotLoggedIn();
        self::scheduleCron();

        $method = $_REQUEST['method'] ?? '';

        if ($method === 'create') {
            return self::createGameForm();
        }

        if ($method === 'save') {
            return self::save();
        }

        if ($method === 'delete') {
            return self::delete();
        }

        if ($method === 'edit') {
            return self::edit();
        }

        if ($method === 'update') {
            return self::update();
        }

        if ($method === 'plus') {
            return self::plus();
        }

        if ($method === 'minus') {
            return self::minus();
        }

        if ($method === 'join') {
            return self::join();
        }

        if ($method === 'leave') {
            return self::leave();
        }

        return TemplateParser::fetchTemplate(
            'list',
            [
                'games' => self::gameRepo()->getAllFutureGames(),
                'users' => get_users(),
                'error' => ($_GET['message'] ?? '') === 'unauthorized' ? 'No permitido.' : '',
                'current_user_id' => wp_get_current_user()->ID,
                'is_admin' => current_user_can('administrator'),
            ]
        );
    }

    public static function topGames(): string
    {
        return TemplateParser::fetchTemplate(
            'top',
            [
                'topGames' => self::gameRepo()->getTopGames(),
            ]
        );
    }
}
