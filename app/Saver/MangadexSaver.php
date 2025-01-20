<?php

namespace App\Saver;

use App\Models\Chapter;
use App\Models\Comic;
use App\Models\MangadexChapter;
use App\Models\MangadexManga;
use App\Models\Team;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MangadexSaver
{
    /**
     * @param string $mangadexId
     * @param array $fields
     * @param string $coverImage
     * @return MangadexManga
     * @throws QueryException
     */
    public function createManga(string $mangadexId, array $fields, string $coverImage): MangadexManga
    {
        $manga = MangadexManga::where('mangadex_id', '=', $mangadexId)->first();
        if (! $manga) {
            $comic = $this->createComic($fields, $coverImage);
            $manga = new MangadexManga();
            $manga->mangadex_id = $mangadexId;
            $manga->comic_id = $comic->id;
            $manga->save();
            $manga->refresh();
        } else {
            $this->updateComic($manga->comic, $fields);
        }
        return $manga;
    }
    public function saveMangadexChapter(MangadexManga $manga, array $chapter, array $files)
    {
        $chapterId = $chapter['id'];
        if (MangadexChapter::where('mangadex_id', '=', $chapterId)->first()) {
            return;
        }
        $fields = [
            'comic_id' => $manga->comic->id,
            'team_id' => Team::first()->id,
            'volume' => $chapter['attributes']['volume'] ?: 1,
            'chapter' => $chapter['attributes']['chapter'],
            'title' => $chapter['attributes']['title'],
            'salt' => Str::random(),
            'language' => $chapter['attributes']['translatedLanguage'],
            'publish_start' => Carbon::now(),
            'publish_end' => null,
            'published_on' => Carbon::now()
        ] ;
        $fields['slug'] = Chapter::generateSlug($fields);

        $chapter = Chapter::create($fields);
        $path = Chapter::path($manga->comic, $chapter);
        Storage::makeDirectory($path);
        Storage::setVisibility($path, 'public');
        $chapter->save();
        $chapter->refresh();
        try{
            $this->saveChapterPages($chapter, $files);
            MangadexChapter::create(
                [
                    'mangadex_id' => $chapterId,
                    'title' => $chapter->title,
                    'chapter_number' => $chapter->chapter,
                    'volume_number' => $chapter->attributes,
                    'language' => $chapter->language,
                    'is_processed' => true,
                    'chapter_id' => $chapter->id,
                    'mangadex_manga_id' => $manga->id
                ]
            );
            $chapter->save();
        } catch (\Exception $exception){
            $chapter->delete();
            throw $exception;
        }
    }
    private function saveChapterPages(Chapter $chapter, array $files)
    {
        $path = Chapter::path($chapter->comic, $chapter);
        $pages = [];
        foreach ($files as $filename => $content) {
            $imagedata = getimagesizefromstring($content);
            $size = mb_strlen($content, '8bit');
            $pages[] =  [
                'chapter_id' => $chapter->id,
                'filename' => $filename,
                'size' => $size,
                'width' => $imagedata[0],
                'height' => $imagedata[1],
                'mime' => $imagedata['mime'],
                'hidden' => false,
                'licensed' => false,
            ];
        }
        foreach ($files as $filename => $content) {
            $this->storeAs($path, $filename, $content);
        }
        $chapter->pages()->createMany($pages);
    }

    /**
     * @param array $fields
     * @param string $coverImage
     * @return Comic
     * @throws QueryException
     */
    private function createComic(array $fields, string $coverImage): Comic
    {
        //fields should be already converted from mangadex format
        $fields['thumbnail'] = 'thumbnail.png';
        $fields['salt'] = Str::random();
        $fields['slug'] = Comic::generateSlug($fields);

        $comic = Comic::create($fields);
        $path = Comic::path($comic);
        Storage::makeDirectory($path);
        Storage::setVisibility($path, 'public');
        $this->storeAs($path, $comic->thumbnail, $coverImage);
        $comic->refresh();
        return $comic;
    }
    private function updateComic(Comic $comic, array $fields): void
    {
        $fields = array_intersect_key(
            $fields,
            array_flip(['adult', 'target', 'status'])
        );
        $comic->update($fields);
    }

    private function storeAs($path, $name, $content)
    {
        Storage::disk('local')->put("{$path}/$name", $content);
    }
}
