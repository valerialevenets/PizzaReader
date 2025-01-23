<?php

namespace App\Models\Factory;

use App\Models\Chapter;
use App\Models\Comic;
use App\Models\Team;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ChapterFactory
{
    public function create(Comic $comic, array $fields): Chapter
    {
        $fields['publish_start'] = Carbon::now();
        $fields['published_on'] = Carbon::now();
        $fields['team_id'] = Team::first()->id;
        $fields['salt'] = Str::random();
        //this part PROBABLY can be removed, maybe we could add this INTO the comic later (in saver)
        $fields['comic_id'] = $comic->id;
        $fields['slug'] = Chapter::generateSlug($fields);

        $chapter = Chapter::create($fields);
        $path = Chapter::path($comic, $chapter);
        Storage::makeDirectory($path);
        Storage::setVisibility($path, 'public');

        return $chapter;
    }
}
