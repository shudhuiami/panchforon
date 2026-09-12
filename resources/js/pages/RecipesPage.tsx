import React from 'react';
import { useLocation } from 'react-router-dom';
import { RecipeBrowser } from '../features/recipes/RecipeBrowser';

export const RecipesPage: React.FC = () => {
    const location = useLocation();
    const focusSearch = Boolean((location.state as { focusSearch?: boolean } | null)?.focusSearch);

    return (
        <div className="mx-auto w-full max-w-7xl px-4 py-8 sm:px-6 sm:py-12 lg:px-8">
            <header className="mb-8 max-w-2xl">
                <p className="text-xs font-semibold uppercase tracking-[0.2em] text-primary">Browse</p>
                <h1 className="mt-2 font-display text-4xl font-semibold text-ink sm:text-5xl">Every recipe, one kitchen.</h1>
                <p className="mt-3 text-base text-ink-2">Search by dish, ingredient or spice, narrow by cuisine, and sort by what the community actually cooks again.</p>
            </header>
            <RecipeBrowser focusSearch={focusSearch} />
        </div>
    );
};
