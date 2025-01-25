<?php

namespace App\Saver;

use App\Models\Chapter;
use App\Models\Comic;
use App\Models\Factory\ChapterFactory;
use App\Models\Factory\ComicFactory;
use App\Models\Factory\MangadexChapterFactory;
use App\Models\MangadexManga;
use App\Models\Repository\MangadexMangaRepository;
use App\Storage\Storage;
use Illuminate\Database\QueryException;

class MangadexSaver
{
    public function __construct(
        private ComicFactory $comicFactory,
        private ChapterFactory $chapterFactory,
        private MangadexChapterFactory $mangadexChapterFactory,
        private Storage $storage,
        private MangadexMangaRepository $mangadexMangaRepository,
    ){}


    /**
     * @param string $mangadexId
     * @param array $fields
     * @param string $coverImage
     * @return MangadexManga
     * @throws QueryException
     */
    public function saveManga(string $mangadexId, array $fields, string $coverImage): MangadexManga
    {
        $manga = $this->mangadexMangaRepository->findByMangadexId($mangadexId);
        if (! $manga) {
            $manga = new MangadexManga(['mangadex_id' => $mangadexId]);
            $comic = $this->comicFactory->create($fields, $coverImage);
            $comic->save();
            $comic->mangadexManga()->save($manga);
        } else {
            $this->updateComic($manga->comic, $fields);
        }
        return $manga;
    }
    public function saveMangadexChapter(MangadexManga $manga, array $chapter, string $mangadexChapterId, array $files)
    {
        if ($manga->chapters()->where('mangadex_id', '=', $mangadexChapterId)->first()) {
            return;
        }
        $chapter = $this->chapterFactory->create($manga->comic, $chapter);
        $chapter->save();
        $chapter->refresh();
        try{
            $this->saveChapterPages($chapter, $files);
            $mangadexChapter = $this->mangadexChapterFactory->create($chapter, $manga, $mangadexChapterId);
            $mangadexChapter->save();
            $chapter->save();
        } catch (\Exception $exception){
            $this->storage->deleteDirectory(Chapter::path($manga->comic, $chapter));
            $chapter->delete();
            throw $exception;
        }
    }
    private function saveChapterPages(Chapter $chapter, array $files)
    {
        $path = Chapter::path($chapter->comic, $chapter);
        $pages = [];
        foreach ($files as $filename => $content) {
            $pages[] =  $this->getPageData($chapter, $filename, $content);
        }
        foreach ($files as $filename => $content) {
            $this->storeAs($path, $filename, $content);
        }
        $chapter->pages()->createMany($pages);
    }
    private function getPageData(Chapter $chapter, string $filename, string $content): array
    {
        $imagedata = getimagesizefromstring($content);
        return [
            'chapter_id' => $chapter->id,
            'filename' => $filename,
            'size' => mb_strlen($content, '8bit'),
            'width' => $imagedata[0],
            'height' => $imagedata[1],
            'mime' => $imagedata['mime'],
            'hidden' => false,
            'licensed' => false,
        ];
    }
    private function updateComic(Comic $comic, array $fields): void
    {
        $fields = array_intersect_key(
            $fields,
            array_flip(['adult', 'target', 'status'])
        );
        $comic->update($fields);
    }

    private function storeAs($path, $name, $content): bool
    {
        return $this->storage->put($path, $name, $content);
    }
}
