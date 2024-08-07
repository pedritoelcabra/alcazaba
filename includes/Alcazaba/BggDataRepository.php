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

    public function getFirstBggGameNeedingSync(): ?string
    {
        $bggId = $this->getGameWithoutBggMetadata();
        if ($bggId !== null) {
            return $bggId;
        }

        $bggId = $this->getGameWithOutdatedBggMetadata();
        if ($bggId !== null) {
            return $bggId;
        }

        return null;
    }

    private function getGameWithoutBggMetadata(): ?string
    {
        global $wpdb;

        $sql = <<<EOF
SELECT j.bgg_id
FROM wp_juegos_alcazaba j
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

    private function getGameWithOutdatedBggMetadata(): ?string
    {
        global $wpdb;

        $sql = <<<EOF
SELECT j.bgg_id
FROM wp_juegos_alcazaba j
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
}
