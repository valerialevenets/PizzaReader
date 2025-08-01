<?php

namespace App\Jobs;

use App\Mangadex\Api\Manga as MangadexApi;
use App\Models\MangadexManga;
use App\Saver\MangadexSaver;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class MangadexSave implements ShouldQueue
{
    use Queueable;

    private ?MangadexApi $mangadexApi = null;
    private ?MangadexSaver $mangadexSaver = null;
    public function __construct(private string $mangadexId)
    {
    }

    /**
     * Execute the job.
     */
    public function handle(MangadexApi $mangadexApi, MangadexSaver $mangadexSaver): void
    {
        $this->mangadexApi = $mangadexApi;
        $this->mangadexSaver = $mangadexSaver;
        $response = $this->mangadexApi->getMangaById($this->mangadexId);
        $response = json_decode(json_encode($response), true);
        $manga = $this->mangadexSaver->saveManga(
            $response['data']['id'],
            $this->convertMangadexFields($response['data']),
            $this->mangadexApi->getMangaCover($response['data']['id'], $this->getCoverArtId($response['data']['relationships']))
        );
        $this->saveChapters($manga);
    }

    private function convertMangadexFields(array $item): array
    {
        $genres = [];
        foreach ($item['attributes']['tags'] as $tag) {
            $names = $tag['attributes']['name'];
            $name = isset($names['en']) ? $names['en'] : array_values($names)[0];
            $genres[] = $name;
        }
        $title = isset($item['attributes']['title']['en'])
            ? $item['attributes']['title']['en'] : array_values($item['attributes']['title'])[0];
        $description = isset($item['attributes']['description']['en'])
            ? $item['attributes']['description']['en'] : '';
        $fields = [
            'name' => $title,
            'description' => $description,
            'author' => $this->getAuthor($item),
            'genres' => implode(',', $genres),
            'adult' => $this->isAdult($item['attributes']['contentRating']),
            'target' => mb_ucfirst((string)$item['attributes']['publicationDemographic']),
            'status' => mb_ucfirst((string)$item['attributes']['status']),
        ];
        return $fields;
    }
    private function getAuthor(array $item): string
    {
        foreach ($item['relationships'] as $relationship) {
            if ($relationship['type'] === 'author') {
                return $relationship['attributes']['name'];
            }
        }
        return '';
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
    private function getMangaChapters(MangadexManga $manga): array
    {
        $limit = 50;
        $offset = 0;
        $chapters = [];
        do {
            if($offset!=0) {
                usleep(500000);
            }
            $response = $this->mangadexApi->getMangaChapters($manga->mangadex_id, $limit, $offset);
            if (! $response->ok()) {
                throw new \Exception($response->getStatusCode().' '.$response->getReasonPhrase());
            }
            $chapters = array_merge($chapters, $response->json('data'));
            $offset += $limit;
        } while ($response->json('total') >= $offset);

        if(empty($chapters)) {
            $response = $this->mangadexApi->getMangaAggregate($manga->mangadex_id);
            if (! $response->ok()) {
                throw new \Exception($response->getStatusCode().' '.$response->getReasonPhrase());
            }
            $chapterIds = [];
            foreach ($response->json('volumes') as $volume) {
                foreach ($volume['chapters'] as $chapter) {
                    $chapterIds[$chapter['id']] = $chapter['id'];
                    foreach ($chapter['others'] as $other) {
                        $chapterIds[$other] = $other;
                    }
                }
            }
            foreach ($chapterIds as $chapterId) {
                usleep(500000);
                $response = $this->mangadexApi->getMangaChapter($chapterId);
                if (! $response->ok()) {
                    throw new \Exception($response->getStatusCode().' '.$response->getReasonPhrase());
                }
                $chapters[] = $response->json('data');
            }
        }
        return $chapters;
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
        echo PHP_EOL;
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
    private function getCoverArtId(array $relationships): ?string
    {
        foreach ($relationships as $relationship) {
            if ($relationship['type'] === 'cover_art') {
                return $relationship['id'];
            }
        }
        return null;
    }

    private function isAdult(string $contentRating): bool
    {
        $adultRatings = array_flip([
            'erotica',
//            'suggestive',
            'pornographic'
        ]);
        return isset($adultRatings[$contentRating]);
    }
}
