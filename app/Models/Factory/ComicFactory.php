<?php
namespace App\Models\Factory;
use App\Models\Comic;
use App\Models\ComicFormat;
use App\Storage\Storage;
use Illuminate\Support\Str;

class ComicFactory
{
    public function __construct(private readonly Storage $storage)
    {
    }

    public function create(array $fields, ?string $coverImage = null): Comic
    {
        //TODO add validation
        $fields['salt'] = Str::random();
        $fields['slug'] = Comic::generateSlug($fields);
        $fields['hidden'] = false;
        $fields['comic_format_id'] = ComicFormat::all()->last()->id;
        $fields['order_index'] = 0;

        $comic = new Comic($fields);
        $path = Comic::path($comic);
        $this->storage->createVisibleDirectory($path);
        if ($coverImage) {
            $comic->thumbnail = 'thumbnail.png';
            $this->storeAs($path, $comic->thumbnail, $coverImage);
        }
        return $comic;
    }

    private function storeAs($path, $name, $content)
    {
        $this->storage->put($path, $name, $content);
    }
}
