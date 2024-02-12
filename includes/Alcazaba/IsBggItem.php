<?php

trait IsBggItem
{
    public readonly ?string $bggId;

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
