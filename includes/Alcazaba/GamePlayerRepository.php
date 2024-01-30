<?php

namespace includes\Alcazaba;

use DateTime;
use Game;
use GamePlayer;
use Timber\Timber;

class GamePlayerRepository
{
    private const SYSTEM_USER = 'Sistema';

    private function tableName(): string
    {
        global $wpdb;

        return $wpdb->prefix . "jugadores_alcazaba";
    }

    public function joinGame(int $gameId, int $playerId): void
    {
        global $wpdb;

        $wpdb->insert(
            $this->tableName(),
            array(
                'created_on' => (new DateTime())->format(DateTime::ATOM),
                'player_id' => $playerId,
                'game_id' => $gameId,
                'amount' => 1,
            )
        );
    }

    public function leaveGame(int $gameId, int $playerId): void
    {
        global $wpdb;

        $wpdb->delete(
            $this->tableName(),
            array(
                'player_id' => $playerId,
                'game_id' => $gameId,
            )
        );
    }

    public function increaseAmount(int $gameId, int $playerId): void
    {

        global $wpdb;

        $wpdb->query(
            "UPDATE {$this->tableName()} SET amount = amount + 1 WHERE player_id = $playerId AND game_id = $gameId"
        );
    }

    public function decreaseAmount(int $gameId, int $playerId): void
    {

        global $wpdb;

        $wpdb->query(
            "UPDATE {$this->tableName()} SET amount = amount - 1 WHERE player_id = $playerId AND game_id = $gameId"
        );
    }

    /**
     * @return GamePlayer[]
     */
    public function forGame(int $id): array
    {
        global $wpdb;

        $results = $wpdb->get_results("SELECT * FROM {$this->tableName()} WHERE game_id = $id");

        $players = [];
        foreach ($results as $result) {
            $players[] = new GamePlayer(
                $result->player_id,
                $result->game_id,
                $result->amount,
                $this->getUserName($result->player_id)
            );
        }

        return $players;
    }

    private function getUserName(int $id): string
    {
        $users = get_users();

        foreach ($users as $user) {
            if ((int)$user->data->ID === $id) {
                return $user->user_nicename;
            }
        }

        return self::SYSTEM_USER;
    }
}
