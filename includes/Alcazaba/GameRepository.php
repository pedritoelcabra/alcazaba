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
    public function getAllGames(array $users): array
    {
        global $wpdb;

        $results = $wpdb->get_results("SELECT * FROM {$this->tableName()} WHERE 1");

        $userNames = [];
        foreach ($users as $user) {
            $userNames[(int) $user->data->ID] = $user->user_nicename;
        }
        $games = [];
        foreach ($results as $result) {
            $games[] = new Game(
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

        usort($games, static function (Game $a, Game $b): int {
            if ($a->startTime === $b->startTime) {
                return 0;
            }

            return $a->startTime > $b->startTime ? 1 : -1;
        });

        return $games;
    }

    public function saveGame(Game $game): void
    {
        global $wpdb;

        $table_name = $wpdb->prefix . "partidas_alcazaba";

        $res = $wpdb->insert(
            $table_name,
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
