<?php

namespace App\Filament\Studio\Resources\Recipes\Schemas;

use App\Enums\ModerationStatus;
use App\Enums\SpiceLevel;
use App\Filament\Studio\Resources\Recipes\RecipeResource;
use App\Models\Recipe;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * What a creator sees of their own recipe.
 *
 * Narrower than the admin panel's infolist: no author (it is always them), no
 * source (everything they wrote is a user submission), no moderator name and
 * no Bayesian ranking score. Those are the catalogue's business, not the
 * author's.
 */
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

                        TextEntry::make('name_bn')
                            ->label('Bengali title')
                            ->columnSpanFull()
                            ->placeholder('Not given'),

                        TextEntry::make('cuisine')->placeholder('-'),
                        TextEntry::make('category')->placeholder('-'),

                        TextEntry::make('created_at')
                            ->label('Added')
                            ->date('j M Y'),

                        TextEntry::make('moderated_at')
                            ->label('Last reviewed')
                            ->dateTime('j M Y, H:i')
                            ->placeholder('Never'),
                    ]),

                Section::make('Timing and heat')
                    ->columns(4)
                    ->schema([
                        TextEntry::make('prep_minutes')
                            ->label('Prep')
                            ->formatStateUsing(fn (int $state): string => "{$state} min")
                            ->placeholder('Not given'),

                        TextEntry::make('cook_minutes')
                            ->label('Cook')
                            ->formatStateUsing(fn (int $state): string => "{$state} min")
                            ->placeholder('Not given'),

                        TextEntry::make('total_minutes')
                            ->label('Total')
                            ->state(function (Recipe $record): ?string {
                                $total = RecipeResource::totalMinutes($record);

                                return $total === null ? null : "{$total} min";
                            })
                            ->weight('medium')
                            ->placeholder('-'),

                        TextEntry::make('spice_level')
                            ->label('Spice level')
                            ->badge()
                            ->formatStateUsing(fn (SpiceLevel $state): string => $state->label())
                            ->color(fn (SpiceLevel $state): string => $state->color())
                            ->placeholder('Not stated'),
                    ]),

                Section::make('Ratings')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('stat.ratings_avg')
                            ->label('Average')
                            ->numeric(decimalPlaces: 2)
                            ->placeholder('Unrated'),

                        TextEntry::make('stat.ratings_count')
                            ->label('Number of ratings')
                            ->placeholder('0'),
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
