<?php

namespace App\Console\Commands;

use App\Helpers\MangadexFields;
use App\Mangadex\Api\Manga as MangadexApi;
use App\Models\MangadexManga;
use App\Saver\MangadexSaver;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Helper\ProgressBar;

class SaveSingleMangadexTitle extends Command
{
    private ?ProgressBar $progressBar = null;
    public function __construct(
        private MangadexApi $mangadexApi,
        private MangadexSaver $mangadexSaver,
        private MangadexFields $mangadexFields
    ) {
        return parent:: __construct();
    }
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mangadex:save {mangadexId}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Saves single mangadex title';

    protected function promptForMissingArgumentsUsing()
    {
        return [
            'mangadexId' => 'MangadexId is required',
        ];
    }
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $mangadexId = $this->argument('mangadexId');
        $response = $this->mangadexApi->getMangaById($mangadexId);
        $response = json_decode(json_encode($response), true);
        $manga = $this->mangadexSaver->saveManga(
            $response['data']['id'],
            $this->mangadexFields->convertTitleFields($response['data']),
            $this->mangadexApi->getMangaCover($response['data']['id'], $this->getCoverArtId($response['data']['relationships']))
        );
        $this->saveChapters($manga);
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
        $this->progressBar = $this->output->createProgressBar(count($chapters));
        $this->progressBar->start();
        foreach ($chapters as $chapter) {
            try{
                $this->saveSingleChapter($manga, $chapter);
                $this->progressBar->advance();
            } catch (\Exception $e) {
                Log::error($e);
                continue;
            }
            usleep(250000);
        }
        $this->progressBar->finish();
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
            $this->mangadexFields->convertChapterFields($chapter),
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
}
