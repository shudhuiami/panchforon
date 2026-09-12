import React from 'react';
import { Utensils } from 'lucide-react';
import { RecipeList } from '../../types/api';
import { RecipeCard } from './RecipeCard';
import { EmptyState } from '../../components/common/EmptyState';
import { Pagination } from '../../components/ui/Pagination';

interface RecipeGridProps {
    recipes: RecipeList[];
    isLoading: boolean;
    meta?: { current_page: number; last_page: number; total: number };
    onPageChange?: (page: number) => void;
    onResetFilters?: () => void;
}

const STAGGER = ['', 'animation-delay-75', 'animation-delay-100', 'animation-delay-150', 'animation-delay-200', 'animation-delay-300', 'animation-delay-300', 'animation-delay-500'];

const gridClass = 'grid grid-cols-2 gap-3 sm:gap-5 lg:grid-cols-3 xl:grid-cols-4';

export const RecipeGrid: React.FC<RecipeGridProps> = ({ recipes, isLoading, meta, onPageChange, onResetFilters }) => {
    if (isLoading) {
        return (
            <div role="status" aria-live="polite" className={gridClass}>
                <p className="sr-only">Loading recipes…</p>
                {Array.from({ length: 8 }).map((_, i) => (
                    <div key={i} className={`animate-fade-in overflow-hidden rounded-3xl border border-line bg-surface ${STAGGER[i]}`} aria-hidden="true">
                        <div className="skeleton-shimmer aspect-4/5" />
                        <div className="flex items-center justify-between px-4 py-4">
                            <span className="skeleton-shimmer h-3.5 w-24 rounded-full" />
                            <span className="skeleton-shimmer h-3.5 w-8 rounded-full" />
                        </div>
                    </div>
                ))}
            </div>
        );
    }

    if (recipes.length === 0) {
        return (
            <EmptyState
                icon={Utensils}
                title="No recipes match"
                description="Try another spelling, a different cuisine, or clear the filters to see everything."
                actionLabel="Clear filters"
                onAction={onResetFilters}
            />
        );
    }

    return (
        <div className="space-y-8">
            <div className={gridClass}>
                {recipes.map((recipe, index) => (
                    <RecipeCard key={recipe.id} recipe={recipe} className={`animate-slide-up ${STAGGER[index % STAGGER.length]}`} />
                ))}
            </div>

            {meta && meta.last_page > 1 && onPageChange && (
                <div className="flex flex-col items-center justify-between gap-4 rounded-3xl border border-line bg-surface px-5 py-4 sm:flex-row">
                    <p className="text-sm text-ink-2">
                        Page <span className="font-semibold text-ink">{meta.current_page}</span> of {meta.last_page}
                        <span className="text-ink-3"> · {meta.total} recipes</span>
                    </p>
                    <Pagination currentPage={meta.current_page} totalPages={meta.last_page} onPageChange={onPageChange} />
                </div>
            )}
        </div>
    );
};
