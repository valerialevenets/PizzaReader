<?php

namespace App\Mangadex\Api;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class Manga extends MangadexApi
{
    private readonly array $credentials;
    private const   MANGA_ENDPOINT = '/manga';
    private const RELATION_ENDPOINT = '/relation';
    private const RANDOM_ENDPOINT = '/random';
    private const TAG_ENDPOINT = '/tag';

    public function __construct()
    {
        $this->credentials = [
            'username'=>env('MANGADEX_USERNAME'),
            'password'=>env('MANGADEX_PASSWORD'),
            'client_id' => env('MANGADEX_CLIENT_ID'),
            'client_secret' => env('MANGADEX_CLIENT_SECRET')
        ];
        parent::__construct();
    }


    /**
     * Get Manga Lists
     *
     * @param array $queryParams
     *
     * @return object
     *
     */
    public function getMangas (array $queryParams) : object
    {
        $query = $this->buildQueryParams($queryParams);

        $response = $this->client->request('GET', self::MANGA_ENDPOINT, [
            'query' => $query,
        ]);

        return $this->handleResponse($response);
    }

    /**
     * Get Manga by MangaDex ID
     *
     * @param string $id
     * @param bool $withRelationship
     * @param array $queryParams
     *
     * @return object
     */

    public function getMangaById (string $id, bool $withRelationship = false, array $queryParams = []) : object
    {
        $pathParam = $withRelationship ? $id . self::RELATION_ENDPOINT : $id;

        $query = !empty($queryParams) ? $this->buildQueryParams($queryParams) : [];

        $response = $this->client->request('GET', self::MANGA_ENDPOINT . '/' . $pathParam, [
            'query' => $query,
        ]);

        return $this->handleResponse($response);
    }
    public function getMangaChapters(string $mangaId, int $limit = 10, int $offset = 0) : Response
    {
        return Http::timeout(60)->get(
            $this->getHostUrl().self::MANGA_ENDPOINT . '/' . $mangaId . '/feed', ['limit' => $limit, 'offset' => $offset]);
    }
    public function getChapterImages(string $chapterId) : object
    {
        return Http::timeout(60)->get(
            $this->getHostUrl().'/at-home/server/' . $chapterId);
    }
    public function getChapterImage(string $baseUrl, string $chapterHash, string $imageId)
    {
        return Http::timeout(60)->get("{$baseUrl}/data/{$chapterHash}/{$imageId}");
    }

    /**
     * Get a random Manga
     *
     * @param array $queryParams
     *
     * @return object
     */

    public function getRandomManga (array $queryParams = []) : object
    {
        $query = $this->buildQueryParams($queryParams);

        $response = $this->client->request('GET', self::MANGA_ENDPOINT . self::RANDOM_ENDPOINT, [
            'query' => $query,
        ]);

        return $this->handleResponse($response);
    }

    /**
     * Get all manga tags
     *
     * @return object
     */
    public function getMangaTags () : object
    {
        $response = $this->client->request('GET', self::MANGA_ENDPOINT . self::TAG_ENDPOINT);

        return $this->handleResponse($response);
    }

    /**
     * TODO: Get manga volumes and chapters
     *
     * @param array $queryParams
     *
     * @return object
     */
    public function getMangaAggregate(Type $var = null)
    {
        //
    }
    public function auth()
    {
        $response = Http::asForm()->post(
            'https://auth.mangadex.org/realms/mangadex/protocol/openid-connect/token',
            array_merge($this->credentials, ['grant_type'=>'password'])
        );
        return json_decode($response->getBody()->getContents(), true);
    }
    public function getList(string $token, int $limit = 10, int $offset = 0): Response
    {
        $url = 'https://api.mangadex.org/user/follows/manga';
        return Http::withHeaders(['Authorization' => 'Bearer '.$token, 'accept' => 'application/json'])
            ->get($url, ['limit' => $limit, 'offset' => $offset]);
    }

    public function getMangaCover(string $mangaId, string $coverId)
    {
        $filename = Http::timeout(60)->get("https://api.mangadex.org/cover/{$coverId}")->json()['data']['attributes']['fileName'];
        return Http::timeout(60)->get("https://uploads.mangadex.org/covers/$mangaId/{$filename}")->body();
    }
}
