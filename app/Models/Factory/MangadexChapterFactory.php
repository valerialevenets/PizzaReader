<?php

namespace App\Models\Factory;

use App\Models\Chapter;
use App\Models\MangadexChapter;
use App\Models\MangadexManga;

class MangadexChapterFactory
{
    public function create(Chapter $chapter, MangadexManga $manga, string $mangadexChapterId): MangadexChapter
    {
        return new MangadexChapter(
            [
                'mangadex_id' => $mangadexChapterId,
                'title' => $chapter->title,
                'chapter_number' => $chapter->chapter,
                'volume_number' => $chapter->volume,
                'language' => $chapter->language,
                'is_processed' => true,
                'chapter_id' => $chapter->id,
                'mangadex_manga_id' => $manga->id
            ]
        );
    }
}
