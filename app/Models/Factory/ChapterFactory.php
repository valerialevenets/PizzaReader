<?php

namespace App\Models\Factory;

use App\Models\Chapter;
use App\Models\Comic;
use App\Models\Team;
use App\Storage\Storage;
use Carbon\Carbon;
use Illuminate\Support\Str;

class ChapterFactory
{
    public function __construct(private readonly Storage $storage)
    {
    }
    public function create(Comic $comic, array $fields): Chapter
    {
        $fields['publish_start'] = Carbon::now();
        $fields['published_on'] = Carbon::now();
        $fields['team_id'] = Team::first()->id;
        $fields['salt'] = Str::random();
        //this part PROBABLY can be removed, maybe we could add this INTO the comic later (in saver)
        $fields['comic_id'] = $comic->id;
        $fields['slug'] = Chapter::generateSlug($fields);

        $chapter = new Chapter($fields);
        $path = Chapter::path($comic, $chapter);
        $this->storage->createVisibleDirectory($path);

        return $chapter;
    }
    public function savePages(Chapter $chapter)
    {

    }
}
