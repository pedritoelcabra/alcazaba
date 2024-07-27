<?php

class BoardgameRepository
{
    private const UNKNOWN_USER = 'Usuario inactivo';

    private function tableName(): string
    {
        global $wpdb;

        return $wpdb->prefix . "juegos_alcazaba";
    }

    private function logTableName(): string
    {
        global $wpdb;

        return $wpdb->prefix . "juegos_log_alcazaba";
    }

    public function get(int $id): Boardgame
    {
        return $this->getAll("id = $id")[0];
    }

    /**
     * @return Boardgame[]
     */
    public function getAll(string $where = '1'): array
    {
        global $wpdb;

        $results = $wpdb->get_results("SELECT * FROM {$this->tableName()} WHERE $where");

        $games = [];
        foreach ($results as $result) {
            $games[] = new Boardgame(
                $result->id,
                $result->bgg_id,
                $result->name,
                $result->loaner_id,
                $result->loaner_id !== null ? $this->getUserName($result->loaner_id) : null,
                $result->loaner_id !== null ? DateTime::createFromFormat('Y-m-d H:i:s', $result->loaned_on, new DateTimeZone('Europe/Madrid')) : null,
                $result->loanable,
            );
        }

        usort($games, static function (Boardgame $a, Boardgame $b): int {
            if ($a->name === $b->name) {
                return 0;
            }

            return $a->name > $b->name ? 1 : -1;
        });

        return $games;
    }

    /**
     * @return Boardgame[]
     */
    public function getGamesOverDue(): array
    {
        return $this->getAll('loaned_on < DATE_SUB(CURDATE(), INTERVAL 2 WEEK)');
    }

    public function setPendingBggSync(int $id, bool $val): void
    {
        global $wpdb;

        $wpdb->update(
            $this->tableName(),
            [
                'pending_bgg_sync' => $val,
            ],
            ['id' => $id]
        );
    }

    public function loanOut(Boardgame $game, int $userId): void
    {
        global $wpdb;

        $wpdb->update(
            $this->tableName(),
            [
                'loaner_id' => $userId,
                'loaned_on' => (new DateTime())->format(DateTime::ATOM),
            ],
            ['id' => $game->id]
        );

        $wpdb->insert(
            $this->logTableName(),
            [
                'created_on' => (new DateTime())->format(DateTime::ATOM),
                'game_bgg_id' => $game->bggId,
                'game_id' => $game->id,
                'game_name' => $game->name,
                'loaner_id' => $userId,
                'loaner_name' => $this->getUserName($userId),
            ]
        );
    }

    public function return(Boardgame $game, int $userId): void
    {
        global $wpdb;

        $wpdb->update(
            $this->tableName(),
            [
                'loaner_id' => null,
                'loaned_on' => null,
            ],
            ['id' => $game->id]
        );

        $wpdb->insert(
            $this->logTableName(),
            [
                'created_on' => (new DateTime())->format(DateTime::ATOM),
                'game_bgg_id' => $game->bggId,
                'game_id' => $game->id,
                'game_name' => $game->name,
                'loaner_id' => $userId,
                'loaner_name' => $this->getUserName($userId),
            ]
        );
    }

    private function getUserName(int $id): string
    {
        $users = get_users();

        foreach ($users as $user) {
            if ((int)$user->data->ID === $id) {
                return $user->user_nicename;
            }
        }

        return self::UNKNOWN_USER;
    }

    public function create(Boardgame $game): int
    {
        global $wpdb;

        $res = $wpdb->insert(
            $this->tableName(),
            [
                'created_on' => (new DateTime())->format(DateTime::ATOM),
                'bgg_id' => $game->bggId,
                'name' => $game->name,
                'loanable' => $game->loanable,
            ]
        );

        if ($res === false) {
            throw new RuntimeException($wpdb->print_error());
        }

        return $wpdb->insert_id;
    }

    /**
     * @param stdClass[] $users
     */
    public function delete(int $id): void
    {
        global $wpdb;

        $wpdb->query("DELETE FROM {$this->tableName()} WHERE id = $id");
    }
}
