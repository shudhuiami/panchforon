<?php

namespace App\Filament\Resources\Pages\Schemas;

use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class PageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('The page')
                    ->description('The title and address people see, and the words on it.')
                    ->schema([
                        TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            /**
                             * The address is suggested from the title while the
                             * page is new, then left alone: changing it later
                             * would break every link already shared.
                             */
                            ->afterStateUpdated(function (?string $state, callable $set, string $operation): void {
                                if ($operation === 'create' && $state !== null) {
                                    $set('slug', Str::slug($state));
                                }
                            }),

                        TextInput::make('slug')
                            ->label('Address')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->rule('regex:/^[a-z0-9\-]+$/')
                            ->helperText(fn (?string $state): string => 'Lives at /p/'.($state ?: 'your-address').'. Changing it breaks links already shared.')
                            ->maxLength(255),

                        Textarea::make('excerpt')
                            ->label('Standfirst')
                            ->helperText('One line under the title. Optional.')
                            ->rows(2)
                            ->maxLength(255),

                        MarkdownEditor::make('body')
                            ->label('Body')
                            ->required()
                            ->helperText('Markdown. Any HTML you paste is shown as text, not rendered.')
                            ->columnSpanFull(),
                    ]),

                Section::make('Publishing')
                    ->description('Whether people can reach it, and where it is listed.')
                    ->schema([
                        Toggle::make('is_published')
                            ->label('Published')
                            ->helperText('Off keeps it a draft: the address answers as though the page does not exist.'),

                        Toggle::make('show_in_footer')
                            ->label('List in the footer')
                            ->helperText('Published pages only.'),

                        TextInput::make('position')
                            ->label('Order in the footer')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->maxValue(999)
                            ->helperText('Lower numbers come first.'),

                        Textarea::make('meta_description')
                            ->label('Link preview description')
                            ->helperText('Shown when the page is shared. Falls back to the standfirst.')
                            ->rows(2)
                            ->maxLength(255),
                    ])
                    ->columns(2),
            ]);
    }
}
