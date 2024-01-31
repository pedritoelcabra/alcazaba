<?php

use Google\Client;
use Google\Service\Calendar;

class GoogleSync
{
    protected const TOKEN = 'alcazaba_google_token';
    protected const CALENDAR = 'ccd706da1e5a7207ce29e667a9b3a52af29682ffeca80170e72bb64385004f9f@group.calendar.google.com';

    public static function createInCalendar(Game $game): ?string
    {
        $sync = new self();
        $client = $sync->getClient();
        $token = get_option(self::TOKEN);
        if ($token === false) {
            return null;
        }

        $client->setAccessToken($token);

        $service = new Google_Service_Calendar($client);

        $event = new Google_Service_Calendar_Event([
            'summary' => $game->name,
            'description' => $game->simpleHtmlDescription(),
            'start' => [
                'dateTime' => $game->startTime->format(DateTime::ISO8601),
                'timeZone' => "Europe/Madrid",
            ],
            'end' => [
                'dateTime' => $game->startTime->add(DateInterval::createFromDateString('3 hours'))
                    ->format(DateTime::ISO8601),
                'timeZone' => "Europe/Madrid",
            ],
        ]);

        $event = $service->events->insert(self::CALENDAR, $event);

        return $event->getId();
    }

    public static function updateInCalendar(Game $game): void
    {
        $sync = new self();
        $client = $sync->getClient();
        $token = get_option(self::TOKEN);
        if ($token === false || $game->gcalId === null) {
            return;
        }

        $client->setAccessToken($token);

        $service = new Google_Service_Calendar($client);

        $event = $service->events->get(self::CALENDAR, $game->gcalId);

        $event->setSummary($game->name);
        $event->setDescription($game->simpleHtmlDescription());
        $event->setStart(new Calendar\EventDateTime([
            'dateTime' => $game->startTime->format(DateTime::ISO8601),
        ]));
        $event->setEnd(new Calendar\EventDateTime([
            'dateTime' => $game->startTime->add(DateInterval::createFromDateString('3 hours'))
                ->format(DateTime::ISO8601),
        ]));

        $service->events->update(self::CALENDAR, $game->gcalId, $event);
    }

    public static function deleteFromCalendar(string $id): void
    {
        $sync = new self();
        $client = $sync->getClient();
        $token = get_option(self::TOKEN);
        if ($token === false) {
            return;
        }

        $client->setAccessToken($token);

        $service = new Google_Service_Calendar($client);

        $service->events->delete(self::CALENDAR, $id);
    }

    private function getClient(): Client
    {
        $client = new Google\Client();
        $client->setAuthConfig(dirname(__FILE__) . '/google.json');

        $client->addScope(Calendar::CALENDAR);

        $client->setRedirectUri(self::url());
        $client->setAccessType('offline');

        return $client;
    }

    private static function url(): string
    {
        return 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . '?page=wp-alcazaba/admin.php';
    }

    private function getCalendars(): array
    {
        $client = $this->getClient();
        $token = get_option(self::TOKEN);
        if ($token === false) {
            return [];
        }

        $client->setAccessToken($token);

        $service = new Google_Service_Calendar($client);
        $calendars = $service->calendarList->listCalendarList();

        $content = [];
        foreach ($calendars->getItems() as $calendarListEntry) {
            $content[] = $calendarListEntry->id . "({$calendarListEntry->getDescription()})";
        }

        return $content;
    }

    public static function adminPage(): string
    {
        $sync = new self();
        $client = $sync->getClient();
        $url = $sync::url();

        if (!isset($_GET['code']) && !isset($_GET['authorize'])) {
            $cal = [];
            try {
                $cal = $sync->getCalendars();
            } catch (Throwable) {}
            if ($cal !== []) {
                $content = '<br />Conectado a Google!';
            } else {
                $content = "<br /><a href='{$url}&authorize=1'>Autorizar</a>";
            }
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
