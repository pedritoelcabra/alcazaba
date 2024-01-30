<?php

use includes\Alcazaba\GamePlayerRepository;
use Timber\Timber;

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
        $repo = new GameRepository();

        $game = $repo->get($gameId);

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

        return self::fetchTemplate('create', []);
    }

    public static function delete(): string
    {
        self::redirectIfNotLoggedIn();
        $id = (int)$_REQUEST['id'];
        self::checkOwnerPermissions($id);

        $repo = new GameRepository();
        $repo->delete($id);

        wp_redirect('/lista-de-partidas');
        exit;
    }

    public static function join(): string
    {
        self::redirectIfNotLoggedIn();
        $gameId = (int)$_REQUEST['id'];
        $playerId = wp_get_current_user()->ID;

        $repo = new GameRepository();
        $game = $repo->get($gameId);

        if ($game->playerInGame($playerId)) {
            self::unauthorized();
        }

        if (! $game->hasFreeSlots()) {
            self::unauthorized();
        }

        $playerRepo = new GamePlayerRepository();
        $playerRepo->joinGame($gameId, $playerId);

        wp_redirect('/lista-de-partidas');
        exit;
    }

    public static function leave(): string
    {
        self::redirectIfNotLoggedIn();
        $gameId = (int)$_REQUEST['id'];
        $playerId = wp_get_current_user()->ID;

        $repo = new GameRepository();
        $game = $repo->get($gameId);

        if (! $game->playerInGame($playerId)) {
            self::unauthorized();
        }

        if ($game->createdBy === $playerId) {
            self::unauthorized();
        }

        $playerRepo = new GamePlayerRepository();
        $playerRepo->leaveGame($gameId, $playerId);

        wp_redirect('/lista-de-partidas');
        exit;
    }

    public static function plus(): string
    {
        self::redirectIfNotLoggedIn();
        $gameId = (int)$_REQUEST['id'];
        $playerId = wp_get_current_user()->ID;

        $repo = new GameRepository();
        $game = $repo->get($gameId);

        if (! $game->playerInGame(wp_get_current_user()->ID)) {
            self::unauthorized();
        }

        if (! $game->hasFreeSlots()) {
            self::unauthorized();
        }

        $playerRepo = new GamePlayerRepository();
        $playerRepo->increaseAmount($gameId, $playerId);

        wp_redirect('/lista-de-partidas');
        exit;
    }

    public static function minus(): string
    {
        self::redirectIfNotLoggedIn();
        $gameId = (int)$_REQUEST['id'];
        $playerId = wp_get_current_user()->ID;

        $repo = new GameRepository();
        $game = $repo->get($gameId);

        if (! $game->playerHasOthers(wp_get_current_user()->ID)) {
            self::unauthorized();
        }

        $playerRepo = new GamePlayerRepository();
        $playerRepo->decreaseAmount($gameId, $playerId);

        wp_redirect('/lista-de-partidas');
        exit;
    }

    public static function save(): string
    {
        self::redirectIfNotLoggedIn();

        $data = ['error' => ''];
        $gameRepo = new GameRepository();
        $playerRepo = new GamePlayerRepository();

        try {
            $game = Game::fromPost($_POST);

            $gameId = $gameRepo->create($game);
            $playerRepo->joinGame($gameId, wp_get_current_user()->ID);

            wp_redirect('/lista-de-partidas');
            exit;
        } catch (Throwable $e) {
            $data['sent'] = $_POST;
            $data['error'] = $e->getMessage();
        }

        return self::fetchTemplate('create', $data);
    }

    public static function update(): string
    {
        self::redirectIfNotLoggedIn();
        $id = (int)$_REQUEST['id'];
        self::checkOwnerPermissions($id);

        $data = ['error' => ''];
        $repo = new GameRepository();

        try {
            $game = $repo->get($id);
            $updatedGame = $game->updateFromPost($_POST);
            $repo->update($updatedGame);

            wp_redirect('/lista-de-partidas');
            exit;
        } catch (Throwable $e) {
            $data['sent'] = $_POST;
            $data['error'] = $e->getMessage();
        }

        return self::fetchTemplate('create', $data);
    }

    public static function edit(): string
    {
        self::redirectIfNotLoggedIn();
        $id = (int)$_REQUEST['id'];
        self::checkOwnerPermissions($id);

        $data = ['error' => ''];
        $repo = new GameRepository();

        $game = $repo->get($id);

        $data['game-name'] = $game->name;
        $data['game-id'] = $game->bggId;
        $data['game-datetime'] = $game->startTime->format('Y-m-d H:i');
        $data['game-players'] = $game->maxPlayers;
        if ($game->joinable) {
            $data['game-open'] = 1;
        }

        return self::fetchTemplate('create', ['sent' => $data, 'id' => $game->id]);
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

    public static function listGames(): string
    {
        self::redirectIfNotLoggedIn();

        $action = $_REQUEST['action'] ?? '';

        if ($action === 'create') {
            return self::createGameForm();
        }

        if ($action === 'save') {
            return self::save();
        }

        if ($action === 'delete') {
            return self::delete();
        }

        if ($action === 'edit') {
            return self::edit();
        }

        if ($action === 'update') {
            return self::update();
        }

        if ($action === 'plus') {
            return self::plus();
        }

        if ($action === 'minus') {
            return self::minus();
        }

        if ($action === 'join') {
            return self::join();
        }

        if ($action === 'leave') {
            return self::leave();
        }

        return self::fetchTemplate(
            'list',
            [
                'games' => (new GameRepository())->getAllGames(),
                'users' => get_users(),
                'error' => ($_GET['message'] ?? '') === 'unauthorized' ? 'No permitido.' : '',
                'current_user_id' => wp_get_current_user()->ID
            ]
        );
    }

    private static function fetchTemplate(string $templateName, array $data): string
    {
        return Timber::fetch(
            sprintf(
                '%s../../public/twig/%s.twig',
                plugin_dir_path(__FILE__),
                $templateName
            ),
            $data
        );
    }
}
