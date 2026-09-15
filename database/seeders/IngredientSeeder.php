<?php

namespace Database\Seeders;

use App\Enums\UnitDimension;
use App\Models\Ingredient;
use App\Models\IngredientAlias;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * The ingredient dictionary: what each thing *is*, independent of how any one
 * recipe measures it.
 *
 * The list itself comes from the recipe dataset, so the dictionary and the
 * recipes cannot drift apart — every ingredient a recipe names is defined, and
 * nothing is defined that no recipe uses. What the dataset does not carry is
 * the vocabulary people actually type: the Bangla names, and the English
 * variants TheMealDB imports arrive with. Those live in the overlay below and
 * are matched to the dataset by slug.
 *
 * default_dimension is only the fallback for a line with no usable unit — the
 * unit on the recipe row decides — but it still says how this dataset measures
 * the thing, so the vegetables that are weighed are mass.
 */
class IngredientSeeder extends Seeder
{
    private const DATA_FILE = 'data/bangladeshi-recipes-80.json';

    /**
     * Words that should resolve to a dictionary entry, keyed by the dataset's
     * ingredient slug. An alias is the same thing in the trolley written a
     * different way — "shorsher tel" is mustard oil — never a different
     * purchase: ginger paste and ginger are two rows in this dataset, so
     * neither aliases the other.
     *
     * @var array<string, array{name_bn?: string, aliases?: array<int, string>}>
     */
    private const OVERLAY = [
        'onion' => ['name_bn' => 'পেঁয়াজ', 'aliases' => ['onions', 'peyaj', 'pyaj', 'red onion', 'brown onion', 'yellow onion']],
        'garlic' => ['name_bn' => 'রসুন', 'aliases' => ['garlic clove', 'garlic cloves', 'roshun', 'rasun']],
        'garlic-paste' => ['name_bn' => 'রসুন বাটা', 'aliases' => ['roshun bata']],
        'ginger' => ['name_bn' => 'আদা', 'aliases' => ['fresh ginger', 'ada', 'adrak']],
        'ginger-paste' => ['name_bn' => 'আদা বাটা', 'aliases' => ['ada bata']],
        'potato' => ['name_bn' => 'আলু', 'aliases' => ['potatoes', 'alu', 'aloo']],
        'tomato' => ['name_bn' => 'টমেটো', 'aliases' => ['tomatoes', 'fresh tomato', 'tometo']],
        'eggplant' => ['name_bn' => 'বেগুন', 'aliases' => ['aubergine', 'brinjal', 'begun']],
        'cauliflower' => ['name_bn' => 'ফুলকপি', 'aliases' => ['cauliflower florets', 'phulkopi', 'fulkopi']],
        'cabbage' => ['name_bn' => 'বাঁধাকপি', 'aliases' => ['bandhakopi', 'badhakopi']],
        'green-peas' => ['name_bn' => 'মটরশুঁটি', 'aliases' => ['peas', 'green pea', 'motorshuti', 'matarshuti']],
        'green-beans' => ['name_bn' => 'বরবটি', 'aliases' => ['french beans', 'borboti']],
        'carrot' => ['name_bn' => 'গাজর', 'aliases' => ['carrots', 'gajor']],
        'radish' => ['name_bn' => 'মুলা', 'aliases' => ['mula', 'mooli', 'daikon']],
        'pumpkin' => ['name_bn' => 'কুমড়া', 'aliases' => ['sweet pumpkin', 'kumra', 'misti kumra']],
        'bottle-gourd' => ['name_bn' => 'লাউ', 'aliases' => ['lau', 'calabash', 'doodhi']],
        'ridge-gourd' => ['name_bn' => 'ঝিঙে', 'aliases' => ['jhinge', 'jhinga', 'turai']],
        'pointed-gourd' => ['name_bn' => 'পটল', 'aliases' => ['potol', 'parwal']],
        'green-papaya' => ['name_bn' => 'কাঁচা পেঁপে', 'aliases' => ['raw papaya', 'kacha pepe', 'pepe']],
        'taro-stolon' => ['name_bn' => 'কচুর লতি', 'aliases' => ['kochur loti', 'taro stem']],
        'red-amaranth' => ['name_bn' => 'লাল শাক', 'aliases' => ['lal shak', 'red spinach', 'amaranth']],
        'fresh-coriander' => ['name_bn' => 'ধনেপাতা', 'aliases' => ['cilantro', 'coriander', 'coriander leaves', 'dhania', 'dhone pata']],
        'green-chili' => ['name_bn' => 'কাঁচা মরিচ', 'aliases' => ['chili', 'chillies', 'chilies', 'green chilli', 'morich', 'kacha morich']],
        'dried-red-chili' => ['name_bn' => 'শুকনা মরিচ', 'aliases' => ['dried red chilli', 'dry red chili', 'dried chili', 'shukna morich', 'shukno morich']],
        'chili-powder' => ['name_bn' => 'মরিচ গুঁড়া', 'aliases' => ['red chili powder', 'morich gura', 'lal morich']],
        'turmeric-powder' => ['name_bn' => 'হলুদ', 'aliases' => ['turmeric', 'ground turmeric', 'holud', 'haldi']],
        'cumin-powder' => ['name_bn' => 'জিরা গুঁড়া', 'aliases' => ['ground cumin', 'cumin', 'jeera', 'jira']],
        'cumin-seed' => ['name_bn' => 'জিরা', 'aliases' => ['cumin seeds', 'whole cumin', 'gota jira']],
        'coriander-powder' => ['name_bn' => 'ধনে গুঁড়া', 'aliases' => ['ground coriander', 'dhone gura']],
        'garam-masala' => ['name_bn' => 'গরম মসলা', 'aliases' => ['garam masala powder', 'allspice blend', 'gorom moshla']],
        'black-pepper-powder' => ['name_bn' => 'গোলমরিচ গুঁড়া', 'aliases' => ['black pepper', 'ground black pepper', 'pepper', 'golmorich', 'gol morich']],
        'white-pepper-powder' => ['name_bn' => 'সাদা গোলমরিচ', 'aliases' => ['white pepper', 'ground white pepper']],
        'bay-leaf' => ['name_bn' => 'তেজপাতা', 'aliases' => ['bay leaves', 'tejpata', 'tej pata']],
        'cinnamon' => ['name_bn' => 'দারুচিনি', 'aliases' => ['cinnamon stick', 'cinnamon sticks', 'daruchini', 'darchini']],
        'green-cardamom' => ['name_bn' => 'এলাচ', 'aliases' => ['cardamom', 'cardamom pod', 'cardamom pods', 'elach', 'elachi', 'choto elach']],
        'clove' => ['name_bn' => 'লবঙ্গ', 'aliases' => ['cloves', 'lobongo', 'laung']],
        'nigella-seed' => ['name_bn' => 'কালোজিরা', 'aliases' => ['nigella', 'nigella seeds', 'kalojira', 'kalonji', 'black cumin']],
        'fenugreek-seed' => ['name_bn' => 'মেথি', 'aliases' => ['fenugreek', 'methi', 'methi seeds']],
        'fennel-seed' => ['name_bn' => 'মৌরি', 'aliases' => ['fennel', 'mouri', 'saunf']],
        'mustard-seed' => ['name_bn' => 'সরিষা', 'aliases' => ['mustard seeds', 'shorshe', 'sorisha']],
        'mustard-paste' => ['name_bn' => 'সরিষা বাটা', 'aliases' => ['mustard seed paste', 'shorshe bata']],
        'salt' => ['name_bn' => 'লবণ', 'aliases' => ['table salt', 'sea salt', 'lobon', 'noon']],
        'sugar' => ['name_bn' => 'চিনি', 'aliases' => ['granulated sugar', 'white sugar', 'chini']],
        'chicken' => ['name_bn' => 'মুরগির মাংস', 'aliases' => ['whole chicken', 'chicken curry cut', 'chicken breast', 'boneless chicken', 'chicken leg quarter', 'chicken leg quarters', 'murgir mangsho']],
        'chicken-liver' => ['name_bn' => 'মুরগির কলিজা', 'aliases' => ['murgir kolija', 'chicken livers']],
        'beef' => ['name_bn' => 'গরুর মাংস', 'aliases' => ['beef meat', 'gorur mangsho', 'stewing beef', 'beef chuck']],
        'beef-mince' => ['name_bn' => 'গরুর কিমা', 'aliases' => ['minced beef', 'ground beef', 'beef keema', 'gorur kima']],
        'beef-shank' => ['name_bn' => 'গরুর নলি', 'aliases' => ['shank', 'nihari meat', 'nalli']],
        'mutton' => ['name_bn' => 'খাসির মাংস', 'aliases' => ['goat meat', 'lamb', 'khashir mangsho', 'khasir mangsho']],
        'hilsa' => ['name_bn' => 'ইলিশ মাছ', 'aliases' => ['ilish', 'ilish mach', 'hilsa fish']],
        'rohu' => ['name_bn' => 'রুই মাছ', 'aliases' => ['rui', 'rui fish', 'rui mach', 'rui machh', 'rohu fish']],
        'katla-fish' => ['name_bn' => 'কাতলা মাছ', 'aliases' => ['katla', 'catla', 'katol']],
        'pabda-fish' => ['name_bn' => 'পাবদা মাছ', 'aliases' => ['pabda']],
        'tengra-fish' => ['name_bn' => 'টেংরা মাছ', 'aliases' => ['tengra']],
        'boal-fish' => ['name_bn' => 'বোয়াল মাছ', 'aliases' => ['boal']],
        'pangasius-fish' => ['name_bn' => 'পাঙ্গাশ মাছ', 'aliases' => ['pangash', 'pangas', 'basa']],
        'climbing-perch' => ['name_bn' => 'কই মাছ', 'aliases' => ['koi', 'koi fish', 'koi mach']],
        'dried-fish' => ['name_bn' => 'শুঁটকি', 'aliases' => ['shutki', 'shutki mach', 'dried fish flakes']],
        'prawn' => ['name_bn' => 'চিংড়ি', 'aliases' => ['prawns', 'shrimp', 'chingri', 'chingri mach']],
        'egg' => ['name_bn' => 'ডিম', 'aliases' => ['eggs', 'dim']],
        'rice' => ['name_bn' => 'চাল', 'aliases' => ['plain rice', 'white rice', 'chaal']],
        'basmati-rice' => ['name_bn' => 'বাসমতি চাল', 'aliases' => ['basmati', 'polao rice', 'pulao rice']],
        'gobindobhog-rice' => ['name_bn' => 'গোবিন্দভোগ চাল', 'aliases' => ['gobindobhog', 'chinigura rice', 'chinigura', 'kalijira rice']],
        'masoor-dal' => ['name_bn' => 'মসুর ডাল', 'aliases' => ['red lentil', 'red lentils', 'musur dal', 'dal']],
        'moong-dal' => ['name_bn' => 'মুগ ডাল', 'aliases' => ['mung dal', 'mug dal', 'moong lentil', 'yellow moong dal', 'split moong dal']],
        'chana-dal' => ['name_bn' => 'ছোলার ডাল', 'aliases' => ['cholar dal', 'bengal gram', 'split chickpea']],
        'besan' => ['name_bn' => 'বেসন', 'aliases' => ['gram flour', 'chickpea flour']],
        'all-purpose-flour' => ['name_bn' => 'ময়দা', 'aliases' => ['plain flour', 'maida']],
        'wheat-flour' => ['name_bn' => 'আটা', 'aliases' => ['atta', 'whole wheat flour']],
        'rice-flour' => ['name_bn' => 'চালের গুঁড়া', 'aliases' => ['chaler guro']],
        'semolina' => ['name_bn' => 'সুজি', 'aliases' => ['suji', 'sooji', 'rava']],
        'vermicelli' => ['name_bn' => 'সেমাই', 'aliases' => ['semai', 'shemai', 'fine vermicelli', 'vermicelli noodles']],
        'milk' => ['name_bn' => 'দুধ', 'aliases' => ['whole milk', 'full-fat milk', 'full cream milk', 'dudh']],
        'yogurt' => ['name_bn' => 'দই', 'aliases' => ['plain yogurt', 'yoghurt', 'curd', 'doi', 'tok doi']],
        'ghee' => ['name_bn' => 'ঘি', 'aliases' => ['clarified butter', 'ghi']],
        'mustard-oil' => ['name_bn' => 'সরিষার তেল', 'aliases' => ['shorsher tel', 'shorshe tel', 'mustard seed oil']],
        'oil' => ['name_bn' => 'তেল', 'aliases' => ['cooking oil', 'vegetable oil', 'soybean oil', 'soyabean oil', 'canola oil', 'sunflower oil', 'shonapathi tel']],
        'coconut-milk' => ['name_bn' => 'নারিকেলের দুধ', 'aliases' => ['narikel dudh']],
        'coconut-paste' => ['name_bn' => 'নারিকেল বাটা', 'aliases' => ['grated coconut', 'narikel bata']],
        'cashew-nut' => ['name_bn' => 'কাজু বাদাম', 'aliases' => ['cashew', 'cashews', 'cashew nuts', 'kaju', 'kaju badam']],
        'cashew-nut-paste' => ['name_bn' => 'কাজু বাটা', 'aliases' => ['cashew paste', 'kaju bata']],
        'pistachio' => ['name_bn' => 'পেস্তা বাদাম', 'aliases' => ['pistachios', 'pesta badam']],
        'raisin' => ['name_bn' => 'কিশমিশ', 'aliases' => ['raisins', 'sultanas', 'kishmish']],
        'kewra-water' => ['name_bn' => 'কেওড়া জল', 'aliases' => ['kewra', 'keora water', 'kewra essence', 'pandanus water']],
        'rose-water' => ['name_bn' => 'গোলাপ জল', 'aliases' => ['golap jol']],
        'soy-sauce' => ['name_bn' => 'সয়া সস', 'aliases' => ['soya sauce']],
        'food-color' => ['name_bn' => 'ফুড কালার', 'aliases' => ['food colour', 'food coloring']],
        'water' => ['name_bn' => 'পানি', 'aliases' => ['cold water', 'pani', 'jol']],
        'hot-water' => ['name_bn' => 'গরম পানি', 'aliases' => ['warm water', 'boiling water', 'gorom pani']],
        'oil-for-frying' => ['aliases' => ['frying oil', 'oil for deep frying']],
    ];

