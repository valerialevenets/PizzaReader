<?php
namespace App\Models\Factory;
use App\Models\Comic;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ComicFactory
{
    public function create(array $fields, ?string $coverImage = null): Comic
    {
        //TODO add validation
        $fields['salt'] = Str::random();
        $fields['slug'] = Comic::generateSlug($fields);
        $fields['hidden'] = false;
        $fields['comic_format_id'] = 1;
        $fields['order_index'] = 0;

        $comic = new Comic($fields);
        $path = Comic::path($comic);
        Storage::makeDirectory($path);
        Storage::setVisibility($path, 'public');
        if ($coverImage) {
            $comic->thumbnail = 'thumbnail.png';
            $this->storeAs($path, $comic->thumbnail, $coverImage);
        }
        return $comic;
    }

    private function storeAs($path, $name, $content)
    {
        Storage::disk('local')->put("{$path}/$name", $content);
    }
}
