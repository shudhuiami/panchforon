import React from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { Star } from 'lucide-react';
import { accountApi } from '../api/account';
import { MyRating } from '../types/api';
import { AccountLayout } from '../features/account/AccountLayout';
import { RecipeFallback } from '../features/recipes/RecipeCard';
import { Photo } from '../components/ui/Photo';
import { StarRating } from '../components/ui/StarRating';
import { Pagination } from '../components/ui/Pagination';
import { EmptyState } from '../components/common/EmptyState';
import { useRecipeFilters } from '../features/recipes/useRecipeFilters';

const formatDate = (iso?: string): string => (iso ? new Date(iso).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' }) : '');

const Row: React.FC<{ rating: MyRating }> = ({ rating }) => {
    const recipe = rating.recipe;

    return (
        <li className="p-4 sm:p-5">
            <div className="flex items-start gap-4">
                {recipe && (
                    <Link to={`/recipes/${recipe.slug}`} tabIndex={-1} aria-hidden="true" className="block size-16 shrink-0 overflow-hidden rounded-2xl bg-surface-2">
                        <Photo src={recipe.image_url} alt="" loading="lazy" className="h-full w-full object-cover" fallback={<RecipeFallback recipe={recipe} />} />
                    </Link>
                )}
                <div className="min-w-0 flex-1">
                    {recipe ? (
                        <Link to={`/recipes/${recipe.slug}`} className="block truncate font-display text-lg font-semibold text-ink transition-colors hover:text-primary">
                            {recipe.title}
                        </Link>
                    ) : (
                        <p className="font-display text-lg font-semibold text-ink-3">This recipe is no longer listed</p>
                    )}
                    <div className="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-1">
                        <StarRating score={rating.stars} size="sm" />
                        <span className="text-xs text-ink-3">{formatDate(rating.created_at)}</span>
                    </div>
                    {rating.review && <p className="mt-3 text-sm leading-relaxed text-ink-2">{rating.review}</p>}
                </div>
            </div>
        </li>
    );
};

export const MyRatingsPage: React.FC = () => {
    const navigate = useNavigate();
    const { page, setPage } = useRecipeFilters();
    const { data, isLoading } = useQuery({ queryKey: ['my-ratings', page], queryFn: () => accountApi.myRatings(page) });

    const ratings = data?.data ?? [];

    return (
        <AccountLayout title="What you thought." blurb="Every dish you rated, and what you said about it. Open a recipe to change your mind.">
            {isLoading ? (
                <ul className="divide-y divide-line overflow-hidden rounded-3xl border border-line bg-surface" aria-hidden="true">
                    {Array.from({ length: 4 }).map((_, i) => (
                        <li key={i} className="flex items-center gap-4 p-4 sm:p-5">
                            <span className="skeleton-shimmer size-16 rounded-2xl" />
                            <span className="skeleton-shimmer h-5 flex-1 rounded-full" />
                        </li>
                    ))}
                </ul>
            ) : ratings.length === 0 ? (
                <EmptyState
                    icon={Star}
                    title="No ratings yet"
                    description="Cook something from the catalogue and say how it went. Honest scores are what keep the ranking useful."
                    actionLabel="Find something to cook"
                    onAction={() => navigate('/recipes')}
                />
            ) : (
                <div className="space-y-6">
                    <ul className="divide-y divide-line overflow-hidden rounded-3xl border border-line bg-surface">
                        {ratings.map((rating) => (
                            <Row key={rating.id} rating={rating} />
                        ))}
                    </ul>
                    {data?.meta && data.meta.last_page > 1 && <Pagination currentPage={data.meta.current_page} totalPages={data.meta.last_page} onPageChange={setPage} />}
                </div>
            )}
        </AccountLayout>
    );
};