    public function run(): void
    {
        foreach ($this->dictionary() as $entry) {
            $ingredient = Ingredient::updateOrCreate(
                ['canonical_name' => $entry['canonical_name']],
                [
                    'default_dimension' => $entry['dimension'],
                    'name_bn' => $entry['name_bn'],
                    'preferred_unit' => $entry['preferred_unit'],
                    'is_shoppable' => $entry['is_shoppable'],
                ]
            );

            foreach ($entry['aliases'] as $alias) {
                /**
                 * updateOrCreate rather than firstOrCreate: an alias that has
                 * moved to a better home — "chicken breast" now meaning
                 * chicken — has to follow the dictionary, not the old row.
                 */
                IngredientAlias::updateOrCreate(
                    ['alias' => $alias],
                    ['ingredient_id' => $ingredient->id]
                );
            }
        }
    }

    /**
     * The dataset's ingredients, with the overlay's vocabulary folded in.
     *
     * @return array<int, array{canonical_name: string, dimension: UnitDimension, name_bn: ?string, preferred_unit: string, is_shoppable: bool, aliases: array<int, string>}>
     */
    private function dictionary(): array
    {
        $path = database_path(self::DATA_FILE);
        $json = file_get_contents($path);

        if ($json === false) {
            throw new RuntimeException("Could not read the recipe data file at {$path}.");
        }

        /** @var array{ingredients: array<int, array{slug: string, name: string, is_shoppable: bool, default_unit: string}>} $data */
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        $entries = [];
        $canonicalNames = [];

        foreach ($data['ingredients'] as $ingredient) {
            $canonical = mb_strtolower($ingredient['name']);
            $canonicalNames[$canonical] = true;

            $overlay = self::OVERLAY[$ingredient['slug']] ?? [];

            $entries[] = [
                'canonical_name' => $canonical,
                'dimension' => $this->dimensionOf($ingredient['default_unit']),
                'name_bn' => $overlay['name_bn'] ?? null,
                'preferred_unit' => $ingredient['default_unit'],
                'is_shoppable' => $ingredient['is_shoppable'],
                'aliases' => $overlay['aliases'] ?? [],
            ];
        }

        /**
         * An alias that is also somebody's canonical name would shadow a real
         * entry: "coriander" pointing at fresh coriander is fine, "rice"
         * pointing at basmati is not, because rice is its own row.
         */
        foreach ($entries as $entry) {
            foreach ($entry['aliases'] as $alias) {
                if (isset($canonicalNames[$alias])) {
                    throw new RuntimeException("The alias \"{$alias}\" on {$entry['canonical_name']} is also a canonical ingredient. Drop the alias, or point the recipes at the entry it shadows.");
                }
            }
        }

        return $entries;
    }

    /**
     * The dimension a thing is usually measured in, read off the unit the
     * dataset prefers for it.
     */
    private function dimensionOf(string $unit): UnitDimension
    {
        return match ($unit) {
            'g', 'kg' => UnitDimension::Mass,
            'ml', 'l' => UnitDimension::Volume,
            default => UnitDimension::Count,
        };
    }
}
