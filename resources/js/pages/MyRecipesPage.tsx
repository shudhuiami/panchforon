import React from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { ChefHat, Pencil } from 'lucide-react';
import { accountApi } from '../api/account';
import { ModerationStatus, RecipeList } from '../types/api';
import { AccountLayout } from '../features/account/AccountLayout';
import { RecipeFallback } from '../features/recipes/RecipeCard';
import { Photo } from '../components/ui/Photo';
import { Badge } from '../components/ui/Badge';
import { ButtonLink } from '../components/ui/Button';
import { Pagination } from '../components/ui/Pagination';
import { EmptyState } from '../components/common/EmptyState';
import { useRecipeFilters } from '../features/recipes/useRecipeFilters';

const MODERATION: Record<ModerationStatus, { label: string; variant: 'success' | 'category' | 'danger'; note: string }> = {
    approved: { label: 'Published', variant: 'success', note: 'Anyone can find and cook this.' },
    pending: { label: 'In review', variant: 'category', note: 'A moderator is having a look; only you can see it.' },
    unpublished: { label: 'Unpublished', variant: 'danger', note: 'A moderator pulled this from the catalogue.' },
};

const Row: React.FC<{ recipe: RecipeList }> = ({ recipe }) => {
    const moderation = MODERATION[recipe.moderation_status];

    return (
        <li className="flex items-center gap-4 p-4 sm:p-5">
            <Link to={`/recipes/${recipe.slug}`} tabIndex={-1} aria-hidden="true" className="block size-16 shrink-0 overflow-hidden rounded-2xl bg-surface-2">
                <Photo src={recipe.image_url} alt="" loading="lazy" className="h-full w-full object-cover" fallback={<RecipeFallback recipe={recipe} />} />
            </Link>
            <div className="min-w-0 flex-1">
                <Link to={`/recipes/${recipe.slug}`} className="block truncate font-display text-lg font-semibold text-ink transition-colors hover:text-primary">
                    {recipe.title}
                </Link>
                <p className="mt-1 flex flex-wrap items-center gap-2 text-xs text-ink-3">
                    <Badge variant={moderation.variant}>{moderation.label}</Badge>
                    <span className="hidden sm:inline">{moderation.note}</span>
                    <span className="sm:hidden">
                        {recipe.ratings_count} {recipe.ratings_count === 1 ? 'rating' : 'ratings'}
                    </span>
                </p>
            </div>
            <span className="hidden text-sm text-ink-3 sm:block">
                {recipe.ratings_count} {recipe.ratings_count === 1 ? 'rating' : 'ratings'}
            </span>
            <ButtonLink to={`/recipes/${recipe.slug}/edit`} variant="outline" size="sm">
                <Pencil className="size-3.5" aria-hidden="true" />
                <span className="hidden sm:inline">Edit</span>
            </ButtonLink>
        </li>
    );
};

export const MyRecipesPage: React.FC = () => {
    const navigate = useNavigate();
    const { page, setPage } = useRecipeFilters();
    const { data, isLoading } = useQuery({ queryKey: ['my-recipes', page], queryFn: () => accountApi.myRecipes(page) });

    const recipes = data?.data ?? [];

    return (
        <AccountLayout title="Recipes you posted." blurb="Everything you have written up, including the ones still waiting on a moderator.">
            {isLoading ? (
                <ul className="divide-y divide-line overflow-hidden rounded-3xl border border-line bg-surface" aria-hidden="true">
                    {Array.from({ length: 4 }).map((_, i) => (
                        <li key={i} className="flex items-center gap-4 p-4 sm:p-5">
                            <span className="skeleton-shimmer size-16 rounded-2xl" />
                            <span className="skeleton-shimmer h-5 flex-1 rounded-full" />
                        </li>
                    ))}
                </ul>
            ) : recipes.length === 0 ? (
                <EmptyState
                    icon={ChefHat}
                    title="No recipes yet"
                    description="Write up a dish you cook often. It goes live once a moderator has had a look."
                    actionLabel="Post a recipe"
                    onAction={() => navigate('/recipes/create')}
                />
            ) : (
                <div className="space-y-6">
                    <ul className="divide-y divide-line overflow-hidden rounded-3xl border border-line bg-surface">
                        {recipes.map((recipe) => (
                            <Row key={recipe.id} recipe={recipe} />
                        ))}
                    </ul>
                    {data?.meta && data.meta.last_page > 1 && <Pagination currentPage={data.meta.current_page} totalPages={data.meta.last_page} onPageChange={setPage} />}
                </div>
            )}
        </AccountLayout>
    );
};
