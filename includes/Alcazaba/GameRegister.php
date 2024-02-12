<?php

class GameRegister
{
    private static function redirectIfNotLoggedIn(): void
    {
        if (wp_get_current_user()->ID === 0) {
            wp_redirect('/area-de-socios');
            exit;
        }
    }

    private static function redirectToLudoteca(): void
    {
        wp_redirect('/ludoteca');
        exit;
    }

    private static function checkEditPermission(): void
    {
        if (current_user_can('edit_posts')) {
            return;
        }

        self::unauthorized();
    }

    private static function unauthorized(): void
    {
        wp_redirect('/ludoteca?message=unauthorized');
        exit;
    }

    public static function save(): string
    {
        self::checkEditPermission();

        $data = ['error' => ''];

        try {
            $game = Boardgame::fromPost($_POST);

            self::gameRepo()->create($game);

            self::redirectToLudoteca();
        } catch (Throwable $e) {
            $data['sent'] = $_POST;
            $data['error'] = $e->getMessage();
        }

        return TemplateParser::fetchTemplate('register', $data);
    }

    public static function createGameForm(): string
    {
        self::checkEditPermission();

        return TemplateParser::fetchTemplate('register', []);
    }

    public static function ludoteca(): string
    {
        $method = $_REQUEST['method'] ?? '';

        if ($method === 'save') {
            return self::save();
        }

        if ($method === 'create') {
            return self::createGameForm();
        }

        return TemplateParser::fetchTemplate(
            'ludoteca',
            [
                'games' => self::gameRepo()->getAll(),
                'users' => get_users(),
                'current_user_id' => wp_get_current_user()->ID,
                'can_edit' => current_user_can('edit_posts'),
                'is_admin' => current_user_can('administrator'),
            ]
        );
    }

    private static function gameRepo(): BoardgameRepository
    {
        return new BoardgameRepository();
    }
}
