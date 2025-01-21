<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MangadexManga extends Model
{
    /** @use HasFactory<\Database\Factories\MangadexMangaFactory> */
    use HasFactory;

    protected $fillable = ['mangadex_id', 'comic'];

    public function comic(): BelongsTo
    {
        return $this->belongsTo(Comic::class);
    }
    public function chapters(): HasMany
    {
        return $this->hasMany(MangadexChapter::class);
    }
}
