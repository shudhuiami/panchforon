<?php

namespace App\Filament\Resources\Units\Pages;

use App\Filament\Resources\Units\UnitResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUnit extends CreateRecord
{
    protected static string $resource = UnitResource::class;

    /**
     * Adding a unit is the safe end of this screen: a symbol nothing has used
     * yet simply starts resolving. It is how an importer's "pinch" stops being
     * an unrecognised unit.
     */
    public function getSubheading(): ?string
    {
        return 'The symbol is fixed once this is saved, because recipe lines store it as text. Get it right here rather than renaming it later.';
    }
}
