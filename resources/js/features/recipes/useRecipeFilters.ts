import { useCallback, useMemo } from 'react';
import { useSearchParams } from 'react-router-dom';

export type RecipeSort = 'bayesian' | 'rating' | 'latest' | 'title';

const SORTS: RecipeSort[] = ['bayesian', 'rating', 'latest', 'title'];
const DEFAULT_SORT: RecipeSort = 'bayesian';

export interface RecipeFilters {
    search: string;
    cuisine: string;
    category: string;
    sort: RecipeSort;
    page: number;
    isFiltered: boolean;
    setSearch: (value: string) => void;
    setCuisine: (value: string) => void;
    setCategory: (value: string) => void;
    setSort: (value: RecipeSort) => void;
    setPage: (value: number) => void;
    reset: () => void;
}

/**
 * Recipe list filters live in the URL, so a filtered view can be shared,
 * survives refresh and the back button, and deep links like ?cuisine=Thai
 * from elsewhere in the app actually apply.
 */
export function useRecipeFilters(): RecipeFilters {
    const [params, setParams] = useSearchParams();

    const search = params.get('q') ?? '';
    const cuisine = params.get('cuisine') ?? '';
    const category = params.get('category') ?? '';
    const rawSort = params.get('sort');
    const sort: RecipeSort = SORTS.includes(rawSort as RecipeSort) ? (rawSort as RecipeSort) : DEFAULT_SORT;
    const page = Math.max(1, Number.parseInt(params.get('page') ?? '1', 10) || 1);

    const patch = useCallback(
        (changes: Partial<Record<'q' | 'cuisine' | 'category' | 'sort' | 'page', string | number>>) => {
            setParams(
                (current) => {
                    const next = new URLSearchParams(current);
                    for (const [key, value] of Object.entries(changes)) {
                        const isDefault =
                            value === '' ||
                            value === undefined ||
                            (key === 'sort' && value === DEFAULT_SORT) ||
                            (key === 'page' && Number(value) <= 1);
                        if (isDefault) {
                            next.delete(key);
                        } else {
                            next.set(key, String(value));
                        }
                    }
                    // Changing what is listed always restarts from the first page.
                    if (!('page' in changes)) {
                        next.delete('page');
                    }
                    return next;
                },
                { replace: true },
            );
        },
        [setParams],
    );

    return useMemo(
        () => ({
            search,
            cuisine,
            category,
            sort,
            page,
            isFiltered: search !== '' || cuisine !== '' || category !== '' || sort !== DEFAULT_SORT,
            setSearch: (value) => patch({ q: value.trim() }),
            setCuisine: (value) => patch({ cuisine: value }),
            setCategory: (value) => patch({ category: value }),
            setSort: (value) => patch({ sort: value }),
            setPage: (value) => patch({ page: value }),
            reset: () => patch({ q: '', cuisine: '', category: '', sort: DEFAULT_SORT, page: 1 }),
        }),
        [search, cuisine, category, sort, page, patch],
    );
}
