import React from 'react';
import { RecipeList } from '../../types/api';
import { RecipeCard } from './RecipeCard';
import { EmptyState } from '../../components/common/EmptyState';
import { Pagination } from '../../components/ui/Pagination';
import { Utensils } from 'lucide-react';

interface RecipeGridProps {
    recipes: RecipeList[];
    isLoading: boolean;
    meta?: {
        current_page: number;
        last_page: number;
        total: number;
    };
    onPageChange?: (page: number) => void;
    onResetFilters?: () => void;
}

const STAGGER = [
    '',
    'animation-delay-75',
    'animation-delay-100',
    'animation-delay-150',
    'animation-delay-200',
    'animation-delay-300',
    'animation-delay-300',
    'animation-delay-500',
];

const SKELETON_TINTS = ['bg-saffron-soft', 'bg-plum-soft', 'bg-mint-soft', 'bg-turmeric-soft', 'bg-chili-soft'];

const RecipeSkeleton: React.FC<{ index: number }> = ({ index }) => (
    <div
        className={`rounded-3xl overflow-hidden border border-line bg-paper animate-fade-in ${STAGGER[index % STAGGER.length]}`}
        aria-hidden="true"
    >
        <div className={`aspect-4/5 sm:aspect-5/6 ${SKELETON_TINTS[index % SKELETON_TINTS.length]} relative`}>
            <div className="absolute inset-0 skeleton-shimmer opacity-60" />
            <div className="absolute top-4 left-4 h-6 w-20 rounded-full bg-white/70" />
            <div className="absolute bottom-5 left-5 right-16 space-y-2">
                <div className="h-3 w-16 rounded-full bg-white/70" />
                <div className="h-5 w-4/5 rounded-full bg-white/80" />
                <div className="h-5 w-3/5 rounded-full bg-white/80" />
            </div>
        </div>
        <div className="px-5 pt-4 pb-5 flex items-center justify-between">
            <div className="h-4 w-24 rounded-full skeleton-shimmer" />
            <div className="h-6 w-20 rounded-full skeleton-shimmer" />
        </div>
    </div>
);

export const RecipeGrid: React.FC<RecipeGridProps> = ({
    recipes,
    isLoading,
    meta,
    onPageChange,
    onResetFilters,
}) => {
    if (isLoading) {
        return (
            <div role="status" aria-live="polite" className="space-y-4">
                <p className="sr-only">Curating regional recipes and ratings...</p>
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5 sm:gap-6">
                    {Array.from({ length: 8 }).map((_, i) => (
                        <RecipeSkeleton key={i} index={i} />
                    ))}
                </div>
            </div>
        );
    }

    if (!recipes || recipes.length === 0) {
        return (
            <EmptyState
                icon={Utensils}
                title="No recipes found"
                description="We couldn't find any recipes matching your current cuisine, search term, or filters."
                actionLabel="Clear Filters"
                onAction={onResetFilters}
            />
        );
    }

    return (
        <div className="space-y-10">
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5 sm:gap-6">
                {recipes.map((recipe, index) => (
                    <RecipeCard
                        key={recipe.id}
                        recipe={recipe}
                        className={`animate-slide-up ${STAGGER[index % STAGGER.length]}`}
                    />
                ))}
            </div>

            {/* Pagination Controls */}
            {meta && meta.last_page > 1 && onPageChange && (
                <div className="flex flex-col sm:flex-row items-center justify-between gap-4 rounded-3xl bg-paper border border-line px-5 py-4 sm:px-6">
                    <p className="text-sm text-ink-2 font-medium">
                        Page{' '}
                        <span className="inline-flex items-center justify-center min-w-7 h-7 px-2 rounded-full bg-saffron-soft text-saffron-deep font-display font-extrabold text-sm">
                            {meta.current_page}
                        </span>{' '}
                        of <span className="font-extrabold text-ink">{meta.last_page}</span>
                        <span className="text-ink-3"> &middot; {meta.total} recipes</span>
                    </p>

                    <Pagination
                        currentPage={meta.current_page}
                        totalPages={meta.last_page}
                        onPageChange={onPageChange}
                    />
                </div>
            )}
        </div>
    );
};
