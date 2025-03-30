<?php

namespace App\ExternalApi\Kitsu;
use Illuminate\Cache\RedisStore;
use Illuminate\Cache\RedisTaggedCache;
use Illuminate\Support\Facades\Cache;

class Kitsu
{
    private RedisStore $redis;
    public function __construct(private readonly Api $api)
    {
        $this->redis = Cache::getStore('redis');
    }
    public function init()
    {
        $this->initializeCategories();
        $this->initializeGenres();
        $this->initializeMappings();
    }
    public function getMangaById(int $id): array
    {
        return $this->convertManga($this->api->getMangaById($id)->json('data'));
        if ($this->getCache()->has('manga:' . $id)) {
            //return from cache
        } else {
            //get from api, save to cache and return
        }
    }
    private function convertManga(array $manga): array
    {
        dd($manga);
        $out = [
            'id' => $manga['id'],
            'titles' => $manga['attributes']['titles'],
            'createdAt' => $manga['attributes']['createdAt'],
            'updatedAt' => $manga['attributes']['updatedAt'],
            'canonicalTitle' => $manga['attributes']['canonicalTitle'],
            'averageRating' => $manga['attributes']['averageRating'],
            'startDate' => $manga['attributes']['startDate'],
            'endDate' => $manga['attributes']['endDate'],
            'ageRating' => $manga['attributes']['ageRating'],
            'ageRatingGuide' => $manga['attributes']['ageRatingGuide'],
            'subtype' => $manga['attributes']['subtype'],
            'status' => $manga['attributes']['status'],
            'posterImageUrl' => $this->getImageUrl($manga['attributes']['posterImage']),
            'coverImageUrl' => $this->getImageUrl($manga['attributes']['coverImage']),
            'chapterCount' => $manga['attributes']['chapterCount'],
            'volumeCount' => $manga['attributes']['volumeCount'],
            'genres' => $this->getMangaGenres($manga['id']),
            'categories' => $this->getCategories($manga['id']),
            'mappings' => $this->getMappings($manga['id'])
        ];
        return $out;
    }
    private function getMangaGenres(int $mangaId): array
    {
        $ids = [];
        foreach ($this->api->getMangaGenres($mangaId)->json('data') as $item) {
            $ids[] = 'genre:'.$item['id'];
        }
        return array_values($this->getCache()->many($ids));
    }
    private function getCategories(int $mangaId): array
    {
        $ids = [];
        foreach ($this->api->getMangaCategories($mangaId)->json('data') as $item) {
            $ids[] = 'category:'.$item['id'];
        }
        return array_values($this->getCache()->many($ids));
    }
    private function getMappings(int $mangaId): array //relations to external websites
    {
        $ids = [];
        foreach ($this->api->getMangaMappings($mangaId)->json('data') as $item) {
            $ids[] = 'mapping:'.$item['id'];
        }
        return array_values($this->getCache()->many($ids));
    }
    private function getImageUrl(?array $mangaImage = null): ?string
    {
        if (is_null($mangaImage)) {
            return null;
        }
        //TODO actually has lot more, process it
        if (isset($mangaImage['original'])) {
            return $mangaImage['original'];
        }
        return null;
    }
    private function getCache(): RedisTaggedCache
    {
        return $this->redis->tags('kitsu:');
    }
    private function initializeCategories()
    {
        $limit = 20;
        $offset = 0;
        do {
            $response = $this->api->getCategories($offset, $limit);
            foreach ($response->json('data') as $item) {
                $this->getCache()->put(
                    'category:'.$item['id'],
                    [
                        'id' => $item['id'],
                        'name' => $item['attributes']['title'],
                        'description' => $item['attributes']['description'],
                        'nsfw' => $item['attributes']['nsfw'],
                    ]
                );
            }
            $offset += $limit;
        } while (!empty($response->json('data')));

    }
    private function initializeGenres()
    {
        $limit = 20;
        $offset = 0;
        do {
            $response = $this->api->getGenres($offset, $limit);
            foreach ($response->json('data') as $item) {
                $this->getCache()->put(
                    'genre:'.$item['id'],
                    $item['attributes']
                );
            }
            $offset += $limit;
        } while (!empty($response->json('data')));
    }
    private function initializeMappings()
    {
        $limit = 20;
        $offset = 0;
        do {
            $response = $this->api->getMappings($offset, $limit);
            foreach ($response->json('data') as $item) {
                $this->getCache()->put(
                    'mapping:'.$item['id'],
                    $item['attributes']
                );
            }
            $offset += $limit;
        } while (!empty($response->json('data')));
    }
}
