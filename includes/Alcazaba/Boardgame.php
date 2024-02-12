<?php

class Boardgame
{
    use IsBggItem;

    public function __construct(
        public readonly ?int $id,
        public readonly ?string $bggId,
        public readonly string $name,
        public readonly ?int $loanerId = null,
        public readonly ?string $loanerName = null,
        public readonly ?DateTime $loanedOn = null,
    ) {
    }

    public static function fromPost(array $data): self
    {
        if (($data['game-id'] ?? '') === '') {
            throw new RuntimeException('Es necesario elegir un juego de la BGG');
        }

        if (strlen(($data['game-name'] ?? '')) < 3) {
            throw new RuntimeException('Nombre demasiado corto');
        }

        return new self(
            null,
            (int) $data['game-id'],
            $data['game-name'],
        );
    }
}
