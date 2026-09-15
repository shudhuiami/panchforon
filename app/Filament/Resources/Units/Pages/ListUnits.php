<?php

namespace App\Filament\Resources\Units\Pages;

use App\Filament\Resources\Units\UnitResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListUnits extends ListRecords
{
    protected static string $resource = UnitResource::class;

    /**
     * Said once, at the top, because nothing else on this screen looks
     * dangerous: it is five short columns of reference data.
     */
    public function getSubheading(): ?string
    {
        return 'Reference data the shopping list runs on. Recipe lines store the symbol, and these factors are what a list converts them through before it adds anything up — so an edit here changes every list that touches that unit.';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Add unit'),
        ];
    }
}
