<?php

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

    public static function createGameForm(): string
    {
        self::redirectIfNotLoggedIn();

        return self::fetchTemplate('create', []);
    }

    public static function delete(): string
    {
        self::redirectIfNotLoggedIn();

        $repo = new GameRepository();

        $id = (int)$_REQUEST['id'];
        $game = $repo->get($id);

        if ($game === null || $game->createdBy !== wp_get_current_user()->ID) {
            return 'No eres el creador de la partida que intentas borrar.';
        }

        $repo->delete($id);

        wp_redirect('/lista-de-partidas');
        exit;
    }

    public static function save(): string
    {
        self::redirectIfNotLoggedIn();

        $data = ['error' => ''];
        $repo = new GameRepository();

        try {
            $game = Game::fromPost($_POST);

            $repo->saveGame($game);

            wp_redirect('/lista-de-partidas');
            exit;
        } catch (Throwable $e) {
            $data['sent'] = $_POST;
            $data['error'] = $e->getMessage();
        }

        return self::fetchTemplate('create', $data);
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

        return self::fetchTemplate(
            'list',
            [
                'games' => (new GameRepository())->getAllGames(),
                'users' => get_users(),
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
