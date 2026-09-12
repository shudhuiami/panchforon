import React, { useEffect, useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { recipesApi } from '../api/recipes';
import { CuisineFilters } from '../features/recipes/CuisineFilters';
import { RecipeGrid } from '../features/recipes/RecipeGrid';
import {
    Sparkles,
    Search,
    X,
    ArrowRight,
    ChevronLeft,
    ChevronRight,
    CheckCircle2,
    ChefHat,
    Star,
    ShoppingBasket,
    CalendarDays,
    Utensils,
    Globe2,
    PenLine,
    Flame,
    Shuffle,
} from 'lucide-react';
import { useAuth } from '../context/AuthContext';
import { useRecipeFilters } from '../features/recipes/useRecipeFilters';
import { Link } from 'react-router-dom';
import { Button } from '../components/ui/Button';
import { RecipeList } from '../types/api';

const STACK_GRADIENTS = ['bg-sunrise-gradient', 'bg-plum-gradient', 'bg-mint-gradient', 'bg-spice-gradient', 'bg-ink-gradient'];

/** Position + rotation for each layer of the hero card stack (index 0 = front). */
const STACK_LAYERS = [
    'z-30 left-6 right-6 top-12 bottom-0 animate-float',
    'z-20 left-0 right-20 top-4 bottom-14 -rotate-6',
    'z-10 left-20 right-0 top-0 bottom-20 rotate-6',
];

const HeroStackCard: React.FC<{ recipe: RecipeList; layer: number }> = ({ recipe, layer }) => (
    <Link
        to={`/recipes/${recipe.slug}`}
        className={`absolute rounded-3xl overflow-hidden border-2 border-ink bg-paper shadow-pop transition-transform duration-300 hover:scale-[1.02] focus-visible:outline-3 focus-visible:outline-saffron focus-visible:outline-offset-4 ${STACK_LAYERS[layer]}`}
        style={layer === 0 ? ({ '--float-rot': '-2deg' } as React.CSSProperties) : undefined}
        tabIndex={layer === 0 ? 0 : -1}
        aria-hidden={layer !== 0}
    >
        {recipe.image_url ? (
            <img src={recipe.image_url} alt={recipe.title} className="absolute inset-0 w-full h-full object-cover" />
        ) : (
            <div
                className={`absolute inset-0 ${STACK_GRADIENTS[recipe.id % STACK_GRADIENTS.length]} bg-dots-light flex items-center justify-center text-white`}
            >
                <ChefHat className="w-16 h-16 opacity-90" strokeWidth={1.5} />
            </div>
        )}
        <div className="img-fade absolute inset-0" />
        {recipe.cuisine && (
            <span className="absolute top-4 left-4 sticker inline-flex px-2.5 py-1 rounded-full bg-turmeric text-ink text-[11px] font-extrabold uppercase tracking-wider shadow-md">
                {recipe.cuisine}
            </span>
        )}
        <div className="absolute inset-x-0 bottom-0 p-5">
            <p className="font-display font-extrabold text-white text-lg sm:text-xl leading-tight line-clamp-2 drop-shadow-md">
                {recipe.title}
            </p>
            <p className="mt-1 text-xs font-bold text-white/80 flex items-center gap-1.5">
                <Star className="w-3.5 h-3.5 fill-turmeric text-turmeric" />
                {recipe.ratings_avg ? recipe.ratings_avg.toFixed(1) : 'New'}
                <span className="text-white/50">&middot;</span>
                {recipe.servings} servings
            </p>
        </div>
    </Link>
);

const HeroStackSkeleton: React.FC<{ layer: number }> = ({ layer }) => (
    <div
        className={`absolute rounded-3xl overflow-hidden border-2 border-ink/20 skeleton-shimmer ${STACK_LAYERS[layer]}`}
        aria-hidden="true"
    />
);

const HOW_IT_WORKS = [
    {
        step: '01',
        title: 'Find a dish you love',
        text: 'Search thousands of community recipes by cuisine, category or ingredient. Ratings are ranked with a fair Bayesian score.',
        icon: Utensils,
        bg: 'bg-turmeric-soft',
        chip: 'bg-turmeric text-ink',
        number: 'text-turmeric-deep',
    },
    {
        step: '02',
        title: 'Drop it in your week',
        text: 'One tap adds a recipe to your meal plan. Scale servings up or down and the portions follow along.',
        icon: CalendarDays,
        bg: 'bg-mint-soft',
        chip: 'bg-mint text-ink',
        number: 'text-mint-deep',
    },
    {
        step: '03',
        title: 'Shop one merged list',
        text: 'Ingredients across every planned recipe are merged by unit, so 2 onions here and 1 there become one line.',
        icon: ShoppingBasket,
        bg: 'bg-plum-soft',
        chip: 'bg-plum text-white',
        number: 'text-plum-deep',
    },
];

export const HomePage: React.FC = () => {
    const { user, demoLogin } = useAuth();
    const filters = useRecipeFilters();
    const { search, cuisine: selectedCuisine, category: selectedCategory, sort: selectedSort, page } = filters;
    const setSelectedSort = filters.setSort;
    const setPage = filters.setPage;
    const [searchDraft, setSearchDraft] = useState(search);
    const [isDemoLoading, setIsDemoLoading] = useState(false);
    const [activeHeroIndex, setActiveHeroIndex] = useState(0);

    // Fetch recipes
    const { data: recipesData, isLoading: isRecipesLoading } = useQuery({
        queryKey: ['recipes', { search, selectedCuisine, selectedCategory, selectedSort, page }],
        queryFn: () =>
            recipesApi.list({
                q: search || undefined,
                cuisine: selectedCuisine || undefined,
                category: selectedCategory || undefined,
                sort: selectedSort,
                page,
                per_page: 12,
            }),
    });

    // Fetch cuisines list
    const { data: cuisinesData } = useQuery({
        queryKey: ['cuisines'],
        queryFn: () => recipesApi.cuisines(),
        staleTime: 1000 * 60 * 10,
    });

    // Fetch categories list
    const { data: categoriesData } = useQuery({
        queryKey: ['categories'],
        queryFn: () => recipesApi.categories(),
        staleTime: 1000 * 60 * 10,
    });

    // Featured hero recipes (card stack)
    const heroRecipes = recipesData?.data?.slice(0, 4) || [];
    const stackRecipes = heroRecipes.length
        ? [0, 1, 2].map((offset) => heroRecipes[(activeHeroIndex + offset) % heroRecipes.length]).slice(0, Math.min(3, heroRecipes.length))
        : [];

    const handleNextHero = () => {
        if (heroRecipes.length > 0) {
            setActiveHeroIndex((prev) => (prev + 1) % heroRecipes.length);
        }
    };

    const handlePrevHero = () => {
        if (heroRecipes.length > 0) {
            setActiveHeroIndex((prev) => (prev - 1 + heroRecipes.length) % heroRecipes.length);
        }
    };

    // The hero search box holds a draft until submitted; the URL holds the truth.
    useEffect(() => {
        setSearchDraft(search);
    }, [search]);

    const handleResetFilters = () => {
        filters.reset();
        setSearchDraft('');
    };

    const handleCuisineSelect = (cuisine: string) => filters.setCuisine(cuisine);
    const handleCategorySelect = (category: string) => filters.setCategory(category);

    const handleSearchChange = (value: string) => {
        filters.setSearch(value);
        setSearchDraft(value);
    };

    const handleSearchSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        handleSearchChange(searchDraft.trim());
        document.getElementById('recipes')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    };

    const handleQuickDemo = async () => {
        setIsDemoLoading(true);
        try {
            await demoLogin();
        } catch (err) {
            console.error('Demo login failed:', err);
        } finally {
            setIsDemoLoading(false);
        }
    };

    // Hero stats (live when loaded, friendly labels otherwise)
    const totalRecipes = recipesData?.meta?.total;
    const cuisineCount = cuisinesData?.data?.length;
    const ratedRecipes = (recipesData?.data || []).filter((r) => r.ratings_avg && r.ratings_avg > 0);
    const avgRating = ratedRecipes.length
        ? ratedRecipes.reduce((sum, r) => sum + (r.ratings_avg || 0), 0) / ratedRecipes.length
        : null;

    const stats = [
        {
            icon: Utensils,
            value: totalRecipes ? totalRecipes.toLocaleString() : 'Hundreds of',
            label: 'recipes',
            tint: 'bg-saffron-soft text-saffron-deep',
        },
        {
            icon: Globe2,
            value: cuisineCount ? String(cuisineCount) : 'Global',
            label: 'cuisines',
            tint: 'bg-plum-soft text-plum-deep',
        },
        {
            icon: Star,
            value: avgRating ? avgRating.toFixed(1) : 'Community',
            label: avgRating ? 'avg rating' : 'rated',
            tint: 'bg-turmeric-soft text-turmeric-deep',
        },
    ];

    const container = 'max-w-7xl mx-auto px-4 sm:px-6 lg:px-8';

    return (
        <div className="pb-20">
            {/* ============================= HERO ============================= */}
            <section className="bg-mesh relative overflow-hidden">
                <div className="pointer-events-none absolute -top-24 -left-24 w-72 h-72 rounded-full bg-turmeric/30 blur-3xl animate-float" />
                <div className="pointer-events-none absolute top-1/2 -right-32 w-96 h-96 blob-2 bg-plum/15 blur-3xl" />
                <div className="pointer-events-none absolute inset-0 bg-dots opacity-40 [mask-image:radial-gradient(60%_60%_at_50%_40%,black,transparent)]" />

                <div className={`${container} relative py-14 sm:py-20 lg:py-24 grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-8 items-center`}>
                    {/* Left: headline + search */}
                    <div className="lg:col-span-7 space-y-7 animate-slide-up">
                        <span className="sticker inline-flex items-center gap-2 px-4 py-2 rounded-full bg-ink text-white text-xs sm:text-sm font-extrabold shadow-pop-saffron">
                            <Sparkles className="w-4 h-4 text-turmeric" />
                            Community recipes
                            <span className="text-saffron" aria-hidden="true">
                                &bull;
                            </span>
                            Weekly meal plans
                        </span>

                        <h1 className="font-display font-extrabold text-5xl md:text-7xl leading-[0.95] tracking-tight text-ink text-balance">
                            Cook something{' '}
                            <span className="text-spice">delicious</span> every single week.
                        </h1>

                        <p className="text-lg sm:text-xl text-ink-2 max-w-xl leading-relaxed text-pretty">
                            Discover dishes from home cooks around the world, plan your week in a tap, and get one
                            smart shopping list with every ingredient merged.
                        </p>

                        {/* Search bar */}
                        <form
                            onSubmit={handleSearchSubmit}
                            role="search"
                            className="relative flex items-center w-full max-w-2xl rounded-full bg-paper border-2 border-ink p-1.5 pl-5 shadow-pop focus-within:shadow-glow-saffron transition-shadow"
                        >
                            <Search className="w-5 h-5 text-saffron-deep shrink-0" aria-hidden="true" />
                            <label htmlFor="hero-search" className="sr-only">
                                Search recipes
                            </label>
                            <input
                                id="hero-search"
                                type="search"
                                value={searchDraft}
                                onChange={(e) => setSearchDraft(e.target.value)}
                                placeholder="Try “khichuri”, “mustard fish” or “curry”"
                                className="flex-1 min-w-0 bg-transparent px-3 py-3 text-base text-ink placeholder:text-ink-3 font-medium focus:outline-none [&::-webkit-search-cancel-button]:hidden"
                            />
                            {searchDraft && (
                                <button
                                    type="button"
                                    onClick={() => handleSearchChange('')}
                                    className="mr-1 p-2 rounded-full text-ink-2 hover:bg-cream-2 hover:text-ink cursor-pointer focus-visible:outline-3 focus-visible:outline-saffron"
                                    aria-label="Clear search"
                                >
                                    <X className="w-4 h-4" />
                                </button>
                            )}
                            <button
                                type="submit"
                                className="inline-flex items-center gap-2 px-5 sm:px-6 py-3 rounded-full bg-saffron text-ink font-display font-extrabold text-sm sm:text-base shadow-glow-saffron hover:bg-turmeric hover:-translate-y-0.5 active:scale-95 transition-all cursor-pointer focus-visible:outline-3 focus-visible:outline-ink focus-visible:outline-offset-2"
                            >
                                <span className="hidden sm:inline">Search</span>
                                <ArrowRight className="w-5 h-5" />
                            </button>
                        </form>

                        {/* Stat chips + demo */}
                        <div className="flex flex-wrap items-center gap-2.5">
                            {stats.map(({ icon: Icon, value, label, tint }) => (
                                <span
                                    key={label}
                                    className={`inline-flex items-center gap-2 pl-2 pr-3.5 py-1.5 rounded-full ${tint} text-sm font-bold`}
                                >
                                    <span className="w-7 h-7 rounded-full bg-white/70 flex items-center justify-center">
                                        <Icon className="w-3.5 h-3.5" />
                                    </span>
                                    <span className="font-display font-extrabold">{value}</span>
                                    <span className="opacity-80">{label}</span>
                                </span>
                            ))}

                            {!user && (
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    onClick={handleQuickDemo}
                                    isLoading={isDemoLoading}
                                    className="rounded-full border-2 border-dashed border-ink/30 hover:border-ink"
                                >
                                    <Sparkles className="w-3.5 h-3.5 text-saffron" />
                                    <span>1-click demo</span>
                                </Button>
                            )}
                        </div>
                    </div>

                    {/* Right: floating card stack */}
                    <div className="lg:col-span-5 flex flex-col items-center gap-6 animate-scale-in animation-delay-150">
                        <div className="relative w-full max-w-xs sm:max-w-sm aspect-4/5">
                            {stackRecipes.length > 0
                                ? stackRecipes.map((recipe, layer) => (
                                      <HeroStackCard key={`${recipe.id}-${layer}`} recipe={recipe} layer={layer} />
                                  ))
                                : [0, 1, 2].map((layer) => <HeroStackSkeleton key={layer} layer={layer} />)}

                            {/* Floating pills */}
                            <div className="sticker absolute z-40 -left-3 sm:-left-8 bottom-10 inline-flex items-center gap-2 px-3.5 py-2 rounded-full bg-mint text-ink text-xs sm:text-sm font-extrabold shadow-glow-mint animate-pop-in animation-delay-300">
                                <CheckCircle2 className="w-4 h-4" />
                                Shopping list merged
                            </div>
                            <div className="sticker-r absolute z-40 -right-2 sm:-right-6 top-6 inline-flex items-center gap-1.5 px-3.5 py-2 rounded-full bg-plum text-white text-xs sm:text-sm font-extrabold shadow-glow-plum animate-pop-in animation-delay-500">
                                <Star className="w-4 h-4 fill-turmeric text-turmeric" />
                                {heroRecipes[activeHeroIndex]?.bayesian_score
                                    ? `${heroRecipes[activeHeroIndex].bayesian_score?.toFixed(1)} Bayesian`
                                    : 'Fairly ranked'}
                            </div>
                        </div>

                        {/* Shuffle controls */}
                        {heroRecipes.length > 1 && (
                            <div className="flex items-center gap-3">
                                <button
                                    type="button"
                                    onClick={handlePrevHero}
                                    className="w-10 h-10 rounded-full bg-paper border-2 border-ink text-ink hover:bg-ink hover:text-white flex items-center justify-center transition-colors cursor-pointer focus-visible:outline-3 focus-visible:outline-saffron focus-visible:outline-offset-2"
                                    title="Previous featured recipe"
                                    aria-label="Previous recipe"
                                >
                                    <ChevronLeft className="w-4 h-4" />
                                </button>

                                <div className="flex items-center gap-1.5">
                                    {heroRecipes.map((_, idx) => (
                                        <button
                                            key={idx}
                                            type="button"
                                            onClick={() => setActiveHeroIndex(idx)}
                                            aria-label={`Slide ${idx + 1}`}
                                            className={`h-2.5 rounded-full transition-all cursor-pointer ${
                                                idx === activeHeroIndex ? 'w-8 bg-saffron' : 'w-2.5 bg-ink/25 hover:bg-ink/50'
                                            }`}
                                        />
                                    ))}
                                </div>

                                <button
                                    type="button"
                                    onClick={handleNextHero}
                                    className="w-10 h-10 rounded-full bg-paper border-2 border-ink text-ink hover:bg-ink hover:text-white flex items-center justify-center transition-colors cursor-pointer focus-visible:outline-3 focus-visible:outline-saffron focus-visible:outline-offset-2"
                                    title="Next featured recipe"
                                    aria-label="Next recipe"
                                >
                                    <ChevronRight className="w-4 h-4" />
                                </button>
                                <span className="hidden sm:inline-flex items-center gap-1 text-xs font-bold text-ink-3 ml-1">
                                    <Shuffle className="w-3.5 h-3.5" /> shuffle
                                </span>
                            </div>
                        )}
                    </div>
                </div>
            </section>

            {/* ========================== CUISINE STRIP ========================== */}
            <section className={`${container} py-10 sm:py-14`} aria-labelledby="cuisines-heading">
                <div className="flex items-end justify-between gap-4 mb-5">
                    <div>
                        <p className="text-xs font-extrabold uppercase tracking-[0.16em] text-saffron-deep mb-1">
                            Browse by
                        </p>
                        <h2 id="cuisines-heading" className="text-3xl sm:text-4xl">
                            Pick a <span className="text-sunrise">cuisine</span>
                        </h2>
                    </div>
                    <p className="hidden sm:block text-sm text-ink-3 font-medium">Scroll sideways &rarr;</p>
                </div>

                <CuisineFilters
                    search={search}
                    onSearchChange={handleSearchChange}
                    selectedCuisine={selectedCuisine}
                    onSelectCuisine={handleCuisineSelect}
                    selectedCategory={selectedCategory}
                    onSelectCategory={handleCategorySelect}
                    selectedSort={selectedSort}
                    onSelectSort={(sort) => {
                        setSelectedSort(sort);
                        setPage(1);
                    }}
                    cuisines={cuisinesData?.data || []}
                    categories={categoriesData?.data || []}
                    onReset={handleResetFilters}
                    hideSearch
                    hideControls
                />
            </section>

            {/* ========================== HOW IT WORKS ========================== */}
            <section className={`${container} py-6 sm:py-10`} aria-labelledby="how-heading">
                <div className="text-center max-w-2xl mx-auto mb-8 sm:mb-10">
                    <span className="sticker-r inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-chili-soft text-chili-deep text-xs font-extrabold uppercase tracking-wider">
                        <Flame className="w-3.5 h-3.5" /> How it works
                    </span>
                    <h2 id="how-heading" className="mt-4 text-3xl sm:text-5xl">
                        From craving to <span className="text-spice">cart</span> in three steps
                    </h2>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-3 gap-4 sm:gap-5">
                    {HOW_IT_WORKS.map(({ step, title, text, icon: Icon, bg, chip, number }, i) => (
                        <article
                            key={step}
                            className={`relative overflow-hidden rounded-3xl ${bg} p-7 sm:p-8 flex flex-col gap-5 min-h-64 transition-transform duration-300 hover:-translate-y-1.5 hover:shadow-lg animate-slide-up ${
                                i === 1 ? 'animation-delay-100' : i === 2 ? 'animation-delay-200' : ''
                            }`}
                        >
                            <span
                                className={`absolute -top-4 -right-2 font-display font-extrabold text-[7rem] leading-none ${number} opacity-20 select-none`}
                                aria-hidden="true"
                            >
                                {step}
                            </span>
                            <div className="flex items-center justify-between">
                                <span className={`w-14 h-14 rounded-2xl ${chip} flex items-center justify-center shadow-md`}>
                                    <Icon className="w-7 h-7" />
                                </span>
                                <span className={`font-display font-extrabold text-sm ${number}`}>Step {step}</span>
                            </div>
                            <div className="relative">
                                <h3 className="text-2xl mb-2">{title}</h3>
                                <p className="text-ink-2 leading-relaxed text-pretty">{text}</p>
                            </div>
                        </article>
                    ))}
                </div>
            </section>

            {/* =========================== RECIPE GRID =========================== */}
            <section id="recipes" className={`${container} py-10 sm:py-14 scroll-mt-24`} aria-labelledby="recipes-heading">
                <div className="flex flex-col gap-5 mb-8">
                    <div className="flex flex-col sm:flex-row sm:items-end justify-between gap-4">
                        <div className="flex flex-wrap items-center gap-3">
                            <h2 id="recipes-heading" className="text-3xl sm:text-4xl">
                                {search
                                    ? `Results for “${search}”`
                                    : selectedCuisine
                                      ? `${selectedCuisine} recipes`
                                      : 'Fresh from the community'}
                            </h2>
                            {!isRecipesLoading && recipesData?.meta && (
                                <span className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-ink text-white text-xs font-extrabold animate-pop-in">
                                    <Utensils className="w-3.5 h-3.5 text-turmeric" />
                                    {recipesData.meta.total} {recipesData.meta.total === 1 ? 'recipe' : 'recipes'}
                                </span>
                            )}
                        </div>
                        <p className="text-sm text-ink-3 font-medium">
                            {selectedCategory ? `Filtered to ${selectedCategory}` : 'Ranked by community rating'}
                        </p>
                    </div>

                    <div className="rounded-3xl bg-paper border border-line p-3 sm:p-4">
                        <CuisineFilters
                            search={search}
                            onSearchChange={handleSearchChange}
                            selectedCuisine={selectedCuisine}
                            onSelectCuisine={handleCuisineSelect}
                            selectedCategory={selectedCategory}
                            onSelectCategory={handleCategorySelect}
                            selectedSort={selectedSort}
                            onSelectSort={(sort) => {
                                setSelectedSort(sort);
                                setPage(1);
                            }}
                            cuisines={cuisinesData?.data || []}
                            categories={categoriesData?.data || []}
                            onReset={handleResetFilters}
                            hideCuisines
                        />
                    </div>
                </div>

                <RecipeGrid
                    recipes={recipesData?.data || []}
                    isLoading={isRecipesLoading}
                    meta={recipesData?.meta}
                    onPageChange={(newPage) => setPage(newPage)}
                    onResetFilters={handleResetFilters}
                />
            </section>

            {/* ============================= CTA BAND ============================= */}
            <section className={`${container} pt-6`}>
                <div className="relative overflow-hidden rounded-4xl bg-saffron px-6 py-12 sm:px-12 sm:py-16 lg:px-16 text-center lg:text-left">
                    <div className="pointer-events-none absolute inset-0 bg-dots-light opacity-70" />
                    <div className="pointer-events-none absolute -right-16 -top-16 w-64 h-64 blob-1 bg-turmeric/70 animate-spin-slow" />
                    <div className="pointer-events-none absolute -left-10 -bottom-20 w-56 h-56 blob-2 bg-chili/50" />
                    <div className="relative flex flex-col lg:flex-row items-center justify-between gap-8">
                        <div className="max-w-2xl">
                            <span className="sticker inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-ink text-white text-xs font-extrabold uppercase tracking-wider">
                                <PenLine className="w-3.5 h-3.5 text-turmeric" /> Share yours
                            </span>
                            <h2 className="mt-4 text-3xl sm:text-5xl text-ink text-balance">
                                Cooked something great? Share your recipe.
                            </h2>
                            <p className="mt-3 text-ink/80 text-base sm:text-lg font-medium text-pretty">
                                Join the community, get rated by fellow home cooks, and help someone find their new
                                favourite dinner.
                            </p>
                        </div>
                        <Link
                            to={user ? '/recipes/create' : '/login'}
                            className="group inline-flex items-center gap-3 px-7 py-4 rounded-full bg-ink text-white font-display font-extrabold text-base sm:text-lg shadow-pop-sm hover:-translate-y-1 hover:shadow-glow-plum transition-all focus-visible:outline-3 focus-visible:outline-ink focus-visible:outline-offset-4 whitespace-nowrap"
                        >
                            {user ? 'Create a recipe' : 'Log in to share'}
                            <span className="w-8 h-8 rounded-full bg-saffron text-ink flex items-center justify-center group-hover:translate-x-1 transition-transform">
                                <ArrowRight className="w-4 h-4" />
                            </span>
                        </Link>
                    </div>
                </div>
            </section>
        </div>
    );
};
