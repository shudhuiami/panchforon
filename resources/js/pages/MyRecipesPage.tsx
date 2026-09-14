import React, { useState } from 'react';
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

const MODERATION: Record<ModerationStatus, { label: string; variant: 'success' | 'category' | 'danger' | 'outline'; note: string }> = {
    draft: { label: 'Draft', variant: 'outline', note: 'Only you can see this. Publish it when it’s ready.' },
    approved: { label: 'Published', variant: 'success', note: 'Anyone can find and cook this.' },
    pending: { label: 'In review', variant: 'category', note: 'A moderator is having a look; only you can see it.' },
    unpublished: { label: 'Unpublished', variant: 'danger', note: 'A moderator pulled this from the catalogue.' },
};

/** The shelves the list can be narrowed to; "all" sends no filter at all. */
type Shelf = 'all' | 'draft' | 'pending' | 'approved';

const SHELVES: Array<{ value: Shelf; label: string }> = [
    { value: 'all', label: 'Everything' },
    { value: 'draft', label: 'Drafts' },
    { value: 'pending', label: 'In review' },
    { value: 'approved', label: 'Published' },
];

const EMPTY: Record<Shelf, { title: string; description: string }> = {
    all: { title: 'No recipes yet', description: 'Write up a dish you cook often. Save it as a draft, or publish it once a moderator has had a look.' },
    draft: { title: 'No drafts', description: 'A recipe you save as a draft waits here, private to you, until you publish it.' },
    pending: { title: 'Nothing in review', description: 'Recipes you publish sit here while a moderator reads them over.' },
    approved: { title: 'Nothing published yet', description: 'Once a moderator approves one of your recipes it shows up here, and in the catalogue.' },
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

const ShelfChip: React.FC<{ active: boolean; onClick: () => void; children: React.ReactNode }> = ({ active, onClick, children }) => (
    <button
        type="button"
        onClick={onClick}
        aria-pressed={active}
        className={`inline-flex h-9 shrink-0 cursor-pointer items-center rounded-full border px-3.5 text-sm font-medium transition-colors focus-visible:outline-3 focus-visible:outline-primary focus-visible:outline-offset-2 ${
            active ? 'border-primary bg-primary text-on-primary' : 'border-line bg-surface text-ink-2 hover:border-line-strong hover:text-ink'
        }`}
    >
        {children}
    </button>
);

export const MyRecipesPage: React.FC = () => {
    const navigate = useNavigate();
    const { page, setPage } = useRecipeFilters();
    const [shelf, setShelf] = useState<Shelf>('all');
    const { data, isLoading } = useQuery({
        queryKey: ['my-recipes', page, shelf],
        queryFn: () => accountApi.myRecipes(page, shelf === 'all' ? undefined : shelf),
    });

    const recipes = data?.data ?? [];

    return (
        <AccountLayout title="Recipes you posted." blurb="Everything you have written up: your private drafts, the ones waiting on a moderator, and the ones that went live.">
            <div role="group" aria-label="Filter by status" className="no-scrollbar -mx-4 mb-6 flex gap-2 overflow-x-auto px-4 pb-1 sm:mx-0 sm:flex-wrap sm:px-0">
                {SHELVES.map(({ value, label }) => (
                    <ShelfChip
                        key={value}
                        active={shelf === value}
                        onClick={() => {
                            setShelf(value);
                            setPage(1);
                        }}
                    >
                        {label}
                    </ShelfChip>
                ))}
            </div>

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
                    title={EMPTY[shelf].title}
                    description={EMPTY[shelf].description}
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
