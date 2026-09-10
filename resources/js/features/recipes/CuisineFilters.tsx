import React from 'react';
import { Search, X, RotateCcw, ArrowDownWideNarrow, Tag, Globe2, ChevronDown } from 'lucide-react';
import { CuisineCount, CategoryCount } from '../../types/api';

interface CuisineFiltersProps {
    search: string;
    onSearchChange: (value: string) => void;
    selectedCuisine: string;
    onSelectCuisine: (cuisine: string) => void;
    selectedCategory: string;
    onSelectCategory: (category: string) => void;
    selectedSort: 'bayesian' | 'rating' | 'latest' | 'title';
    onSelectSort: (sort: 'bayesian' | 'rating' | 'latest' | 'title') => void;
    cuisines: CuisineCount[];
    categories: CategoryCount[];
    onReset: () => void;
    /** Optional layout switches so the page can place each part in its own section. */
    hideSearch?: boolean;
    hideControls?: boolean;
    hideCuisines?: boolean;
}

/** Spice tints cycled across cuisine tiles. */
const TILE_THEMES = [
    { bg: 'bg-saffron-soft', text: 'text-saffron-deep', active: 'bg-saffron text-ink shadow-glow-saffron', count: 'bg-saffron/20' },
    { bg: 'bg-mint-soft', text: 'text-mint-deep', active: 'bg-mint text-ink shadow-glow-mint', count: 'bg-mint/20' },
    { bg: 'bg-plum-soft', text: 'text-plum-deep', active: 'bg-plum text-white shadow-glow-plum', count: 'bg-plum/20' },
    { bg: 'bg-turmeric-soft', text: 'text-turmeric-deep', active: 'bg-turmeric text-ink shadow-glow-turmeric', count: 'bg-turmeric/30' },
    { bg: 'bg-chili-soft', text: 'text-chili-deep', active: 'bg-chili text-white shadow-glow-chili', count: 'bg-chili/20' },
];

const CUISINE_EMOJI: Record<string, string> = {
    bangladeshi: '🍛',
    bengali: '🍛',
    indian: '🍛',
    pakistani: '🥘',
    italian: '🍝',
    chinese: '🥡',
    japanese: '🍣',
    thai: '🍜',
    vietnamese: '🍜',
    korean: '🍲',
    mexican: '🌮',
    american: '🍔',
    british: '🫖',
    french: '🥐',
    greek: '🥗',
    spanish: '🥘',
    moroccan: '🍲',
    turkish: '🧆',
    tunisian: '🌶️',
    egyptian: '🧆',
    jamaican: '🍗',
    canadian: '🥞',
    dutch: '🧀',
    irish: '🥔',
    croatian: '🐟',
    portuguese: '🐟',
    russian: '🥟',
    polish: '🥟',
    malaysian: '🍢',
    filipino: '🍢',
    kenyan: '🍲',
    ukrainian: '🥟',
    uruguayan: '🥩',
    unknown: '🍽️',
};
const EMOJI_FALLBACKS = ['🍲', '🥘', '🍛', '🍜', '🥗'];

const cuisineEmoji = (name: string, index: number) =>
    CUISINE_EMOJI[name.trim().toLowerCase()] ?? EMOJI_FALLBACKS[index % EMOJI_FALLBACKS.length];

const selectClasses =
    'appearance-none w-full sm:w-auto text-sm bg-paper border-2 border-line-strong hover:border-ink rounded-full pl-10 pr-10 py-2.5 text-ink font-bold focus-visible:outline-3 focus-visible:outline-saffron focus-visible:outline-offset-2 cursor-pointer transition-colors';

