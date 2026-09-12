import React from 'react';
import { useQuery } from '@tanstack/react-query';
import { recipesApi } from '../../api/recipes';
import { FilterBar, SORT_LABELS } from './FilterBar';
import { CuisineChips } from './CuisineChips';
import { RecipeGrid } from './RecipeGrid';
import { useRecipeFilters } from './useRecipeFilters';

/**
 * Filters, results and pagination for the recipe catalogue, driven entirely
 * by the URL so the state is shareable and survives navigation.
 */
export const RecipeBrowser: React.FC<{ focusSearch?: boolean }> = ({ focusSearch = false }) => {
    const filters = useRecipeFilters();

    const { data: recipes, isLoading } = useQuery({
        queryKey: ['recipes', { q: filters.search, cuisine: filters.cuisine, category: filters.category, sort: filters.sort, page: filters.page }],
        queryFn: () =>
            recipesApi.list({
                q: filters.search || undefined,
                cuisine: filters.cuisine || undefined,
                category: filters.category || undefined,
                sort: filters.sort,
                page: filters.page,
                per_page: 12,
            }),
    });

    const { data: cuisines } = useQuery({ queryKey: ['cuisines'], queryFn: () => recipesApi.cuisines(), staleTime: 1000 * 60 * 10 });
    const { data: categories } = useQuery({ queryKey: ['categories'], queryFn: () => recipesApi.categories(), staleTime: 1000 * 60 * 10 });

    const total = recipes?.meta?.total;

    return (
        <div className="space-y-5">
            <div className="rounded-3xl border border-line bg-surface p-3 sm:p-4">
                <FilterBar filters={filters} categories={categories?.data ?? []} focusSearch={focusSearch} />
            </div>

            <CuisineChips cuisines={cuisines?.data ?? []} selected={filters.cuisine} onSelect={filters.setCuisine} />

            <div className="flex items-center justify-between gap-3 text-sm text-ink-3" aria-live="polite">
                <span>{isLoading || total === undefined ? 'Finding recipes…' : `${total} ${total === 1 ? 'recipe' : 'recipes'}`}</span>
                <span>{SORT_LABELS[filters.sort]}</span>
            </div>

            <RecipeGrid recipes={recipes?.data ?? []} isLoading={isLoading} meta={recipes?.meta} onPageChange={filters.setPage} onResetFilters={filters.reset} />
        </div>
    );
};
