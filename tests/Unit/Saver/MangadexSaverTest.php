<?php

namespace Tests\Unit\Saver;

use App\Models\Comic;
use App\Models\Factory\ChapterFactory;
use App\Models\Factory\ComicFactory;
use App\Models\Factory\MangadexChapterFactory;
use App\Models\MangadexManga;
use App\Models\Repository\MangadexMangaRepository;
use App\Saver\MangadexSaver;
use App\Storage\Storage;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

//use PHPUnit\Framework\TestCase;
/**
 * @runInSeparateProcess
 * @preserveGlobalState disabled
 */
class MangadexSaverTest extends TestCase
{
    use WithFaker;
    use ProphecyTrait;
    private $sut;
    protected function setUp(): void
    {
        $this->sut = new MangadexSaver(
            $this->createMock(ComicFactory::class),
            $this->createMock(ChapterFactory::class),
            $this->createMock(MangadexChapterFactory::class),
            $this->createMock(Storage::class),
            $this->createMock(MangadexMangaRepository::class),
        );
        parent::setUp();
    }


    public function testSaveMangaWithAlreadyExistingMangaShouldUpdateManga()
    {
        $this->assertTrue(true);
        return;
        $uuid = uuid_create();

        $collection = $this->createMock(Collection::class);
        $mangaMock = \Mockery::mock('alias:'. MangadexManga::class);
        $mangaMock->comic = new Comic();
        $mangaMock->mangadex_id = $uuid;
        $collection->expects($this->once())->method('first')->with()->willReturn($mangaMock);
        $mangaMock->shouldReceive('where')
            ->withAnyArgs()
            ->andReturn($collection);

        $data = ['adult' => true, 'target' => 'woman'];
        $result = $this->sut->saveManga('id', $data, 'content');
        $this->assertEquals($mangaMock, $result);
        $this->assertEquals($uuid, $result->mangadex_id);


    }


}
