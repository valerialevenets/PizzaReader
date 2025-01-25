<?php

namespace App\Models\Repository;

use App\Models\MangadexManga;

class MangadexMangaRepository
{
    public function has(string $mangadexId): bool
    {
        return (bool) $this->findByMangadexId($mangadexId);
    }
    public function findByMangadexId(string $mangadexId): ?MangadexManga
    {
        return MangadexManga::where('mangadex_id', '=', $mangadexId)->first();
    }
}
