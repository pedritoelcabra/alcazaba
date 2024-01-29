<?php

class Game
{
    public function __construct(
        public readonly ?int $id,
        public readonly DateTime $createdOn,
        public readonly int $createdBy,
        public readonly string $createdByName,
        public readonly DateTime $startTime,
        public readonly string $name,
        public readonly ?string $bggId,
        public readonly bool $joinable,
        public readonly int $maxPlayers,
    ) {
    }

    public static function fromPost(array $data): self
    {
        $name = $data['game-name'] ?? '';
        $bggId = $data['game-id'] ?? '';
        $start = $data['game-datetime'] ?? null;
        $players = (int) ($data['game-players'] ?? 0);
        $open = isset($data['game-open']) ? true : false;

        if ($open && $players < 1) {
            throw new Exception('El número de jugadores es obligatorio para partidas abiertas.');
        }

        $currentUser = wp_get_current_user();

        $startDt = DateTime::createFromFormat('Y-m-d H:i', $start);
        if ($startDt === false) {
            throw new Exception('Debe incluir una fecha válida.');
        }

        if (strlen($name) < 3) {
            throw new Exception('El nombre debe tener mínimo 3 caracteres.');
        }

        return new self(
            null,
            new DateTime(),
            $currentUser->ID,
            $currentUser->user_nicename,
            $startDt,
            $name,
            $bggId,
            $open,
            $players
        );
    }
}
