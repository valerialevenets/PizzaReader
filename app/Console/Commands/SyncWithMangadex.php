<?php

namespace App\Console\Commands;

use App\Mangadex\Api\Manga as MangadexApi;
use App\Models\Chapter;
use App\Models\Comic;
use App\Models\MangadexChapter;
use App\Models\MangadexManga;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Console\Helper\ProgressBar;

class SyncWithMangadex extends Command
{
    private ?ProgressBar $progressBar = null;
    public function __construct(private MangadexApi $mangadexApi)
    {
        return parent:: __construct();
    }
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-with-mangadex';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->init();
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
//        dd($mangaList);
        $this->saveMangaAndChapters($mangaList);
        $this->progressBar->finish();
    }
    private function saveMangaAndChapters(array $data)
    {
        foreach ($data as $item) {
            $manga = MangadexManga::where('mangadex_id', '=', $item['id'])->first();
            if (!$manga) {
                $comic = $this->createComic($item);
                $manga = new MangadexManga();
                $manga->mangadex_id = $item['id'];
                $manga->comic_id = $comic->id;
                $manga->save();
                $manga->refresh();
            }
            $this->saveChapters($manga);
            $this->progressBar->advance();
        }
    }

    private function createComic(array $item): Comic
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
            'cover_image' => $this->mangadexApi->getMangaCover($item['id'], $this->getCoverArtId($item['relationships'])),
            'thumbnail' => 'thumbnail.png',
        ];
        $fields['salt'] = Str::random();
        $fields['slug'] = Comic::generateSlug($fields);

        //echo 'Saving manga '.$title.PHP_EOL;

        $comic = Comic::create($fields);
        $path = Comic::path($comic);
        Storage::makeDirectory($path);
        Storage::setVisibility($path, 'public');
        $this->storeAs($path, $comic->thumbnail, $fields['cover_image']);
        $comic->refresh();
        return $comic;
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
                if ($mangaChapter['attributes']['translatedLanguage'] === 'en') {
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
        $comic = $manga->comic;
        $chapterId = $chapter['id'];
        if (MangadexChapter::where('mangadex_id', '=', $chapterId)->first()) {
            return;
        }
        $fields = [
            'comic_id' => $comic->id,
            'team_id' => Team::first()->id,
            'volume' => $chapter['attributes']['volume'] ?: 1,
            'chapter' => $chapter['attributes']['chapter'],
            'title' => $chapter['attributes']['title'],
            'salt' => Str::random(),
            'views' => 1,
            'rating' => 1,
            'language' => $chapter['attributes']['translatedLanguage'],
            'publish_start' => Carbon::yesterday(),
            'publish_end' => null,
            'published_on' => Carbon::yesterday()
        ] ;
        $fields['slug'] = Chapter::generateSlug($fields);

        $chapter = Chapter::create($fields);
        $path = Chapter::path($comic, $chapter);
        Storage::makeDirectory($path);
        Storage::setVisibility($path, 'public');
        $chapter->save();
        $chapter->refresh();
        try{
            $this->saveChapterImages($comic, $chapter, $chapterId);
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
    private function saveChapterImages(Comic $comic, Chapter $chapter, string $chapterId)
    {
        //echo "Downloading image data".PHP_EOL;
        $response = $this->mangadexApi->getChapterImages($chapterId);
        if (! $response->ok()) {
            throw new \Exception($response->getStatusCode().' '.$response->getReasonPhrase());
        }
        $path = Chapter::path($comic, $chapter);

        $files = [];
        $pages = [];
        foreach ($response->json('chapter.data') as $filename) {
            $imageResponse = $this->mangadexApi->getChapterImage($response->json('baseUrl'), $response->json('chapter.hash'), $filename);
            $original_file_name = strip_forbidden_chars($filename);
            $files[$original_file_name] = $imageResponse->body();
            $imagedata = getimagesizefromstring($imageResponse->body());
            $size = mb_strlen($imageResponse->body(), '8bit');
            $pages[] =  [
                'chapter_id' => $chapter->id,
                'filename' => $original_file_name,
                'size' => $size,
                'width' => $imagedata[0],
                'height' => $imagedata[1],
                'mime' => $imagedata['mime'],
                'hidden' => false,
                'licensed' => false,
            ];
            usleep(200000);
        }
        //echo "Image data downloaded".PHP_EOL;
        //echo "Saving chapter pages".PHP_EOL;
        foreach ($files as $filename => $content) {
            $this->storeAs($path, $filename, $content);
        }
        $chapter->pages()->createMany($pages);
//        Page::createMany($pages);
    }
    private function storeAs($path, $name, $content)
    {
        Storage::disk('local')->put("{$path}/$name", $content);
    }
    private function init()
    {
        if (Team::all()->count() != 0) {
            return;
        }
        Team::create(
            [
                'name' => 'test', 'slug' => 'test', 'url' => 'google.com',
            ]
        );
        $user = User::create([
            'name' => 'Valerie',
            'email' => 'v@v.c',
            'password' => Hash::make('12345678'),
        ]);
        $user->role()->associate(Role::where('name', 'admin')->first());
        $user->save();
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
