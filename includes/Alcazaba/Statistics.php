<?php

class Statistics
{
    public static function stats(): string
    {
        $gameRepo = new GameRepository();
        $data = self::extractContents($gameRepo->getAllGameStartTimes());
        return TemplateParser::fetchTemplate(
            'stats',
            [
                'gameDateTimes' => base64_encode(json_encode($data))
            ]
        );
    }

    private static function extractContents(array $gameData): array
    {
        $contents = [];

        foreach ($gameData as $game) {
            $metadata = json_decode($game->base_content, true);
            $game->abstracts = false;
            $game->cgs = false;
            $game->thematic = false;
            $game->familygames = false;
            $game->childrensgames = false;
            $game->partygames = false;
            $game->strategygames = false;
            $game->wargames = false;
            foreach ($metadata['item']['statistics']['ratings']['ranks']['rank'] ?? [] as $rank) {
                if (($rank['@attributes']['type'] ?? '') === 'family') {
                    $name = $rank['@attributes']['name'] ?? null;
                    if ($name && $game->$name === false) {
                        $game->$name = true;
                    }
                }
            }
            $game->base_content = null;
            $contents[] = $game;
        }

        return $contents;
    }
}
