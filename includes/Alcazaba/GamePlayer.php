<?php

class GamePlayer
{
    public function __construct(
        public readonly int    $playerId,
        public readonly int    $gameId,
        public readonly int    $amount,
        public readonly string $name,
    )
    {
    }
}
