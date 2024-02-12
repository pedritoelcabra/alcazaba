<?php

class BoardgameRepository
{
    private const UNKNOWN_USER = 'Usuario inactivo';

    private function tableName(): string
    {
        global $wpdb;

        return $wpdb->prefix . "juegos_alcazaba";
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

    public function loanOut(int $id, int $userId): void
    {
        global $wpdb;

        $wpdb->update(
            $this->tableName(),
            [
                'loaner_id' => $userId,
                'loaned_on' => (new DateTime())->format(DateTime::ATOM),
            ],
            ['id' => $id]
        );
    }

    public function return(int $id): void
    {
        global $wpdb;

        $wpdb->update(
            $this->tableName(),
            [
                'loaner_id' => null,
                'loaned_on' => null,
            ],
            ['id' => $id]
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
            ]
        );

        if ($res === false) {
            throw new RuntimeException($wpdb->print_error());
        }

        return $wpdb->insert_id;
    }
}
