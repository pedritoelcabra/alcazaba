<?php

class Statistics
{
    public static function stats(): string
    {
        $gameRepo = new GameRepository();
        return TemplateParser::fetchTemplate(
            'stats',
            [
                'gameDateTimes' => base64_encode(json_encode($gameRepo->getAllGameStartTimes()))
            ]
        );
    }
}
