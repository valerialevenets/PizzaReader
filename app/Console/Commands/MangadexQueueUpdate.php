<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Psr\Log\LoggerInterface;

class MangadexQueueUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mangadex:queue-update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(LoggerInterface $logger)
    {
        $logger->info("Mangadex: Queueing update");
        \App\Jobs\MangadexUpdate::dispatch();
    }
}
