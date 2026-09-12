<?php

namespace App\Filament\Resources\ContentBlocks\Pages;

use App\Filament\Resources\ContentBlocks\ContentBlockResource;
use App\Services\ContentRepository;
use Filament\Resources\Pages\ListRecords;

class ListContentBlocks extends ListRecords
{
    protected static string $resource = ContentBlockResource::class;

    /**
     * Every string the storefront can render is listed, not only the ones an
     * admin has already touched, so nothing editable is invisible.
     */
    public function mount(): void
    {
        parent::mount();

        app(ContentRepository::class)->seedMissingBlocks();
    }
}
