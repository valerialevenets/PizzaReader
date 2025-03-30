<?php

namespace App\Console\Commands;

use App\ExternalApi\Kitsu\Kitsu;
use Illuminate\Cache\RedisStore;
use Illuminate\Console\Command;
use Illuminate\Redis\Connections\PredisConnection;
use Illuminate\Support\Facades\Cache;
class MetadataUpdate extends Command
{
    public function __construct(private Kitsu $kitsu)
    {
        parent::__construct();
    }

    protected $signature = 'metadata:update';
    protected $description = 'Fetches metadata from anilist/etc';

    public function handle()
    {
//        $this->kitsu->init();
        dd($this->kitsu->getMangaById(13612));
        $this->saveKitsuMappings();
    }
    private function saveKitsuMappings()
    {
        $limit = 20;
        $offset = 0;
        do {
            $response = $this->kitsu->getMappings($offset, $limit);
            foreach ($response->json('data') as $item) {
                Cache::getStore('redis')->forever('kitsu:mappings:'.$item['id'], $item);
            }
            usleep(100000);
        } while (! empty($response->json('data')));
    }
}
