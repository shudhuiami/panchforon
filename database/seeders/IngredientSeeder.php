<?php

namespace Database\Seeders;

use App\Enums\UnitDimension;
use App\Models\Ingredient;
use App\Models\IngredientAlias;
use Illuminate\Database\Seeder;

class IngredientSeeder extends Seeder
{
    public function run(): void
    {
        /** @var array<string, array{dimension: UnitDimension, aliases: array<int, string>}> $data */
        $data = [
            'onion' => [
                'dimension' => UnitDimension::Count,
                'aliases' => ['onions', 'peyaj', 'pyaj', 'red onion', 'brown onion', 'yellow onion'],
            ],
            'garlic' => [
                'dimension' => UnitDimension::Count,
                'aliases' => ['garlic clove', 'garlic cloves', 'roshun', 'rasun'],
            ],
            'ginger' => [
                'dimension' => UnitDimension::Mass,
                'aliases' => ['fresh ginger', 'ada', 'adrak'],
            ],
            'coriander' => [
                'dimension' => UnitDimension::Count,
                'aliases' => ['cilantro', 'fresh coriander', 'dhania', 'dhone pata'],
            ],
            'turmeric' => [
                'dimension' => UnitDimension::Mass,
                'aliases' => ['turmeric powder', 'ground turmeric', 'holud', 'haldi'],
            ],
            'chili' => [
                'dimension' => UnitDimension::Count,
                'aliases' => ['chillies', 'chilies', 'green chili', 'green chilli', 'morich', 'kacha morich'],
            ],
            'chili powder' => [
                'dimension' => UnitDimension::Mass,
                'aliases' => ['red chili powder', 'morich gura', 'lal morich'],
            ],
            'cumin' => [
                'dimension' => UnitDimension::Mass,
                'aliases' => ['ground cumin', 'cumin powder', 'cumin seed', 'cumin seeds', 'jeera', 'jira'],
            ],
            'coriander powder' => [
                'dimension' => UnitDimension::Mass,
                'aliases' => ['ground coriander', 'dhone gura'],
            ],
            'garam masala' => [
                'dimension' => UnitDimension::Mass,
                'aliases' => ['allspice blend', 'gorom moshla'],
            ],
            'mustard oil' => [
                'dimension' => UnitDimension::Volume,
                'aliases' => ['shorsher tel', 'shorshe tel', 'mustard seed oil'],
            ],
            'vegetable oil' => [
                'dimension' => UnitDimension::Volume,
                'aliases' => ['cooking oil', 'oil', 'canola oil', 'sunflower oil', 'shonapathi tel'],
            ],
            'salt' => [
                'dimension' => UnitDimension::None,
                'aliases' => ['table salt', 'sea salt', 'lobon', 'noon'],
            ],
            'chicken breast' => [
                'dimension' => UnitDimension::Mass,
                'aliases' => ['chicken breasts', 'boneless chicken', 'murgir mangsho', 'chicken'],
            ],
            'beef' => [
                'dimension' => UnitDimension::Mass,
                'aliases' => ['beef meat', 'gorur mangsho', 'stewing beef', 'beef chuck'],
            ],
            'ilish' => [
                'dimension' => UnitDimension::Mass,
                'aliases' => ['hilsa', 'hilsa fish', 'ilish mach'],
            ],
            'basmati rice' => [
                'dimension' => UnitDimension::Mass,
                'aliases' => ['rice', 'polao rice', 'chinigura rice', 'chaal'],
            ],
            'red lentil' => [
                'dimension' => UnitDimension::Mass,
                'aliases' => ['red lentils', 'masoor dal', 'musur dal', 'dal'],
            ],
            'eggplant' => [
                'dimension' => UnitDimension::Count,
                'aliases' => ['aubergine', 'brinjal', 'begun'],
            ],
            'potato' => [
                'dimension' => UnitDimension::Count,
                'aliases' => ['potatoes', 'alu', 'aloo'],
            ],
            'tomato' => [
                'dimension' => UnitDimension::Count,
                'aliases' => ['tomatoes', 'fresh tomato', 'tometo'],
            ],
            'flour' => [
                'dimension' => UnitDimension::Mass,
                'aliases' => ['all-purpose flour', 'plain flour', 'maida', 'atta'],
            ],
            'milk' => [
                'dimension' => UnitDimension::Volume,
                'aliases' => ['whole milk', 'dudh'],
            ],
            'egg' => [
                'dimension' => UnitDimension::Count,
                'aliases' => ['eggs', 'dim'],
            ],
            'sugar' => [
                'dimension' => UnitDimension::Mass,
                'aliases' => ['granulated sugar', 'white sugar', 'chini'],
            ],
            'scallion' => [
                'dimension' => UnitDimension::Count,
                'aliases' => ['scallions', 'spring onion', 'spring onions', 'green onion'],
            ],
            'panch phoron' => [
                'dimension' => UnitDimension::Mass,
                'aliases' => ['panchforon', 'bengali five spice', 'five spice'],
            ],
        ];

        foreach ($data as $canonical => $info) {
            $ingredient = Ingredient::firstOrCreate(
                ['canonical_name' => $canonical],
                ['default_dimension' => $info['dimension']]
            );

            foreach ($info['aliases'] as $alias) {
                IngredientAlias::firstOrCreate(
                    ['alias' => $alias],
                    ['ingredient_id' => $ingredient->id]
                );
            }
        }
    }
}
