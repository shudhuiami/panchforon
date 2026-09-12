<?php

namespace App\Filament\Resources\ContentBlocks\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ContentBlockForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        TextInput::make('label')
                            ->label('Where this appears')
                            ->disabled()
                            ->dehydrated(false),

                        TextInput::make('key')
                            ->label('Reference')
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('Set in code, so the storefront always knows where to put it.'),

                        Textarea::make('body')
                            ->label('Wording')
                            ->required()
                            ->rows(4)
                            ->maxLength(1000)
                            ->helperText('Plain text. Leave it blank to go back to the built-in wording.')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
