<?php

namespace App\Storage;

use Illuminate\Support\Facades\Storage as OriginalStorage;
class Storage
{
    public function createVisibleDirectory(string $path)
    {
        OriginalStorage::makeDirectory($path);
        OriginalStorage::setVisibility($path, 'public');
    }
    public function put(string $path, string $name, string $content): bool
    {
        return OriginalStorage::disk('local')->put("{$path}/$name", $content);
    }
    public function deleteDirectory(string $path): bool
    {
        return OriginalStorage::deleteDirectory($path);
    }
}
