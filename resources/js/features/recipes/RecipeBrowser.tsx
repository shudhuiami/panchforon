import React, { useEffect } from 'react';
import { useQuery } from '@tanstack/react-query';
import { recipesApi } from '../../api/recipes';
import { CuisineFilters } from './CuisineFilters';
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

    useEffect(() => {
        if (focusSearch) {
            document.querySelector<HTMLInputElement>('input[type="search"]')?.focus();
        }
    }, [focusSearch]);

    return (
        <div className="space-y-6">
            <div className="rounded-3xl border border-line bg-surface p-3 sm:p-4">
                <CuisineFilters
                    search={filters.search}
                    onSearchChange={filters.setSearch}
                    selectedCuisine={filters.cuisine}
                    onSelectCuisine={filters.setCuisine}
                    selectedCategory={filters.category}
                    onSelectCategory={filters.setCategory}
                    selectedSort={filters.sort}
                    onSelectSort={filters.setSort}
                    cuisines={cuisines?.data ?? []}
                    categories={categories?.data ?? []}
                    onReset={filters.reset}
                />
            </div>
            <RecipeGrid
                recipes={recipes?.data ?? []}
                isLoading={isLoading}
                meta={recipes?.meta}
                onPageChange={filters.setPage}
                onResetFilters={filters.reset}
            />
        </div>
    );
};
