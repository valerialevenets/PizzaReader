<?php

namespace App\Console\Commands;

use App\Jobs\MangadexSave;
use Illuminate\Console\Command;

class MangadexQueueSave extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:mangadex-queue-save {mangadexId}';

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
        $mangadexId = $this->argument('mangadexId');
        MangadexSave::dispatch($mangadexId);
    }
    protected function promptForMissingArgumentsUsing()
    {
        return [
            'mangadexId' => 'MangadexId is required',
        ];
    }
}
