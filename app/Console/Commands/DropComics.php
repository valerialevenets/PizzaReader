<?php

namespace App\Console\Commands;

use App\Models\Comic;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class DropComics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:drop-comics';

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
        $comics = Comic::all();
        foreach ($comics as $comic) {
            Storage::deleteDirectory(Comic::path($comic));
            Comic::destroy($comic->id);
        }
    }
}
