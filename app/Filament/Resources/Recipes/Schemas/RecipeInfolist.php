<?php

namespace App\Filament\Resources\Recipes\Schemas;

use App\Enums\ModerationStatus;
use App\Enums\RecipeSource;
use App\Models\Recipe;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RecipeInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->columns(4)
                    ->schema([
                        TextEntry::make('title')
                            ->columnSpan(3)
                            ->size('lg')
                            ->weight('bold'),

                        TextEntry::make('moderation_status')
                            ->label('Status')
                            ->badge()
                            ->formatStateUsing(fn (ModerationStatus $state): string => $state->label())
                            ->color(fn (ModerationStatus $state): string => $state->color())
                            ->icon(fn (ModerationStatus $state): string => $state->icon()),

                        TextEntry::make('source')
                            ->label('Source')
                            ->badge()
                            ->formatStateUsing(fn (RecipeSource $state): string => $state === RecipeSource::Api ? 'TheMealDB' : 'User submitted')
                            ->color(fn (RecipeSource $state): string => $state === RecipeSource::Api ? 'gray' : 'primary'),

                        TextEntry::make('user.name')
                            ->label('Author')
                            ->placeholder('Imported'),

                        TextEntry::make('cuisine')->placeholder('-'),
                        TextEntry::make('category')->placeholder('-'),
                    ]),

                Section::make('Ratings')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('stat.ratings_avg')
                            ->label('Average')
                            ->numeric(decimalPlaces: 2)
                            ->placeholder('Unrated'),

                        TextEntry::make('stat.ratings_count')
                            ->label('Number of ratings')
                            ->placeholder('0'),

                        TextEntry::make('stat.bayesian_score')
                            ->label('Ranking score')
                            ->numeric(decimalPlaces: 4)
                            ->placeholder('-')
                            ->tooltip('Bayesian score used to order the public recipe list.'),
                    ]),

                Section::make('Moderation history')
                    ->columns(3)
                    ->visible(fn (Recipe $record): bool => $record->moderated_at !== null)
                    ->schema([
                        TextEntry::make('moderator.name')
                            ->label('Last actioned by')
                            ->placeholder('-'),

                        TextEntry::make('moderated_at')
                            ->label('Last actioned')
                            ->dateTime('j M Y, H:i')
                            ->placeholder('-'),
                    ]),

                Section::make('Instructions')
                    ->collapsible()
                    ->schema([
                        TextEntry::make('instructions')
                            ->hiddenLabel()
                            ->prose(),
                    ]),

                Section::make('Media and links')
                    ->columns(2)
                    ->collapsed()
                    ->schema([
                        ImageEntry::make('image_url')
                            ->label('Image')
                            ->height(160)
                            ->placeholder('No image'),

                        TextEntry::make('source_url')
                            ->label('Source URL')
                            ->url(fn (Recipe $record): ?string => $record->source_url)
                            ->openUrlInNewTab()
                            ->placeholder('-'),
                    ]),
            ]);
    }
}
