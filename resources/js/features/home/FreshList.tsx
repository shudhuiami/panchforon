import React from 'react';
import { Link } from 'react-router-dom';
import { ArrowUpRight, Star } from 'lucide-react';
import { RecipeList } from '../../types/api';
import { useAuth } from '../../context/AuthContext';
import { ButtonLink } from '../../components/ui/Button';
import { Reveal } from '../../components/motion/Reveal';
import { Photo } from '../../components/ui/Photo';

const TINTS = ['bg-spice-gradient', 'bg-plum-gradient', 'bg-mint-gradient', 'bg-sunrise-gradient'];

const timeAgo = (iso?: string): string => {
    if (!iso) return '';
    const minutes = Math.max(0, Math.round((Date.now() - new Date(iso).getTime()) / 60000));
    const rtf = new Intl.RelativeTimeFormat('en', { numeric: 'auto' });
    if (minutes < 60) return rtf.format(-minutes, 'minute');
    const hours = Math.round(minutes / 60);
    if (hours < 24) return rtf.format(-hours, 'hour');
    const days = Math.round(hours / 24);
    if (days < 30) return rtf.format(-days, 'day');
    const months = Math.round(days / 30);
    if (months < 12) return rtf.format(-months, 'month');
    return rtf.format(-Math.round(months / 12), 'year');
};

const Row: React.FC<{ recipe: RecipeList }> = ({ recipe }) => (
    <li>
        <Link to={`/recipes/${recipe.slug}`} className="group flex items-center gap-4 p-4 transition-colors hover:bg-surface-2 sm:p-5">
            <span className="relative size-16 shrink-0 overflow-hidden rounded-2xl bg-surface-3">
                <Photo
                    src={recipe.image_url}
                    loading="lazy"
                    className="h-full w-full object-cover"
                    fallback={
                        <span className={`flex h-full w-full items-center justify-center font-display text-2xl font-semibold text-ink ${TINTS[recipe.id % TINTS.length]}`}>
                            {recipe.title.charAt(0)}
                        </span>
                    }
                />
            </span>
            <span className="min-w-0 flex-1">
                <span className="block truncate font-display text-lg font-semibold text-ink">{recipe.title}</span>
                <span className="mt-1 block text-xs text-ink-3">
                    {[recipe.cuisine, recipe.category, timeAgo(recipe.created_at)].filter(Boolean).join(' · ')}
                </span>
            </span>
            <span className="hidden items-center gap-1 text-sm text-ink-2 sm:inline-flex">
                <Star className="size-4 fill-turmeric text-turmeric" aria-hidden="true" />
                {recipe.ratings_avg ? recipe.ratings_avg.toFixed(1) : 'New'}
            </span>
            <ArrowUpRight className="size-5 shrink-0 text-ink-3 transition-transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5 group-hover:text-primary" aria-hidden="true" />
        </Link>
    </li>
);

/** The newest approved recipes, plus the invitation to add one. */
export const FreshList: React.FC<{ recipes: RecipeList[]; isLoading: boolean }> = ({ recipes, isLoading }) => {
    const { user } = useAuth();

    return (
        <section className="py-14 lg:py-20" aria-labelledby="fresh-heading">
            <div className="mx-auto grid max-w-7xl grid-cols-1 gap-10 px-4 sm:px-6 lg:grid-cols-12 lg:px-8">
                <Reveal className="lg:col-span-5">
                    <p className="text-xs font-semibold tracking-[0.22em] text-primary uppercase">Fresh from the kitchen</p>
                    <h2 id="fresh-heading" className="mt-3 font-display text-3xl font-semibold tracking-tight text-ink text-balance sm:text-4xl lg:text-5xl">
                        Just posted by home cooks.
                    </h2>
                    <p className="mt-4 max-w-md text-base text-ink-2">
                        Every recipe here was typed up at someone’s kitchen table. Add yours and it goes live once a moderator has had a look.
                    </p>
                    <div className="mt-6">
                        {user ? (
                            <ButtonLink to="/recipes/create">Post a recipe</ButtonLink>
                        ) : (
                            <ButtonLink to="/register">Join free and post yours</ButtonLink>
                        )}
                    </div>
                </Reveal>
                <Reveal delay={120} className="lg:col-span-7">
                    <ul className="divide-y divide-line overflow-hidden rounded-3xl border border-line bg-surface">
                        {isLoading
                            ? Array.from({ length: 4 }).map((_, i) => (
                                  <li key={i} className="flex items-center gap-4 p-4 sm:p-5" aria-hidden="true">
                                      <span className="skeleton-shimmer size-16 rounded-2xl" />
                                      <span className="skeleton-shimmer h-5 flex-1 rounded-full" />
                                  </li>
                              ))
                            : recipes.map((recipe) => <Row key={recipe.id} recipe={recipe} />)}
                    </ul>
                </Reveal>
            </div>
        </section>
    );
};
