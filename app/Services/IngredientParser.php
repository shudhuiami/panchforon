<?php

namespace App\Services;

use App\DTOs\ParsedIngredient;
use App\Enums\UnitDimension;

class IngredientParser
{
    /**
     * @var array<string, float>
     */
    protected const VULGAR_FRACTIONS = [
        "\u{00BD}" => 0.5,
        "\u{2153}" => 0.3333,
        "\u{2154}" => 0.6667,
        "\u{00BC}" => 0.25,
        "\u{00BE}" => 0.75,
        "\u{2155}" => 0.2,
        "\u{2156}" => 0.4,
        "\u{2157}" => 0.6,
        "\u{2158}" => 0.8,
        "\u{2159}" => 0.1667,
        "\u{215A}" => 0.8333,
        "\u{215B}" => 0.125,
        "\u{215C}" => 0.375,
        "\u{215D}" => 0.625,
        "\u{215E}" => 0.875,
    ];

    /**
     * @var array<string, string>
     */
    protected const UNIT_NORMALIZATION = [
        // Mass
        'g' => 'g',
        'gram' => 'g',
        'grams' => 'g',
        'gm' => 'g',
        'gms' => 'g',
        'kg' => 'kg',
        'kilo' => 'kg',
        'kilos' => 'kg',
        'kilogram' => 'kg',
        'kilograms' => 'kg',
        'kgs' => 'kg',
        'oz' => 'oz',
        'ounce' => 'oz',
        'ounces' => 'oz',
        'lb' => 'lb',
        'lbs' => 'lb',
        'pound' => 'lb',
        'pounds' => 'lb',

        // Volume
        'ml' => 'ml',
        'millilitre' => 'ml',
        'millilitres' => 'ml',
        'milliliter' => 'ml',
        'milliliters' => 'ml',
        'l' => 'l',
        'litre' => 'l',
        'litres' => 'l',
        'liter' => 'l',
        'liters' => 'l',
        'tsp' => 'tsp',
        'teaspoon' => 'tsp',
        'teaspoons' => 'tsp',
        'tbsp' => 'tbsp',
        'tablespoon' => 'tbsp',
        'tablespoons' => 'tbsp',
        'tbs' => 'tbsp',
        'cup' => 'cup',
        'cups' => 'cup',
        'floz' => 'floz',
        'fl oz' => 'floz',
        'fluid ounce' => 'floz',
        'fluid ounces' => 'floz',

        // Count
        'piece' => 'piece',
        'pieces' => 'piece',
        'pcs' => 'piece',
        'pc' => 'piece',
        'clove' => 'clove',
        'cloves' => 'clove',
        'slice' => 'slice',
        'slices' => 'slice',
        'whole' => 'whole',
    ];

    /**
     * @var array<string, UnitDimension>
     */
    protected const UNIT_DIMENSIONS = [
        'g' => UnitDimension::Mass,
        'kg' => UnitDimension::Mass,
        'oz' => UnitDimension::Mass,
        'lb' => UnitDimension::Mass,

        'ml' => UnitDimension::Volume,
        'l' => UnitDimension::Volume,
        'tsp' => UnitDimension::Volume,
        'tbsp' => UnitDimension::Volume,
        'cup' => UnitDimension::Volume,
        'floz' => UnitDimension::Volume,

        'piece' => UnitDimension::Count,
        'clove' => UnitDimension::Count,
        'slice' => UnitDimension::Count,
        'whole' => UnitDimension::Count,
    ];

    /**
     * Stopwords and descriptor words to strip from names.
     *
     * @var list<string>
     */
    protected const DESCRIPTORS = [
        'finely chopped', 'roughly chopped', 'thinly sliced', 'freshly ground', 'freshly grated',
        'chopped', 'sliced', 'diced', 'minced', 'fresh', 'dried', 'large', 'small', 'medium',
        'finely', 'roughly', 'grated', 'peeled', 'crushed', 'ground', 'shredded', 'toasted',
        'roasted', 'boneless', 'skinless', 'cooked', 'raw', 'warm', 'cold', 'hot', 'optional',
        'drained', 'rinsed', 'halved', 'quartered', 'melted', 'softened', 'beaten', 'sifted',
        'packed', 'divided', 'cleaned', 'trimmed',
    ];

