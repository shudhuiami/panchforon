import React from 'react';
import { useLocation } from 'react-router-dom';
import { RecipeBrowser } from '../features/recipes/RecipeBrowser';
import { useRecipeFilters } from '../features/recipes/useRecipeFilters';

export const RecipesPage: React.FC = () => {
    const location = useLocation();
    const { search, cuisine } = useRecipeFilters();
    const focusSearch = Boolean((location.state as { focusSearch?: boolean } | null)?.focusSearch);

    const title = search ? `Results for “${search}”` : cuisine ? `${cuisine} recipes` : 'Every recipe, one kitchen.';

    return (
        <div className="mx-auto w-full max-w-7xl px-4 py-8 sm:px-6 lg:px-8 lg:py-12">
            <header className="mb-8 max-w-2xl animate-slide-up">
                <p className="text-xs font-semibold tracking-[0.22em] text-primary uppercase">Browse</p>
                <h1 className="mt-3 font-display text-4xl font-semibold tracking-tight text-ink text-balance sm:text-5xl lg:text-6xl">{title}</h1>
                <p className="mt-4 text-base text-ink-2 sm:text-lg">
                    Search by dish, ingredient or spice, narrow by cuisine, and sort by what the community actually cooks again.
                </p>
            </header>
            <RecipeBrowser focusSearch={focusSearch} />
        </div>
    );
};
