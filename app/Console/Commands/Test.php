<?php

namespace App\Console\Commands;

use App\Mangadex\Api\Manga as MangadexApi;
use App\Models\Chapter;
use App\Models\Comic;
use App\Models\MangadexChapter;
use App\Models\MangadexManga;
use App\Models\Team;
use App\Saver\MangadexSaver;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Console\Helper\ProgressBar;

class Test extends Command
{
    private ?ProgressBar $progressBar = null;
    public function __construct(private MangadexApi $mangadexApi, private MangadexSaver $mangadexSaver)
    {
        return parent:: __construct();
    }
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test-save';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    public function handle()
    {
        $this->saveList();
        //echo 'Done'.PHP_EOL;
    }

    private function saveList()
    {
        $mangaList = [];
        $authData = $this->mangadexApi->auth();
        sleep(1);
        $limit = 20;//TODO increase later
        $offset = 0;
        do {
            $response = $this->mangadexApi->getList($authData['access_token'], $limit, $offset);
            if (! $response->ok()) {
                throw new \Exception($response->getStatusCode().' '.$response->getReasonPhrase());
            }
            $mangaList = array_merge($mangaList, $response->json('data'));
            if(! $this->progressBar) {
                $this->progressBar = $this->output->createProgressBar($response->json('total'));
                $this->progressBar->start();
            }
            $offset += $limit;
            sleep(1);
        } while ($response->json('total') >= $offset);
        $this->saveMangaAndChapters($mangaList);
        $this->progressBar->finish();
    }
    private function saveMangaAndChapters(array $data)
    {
        foreach ($data as $item) {
            try{
                $manga = $this->mangadexSaver->createManga(
                    $item['id'],
                    $this->convertMangadexFields($item),
                    $this->mangadexApi->getMangaCover($item['id'], $this->getCoverArtId($item['relationships']))
                );
            } catch (QueryException $exception) {
                Log::error($exception);
                continue;
            }
            $this->saveChapters($manga);
            $this->progressBar->advance();
        }
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
            'hidden' => false,
            'author' => '',
            'genres' => implode(',', $genres),
            'order_index' => 0,
            'comic_format_id' => 1,
            'adult' => $this->isAdult($item['attributes']['contentRating']),
            'target' => mb_ucfirst((string)$item['attributes']['publicationDemographic']),
            'status' => mb_ucfirst((string)$item['attributes']['status']),
        ];
        return $fields;
    }
    private function saveChapters(MangadexManga $manga)
    {
        $limit = 50;
        $offset = 0;
        $chapters = [];
        do {
            if($offset!=0) {
                sleep(1);
            }
            $response = $this->mangadexApi->getMangaChapters($manga->mangadex_id, $limit, $offset);
            if (! $response->ok()) {
                throw new \Exception($response->getStatusCode().' '.$response->getReasonPhrase());
            }
            foreach ($response->json('data') as $mangaChapter) {
                if (in_array($mangaChapter['attributes']['translatedLanguage'], ['en', 'ru'])) {
                    $chapters[] = $mangaChapter;
                }
            }
            $offset += $limit;
        } while ($response->json('total') >= $offset);
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
        if (MangadexChapter::where('mangadex_id', '=', $chapterId)->first()) {
            return;
        }
        $files = $this->getChapterImages($chapterId);
        $this->mangadexSaver->saveMangadexChapter($manga, $chapter, $files);
    }
    private function getChapterImages(string $chapterId): array
    {
        $response = $this->mangadexApi->getChapterImages($chapterId);
        if (! $response->ok()) {
            throw new \Exception($response->getStatusCode().' '.$response->getReasonPhrase());
        }
        $files = [];
        foreach ($response->json('chapter.data') as $filename) {
            $imageResponse = $this->mangadexApi->getChapterImage($response->json('baseUrl'), $response->json('chapter.hash'), $filename);
            $filename = strip_forbidden_chars($filename);
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
