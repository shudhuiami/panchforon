<?php

namespace App\Models;

use Database\Factories\PageFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * A standalone content page written in the admin panel, such as About or
 * Privacy. The body is markdown; it is rendered server-side with HTML input
 * escaped, so nothing an editor pastes can inject markup into the storefront.
 *
 * @property string $body
 */
class Page extends Model
{
    /** @use HasFactory<PageFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'slug',
        'title',
        'excerpt',
        'body',
        'meta_description',
        'is_published',
        'show_in_footer',
        'position',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'show_in_footer' => 'boolean',
            'position' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => PageCacheKeys::forget());
        static::deleted(fn () => PageCacheKeys::forget());
    }

    /**
     * The body as safe HTML.
     */
    public function renderedBody(): string
    {
        return Str::markdown($this->body, ['html_input' => 'escape', 'allow_unsafe_links' => false]);
    }

    /**
     * @param  Builder<Page>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('is_published', true);
    }
}