    /**
     * Parse raw ingredient text into a ParsedIngredient DTO.
     *
     * @param  (callable(string): ?int)|null  $identityResolver
     */
    public function parse(string $rawText, ?callable $identityResolver = null): ParsedIngredient
    {
        $raw = trim($rawText);
        if ($raw === '') {
            return new ParsedIngredient(
                quantity: null,
                unit: null,
                name: null,
                rawText: $rawText,
                ingredientId: null,
                dimension: UnitDimension::None,
            );
        }

        $lowerRaw = mb_strtolower($raw);

        // Check for "to taste" or "as required" or "pinch" without number
        if (str_contains($lowerRaw, 'to taste') || str_contains($lowerRaw, 'as needed') || str_contains($lowerRaw, 'as required') || str_contains($lowerRaw, 'for frying') || str_contains($lowerRaw, 'for garnish')) {
            $cleaned = preg_replace('/\b(to taste|as needed|as required|for frying|for garnish)\b/i', '', $raw);
            $name = $this->cleanIngredientName($cleaned ?? $raw);

            $ingredientId = null;
            if ($identityResolver !== null && $name !== '') {
                $ingredientId = $identityResolver($name);
            }

            return new ParsedIngredient(
                quantity: null,
                unit: null,
                name: $name !== '' ? $name : null,
                rawText: $rawText,
                ingredientId: $ingredientId,
                dimension: UnitDimension::None,
            );
        }

        $remaining = $raw;
        $quantity = $this->extractQuantity($remaining);
        $unit = $this->extractUnit($remaining);
        if ($unit === null) {
            $unit = $this->extractTrailingUnit($remaining);
        }
        $name = $this->cleanIngredientName($remaining);

        if ($name === '' && $quantity === null && $unit === null) {
            return new ParsedIngredient(
                quantity: null,
                unit: null,
                name: null,
                rawText: $rawText,
                ingredientId: null,
                dimension: UnitDimension::None,
            );
        }

        $dimension = UnitDimension::None;
        if ($unit !== null && isset(self::UNIT_DIMENSIONS[$unit])) {
            $dimension = self::UNIT_DIMENSIONS[$unit];
        } elseif ($quantity !== null && $unit === null) {
            // e.g. "2 onions" -> count dimension
            $dimension = UnitDimension::Count;
            $unit = 'piece';
        }

        $ingredientId = null;
        if ($identityResolver !== null && $name !== '') {
            $ingredientId = $identityResolver($name);
        }

        return new ParsedIngredient(
            quantity: $quantity,
            unit: $unit,
            name: $name !== '' ? $name : null,
            rawText: $rawText,
            ingredientId: $ingredientId,
            dimension: $dimension,
        );
    }

    /**
     * Extract quantity from the start of the string, modifying remaining text by reference.
     */
    protected function extractQuantity(string &$text): ?float
    {
        $text = trim($text);

        // Check for vulgar fractions at the start (e.g. "½ tsp salt" or "1 ½ tsp")
        $vulgarPattern = '/^(\d+)?\s*([\x{00BD}\x{2153}\x{2154}\x{00BC}\x{00BE}\x{2155}\x{2156}\x{2157}\x{2158}\x{2159}\x{215A}\x{215B}\x{215C}\x{215D}\x{215E}])/u';
        if (preg_match($vulgarPattern, $text, $matches)) {
            $whole = ! empty($matches[1]) ? (float) $matches[1] : 0.0;
            $char = (string) $matches[2];
            $fraction = isset(self::VULGAR_FRACTIONS[$char]) ? self::VULGAR_FRACTIONS[$char] : 0.0;
            $text = trim(mb_substr($text, mb_strlen($matches[0])));

            return $whole + $fraction;
        }

        // Check for ranges e.g. "2-3", "2 - 3" (Documented choice: midpoint)
        $rangePattern = '/^(\d+(?:\.\d+)?)\s*[-–—]\s*(\d+(?:\.\d+)?)\b/';
        if (preg_match($rangePattern, $text, $matches)) {
            $val1 = (float) $matches[1];
            $val2 = (float) $matches[2];
            $text = trim(substr($text, strlen($matches[0])));

            return round(($val1 + $val2) / 2, 3);
        }

        // Check for mixed numbers e.g. "1 1/2" or "2 3/4"
        $mixedPattern = '/^(\d+)\s+(\d+)\/(\d+)\b/';
        if (preg_match($mixedPattern, $text, $matches)) {
            $whole = (float) $matches[1];
            $denom = (float) $matches[3];
            $fraction = $denom > 0 ? ((float) $matches[2] / $denom) : 0.0;
            $text = trim(substr($text, strlen($matches[0])));

            return round($whole + $fraction, 3);
        }

        // Check for simple fractions e.g. "1/2", "3/4"
        $fractionPattern = '/^(\d+)\/(\d+)\b/';
        if (preg_match($fractionPattern, $text, $matches)) {
            $denom = (float) $matches[2];
            $fraction = $denom > 0 ? ((float) $matches[1] / $denom) : 0.0;
            $text = trim(substr($text, strlen($matches[0])));

            return round($fraction, 3);
        }

        // Check for regular decimals / integers e.g. "1.5", "200", "0.25"
        $decimalPattern = '/^(\d+(?:\.\d+)?)\b/';
        if (preg_match($decimalPattern, $text, $matches)) {
            $val = (float) $matches[1];
            $text = trim(substr($text, strlen($matches[0])));

            return $val;
        }

        return null;
    }

