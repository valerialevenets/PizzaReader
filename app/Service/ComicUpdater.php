<?php

namespace App\Service;

use App\Models\Comic;

class ComicUpdater
{
    public function updateComic(Comic $comic, array $fields): void
    {
        $fields = array_intersect_key(
            $fields,
            array_flip(['adult', 'target', 'status', 'author', 'alt_titles', 'genres'])
        );
        $comic->update($fields);
    }
}
