<?php

namespace App\ExternalApi\Kitsu;

use App\ExternalApi\Kitsu\Exception\LimitSizeException;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;
class Api
{
    public function getMappings(int $offset = 0, int $limit = 10): Response
    {
        return $this->get('mappings', $offset, $limit);
    }
    public function getCategories(int $offset = 0, int $limit = 10): Response
    {
        return $this->get('categories', $offset, $limit);
    }
    public function getGenres(int $offset = 0, int $limit = 10): Response
    {
        return $this->get('genres', $offset, $limit);
    }
    public function getManga(int $offset = 0, int $limit = 10): Response
    {
        return $this->get('manga', $offset, $limit);
    }
    public function getMangaById(int $mangaId): Response
    {
        return $this->get('manga/'.$mangaId);
    }
    public function getMangaGenres(int $mangaId): Response
    {
        return $this->get('manga/'.$mangaId.'/relationships/genres');
    }
    public function getMangaCategories(int $mangaId): Response
    {
        return $this->get('manga/'.$mangaId.'/relationships/categories');
    }
    public function getMangaMappings(int $mangaId): Response
    {
        return $this->get('manga/'.$mangaId.'/relationships/mappings');
    }
    private function get(string $resource, int $offset = 0, int $limit = 10): Response
    {
        if ($limit > 20) {
            throw new LimitSizeException('Limit should be less than 21');
        }
        return Http::timeout(60)->get(
            "https://kitsu.io/api/edge/{$resource}",
            [
                'page[offset]' => $offset,
                'page[limit]' => $limit
            ]
        );
    }
}