    /**
     * Extract unit from the start of the string, modifying remaining text by reference.
     */
    protected function extractUnit(string &$text): ?string
    {
        $text = trim($text);

        // Try two-word units first e.g. "fl oz", "fluid ounce", "fluid ounces"
        $twoWordPattern = '/^(fl(?:uid)?\s*oz(?:s)?|fluid\s+ounces?)\b/i';
        if (preg_match($twoWordPattern, $text, $matches)) {
            $text = trim(substr($text, strlen($matches[0])));

            return 'floz';
        }

        // Single word units
        $wordPattern = '/^([a-zA-Z]+)\b/';
        if (preg_match($wordPattern, $text, $matches)) {
            $candidate = strtolower($matches[1]);
            if (isset(self::UNIT_NORMALIZATION[$candidate])) {
                $text = trim(substr($text, strlen($matches[0])));
                // Also strip optional "of" e.g. "cup of sugar" -> "sugar"
                $text = preg_replace('/^of\s+/i', '', $text) ?? $text;

                return self::UNIT_NORMALIZATION[$candidate];
            }
        }

        return null;
    }

    /**
     * Extract trailing unit from remaining text (e.g. "garlic cloves" -> unit "clove", remaining "garlic").
     */
    protected function extractTrailingUnit(string &$text): ?string
    {
        $text = trim($text);
        $words = explode(' ', $text);
        if (count($words) <= 1) {
            return null;
        }

        $cleanedWords = [];
        foreach ($words as $w) {
            $cleanedWords[] = strtolower(rtrim(trim($w), ',.'));
        }

        for ($i = count($cleanedWords) - 1; $i >= 1; $i--) {
            $candidate = $cleanedWords[$i];
            if (isset(self::UNIT_NORMALIZATION[$candidate])) {
                $unit = self::UNIT_NORMALIZATION[$candidate];
                unset($words[$i]);
                $text = implode(' ', $words);

                return $unit;
            }
        }

        return null;
    }

    /**
     * Clean and normalize ingredient name.
     */
    public function cleanIngredientName(string $text): string
    {
        $cleaned = mb_strtolower($text);

        // Remove parenthesized content e.g. "(chopped)", "(optional)"
        $cleaned = preg_replace('/\([^)]*\)/', ' ', $cleaned) ?? $cleaned;

        // Remove descriptors
        foreach (self::DESCRIPTORS as $descriptor) {
            $cleaned = preg_replace('/\b'.preg_quote($descriptor, '/').'\b/i', ' ', $cleaned) ?? $cleaned;
        }

        // Remove "of " at the beginning if left
        $cleaned = preg_replace('/^\s*of\s+/i', '', $cleaned) ?? $cleaned;

        // Remove punctuation and extra whitespace
        $cleaned = preg_replace('/[,\-\/\.\*\+]/', ' ', $cleaned) ?? $cleaned;
        $cleaned = preg_replace('/\s+/', ' ', $cleaned) ?? $cleaned;
        $cleaned = trim($cleaned);

        if ($cleaned === '') {
            return '';
        }

        // Singularize simple English common plurals
        return $this->singularize($cleaned);
    }

    /**
     * Basic singularization for ingredient names.
     */
    protected function singularize(string $name): string
    {
        $words = explode(' ', $name);
        $lastWord = end($words);

        $singular = match (true) {
            str_ends_with($lastWord, 'tomatoes') => substr($lastWord, 0, -2),
            str_ends_with($lastWord, 'potatoes') => substr($lastWord, 0, -2),
            str_ends_with($lastWord, 'berries') => substr($lastWord, 0, -3).'y',
            str_ends_with($lastWord, 'leaves') => substr($lastWord, 0, -3).'f',
            str_ends_with($lastWord, 'chillies') => substr($lastWord, 0, -3).'i',
            str_ends_with($lastWord, 'chilies') => substr($lastWord, 0, -2),
            str_ends_with($lastWord, 'onions') => substr($lastWord, 0, -1),
            str_ends_with($lastWord, 'cloves') => substr($lastWord, 0, -1),
            str_ends_with($lastWord, 'carrots') => substr($lastWord, 0, -1),
            str_ends_with($lastWord, 'eggs') => substr($lastWord, 0, -1),
            str_ends_with($lastWord, 'pieces') => substr($lastWord, 0, -1),
            str_ends_with($lastWord, 'breasts') => substr($lastWord, 0, -1),
            str_ends_with($lastWord, 'thighs') => substr($lastWord, 0, -1),
            str_ends_with($lastWord, 'mushrooms') => substr($lastWord, 0, -1),
            str_ends_with($lastWord, 'peppers') => substr($lastWord, 0, -1),
            str_ends_with($lastWord, 'apples') => substr($lastWord, 0, -1),
            str_ends_with($lastWord, 'bananas') => substr($lastWord, 0, -1),
            str_ends_with($lastWord, 'lemons') => substr($lastWord, 0, -1),
            str_ends_with($lastWord, 'limes') => substr($lastWord, 0, -1),
            default => (str_ends_with($lastWord, 's') && ! str_ends_with($lastWord, 'ss') && strlen($lastWord) > 3)
                ? substr($lastWord, 0, -1)
                : $lastWord,
        };

        $words[count($words) - 1] = $singular;

        return implode(' ', $words);
    }
}
