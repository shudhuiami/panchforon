import React from 'react';
import { ArrowDownWideNarrow, ChevronDown, RotateCcw, Tag, type LucideIcon } from 'lucide-react';
import { CategoryCount } from '../../types/api';
import { SearchBox } from './SearchBox';
import { RecipeFilters, RecipeSort } from './useRecipeFilters';

export const SORT_LABELS: Record<RecipeSort, string> = {
    bayesian: 'Best ranked',
    rating: 'Highest rating',
    latest: 'Newest first',
    title: 'A to Z',
};

const Select: React.FC<{ icon: LucideIcon; label: string; value: string; onChange: (value: string) => void; children: React.ReactNode }> = ({
    icon: Icon,
    label,
    value,
    onChange,
    children,
}) => (
    <div className="relative">
        <Icon className="pointer-events-none absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-ink-3" aria-hidden="true" />
        <select
            value={value}
            onChange={(e) => onChange(e.target.value)}
            aria-label={label}
            className="h-10 w-full cursor-pointer appearance-none rounded-full border border-line bg-surface-2 pr-9 pl-10 text-sm font-medium text-ink transition-colors hover:border-line-strong focus-visible:outline-3 focus-visible:outline-primary focus-visible:outline-offset-2 sm:w-auto"
        >
            {children}
        </select>
        <ChevronDown className="pointer-events-none absolute top-1/2 right-3 size-4 -translate-y-1/2 text-ink-3" aria-hidden="true" />
    </div>
);

interface FilterBarProps {
    filters: RecipeFilters;
    categories: CategoryCount[];
    focusSearch?: boolean;
}

/** Search, category, sort and reset: the controls that shape the recipe list. */
export const FilterBar: React.FC<FilterBarProps> = ({ filters, categories, focusSearch = false }) => (
    <div className="flex flex-col gap-3 lg:flex-row lg:items-center">
        <SearchBox initialValue={filters.search} onSubmit={filters.setSearch} autoFocus={focusSearch} className="flex-1" />
        <div className="grid grid-cols-2 gap-2 sm:flex sm:items-center">
            <Select icon={Tag} label="Filter by category" value={filters.category} onChange={filters.setCategory}>
                <option value="">All categories</option>
                {categories.map((c) => (
                    <option key={c.category} value={c.category}>
                        {c.category} ({c.count})
                    </option>
                ))}
            </Select>
            <Select icon={ArrowDownWideNarrow} label="Sort recipes by" value={filters.sort} onChange={(v) => filters.setSort(v as RecipeSort)}>
                {(Object.keys(SORT_LABELS) as RecipeSort[]).map((sort) => (
                    <option key={sort} value={sort}>
                        {SORT_LABELS[sort]}
                    </option>
                ))}
            </Select>
            {filters.isFiltered && (
                <button
                    type="button"
                    onClick={filters.reset}
                    className="col-span-2 inline-flex h-10 cursor-pointer items-center justify-center gap-1.5 rounded-full px-4 text-sm font-medium text-hot transition-colors hover:bg-hot-soft focus-visible:outline-3 focus-visible:outline-primary focus-visible:outline-offset-2 sm:col-span-1"
                >
                    <RotateCcw className="size-3.5" aria-hidden="true" />
                    Reset
                </button>
            )}
        </div>
    </div>
);
