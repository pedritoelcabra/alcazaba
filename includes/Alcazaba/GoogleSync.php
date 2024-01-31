<?php

use Google\Client;
use Google\Service\Calendar;

class GoogleSync
{
    protected const TOKEN = 'alcazaba_google_token';

    public function sendToCalendar(Game $game): void
    {
    }

    private function getClient(): Client
    {
        $client = new Google\Client();
        $client->setAuthConfig(dirname(__FILE__) . '/google.json');

        $client->addScope(Calendar::CALENDAR);

        $client->setRedirectUri(self::url());

        return $client;
    }

    private static function url(): string
    {
        return 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . '?page=wp-alcazaba/admin.php';
    }

    public static function adminPage(): string
    {
        $client = (new self())->getClient();
        $url = (new self())::url();

        if (!isset($_GET['code']) && !isset($_GET['authorize'])) {
            $content = "<a href='{$url}&authorize=1'>Autorizar</a>";
        } elseif (!isset($_GET['code'])) {
            $auth_url = $client->createAuthUrl();
            header('Location: ' . filter_var($auth_url, FILTER_SANITIZE_URL));
        } else {
            $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);

            add_option(self::TOKEN, '');
            update_option(self::TOKEN, $token);

            $content = 'Token guardado con exito';
        }

        echo $content;

        return '';
    }
}
