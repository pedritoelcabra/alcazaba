<?php

use Timber\Timber;

class GameRepository
{
    private const SYSTEM_USER = 'Sistema';

    private function tableName(): string
    {
        global $wpdb;

        return $wpdb->prefix . "partidas_alcazaba";
    }

    /**
     * @param stdClass[] $users
     *
     * @return Game[]
     */
    public function getAllGames(): array
    {
        global $wpdb;

        $results = $wpdb->get_results("SELECT * FROM {$this->tableName()} WHERE 1");

        $games = [];
        foreach ($results as $result) {
            $games[] = new Game(
                $result->id,
                DateTime::createFromFormat('Y-m-d H:i:s', $result->created_on),
                $result->created_by,
                $this->getUserName($result->created_by),
                DateTime::createFromFormat('Y-m-d H:i:s', $result->start_time),
                $result->name,
                $result->bgg_id,
                $result->joinable,
                $result->max_players
            );
        }

        usort($games, static function (Game $a, Game $b): int {
            if ($a->startTime === $b->startTime) {
                return 0;
            }

            return $a->startTime > $b->startTime ? 1 : -1;
        });

        return $games;
    }

    private function getUserName(int $id): string
    {
        $users = get_users();

        foreach ($users as $user) {
            if ($user->data->ID === $id) {
                return $user->user_nicename;
            }
        }

        return self::SYSTEM_USER;
    }

    /**
     * @param stdClass[] $users
     */
    public function get(int $id): ?Game
    {
        global $wpdb;

        $result = $wpdb->get_row("SELECT * FROM {$this->tableName()} WHERE id = $id");

        if ($result === null) {
            return null;
        }

        return new Game(
            $result->id,
            DateTime::createFromFormat('Y-m-d H:i:s', $result->created_on),
            $result->created_by,
            $userNames[(int) $result->created_by] ?? self::SYSTEM_USER,
            DateTime::createFromFormat('Y-m-d H:i:s', $result->start_time),
            $result->name,
            $result->bgg_id,
            $result->joinable,
            $result->max_players
        );
    }

    /**
     * @param stdClass[] $users
     */
    public function delete(int $id): void
    {
        global $wpdb;

        $wpdb->query("DELETE FROM {$this->tableName()} WHERE id = $id");
    }

    public function saveGame(Game $game): void
    {
        global $wpdb;

        $res = $wpdb->insert(
            $this->tableName(),
            array(
                'created_on' => $game->createdOn->format(DateTime::ATOM),
                'created_by' => $game->createdBy,
                'bgg_id' => $game->bggId,
                'start_time' => $game->startTime->format(DateTime::ATOM),
                'name' => $game->name,
                'joinable' => $game->joinable,
                'max_players' => $game->maxPlayers,
            )
        );

        if ($res === false) {
            throw new RuntimeException($wpdb->print_error());
        }
    }
}
