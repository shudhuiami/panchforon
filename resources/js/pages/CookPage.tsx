import React from 'react';
import { useParams } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { UserRoundX } from 'lucide-react';
import { cooksApi } from '../api/cooks';
import { Avatar } from '../components/ui/Avatar';
import { Breadcrumb } from '../components/ui/Breadcrumb';
import { LoadingSpinner } from '../components/common/LoadingSpinner';
import { StatusPanel } from '../components/common/StatusPanel';
import { RecipeGrid } from '../features/recipes/RecipeGrid';
import { useRecipeFilters } from '../features/recipes/useRecipeFilters';

export const CookPage: React.FC = () => {
    const { id } = useParams<{ id: string }>();
    const cookId = Number(id);
    const { page, setPage } = useRecipeFilters();

    const { data, isLoading, error } = useQuery({
        queryKey: ['cook', cookId, page],
        queryFn: () => cooksApi.get(cookId, page),
        enabled: Number.isFinite(cookId),
    });

    if (isLoading) return <LoadingSpinner message="Loading the cook…" />;

    if (error || !data) {
        return <StatusPanel icon={UserRoundX} title="Cook not found" text="This page may have been taken down, or the link is wrong." action={{ label: 'Browse recipes', to: '/recipes' }} />;
    }

    const { cook, recipes } = data.data;
    const joined = cook.created_at ? new Date(cook.created_at).toLocaleDateString(undefined, { year: 'numeric', month: 'long' }) : null;

    return (
        <div className="mx-auto w-full max-w-7xl px-4 py-8 sm:px-6 lg:px-8 lg:py-12">
            <Breadcrumb items={[{ label: 'Recipes', to: '/recipes' }, { label: cook.name }]} />

            <header className="mt-6 flex animate-slide-up flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
                <div className="flex items-center gap-5">
                    <Avatar name={cook.name} size="lg" className="size-16 text-xl" />
                    <div>
                        <p className="text-xs font-semibold tracking-[0.22em] text-primary uppercase">Cook</p>
                        <h1 className="mt-2 font-display text-4xl font-semibold tracking-tight text-ink text-balance sm:text-5xl">{cook.name}</h1>
                        {joined && <p className="mt-2 text-sm text-ink-3">Cooking here since {joined}</p>}
                    </div>
                </div>
                <dl className="flex gap-x-6">
                    <div className="flex items-baseline gap-1.5">
                        <dd className="font-display text-2xl font-semibold text-ink tabular-nums">{cook.recipes_count ?? 0}</dd>
                        <dt className="text-sm text-ink-3">{cook.recipes_count === 1 ? 'recipe' : 'recipes'}</dt>
                    </div>
                    <div className="flex items-baseline gap-1.5">
                        <dd className="font-display text-2xl font-semibold text-ink tabular-nums">{cook.ratings_count ?? 0}</dd>
                        <dt className="text-sm text-ink-3">{cook.ratings_count === 1 ? 'rating' : 'ratings'}</dt>
                    </div>
                </dl>
            </header>

            <div className="mt-10">
                <RecipeGrid recipes={recipes.data} isLoading={false} meta={recipes.meta} onPageChange={setPage} />
            </div>
        </div>
    );
};
