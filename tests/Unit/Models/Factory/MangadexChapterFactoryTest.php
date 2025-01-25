<?php

namespace Tests\Unit\Models\Factory;

use App\Models\Chapter;
use App\Models\Factory\MangadexChapterFactory;
use App\Models\MangadexChapter;
use App\Models\MangadexManga;
use Illuminate\Foundation\Testing\TestCase;

class MangadexChapterFactoryTest extends TestCase
{
    private $factory;
    protected function setUp(): void
    {
        $this->factory = new MangadexChapterFactory();
        parent::setUp();
    }

    public function testCreate()
    {
        $chapter = Chapter::factory()->make();
        $manga = MangadexManga::factory()->make();
        $mangadexChapterId = uuid_create();

        $result = $this->factory->create($chapter, $manga, $mangadexChapterId);

        $this->assertInstanceOf(MangadexChapter::class, $result);
        $this->assertEquals($chapter->id, $result->chapter_id);
        $this->assertEquals($mangadexChapterId, $result->mangadex_id);
        $this->assertEquals($manga->id, $result->mangadex_manga_id);
    }
}
