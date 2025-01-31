<?php

namespace App\Console\Commands;

use App\Mangadex\Api\Manga as MangadexApi;
use App\Models\MangadexManga;
use App\Saver\MangadexSaver;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MangadexUpdate extends Command
{
    public function __construct(private MangadexApi $mangadexApi, private MangadexSaver $mangadexSaver)
    {
        return parent:: __construct();
    }
    protected $signature = 'mangadex:update';
    protected $description = 'Checks for updates for already downloaded mangadex titles';

    public function handle()
    {
        $mangas = MangadexManga::all();
        $this->output->progressStart(count($mangas));

        foreach ($mangas as $manga) {
            $this->saveChapters($manga);
            $this->output->progressAdvance();
        }
        $this->output->progressFinish();
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
            $this->convertMangadexChapterFields($chapter),
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
    private function convertMangadexChapterFields(array $chapter): array
    {
        return [
            'volume' => $chapter['attributes']['volume'],
            'chapter' => $chapter['attributes']['chapter'],
            'title' => $chapter['attributes']['title'],
            'language' => $chapter['attributes']['translatedLanguage'],
        ];
    }
}
