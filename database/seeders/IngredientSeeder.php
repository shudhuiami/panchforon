<?php

namespace Database\Seeders;

use App\Enums\UnitDimension;
use App\Models\Ingredient;
use App\Models\IngredientAlias;
use Illuminate\Database\Seeder;

/**
 * The ingredient dictionary: what each thing *is*, independent of how any one
 * recipe measures it.
 *
 * Two rules decide whether a name here becomes its own row or an alias of an
 * existing one:
 *
 *  - Same thing in the trolley, different words on the page → alias.
 *    "ginger paste" is ginger; "garlic paste" is garlic.
 *  - Different thing in the trolley → its own row, even when the words are
 *    close. Green chili, dried red chili and chili powder are three purchases,
 *    and chinigura polao rice is not basmati.
 *
 * default_dimension is only the fallback for a line with no usable unit — the
 * unit on the recipe row decides — but it should still say how this dataset
 * actually measures the thing, so the vegetables that are weighed are mass.
 */
class IngredientSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->dictionary() as $canonical => $info) {
            $ingredient = Ingredient::updateOrCreate(
                ['canonical_name' => $canonical],
                [
                    'default_dimension' => $info['dimension'],
                    'name_bn' => $info['name_bn'],
                    'preferred_unit' => $info['preferred_unit'],
                    'is_shoppable' => $info['is_shoppable'] ?? true,
                ]
            );

