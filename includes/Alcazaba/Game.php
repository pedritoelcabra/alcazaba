<?php

class Game
{
    /**
     * @param GamePlayer[] $players
     */
    public function __construct(
        public readonly ?int $id,
        public readonly DateTime $createdOn,
        public readonly int $createdBy,
        public readonly string $createdByName,
        public readonly DateTime $startTime,
        public readonly string $name,
        public readonly ?string $bggId,
        public readonly ?string $gcalId,
        public readonly bool $joinable,
        public readonly int $maxPlayers,
        public readonly array $players = [],
        public readonly ?string $description = null,
    ) {
    }

    public static function fromPost(array $data): self
    {
        $name = $data['game-name'] ?? '';
        $name = str_replace('\\', '', $name);
        $description = $data['game-description'] ?? null;
        $bggId = $data['game-id'] ?? '';
        $start = $data['game-datetime'] ?? null;
        $maxPlayers = (int) ($data['game-players'] ?? 0);
        $open = isset($data['game-open']) ? true : false;

        if ($open && $maxPlayers < 1) {
            throw new Exception('El número de jugadores es obligatorio para partidas abiertas.');
        }

        $currentUser = wp_get_current_user();

        $startDt = DateTime::createFromFormat('Y-m-d H:i', $start, new DateTimeZone('Europe/Madrid'));
        if ($startDt === false) {
            throw new Exception('Debe incluir una fecha válida.');
        }
        if ($startDt < new DateTime()) {
            throw new Exception('La fecha de comienzo debe estar en el futuro.');
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
            null,
            $open,
            $maxPlayers,
            [],
            $description,
        );
    }

    public function updateFromPost(array $data): self
    {
        $name = $data['game-name'] ?? '';
        $name = str_replace('\\', '', $name);
        $description = $data['game-description'] ?? null;
        $bggId = $data['game-id'] ?? '';
        $start = $data['game-datetime'] ?? null;
        $maxPlayers = (int) ($data['game-players'] ?? 0);
        $open = isset($data['game-open']) ? true : false;

        if ($open && $maxPlayers < 1) {
            throw new Exception('El número de jugadores es obligatorio para partidas abiertas.');
        }

        $startDt = DateTime::createFromFormat('Y-m-d H:i', $start, new DateTimeZone('Europe/Madrid'));
        if ($startDt === false) {
            throw new Exception('Debe incluir una fecha válida.');
        }
        if ($startDt < new DateTime()) {
            throw new Exception('La fecha y hora deben estar en el futuro.');
        }

        if (strlen($name) < 3) {
            throw new Exception('El nombre debe tener mínimo 3 caracteres.');
        }

        return new self(
            $this->id,
            $this->createdOn,
            $this->createdBy,
            $this->createdByName,
            $startDt,
            $name,
            $bggId,
            $this->gcalId,
            $open,
            $maxPlayers,
            [],
            $description,
        );
    }

    public function currentPlayers(): int
    {
        $count = 0;
        foreach ($this->players as $player) {
            $count += $player->amount;
        }

        return $count;
    }

    public function hasFreeSlots(): bool
    {
        return $this->joinable && $this->currentPlayers() < $this->maxPlayers;
    }

    public function playerInGame(int $id): bool
    {
        foreach ($this->players as $player) {
            if ($player->playerId === $id) {
                return true;
            }
        }

        return false;
    }

    public function playerHasOthers(int $id): bool
    {
        foreach ($this->players as $player) {
            if ($player->playerId === $id && $player->amount > 1) {
                return true;
            }
        }

        return false;
    }

    public function simpleHtmlDescription(): string
    {
        $description = $this->description;
        $description .= '<br />';

        if (! $this->joinable) {
            $description .= 'Partida cerrada';

            return $description;
        }

        $description .= "Creada por " . $this->createdByName;
        $description .= '<br />';
        $description .= 'Participantes (' . $this->currentPlayers() . '/' . $this->maxPlayers . '):';
        $description .= '<br />';
        foreach ($this->players as $player) {
            $description .= '- ' . $player->name;
            $description .= '<br />';
        }

        return $description;
    }
}
