<?php

namespace App\Jobs;

use App\Mangadex\Api\Manga as MangadexApi;
use App\Mangadex\FieldMapper;
use App\Models\MangadexManga;
use App\Saver\MangadexSaver;
use App\Service\ComicUpdater;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class MangadexUpdate implements ShouldQueue
{

    use Queueable;
    private MangadexApi $mangadexApi;
    private MangadexSaver $mangadexSaver;
    private FieldMapper $fieldMapper;
    private ComicUpdater $comicUpdater;

    /**
     * Execute the job.
     */
    public function handle(
        MangadexApi $mangadexApi,
        MangadexSaver $mangadexSaver,
        FieldMapper $fieldMapper,
        ComicUpdater $comicUpdater,
    ): void
    {
        $this->mangadexApi = $mangadexApi;
        $this->mangadexSaver = $mangadexSaver;
        $this->fieldMapper = $fieldMapper;
        $this->comicUpdater = $comicUpdater;
    }

    private function actualHandle(): void
    {
        $mangas = MangadexManga::all();

        foreach ($mangas as $manga) {
            try {
                $this->updateComic($manga);
                $this->saveChapters($manga);
            } catch (ClientException $e) {
                Log::error($e);
                continue;
            }
        }
    }

    private function updateComic(MangadexManga $manga)
    {
        $response = $this->mangadexApi->getMangaById($manga->mangadex_id);
        $response = json_decode(json_encode($response), true);
        $fields = $this->fieldMapper->map($response['data']);

        $this->comicUpdater->updateComic($manga->comic, $fields);
    }

    private function saveChapters(MangadexManga $manga)
    {
        $chapters = [];
        foreach ($this->getMangaChapters($manga) as $mangaChapter) {
            if (in_array($mangaChapter['attributes']['translatedLanguage'], ['en', 'ru', 'ukr', 'ua'])) {
                $chapters[] = $mangaChapter;
            }
        }
        foreach ($chapters as $chapter) {
            try{
                $this->saveSingleChapter($manga, $chapter);
            } catch (\Exception $e) {
                Log::error($e);
                continue;
            }
            usleep(250000);
        }
    }
    private function saveSingleChapter(MangadexManga $manga, array $chapter)
    {
        $chapterId = $chapter['id'];
        if ($manga->chapters()->where('mangadex_id', '=', $chapterId)->first()) {
            return;
        }
        $files = $this->getChapterImages($chapterId);
        $this->mangadexSaver->saveMangadexChapter(
            $manga,
            $this->fieldMapper->mapChapter($chapter),
            $chapterId,
            $files
        );
    }
    private function getChapterImages(string $chapterId): array
    {
        $response = $this->mangadexApi->getChapterImages($chapterId);
        if (! $response->ok()) {
            throw new \Exception($response->getStatusCode().' '.$response->getReasonPhrase());
        }
        $files = [];
        $iterator = 0;
        foreach ($response->json('chapter.data') as $filename) {
            $iterator++;
            $imageResponse = $this->mangadexApi->getChapterImage($response->json('baseUrl'), $response->json('chapter.hash'), $filename);
            $filename = strip_forbidden_chars($filename);
            $filename = $iterator.'.'.explode('.', $filename)[1];// this should work because mangadex returns ORDERED files
            $files[$filename] = $imageResponse->body();
            usleep(200000);
        }
        return $files;
    }
    private function getMangaChapters(MangadexManga $manga): array
    {
        $chapters = [];
        $response = $this->mangadexApi->getMangaAggregate($manga->mangadex_id);
        if (! $response->ok()) {
            throw new \Exception($response->getStatusCode().' '.$response->getReasonPhrase());
        }
        $chapterIds = [];
        foreach ($response->json('volumes') as $volume) {
            foreach ($volume['chapters'] as $chapter) {
                $chapterIds[] = $chapter['id'];
                $chapterIds = array_merge($chapterIds, $chapter['others']);
            }
        }
        $chapterIds = array_diff($chapterIds, $manga->chapters()->pluck('mangadex_id')->toArray());
        foreach ($chapterIds as $chapterId) {
            usleep(500000);
            $response = $this->mangadexApi->getMangaChapter($chapterId);
            if (! $response->ok()) {
                throw new \Exception($response->getStatusCode().' '.$response->getReasonPhrase());
            }
            $chapters[] = $response->json('data');
        }
        return $chapters;
    }
}
