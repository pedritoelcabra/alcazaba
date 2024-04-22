<?php

class Game
{
    use IsBggItem;

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
        public readonly ?float $weight = null,
        public readonly ?DateTime $endTime = null,
    ) {
    }

    private static function nameFromPost(array $data): string
    {
        $name = $data['game-name'] ?? '';
        $name = str_replace('\\', '', $name);

        if (strlen($name) < 3) {
            throw new Exception('El nombre debe tener mínimo 3 caracteres.');
        }

        return $name;
    }

    private static function descriptionFromPost(array $data): string
    {
        $description = $data['game-description'] ?? null;
        $description = strip_tags($description);
        $description = str_replace(PHP_EOL . PHP_EOL, PHP_EOL, $description);
        $description = str_replace(PHP_EOL, '<br/>', $description);

        return $description;
    }

    private static function startFromPost(array $data): DateTime
    {
        $start = $data['game-datetime'] ?? null;

        $startDt = DateTime::createFromFormat('Y-m-d H:i', $start, new DateTimeZone('Europe/Madrid'));
        if ($startDt === false) {
            throw new Exception('Debe incluir una fecha válida.');
        }

        if ($startDt < (new DateTime())->sub(DateInterval::createFromDateString('6 hours'))) {
            throw new Exception('La fecha de comienzo debe estar en el futuro.');
        }

        return $startDt;
    }

    private static function endFromPost(array $data, DateTime $startDt): ?DateTime
    {
        $end = $data['game-endtime'] ?? null;
        if ($end === null) {
            return null;
        }

        $endDt = DateTime::createFromFormat('Y-m-d H:i', $end, new DateTimeZone('Europe/Madrid'));
        if ($endDt === false) {
            return null;
        }

        if ($endDt <= $startDt) {
            throw new Exception('La hora de final no puede ser antes que la de inicio.');
        }

        if ($startDt->diff($endDt)->days > 0) {
            throw new Exception('La partida no puede durar más de 24 horas.');
        }

        return $endDt;
    }

    public static function fromPost(array $data): self
    {
        $maxPlayers = (int) ($data['game-players'] ?? 0);
        $open = isset($data['game-open']) ? true : false;

        if ($open && $maxPlayers < 1) {
            throw new Exception('El número de jugadores es obligatorio para partidas abiertas.');
        }

        $currentUser = wp_get_current_user();
        $start = self::startFromPost($data);
        $end = self::endFromPost($data, $start);

        return new self(
            null,
            new DateTime(),
            $currentUser->ID,
            $currentUser->user_nicename,
            $start,
            self::nameFromPost($data),
            $data['game-id'] ?? '',
            null,
            $open,
            $maxPlayers,
            [],
            self::descriptionFromPost($data),
            null,
            $end,
        );
    }

    public function updateFromPost(array $data): self
    {
        $maxPlayers = (int) ($data['game-players'] ?? 0);
        $open = isset($data['game-open']) ? true : false;

        if ($open && $maxPlayers < 1) {
            throw new Exception('El número de jugadores es obligatorio para partidas abiertas.');
        }
        $start = self::startFromPost($data);
        $end = self::endFromPost($data, $start);

        return new self(
            $this->id,
            $this->createdOn,
            $this->createdBy,
            $this->createdByName,
            $start,
            self::nameFromPost($data),
            $data['game-id'] ?? '',
            $this->gcalId,
            $open,
            $maxPlayers,
            [],
            self::descriptionFromPost($data),
            null,
            $end,
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

    public function getHyperlinkedDescription(): string
    {
        $description = $this->description;

        $links = [];
        preg_match_all('~[a-z]+://\S+~', $this->description, $links);
        foreach ($links[0] ?? [] as $link) {
            $description = str_replace(
                $link,
                sprintf('<a href="%s" target="_blank">%s</a>', $link, $link),
                $description
            );
        }

        return $description;
    }

    public function getThumbnailPath(): ?string
    {
        if ($this->bggId === null) {
            return null;
        }

        return sprintf(
            '%s../../public/image/games/%s.jpg',
            plugin_dir_path(__FILE__),
            $this->bggId
        );
    }

    public function publicThumbnailPath(): ?string
    {
        if ($this->bggId === null) {
            return null;
        }

        return sprintf(
            '/wp-content/plugins/wp-alcazaba/public/image/games/%s.jpg',
            $this->bggId
        );
    }

    public function hasThumbnail(): bool
    {
        return $this->getThumbnailPath() !== null && file_exists($this->getThumbnailPath());
    }

    public function bggLink(): ?string
    {
        if ($this->bggId === null) {
            return null;
        }

        return sprintf('https://boardgamegeek.com/boardgame/%s', $this->bggId);
    }
}
