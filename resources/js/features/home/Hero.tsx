import React, { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { ArrowUpRight, Star } from 'lucide-react';
import { HomeCuisine, HomeStats, RecipeList } from '../../types/api';
import { SearchBox } from '../recipes/SearchBox';
import { Photo } from '../../components/ui/Photo';

const HEADLINE: Array<{ word: string; spice?: boolean }> = [
    { word: 'Cook' },
    { word: 'the' },
    { word: 'week' },
    { word: 'you’ll' },
    { word: 'actually', spice: true },
    { word: 'look' },
    { word: 'forward' },
    { word: 'to.' },
];

const FALLBACKS = ['bg-spice-gradient', 'bg-plum-gradient', 'bg-mint-gradient', 'bg-sunrise-gradient'];

const ratingLabel = (recipe: RecipeList) => (recipe.ratings_avg ? recipe.ratings_avg.toFixed(1) : 'New');

/** The featured recipe as a tall card, desktop only. */
const FeaturedCard: React.FC<{ recipe: RecipeList }> = ({ recipe }) => (
    <div className="relative mx-auto max-w-sm animate-scale-in animation-delay-300">
        <Link
            to={`/recipes/${recipe.slug}`}
            className="group relative block aspect-4/5 overflow-hidden rounded-[2rem] border border-white/10 bg-surface-2 shadow-xl transition-[transform,box-shadow] duration-500 ease-out hover:-translate-y-2 hover:shadow-glow-primary focus-visible:outline-3 focus-visible:outline-primary focus-visible:outline-offset-4"
        >
            <Photo
                src={recipe.image_url}
                className="absolute inset-0 h-full w-full object-cover transition-transform duration-700 ease-out group-hover:scale-105"
                fallback={<div className={`absolute inset-0 ${FALLBACKS[recipe.id % FALLBACKS.length]} bg-dots-light`} />}
            />
            <div className="img-fade absolute inset-0" />
            <span className="absolute top-5 left-5 rounded-full bg-canvas/60 px-3 py-1 text-[11px] font-semibold tracking-[0.18em] text-turmeric uppercase backdrop-blur">
                Featured
            </span>
            <div className="absolute inset-x-0 bottom-0 p-6">
                <p className="text-xs font-medium tracking-[0.16em] text-ink-2 uppercase">
                    {[recipe.cuisine, recipe.category].filter(Boolean).join(' · ')}
                </p>
                <h2 className="mt-2 font-display text-3xl leading-tight font-semibold text-ink">{recipe.title}</h2>
                <div className="mt-4 flex items-center justify-between">
                    <span className="inline-flex items-center gap-1.5 text-sm text-ink-2">
                        <Star className="size-4 fill-turmeric text-turmeric" aria-hidden="true" />
                        {ratingLabel(recipe)}
                        <span className="text-ink-3">· {recipe.ratings_count} ratings</span>
                    </span>
                    <span className="inline-flex size-10 items-center justify-center rounded-full bg-primary text-on-primary transition-transform duration-300 group-hover:rotate-45">
                        <ArrowUpRight className="size-5" aria-hidden="true" />
                    </span>
                </div>
            </div>
        </Link>

        <span className="absolute top-10 -left-6 animate-pop-in rounded-full border border-line bg-surface/90 px-3.5 py-2 text-sm font-medium text-ink shadow-lg backdrop-blur animation-delay-500">
            Serves {recipe.servings}
        </span>
        <span
            className="absolute top-[44%] -right-5 animate-pop-in rounded-full bg-turmeric px-3.5 py-2 text-sm font-semibold text-on-primary shadow-glow-turmeric"
            style={{ animationDelay: '720ms' }}
        >
            Community pick
        </span>
    </div>
);

/** The same recipe as a compact row for phones and tablets. */
const FeaturedRow: React.FC<{ recipe: RecipeList; className?: string }> = ({ recipe, className = '' }) => (
    <Link
        to={`/recipes/${recipe.slug}`}
        className={`group flex items-center gap-4 rounded-3xl border border-line bg-surface/80 p-3 pr-4 backdrop-blur transition-colors hover:border-primary/50 ${className}`}
    >
        <span className="relative size-20 shrink-0 overflow-hidden rounded-2xl bg-surface-2">
            <Photo
                src={recipe.image_url}
                className="h-full w-full object-cover"
                fallback={<span className={`block h-full w-full ${FALLBACKS[recipe.id % FALLBACKS.length]}`} />}
            />
        </span>
        <span className="min-w-0 flex-1">
            <span className="block text-[11px] font-semibold tracking-[0.18em] text-turmeric uppercase">Featured</span>
            <span className="mt-1 block truncate font-display text-lg font-semibold text-ink">{recipe.title}</span>
            <span className="mt-1 block text-xs text-ink-3">
                {recipe.cuisine ?? 'Recipe'} · ★ {ratingLabel(recipe)}
            </span>
        </span>
        <ArrowUpRight className="size-5 shrink-0 text-ink-3 transition-transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5" aria-hidden="true" />
    </Link>
);

interface HeroProps {
    featured: RecipeList | null;
    cuisines: HomeCuisine[];
    stats?: HomeStats;
    isLoading: boolean;
}

/**
 * Full-bleed opener: drifting colour behind the featured recipe's photo, an
 * editorial headline, and the one search box the page owns.
 */
export const Hero: React.FC<HeroProps> = ({ featured, cuisines, stats, isLoading }) => {
    const navigate = useNavigate();
    const [backdropLoaded, setBackdropLoaded] = useState(false);
    const [backdropFailed, setBackdropFailed] = useState(false);
    const backdrop = featured?.image_url && !backdropFailed ? featured.image_url : null;

    return (
        <section
            className="relative isolate -mt-[calc(4rem_+_env(safe-area-inset-top))] flex min-h-[88svh] items-center overflow-hidden pt-[calc(6rem_+_env(safe-area-inset-top))] pb-16 lg:min-h-[92svh] lg:pb-24"
            aria-labelledby="hero-heading"
        >
            <div className="absolute inset-0 -z-10" aria-hidden="true">
                <div className="aurora top-[-22%] left-[-14%] size-[62vmax] animate-aurora-a bg-primary/55" />
                <div className="aurora top-[2%] right-[-20%] size-[60vmax] animate-aurora-b bg-plum/45" />
                <div className="aurora bottom-[-30%] left-[24%] size-[54vmax] animate-aurora-c bg-hot/40" />
                <div className="aurora right-[18%] bottom-[2%] size-[34vmax] animate-aurora-a bg-mint/30 [animation-delay:-9s]" />
                {backdrop && (
                    <img
                        src={backdrop}
                        alt=""
                        fetchPriority="high"
                        onLoad={() => setBackdropLoaded(true)}
                        onError={() => setBackdropFailed(true)}
                        className={`absolute inset-0 h-full w-full animate-ken-burns object-cover transition-opacity duration-[1400ms] ${backdropLoaded ? 'opacity-100' : 'opacity-0'}`}
                    />
                )}
                <div className="absolute inset-0 bg-linear-to-t from-canvas via-canvas/70 to-canvas/25" />
                <div className="absolute inset-0 bg-linear-to-r from-canvas/90 via-canvas/45 to-transparent" />
                <div className="absolute inset-0 bg-dots opacity-20 [mask-image:radial-gradient(70%_60%_at_50%_30%,black,transparent)]" />
            </div>

            <div className="mx-auto grid w-full max-w-7xl grid-cols-1 items-center gap-12 px-4 sm:px-6 lg:grid-cols-12 lg:gap-8 lg:px-8">
                <div className="lg:col-span-7">
                    <p className="inline-flex animate-fade-in items-center gap-2.5 rounded-full border border-line bg-canvas/50 px-3.5 py-1.5 text-xs font-medium text-ink-2 backdrop-blur">
                        <span className="relative flex size-2">
                            <span className="absolute inset-0 animate-pulse-dot rounded-full bg-primary" />
                            <span className="relative size-2 rounded-full bg-primary" />
                        </span>
                        {stats ? `${stats.recipes.toLocaleString()} recipes from ${stats.cooks.toLocaleString()} home cooks` : 'Community recipes, ranked fairly'}
                    </p>

                    <h1
                        id="hero-heading"
                        className="mt-6 font-display text-[2.9rem] leading-[0.98] font-semibold tracking-[-0.02em] text-ink sm:text-6xl lg:text-7xl xl:text-[5.6rem]"
                    >
                        {HEADLINE.map(({ word, spice }, index) => (
                            <span key={word} className="mr-[0.22em] -mb-[0.14em] inline-block overflow-hidden pb-[0.14em] align-bottom">
                                <span
                                    className={`headline-word ${spice ? 'text-spice font-medium italic' : ''}`}
                                    style={{ animationDelay: `${120 + index * 70}ms` }}
                                >
                                    {word}
                                </span>
                            </span>
                        ))}
                    </h1>

                    <p className="mt-6 max-w-xl animate-slide-up text-lg leading-relaxed text-ink-2 animation-delay-500 sm:text-xl">
                        Recipes from home cooks in Bangladesh and far beyond, ranked by the people who cooked them. Plan the week in a tap
                        and shop from one merged list.
                    </p>

                    <div className="mt-8 max-w-2xl animate-slide-up animation-delay-500">
                        <SearchBox
                            size="lg"
                            placeholder="Search “khichuri”, “mustard fish”, “biryani”…"
                            onSubmit={(q) => navigate(q ? `/recipes?q=${encodeURIComponent(q)}` : '/recipes')}
                        />
                        <div className="no-scrollbar mt-4 flex items-center gap-2 overflow-x-auto py-1">
                            <span className="shrink-0 text-xs font-medium tracking-[0.18em] text-ink-3 uppercase">Popular</span>
                            {cuisines.slice(0, 4).map((c) => (
                                <Link
                                    key={c.cuisine}
                                    to={`/recipes?cuisine=${encodeURIComponent(c.cuisine)}`}
                                    className="shrink-0 rounded-full border border-line bg-surface/70 px-3.5 py-1.5 text-sm font-medium text-ink transition-colors hover:border-primary/60 hover:text-primary"
                                >
                                    {c.cuisine}
                                </Link>
                            ))}
                            <Link
                                to="/recipes?sort=latest"
                                className="shrink-0 rounded-full border border-line bg-surface/70 px-3.5 py-1.5 text-sm font-medium text-ink transition-colors hover:border-primary/60 hover:text-primary"
                            >
                                Newest
                            </Link>
                        </div>
                    </div>

                    {featured && <FeaturedRow recipe={featured} className="mt-8 animate-slide-up animation-delay-500 lg:hidden" />}
                </div>

                <div className="hidden lg:col-span-5 lg:block">
                    {featured ? (
                        <FeaturedCard recipe={featured} />
                    ) : isLoading ? (
                        <div className="skeleton-shimmer mx-auto aspect-4/5 max-w-sm rounded-[2rem]" aria-hidden="true" />
                    ) : null}
                </div>
            </div>

            <div className="absolute bottom-6 left-1/2 hidden -translate-x-1/2 flex-col items-center gap-2 lg:flex" aria-hidden="true">
                <span className="text-[10px] tracking-[0.3em] text-ink-3 uppercase">Scroll</span>
                <span className="h-10 w-px animate-scroll-cue bg-ink-3/70" />
            </div>
        </section>
    );
};
