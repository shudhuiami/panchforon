import React from 'react';
import { useNavigate } from 'react-router-dom';
import { Heart } from 'lucide-react';
import { useSavedRecipes } from '../features/saves/useSavedRecipes';
import { RecipeGrid } from '../features/recipes/RecipeGrid';
import { EmptyState } from '../components/common/EmptyState';
import { useRecipeFilters } from '../features/recipes/useRecipeFilters';

export const WishlistPage: React.FC = () => {
    const navigate = useNavigate();
    const { page, setPage } = useRecipeFilters();
    const { data, isLoading } = useSavedRecipes(page);

    const recipes = data?.data ?? [];
    const total = data?.meta?.total;

    return (
        <div className="mx-auto w-full max-w-7xl px-4 py-8 sm:px-6 lg:px-8 lg:py-12">
            <header className="mb-8 max-w-2xl animate-slide-up">
                <p className="text-xs font-semibold tracking-[0.22em] text-primary uppercase">Saved</p>
                <h1 className="mt-3 font-display text-4xl font-semibold tracking-tight text-ink text-balance sm:text-5xl lg:text-6xl">Recipes you kept.</h1>
                <p className="mt-4 text-base text-ink-2 sm:text-lg">
                    {isLoading || total === undefined
                        ? 'Gathering your saved dishes…'
                        : total === 0
                          ? 'Tap the heart on any recipe and it waits for you here.'
                          : `${total} ${total === 1 ? 'dish' : 'dishes'} waiting for a free evening.`}
                </p>
            </header>

            {!isLoading && recipes.length === 0 ? (
                <EmptyState
                    icon={Heart}
                    title="Nothing saved yet"
                    description="Tap the heart on a recipe and it lands here, ready for the week you finally have time to cook it."
                    actionLabel="Browse recipes"
                    onAction={() => navigate('/recipes')}
                />
            ) : (
                <RecipeGrid recipes={recipes} isLoading={isLoading} meta={data?.meta} onPageChange={setPage} />
            )}
        </div>
    );
};
