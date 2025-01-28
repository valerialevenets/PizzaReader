<?php

namespace App\Models\Factory;
use App\Models\Chapter;
use App\Models\Page;

class PageFactory
{
    public function create(Chapter $chapter, string $filename, string $content):Page
    {
        return new Page($this->getPageData($chapter, $filename, $content));
    }
    private function getPageData(Chapter $chapter, string $filename, string $content): array
    {
        $imagedata = getimagesizefromstring($content);
        return [
            'chapter_id' => $chapter->id,
            'filename' => $filename,
            'size' => mb_strlen($content, '8bit'),
            'width' => $imagedata[0],
            'height' => $imagedata[1],
            'mime' => $imagedata['mime'],
            'hidden' => false,
            'licensed' => false,
        ];
    }
}
