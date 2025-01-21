<?php

namespace App\Console\Commands;

use App\Http\Controllers\Admin\ComicController;
use Illuminate\Console\Command;
use App\Models\Comic;
use Illuminate\Support\Facades\Log;
use Intervention\Image\Exception\NotReadableException;
use Intervention\Image\Facades\Image;

class ThumbnailResize extends Command {

    protected $signature = 'thumbnail:resize';
    protected $description = 'Generate small thumbnail for all comics';

    function __construct() {
        parent::__construct();
    }

    public function handle() {
        $comics = Comic::whereNotNull('thumbnail')->get();
        foreach ($comics as $comic) {
            Log::info('Resizing '.$comic->name.PHP_EOL);
            try {
                $path = Comic::path($comic);
                $file = Image::make(storage_path("app/$path/$comic->thumbnail"));
                ComicController::storeSmall($file, $path, $comic->thumbnail, 250, 355);
            } catch (NotReadableException $e) {
                Log::error($e);
            }
        }
    }
}
