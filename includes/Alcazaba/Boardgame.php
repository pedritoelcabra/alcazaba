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
        public readonly bool $loanable = false,
    ) {
    }

    public static function fromPost(array $data): self
    {
        if (($data['game-id'] ?? '') === '') {
            throw new RuntimeException('Es necesario elegir un juego de la BGG');
        }

        $name = $data['game-name'] ?? '';
        $name = str_replace('\\', '', $name);

        if (strlen($name) < 3) {
            throw new RuntimeException('Nombre demasiado corto');
        }

        return new self(
            null,
            (int)$data['game-id'],
            $name,
            null,
            null,
            null,
            ($data['game-member-owned'] ?? false) === false
        );
    }

    public function canLoan(): bool
    {
        return $this->loanable && $this->loanerId === null;
    }

    public function loanedText(): string
    {
        if ($this->loanable === false) {
            return 'Juego de socio';
        }

        if ($this->loanedOn === null) {
            return '';
        }

        $days = (new DateTime())->diff($this->loanedOn)->days;

        return sprintf('Sacado por %s hace %s dias', $this->loanerName, $days);
    }

    public function loanedByCurrentUser(): bool
    {
        return wp_get_current_user()->ID === $this->loanerId;
    }
}
