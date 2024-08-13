<?php

class Statistics
{
    public static function stats(): string
    {
        $gameRepo = new GameRepository();
        $gameData = self::extractContents($gameRepo->getAllGameStartTimes());
        $loanRepo = new BoardgameRepository();
        $loanData = $loanRepo->getAllGamesLoanedFromLudoteca();
        $userRepo = new UserDataRepository();
        $userData = $userRepo->getAllUserSignUpData();
        return TemplateParser::fetchTemplate(
            'stats',
            [
                'gameDateTimes' => base64_encode(json_encode($gameData)),
                'loanDateTimes' => base64_encode(json_encode($loanData)),
                'userDateTimes' => base64_encode(json_encode($userData)),
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
