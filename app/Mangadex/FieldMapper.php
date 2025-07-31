<?php

namespace App\Mangadex;

class FieldMapper
{
    public function map(array $item): array
    {
        $fields = [
            'name' => $this->getTitle($item),
            'alt_titles' => implode(PHP_EOL, $this->getAltTitles($item)),
            'description' => $this->getDescription($item),
            'author' => $this->getAuthor($item),
            'genres' => implode(',', $this->getTags($item)),
            'adult' => $this->isAdult($item['attributes']['contentRating']),
            'target' => mb_ucfirst((string)$item['attributes']['publicationDemographic']),
            'status' => mb_ucfirst((string)$item['attributes']['status']),
        ];
        return $fields;
    }
    private function getTitle(array $item): string
    {
        return $item['attributes']['title']['en'] ?? '';
    }
    private function getAltTitles(array $manga): array
    {
        $out = [];
        foreach ($manga['attributes']['altTitles'] as $altTitle) {
            foreach ($altTitle as $string) {
                $out[] = $string;
            }
        }
        return $out;
    }
    private function getAuthor(array $item): string
    {
        foreach ($item['relationships'] as $relationship) {
            if ($relationship['type'] === 'author') {
                return $relationship['attributes']['name'];
            }
        }
        return '';
    }
    public function mapChapter(array $chapter): array
    {
        return [
            'volume' => $chapter['attributes']['volume'],
            'chapter' => $chapter['attributes']['chapter'],
            'title' => $chapter['attributes']['title'],
            'language' => $chapter['attributes']['translatedLanguage'],
        ];
    }
    protected function getTags(array $manga): array
    {
        $genres = [];
        foreach ($manga['attributes']['tags'] as $tag) {
            $genres[] = $tag['attributes']['name']['en'];
        }
        return $genres;
    }
    protected function getCoverImageUrl(array $manga): ?string
    {
        $url = 'https://uploads.mangadex.org/covers/'.$manga['id'].'/';
        foreach ($manga['relationships'] as $relationship) {
            if ($relationship['type'] === 'cover_art') {
                return $url.$relationship['attributes']['fileName'];
            }
        }
        return null;
    }
    protected function getDescription(array $manga): ?string
    {
        return $manga['attributes']['description']['en'] ?? null;
    }
    protected function getStatus(array $manga): ?string
    {
        return $manga['attributes']['status'];
    }
    protected function getYear(array $manga): ?int
    {
        return $manga['attributes']['year'];
    }
    private function isAdult(string $contentRating): bool
    {
        $adultRatings = array_flip([
            'erotica',
//            'suggestive',
            'pornographic'
        ]);
        return isset($adultRatings[$contentRating]);
    }
}