export const CuisineFilters: React.FC<CuisineFiltersProps> = ({
    search,
    onSearchChange,
    selectedCuisine,
    onSelectCuisine,
    selectedCategory,
    onSelectCategory,
    selectedSort,
    onSelectSort,
    cuisines,
    categories,
    onReset,
    hideSearch = false,
    hideControls = false,
    hideCuisines = false,
}) => {
    const isFiltered = !!search || !!selectedCuisine || !!selectedCategory || selectedSort !== 'bayesian';

    return (
        <div className="space-y-6">
            {/* Search + category / sort / reset controls */}
            {(!hideSearch || !hideControls) && (
                <div className="flex flex-col md:flex-row gap-3 items-stretch md:items-center justify-between">
                    {!hideSearch && (
                        <div className="relative flex-1">
                            <Search className="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-saffron-deep" />
                            <input
                                type="text"
                                value={search}
                                onChange={(e) => onSearchChange(e.target.value)}
                                placeholder="Search recipes, ingredients, or spices..."
                                className="w-full pl-11 pr-10 py-3 bg-paper border-2 border-line-strong rounded-full text-sm text-ink placeholder:text-ink-3 focus-visible:outline-3 focus-visible:outline-saffron focus-visible:outline-offset-2 transition-all font-medium"
                            />
                            {search && (
                                <button
                                    type="button"
                                    onClick={() => onSearchChange('')}
                                    className="absolute right-3 top-1/2 -translate-y-1/2 text-ink-2 hover:text-ink hover:bg-cream-2 p-1.5 rounded-full cursor-pointer focus-visible:outline-3 focus-visible:outline-saffron"
                                    aria-label="Clear search"
                                >
                                    <X className="w-4 h-4" />
                                </button>
                            )}
                        </div>
                    )}

                    {!hideControls && (
                        <div className="flex items-center gap-2.5 flex-wrap">
                            {/* Category Selector */}
                            <div className="relative flex-1 sm:flex-none">
                                <Tag className="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-plum-deep" />
                                <select
                                    value={selectedCategory}
                                    onChange={(e) => onSelectCategory(e.target.value)}
                                    aria-label="Filter by category"
                                    className={selectClasses}
                                >
                                    <option value="">All Categories</option>
                                    {categories.map((cat) => (
                                        <option key={cat.category} value={cat.category}>
                                            {cat.category} ({cat.count})
                                        </option>
                                    ))}
                                </select>
                                <ChevronDown className="pointer-events-none absolute right-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-ink-3" />
                            </div>

                            {/* Sort Selector */}
                            <div className="relative flex-1 sm:flex-none">
                                <ArrowDownWideNarrow className="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-mint-deep" />
                                <select
                                    value={selectedSort}
                                    onChange={(e) =>
                                        onSelectSort(e.target.value as 'bayesian' | 'rating' | 'latest' | 'title')
                                    }
                                    aria-label="Sort recipes by"
                                    className={selectClasses}
                                >
                                    <option value="bayesian">Best ranked</option>
                                    <option value="rating">Highest rating</option>
                                    <option value="latest">Newest first</option>
                                    <option value="title">A to Z</option>
                                </select>
                                <ChevronDown className="pointer-events-none absolute right-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-ink-3" />
                            </div>

                            {/* Reset Button */}
                            {isFiltered && (
                                <button
                                    type="button"
                                    onClick={onReset}
                                    className="inline-flex items-center gap-1.5 text-sm font-extrabold text-chili-deep bg-chili-soft hover:bg-chili hover:text-white px-4 py-2.5 rounded-full transition-all whitespace-nowrap cursor-pointer focus-visible:outline-3 focus-visible:outline-saffron focus-visible:outline-offset-2 animate-pop-in"
                                >
                                    <RotateCcw className="w-3.5 h-3.5" />
                                    <span>Reset</span>
                                </button>
                            )}
                        </div>
                    )}
                </div>
            )}

            {/* Cuisine tiles: horizontal scroll strip */}
            {!hideCuisines && cuisines.length > 0 && (
                <div>
                    <div className="flex items-center justify-between gap-2 mb-3">
                        <span className="inline-flex items-center gap-1.5 text-xs font-extrabold uppercase tracking-[0.14em] text-ink-2">
                            <Globe2 className="w-3.5 h-3.5 text-saffron" />
                            Cuisines
                        </span>
                        {selectedCuisine && (
                            <button
                                type="button"
                                onClick={() => onSelectCuisine('')}
                                className="text-xs text-saffron-deep hover:underline font-extrabold cursor-pointer"
                            >
                                Show all cuisines
                            </button>
                        )}
                    </div>

                    <div
                        className="flex items-stretch gap-3 overflow-x-auto no-scrollbar snap-x snap-mandatory -mx-4 px-4 sm:mx-0 sm:px-0 pb-2"
                        role="group"
                        aria-label="Filter by cuisine"
                    >
                        {/* "All" tile */}
                        <button
                            type="button"
                            onClick={() => onSelectCuisine('')}
                            aria-pressed={!selectedCuisine}
                            className={`snap-start shrink-0 flex flex-col items-start justify-between gap-3 min-w-[8.5rem] rounded-2xl px-4 py-3.5 text-left transition-all duration-200 cursor-pointer focus-visible:outline-3 focus-visible:outline-saffron focus-visible:outline-offset-2 hover:-translate-y-1 ${
                                !selectedCuisine
                                    ? 'bg-ink text-white shadow-pop-saffron'
                                    : 'bg-paper text-ink border-2 border-line-strong hover:border-ink'
                            }`}
                        >
                            <span className="text-2xl leading-none" aria-hidden="true">
                                🌍
                            </span>
                            <span className="flex flex-col">
                                <span className="font-display font-extrabold text-sm leading-tight">All cuisines</span>
                                <span className={`text-[11px] font-bold ${!selectedCuisine ? 'text-white/70' : 'text-ink-3'}`}>
                                    Everything
                                </span>
                            </span>
                        </button>

                        {cuisines.map((c, index) => {
                            const isSelected = selectedCuisine === c.cuisine;
                            const theme = TILE_THEMES[index % TILE_THEMES.length];
                            return (
                                <button
                                    key={c.cuisine}
                                    type="button"
                                    onClick={() => onSelectCuisine(isSelected ? '' : c.cuisine)}
                                    aria-pressed={isSelected}
                                    className={`snap-start shrink-0 flex flex-col items-start justify-between gap-3 min-w-[8.5rem] rounded-2xl px-4 py-3.5 text-left transition-all duration-200 cursor-pointer focus-visible:outline-3 focus-visible:outline-saffron focus-visible:outline-offset-2 hover:-translate-y-1 ${
                                        isSelected
                                            ? `${theme.active} scale-[1.02]`
                                            : `${theme.bg} ${theme.text} hover:shadow-md`
                                    }`}
                                >
                                    <span className="text-2xl leading-none" aria-hidden="true">
                                        {cuisineEmoji(c.cuisine, index)}
                                    </span>
                                    <span className="flex flex-col w-full">
                                        <span className="font-display font-extrabold text-sm leading-tight truncate">
                                            {c.cuisine}
                                        </span>
                                        <span
                                            className={`mt-1 self-start inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-extrabold ${
                                                isSelected ? 'bg-white/25' : theme.count
                                            }`}
                                        >
                                            {c.count} {c.count === 1 ? 'recipe' : 'recipes'}
                                        </span>
                                    </span>
                                </button>
                            );
                        })}
                    </div>
                </div>
            )}
        </div>
    );
};
