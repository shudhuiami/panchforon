<?php

namespace App\Models;

use Database\Factories\ContentBlockFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A short piece of storefront copy an admin can edit, such as the home page
 * headline. Plain text, rendered as-is by React, so no markup is involved.
 */
class ContentBlock extends Model
{
    /** @use HasFactory<ContentBlockFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = ['key', 'label', 'body'];

    protected static function booted(): void
    {
        static::saved(fn () => PageCacheKeys::forget());
        static::deleted(fn () => PageCacheKeys::forget());
    }
}
