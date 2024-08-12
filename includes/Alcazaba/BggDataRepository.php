<?php

class BggDataRepository
{
    private function tableName(): string
    {
        global $wpdb;

        return $wpdb->prefix . "juegos_bgg";
    }

    public function saveBggMetadata(string $name, string $bggId, string $content): void
    {
        global $wpdb;

        $result = $wpdb->get_row("SELECT * FROM {$this->tableName()} WHERE bgg_id = $bggId");

        if ($result === null) {
            $wpdb->insert(
                $this->tableName(),
                [
                    'created_on' => date("Y-m-d H:i:s"),
                    'bgg_id' => $bggId,
                ]
            );
        }

        $wpdb->update(
            $this->tableName(),
            [
                'name' => $name,
                'updated_on' => date("Y-m-d H:i:s"),
                'content' => $content,
            ],
            ['bgg_id' => $bggId]
        );
    }

    public function saveGameParent(string $id, bool $hasParent, ?string $parent): void
    {
        global $wpdb;

        $wpdb->update(
            $this->tableName(),
            [
                'has_parent' => $hasParent,
                'parent' => $parent,
            ],
            ['id' => $id]
        );
    }

    public function getFirstBggGameNeedingSync(): ?string
    {
        $bggId = $this->getGameWithoutBggMetadata('wp_partidas_alcazaba');
        if ($bggId !== null) {
            return $bggId;
        }

        $bggId = $this->getGameWithoutBggMetadata('wp_juegos_alcazaba');
        if ($bggId !== null) {
            return $bggId;
        }

        $bggId = $this->getGameWithOutdatedBggMetadata('wp_partidas_alcazaba');
        if ($bggId !== null) {
            return $bggId;
        }

        $bggId = $this->getGameWithOutdatedBggMetadata('wp_juegos_alcazaba');
        if ($bggId !== null) {
            return $bggId;
        }

        return null;
    }

    private function getGameWithoutBggMetadata(string $tableName): ?string
    {
        global $wpdb;

        $sql = <<<EOF
SELECT j.bgg_id
FROM $tableName j
         LEFT JOIN wp_juegos_bgg bgg ON bgg.bgg_id = j.bgg_id
WHERE bgg.updated_on IS NULL
  AND j.bgg_id IS NOT NULL
  AND j.bgg_id != ''
LIMIT 1
EOF;

        $results = $wpdb->get_results($sql);

        if ($results === []) {
            return null;
        }

        return $results[0]->bgg_id;
    }

    private function getGameWithOutdatedBggMetadata(string $tableName): ?string
    {
        global $wpdb;

        $sql = <<<EOF
SELECT j.bgg_id
FROM $tableName j
         LEFT JOIN wp_juegos_bgg bgg ON bgg.bgg_id = j.bgg_id
WHERE bgg.updated_on IS NOT NULL
  AND bgg.updated_on < DATE_SUB(NOW(), INTERVAL 3 MONTH)
  AND j.bgg_id IS NOT NULL
  AND j.bgg_id != ''
LIMIT 1
EOF;

        $results = $wpdb->get_results($sql);

        if ($results === []) {
            return null;
        }

        return $results[0]->bgg_id;
    }

    public function getGamesWithoutSetParent(): array
    {
        global $wpdb;

        $sql = <<<EOF
SELECT id
FROM wp_juegos_bgg
WHERE has_parent IS NULL
  AND content IS NOT NULL;
EOF;

        return $wpdb->get_col($sql);
    }

    public function getGameMetadata(string $id): array
    {
        global $wpdb;

        $sql = <<<EOF
SELECT content
FROM wp_juegos_bgg
WHERE id = $id
  AND content IS NOT NULL;
EOF;

        $result = $wpdb->get_row($sql);

        if ($result === null) {
            return [];
        }

        try {
            $decoded = json_decode($result->content, true);
        } catch (Throwable) {
            return [];
        }

        return $decoded;
    }
}
