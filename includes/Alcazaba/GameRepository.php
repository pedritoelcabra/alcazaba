<?php

class GameRepository
{
    private const SYSTEM_USER = 'Sistema';

    private function tableName(): string
    {
        global $wpdb;

        return $wpdb->prefix . "partidas_alcazaba";
    }

    /**
     * @return Game[]
     */
    public function getAllFutureGames(): array
    {
        return $this->getGamesWhere('1 AND start_time >= CURDATE()');
    }

    /**
     * @return Game[]
     */
    public function getAllGameStartTimes(): array
    {
        global $wpdb;

        $sql = <<<EOF
SELECT
    p.start_time,
    p.bgg_weight,
    bgg.name,
    IF(bgg.has_parent, bgg_p.name, bgg.name) base_name,
    IF(bgg.has_parent, bgg_p.bgg_id, bgg.bgg_id) base_bgg_id,
    IF(bgg.has_parent, bgg_p.content, bgg.content) base_content
FROM
    wp_partidas_alcazaba p
LEFT JOIN wp_juegos_bgg bgg ON
    p.bgg_id = bgg.bgg_id
LEFT JOIN wp_juegos_bgg bgg_p ON
    bgg.parent = bgg_p.bgg_id
WHERE
    1
EOF;

        return $wpdb->get_results($sql);
    }

    /**
     * @return Game[]
     */
    public function getTopGames(): array
    {
        return $this->getGamesWhere('start_time > DATE_SUB(curdate(), INTERVAL 2 MONTH) ' .
            'AND start_time < DATE_ADD(curdate(), INTERVAL 1 WEEK) ' .
            'AND bgg_id IS NOT NULL ' .
            'AND bgg_id > 0 ' .
            'GROUP BY bgg_id ' .
            'ORDER BY COUNT(bgg_id) DESC 
            LIMIT 6',
            false
        );
    }

    /**
     * @return Game[]
     */
    public function getAllGamesPendingGcalSync(): array
    {
        return $this->getGamesWhere('1 AND start_time >= NOW() AND pending_gcal_sync = 1');
    }

    /**
     * @return Game[]
     */
    public function getAllGamesPendingTelegramSync(): array
    {
        return $this->getGamesWhere('1 AND start_time >= NOW() AND pending_telegram_sync = 1');
    }

    /**
     * @return Game[]
     */
    public function getAllGamesPendingBggSync(): array
    {
        return $this->getGamesWhere('1 AND start_time >= NOW() AND pending_bgg_sync = 1 AND bgg_id IS NOT NULL');
    }

    /**
     * @return Game[]
     */
    private function getGamesWhere(string $where, bool $sortByStartTime = true): array
    {
        global $wpdb;

        $results = $wpdb->get_results("SELECT * FROM {$this->tableName()} WHERE $where");

        $games = [];
        $playerRepo = new GamePlayerRepository();
        foreach ($results as $result) {
            $end = null;
            $endDt = DateTime::createFromFormat('Y-m-d H:i:s', $result->end_time ?? '', new DateTimeZone('Europe/Madrid'));
            if ($endDt !== false) {
                $end = $endDt;
            }
            $games[] = new Game(
                $result->id,
                DateTime::createFromFormat('Y-m-d H:i:s', $result->created_on, new DateTimeZone('Europe/Madrid')),
                $result->created_by,
                $this->getUserName($result->created_by),
                DateTime::createFromFormat('Y-m-d H:i:s', $result->start_time, new DateTimeZone('Europe/Madrid')),
                $result->name,
                $result->bgg_id,
                $result->gcal_id,
                $result->joinable,
                $result->max_players,
                $playerRepo->forGame($result->id),
                $result->description,
                $result->bgg_weight,
                $end,
            );
        }

        if ($sortByStartTime) {
            usort($games, static function (Game $a, Game $b): int {
                if ($a->startTime === $b->startTime) {
                    return 0;
                }

                return $a->startTime > $b->startTime ? 1 : -1;
            });
        }

        return $games;
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

    public function setGcalId(int $id, ?string $gcalId): void
    {
        if ($gcalId === null) {
            return;
        }

        global $wpdb;

        $wpdb->update(
            $this->tableName(),
            [
                'gcal_id' => $gcalId,
            ],
            ['id' => $id]
        );
    }

    public function setPendingGcalSync(int $id, bool $val): void
    {
        global $wpdb;

        $wpdb->update(
            $this->tableName(),
            [
                'pending_gcal_sync' => $val,
            ],
            ['id' => $id]
        );
    }

    public function setPendingTelegramSync(int $id, bool $val): void
    {
        global $wpdb;

        $wpdb->update(
            $this->tableName(),
            [
                'pending_telegram_sync' => $val,
            ],
            ['id' => $id]
        );
    }

    public function setGameWeight(int $id, float $weight): void
    {
        global $wpdb;

        $wpdb->update(
            $this->tableName(),
            [
                'bgg_weight' => round($weight, 2),
            ],
            ['id' => $id]
        );
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

        $playerRepo = new GamePlayerRepository();
        $end = null;
        $endDt = DateTime::createFromFormat('Y-m-d H:i:s', $result->end_time ?? '', new DateTimeZone('Europe/Madrid'));
        if ($endDt !== false) {
            $end = $endDt;
        }
        return new Game(
            $result->id,
            DateTime::createFromFormat('Y-m-d H:i:s', $result->created_on, new DateTimeZone('Europe/Madrid')),
            $result->created_by,
            $userNames[(int)$result->created_by] ?? self::SYSTEM_USER,
            DateTime::createFromFormat('Y-m-d H:i:s', $result->start_time, new DateTimeZone('Europe/Madrid')),
            $result->name,
            $result->bgg_id,
            $result->gcal_id,
            $result->joinable,
            $result->max_players,
            $playerRepo->forGame($result->id),
            $result->description,
            $result->bgg_weight,
            $end
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

    public function create(Game $game): int
    {
        global $wpdb;

        $endTime = null;
        if ($game->endTime !== null) {
            $endTime = $game->endTime->format(DateTime::ATOM);
        }
        $res = $wpdb->insert(
            $this->tableName(),
            [
                'created_on' => $game->createdOn->format(DateTime::ATOM),
                'created_by' => $game->createdBy,
                'bgg_id' => $game->bggId,
                'start_time' => $game->startTime->format(DateTime::ATOM),
                'end_time' => $endTime,
                'name' => $game->name,
                'joinable' => $game->joinable,
                'max_players' => $game->maxPlayers,
                'description' => $game->description,
            ]
        );

        if ($res === false) {
            throw new RuntimeException($wpdb->print_error());
        }

        return $wpdb->insert_id;
    }

    public function update(Game $game): void
    {
        global $wpdb;

        $endTime = null;
        if ($game->endTime !== null) {
            $endTime = $game->endTime->format(DateTime::ATOM);
        }
        $res = $wpdb->update(
            $this->tableName(),
            [
                'bgg_id' => $game->bggId,
                'start_time' => $game->startTime->format(DateTime::ATOM),
                'end_time' => $endTime,
                'name' => $game->name,
                'joinable' => $game->joinable,
                'max_players' => $game->maxPlayers,
                'description' => $game->description,
            ],
            ['id' => $game->id]
        );

        if ($res === false) {
            throw new RuntimeException($wpdb->print_error());
        }
    }
}