            foreach ($info['aliases'] as $alias) {
                /**
                 * updateOrCreate rather than firstOrCreate: an alias that has
                 * moved to a better home — "chicken" no longer meaning chicken
                 * breast — has to follow the dictionary, not the old row.
                 */
                IngredientAlias::updateOrCreate(
                    ['alias' => $alias],
                    ['ingredient_id' => $ingredient->id]
                );
            }
        }
    }

    /**
     * @return array<string, array{
     *     dimension: UnitDimension,
     *     name_bn: ?string,
     *     preferred_unit: ?string,
     *     aliases: list<string>,
     *     is_shoppable?: bool,
     * }>
     */
    private function dictionary(): array
    {
        return [
            // Vegetables and fresh produce. Weighed, not counted: the dataset
            // says "400 g potato, 3 medium", and the household count lives on
            // the recipe row as a note.
            'onion' => [
                'dimension' => UnitDimension::Mass,
                'name_bn' => 'পেঁয়াজ',
                'preferred_unit' => 'g',
                'aliases' => ['onions', 'peyaj', 'pyaj', 'red onion', 'brown onion', 'yellow onion'],
            ],
            'garlic' => [
                'dimension' => UnitDimension::Mass,
                'name_bn' => 'রসুন',
                'preferred_unit' => 'g',
                'aliases' => ['garlic clove', 'garlic cloves', 'garlic paste', 'roshun', 'rasun'],
            ],
            'ginger' => [
                'dimension' => UnitDimension::Mass,
                'name_bn' => 'আদা',
                'preferred_unit' => 'g',
                'aliases' => ['fresh ginger', 'ginger paste', 'ada', 'adrak'],
            ],
            'potato' => [
                'dimension' => UnitDimension::Mass,
                'name_bn' => 'আলু',
                'preferred_unit' => 'g',
                'aliases' => ['potatoes', 'alu', 'aloo'],
            ],
            'tomato' => [
                'dimension' => UnitDimension::Mass,
                'name_bn' => 'টমেটো',
                'preferred_unit' => 'g',
                'aliases' => ['tomatoes', 'fresh tomato', 'tometo'],
            ],
            'eggplant' => [
                'dimension' => UnitDimension::Mass,
                'name_bn' => 'বেগুন',
                'preferred_unit' => 'g',
                'aliases' => ['aubergine', 'brinjal', 'begun'],
            ],
            'cauliflower' => [
                'dimension' => UnitDimension::Mass,
                'name_bn' => 'ফুলকপি',
                'preferred_unit' => 'g',
                'aliases' => ['cauliflower florets', 'phulkopi', 'fulkopi'],
            ],
            'green peas' => [
                'dimension' => UnitDimension::Mass,
                'name_bn' => 'মটরশুঁটি',
                'preferred_unit' => 'g',
                'aliases' => ['peas', 'green pea', 'motorshuti', 'matarshuti'],
            ],
            'coriander' => [
                'dimension' => UnitDimension::Mass,
                'name_bn' => 'ধনেপাতা',
                'preferred_unit' => 'g',
                'aliases' => ['cilantro', 'fresh coriander', 'coriander leaves', 'dhania', 'dhone pata'],
            ],
            'scallion' => [
                'dimension' => UnitDimension::Count,
                'name_bn' => 'পেঁয়াজ পাতা',
                'preferred_unit' => 'piece',
                'aliases' => ['scallions', 'spring onion', 'spring onions', 'green onion'],
            ],

            // Chilies. Three separate purchases, however alike the names look.
            'chili' => [
                'dimension' => UnitDimension::Count,
                'name_bn' => 'কাঁচা মরিচ',
                'preferred_unit' => 'piece',
                'aliases' => ['chillies', 'chilies', 'green chili', 'green chilli', 'morich', 'kacha morich'],
            ],
            'dried red chili' => [
                'dimension' => UnitDimension::Count,
                'name_bn' => 'শুকনা মরিচ',
                'preferred_unit' => 'piece',
                'aliases' => ['dried red chilli', 'dry red chili', 'dried chili', 'shukna morich', 'shukno morich'],
            ],
            'chili powder' => [
                'dimension' => UnitDimension::Mass,
                'name_bn' => 'মরিচ গুঁড়া',
                'preferred_unit' => 'g',
                'aliases' => ['red chili powder', 'morich gura', 'lal morich'],
            ],

            // Ground spices and the whole garam masala spices.
            'turmeric' => [
                'dimension' => UnitDimension::Mass,
                'name_bn' => 'হলুদ',
                'preferred_unit' => 'g',
                'aliases' => ['turmeric powder', 'ground turmeric', 'holud', 'haldi'],
            ],
            'cumin' => [
                'dimension' => UnitDimension::Mass,
                'name_bn' => 'জিরা',
                'preferred_unit' => 'g',
                'aliases' => ['ground cumin', 'cumin powder', 'cumin seed', 'cumin seeds', 'jeera', 'jira'],
            ],
            'coriander powder' => [
                'dimension' => UnitDimension::Mass,
                'name_bn' => 'ধনে গুঁড়া',
                'preferred_unit' => 'g',
                'aliases' => ['ground coriander', 'dhone gura'],
            ],
            'garam masala' => [
                'dimension' => UnitDimension::Mass,
                'name_bn' => 'গরম মসলা',
                'preferred_unit' => 'g',
                'aliases' => ['garam masala powder', 'allspice blend', 'gorom moshla'],
            ],
            'black pepper' => [
                'dimension' => UnitDimension::Mass,
                'name_bn' => 'গোলমরিচ',
                'preferred_unit' => 'g',
                'aliases' => ['black pepper powder', 'ground black pepper', 'pepper', 'golmorich', 'gol morich'],
            ],
            'bay leaf' => [
                'dimension' => UnitDimension::Count,
                'name_bn' => 'তেজপাতা',
                'preferred_unit' => 'piece',
                'aliases' => ['bay leaves', 'tejpata', 'tej pata'],
            ],
            'cinnamon' => [
                'dimension' => UnitDimension::Count,
                'name_bn' => 'দারুচিনি',
                'preferred_unit' => 'piece',
                'aliases' => ['cinnamon stick', 'cinnamon sticks', 'daruchini', 'darchini'],
            ],
            'green cardamom' => [
                'dimension' => UnitDimension::Count,
                'name_bn' => 'এলাচ',
                'preferred_unit' => 'piece',
                'aliases' => ['cardamom', 'cardamom pod', 'cardamom pods', 'elach', 'elachi', 'choto elach'],
            ],
            'clove' => [
                'dimension' => UnitDimension::Count,
                'name_bn' => 'লবঙ্গ',
                'preferred_unit' => 'piece',
                'aliases' => ['cloves', 'lobongo', 'laung'],
            ],
            'nigella' => [
                'dimension' => UnitDimension::Mass,
                'name_bn' => 'কালোজিরা',
                'preferred_unit' => 'g',
                'aliases' => ['nigella seed', 'nigella seeds', 'kalojira', 'kalonji', 'black cumin'],
            ],
            'panch phoron' => [
                'dimension' => UnitDimension::Mass,
                'name_bn' => 'পাঁচফোড়ন',
                'preferred_unit' => 'g',
                'aliases' => ['panchforon', 'bengali five spice', 'five spice'],
            ],
            'poppy seed' => [
                'dimension' => UnitDimension::Mass,
                'name_bn' => 'পোস্ত',
                'preferred_unit' => 'g',
                'aliases' => ['poppy seeds', 'poppy seed paste', 'posto', 'khus khus'],
            ],
            'salt' => [
                /**
                 * None on purpose: "salt to taste" is the common line, and a
                 * line with no unit should stay off the shopping list rather
                 * than merge into an invented amount. Rows that do give grams
                 * still merge by mass, because the row's unit decides.
                 */
                'dimension' => UnitDimension::None,
                'name_bn' => 'লবণ',
                'preferred_unit' => 'g',
                'aliases' => ['table salt', 'sea salt', 'lobon', 'noon'],
            ],
            'sugar' => [
                'dimension' => UnitDimension::Mass,
                'name_bn' => 'চিনি',
                'preferred_unit' => 'g',
                'aliases' => ['granulated sugar', 'white sugar', 'chini'],
            ],

            // Meat and fish.
            'chicken' => [
                'dimension' => UnitDimension::Mass,
                'name_bn' => 'মুরগির মাংস',
                'preferred_unit' => 'g',
                'aliases' => ['whole chicken', 'chicken curry cut', 'chicken leg quarter', 'chicken leg quarters', 'murgir mangsho'],
            ],
            'chicken breast' => [
                'dimension' => UnitDimension::Mass,
                'name_bn' => 'মুরগির বুকের মাংস',
                'preferred_unit' => 'g',
                'aliases' => ['chicken breasts', 'boneless chicken', 'murgir buker mangsho'],
            ],
            'beef' => [
                'dimension' => UnitDimension::Mass,
                'name_bn' => 'গরুর মাংস',
                'preferred_unit' => 'g',
                'aliases' => ['beef meat', 'gorur mangsho', 'stewing beef', 'beef chuck'],
            ],
            'ilish' => [
                'dimension' => UnitDimension::Mass,
                'name_bn' => 'ইলিশ মাছ',
                'preferred_unit' => 'g',
                'aliases' => ['hilsa', 'hilsa fish', 'ilish mach'],
            ],
            'rui fish' => [
                'dimension' => UnitDimension::Mass,
                'name_bn' => 'রুই মাছ',
                'preferred_unit' => 'g',
                'aliases' => ['rui', 'rui mach', 'rui machh', 'rui fish steaks', 'rohu', 'rohu fish'],
            ],
            'egg' => [
                'dimension' => UnitDimension::Count,
                'name_bn' => 'ডিম',
                'preferred_unit' => 'piece',
                'aliases' => ['eggs', 'dim'],
            ],

            // Rice, dal and flour.
            'aromatic rice' => [
                /**
                 * Its own row rather than an alias of basmati: nobody cooking
                 * polao in Dhaka comes home with basmati.
                 */
                'dimension' => UnitDimension::Mass,
                'name_bn' => 'পোলাওয়ের চাল',
                'preferred_unit' => 'g',
                'aliases' => ['polao rice', 'pulao rice', 'aromatic polao rice', 'chinigura rice', 'chinigura', 'kalijira rice', 'shugandhi chal'],
            ],
            'basmati rice' => [
                'dimension' => UnitDimension::Mass,
                'name_bn' => 'বাসমতি চাল',
                'preferred_unit' => 'g',
                'aliases' => ['rice', 'basmati', 'chaal'],
            ],
            'red lentil' => [
                'dimension' => UnitDimension::Mass,
                'name_bn' => 'মসুর ডাল',
                'preferred_unit' => 'g',
                'aliases' => ['red lentils', 'masoor dal', 'musur dal', 'dal'],
            ],
            'moong dal' => [
                'dimension' => UnitDimension::Mass,
                'name_bn' => 'মুগ ডাল',
                'preferred_unit' => 'g',
                'aliases' => ['mung dal', 'mug dal', 'moong lentil', 'yellow moong dal', 'split moong dal'],
            ],
            'vermicelli' => [
                'dimension' => UnitDimension::Mass,
                'name_bn' => 'সেমাই',
                'preferred_unit' => 'g',
                'aliases' => ['semai', 'shemai', 'fine vermicelli', 'vermicelli noodles'],
            ],
            'flour' => [
                'dimension' => UnitDimension::Mass,
                'name_bn' => 'ময়দা',
                'preferred_unit' => 'g',
                'aliases' => ['all-purpose flour', 'plain flour', 'maida', 'atta'],
            ],

            // Dairy and fats.
            'milk' => [
                'dimension' => UnitDimension::Volume,
                'name_bn' => 'দুধ',
                'preferred_unit' => 'ml',
                'aliases' => ['whole milk', 'full-fat milk', 'full cream milk', 'dudh'],
            ],
            'milk powder' => [
                'dimension' => UnitDimension::Mass,
                'name_bn' => 'গুঁড়া দুধ',
                'preferred_unit' => 'g',
                'aliases' => ['powdered milk', 'dry milk', 'guro dudh'],
            ],
            'yogurt' => [
                'dimension' => UnitDimension::Mass,
                'name_bn' => 'দই',
                'preferred_unit' => 'g',
                'aliases' => ['plain yogurt', 'yoghurt', 'curd', 'doi', 'tok doi'],
            ],
            'ghee' => [
                'dimension' => UnitDimension::Mass,
                'name_bn' => 'ঘি',
                'preferred_unit' => 'g',
                'aliases' => ['clarified butter', 'ghi'],
            ],
            'mustard oil' => [
                'dimension' => UnitDimension::Volume,
                'name_bn' => 'সরিষার তেল',
                'preferred_unit' => 'ml',
                'aliases' => ['shorsher tel', 'shorshe tel', 'mustard seed oil'],
            ],
            'vegetable oil' => [
                'dimension' => UnitDimension::Volume,
                'name_bn' => 'সয়াবিন তেল',
                'preferred_unit' => 'ml',
                'aliases' => ['cooking oil', 'oil', 'soybean oil', 'soyabean oil', 'canola oil', 'sunflower oil', 'shonapathi tel'],
            ],

            // Nuts, dried fruit and the finishing touches.
            'cashew' => [
                'dimension' => UnitDimension::Mass,
                'name_bn' => 'কাজু বাদাম',
                'preferred_unit' => 'g',
                'aliases' => ['cashews', 'cashew nut', 'cashew nuts', 'cashew paste', 'kaju', 'kaju badam'],
            ],
            'raisin' => [
                'dimension' => UnitDimension::Mass,
                'name_bn' => 'কিশমিশ',
                'preferred_unit' => 'g',
                'aliases' => ['raisins', 'sultanas', 'kishmish'],
            ],
            'kewra water' => [
                'dimension' => UnitDimension::Volume,
                'name_bn' => 'কেওড়া জল',
                'preferred_unit' => 'ml',
                'aliases' => ['kewra', 'keora water', 'kewra essence', 'pandanus water'],
            ],

            // Real, needed by the method, scaled with the dish — and never
            // bought. is_shoppable is what keeps it off the list.
            'water' => [
                'dimension' => UnitDimension::Volume,
                'name_bn' => 'পানি',
                'preferred_unit' => 'ml',
                'is_shoppable' => false,
                'aliases' => ['hot water', 'warm water', 'cold water', 'boiling water', 'pani', 'jol'],
            ],
        ];
    }
}
