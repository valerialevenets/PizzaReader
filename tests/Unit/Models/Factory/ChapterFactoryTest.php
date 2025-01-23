<?php

namespace Tests\Unit\Models\Factory;

use App\Models\Chapter;
use App\Models\Comic;
use App\Models\Factory\ChapterFactory;
use App\Storage\Storage;
use Illuminate\Foundation\Testing\TestCase;

class ChapterFactoryTest extends TestCase
{
    private $chapterFactory;
    protected function setUp(): void
    {
        $this->chapterFactory = new ChapterFactory(
            $this->createMock(Storage::class)
        );
        parent::setUp();
    }

    public function testCreate()
    {
        $fields = [
            'title' => 'No name',
            'chapter' => 1,
            'comic_id' => 32,
            'volume' => 1,
            'language' => 'en'
        ];
        $comic = Comic::factory()->make();

        $chapter = $this->chapterFactory->create($comic, $fields);
        $this->assertInstanceOf(Chapter::class, $chapter);
    }
}
