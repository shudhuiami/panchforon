import React from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { AlertTriangle, Plus, Scale, ShoppingBasket, SplitSquareHorizontal, Utensils, X, PenLine, type LucideIcon } from 'lucide-react';
import { mealPlanApi } from '../api/mealPlan';
import { MealPlanItem } from '../types/api';
import { Button, ButtonLink } from '../components/ui/Button';
import { IconButton } from '../components/ui/IconButton';
import { Photo } from '../components/ui/Photo';
import { Alert } from '../components/ui/Alert';
import { LoadingSpinner } from '../components/common/LoadingSpinner';
import { EmptyState } from '../components/common/EmptyState';
import { StatusPanel } from '../components/common/StatusPanel';
import { Reveal } from '../components/motion/Reveal';
import { RecipeFallback } from '../features/recipes/RecipeCard';
import { ServingsControl } from '../features/meal-plan/ServingsControl';

const MERGE_RULES: Array<{ icon: LucideIcon; tone: string; title: string; text: string }> = [
    { icon: Scale, tone: 'text-primary', title: 'Scaled', text: 'Portions multiply cleanly with the servings you set here.' },
    { icon: SplitSquareHorizontal, tone: 'text-plum', title: 'Kept apart', text: 'Weights never merge with volumes; 200 g of chicken stays separate from a cup of it.' },
    { icon: PenLine, tone: 'text-turmeric', title: 'As written', text: 'Pinches and “to taste” lines stay exactly as the cook wrote them.' },
];

const PlanItem: React.FC<{ item: MealPlanItem; onRemove: () => void; isRemoving: boolean }> = ({ item, onRemove, isRemoving }) => {
    const recipe = item.recipe;
    const to = `/recipes/${recipe?.slug ?? item.recipe_id}`;
    const title = recipe?.title ?? `Recipe #${item.recipe_id}`;

    return (
        <li className={`flex flex-col gap-4 rounded-3xl border border-line bg-surface p-4 transition-opacity sm:p-5 ${isRemoving ? 'pointer-events-none opacity-50' : ''}`}>
            <div className="flex items-start gap-4">
                <Link to={to} tabIndex={-1} aria-hidden="true" className="block size-20 shrink-0 overflow-hidden rounded-2xl bg-surface-2">
                    <Photo src={recipe?.image_url} alt="" loading="lazy" className="h-full w-full object-cover" fallback={<RecipeFallback recipe={{ id: item.recipe_id, title }} />} />
                </Link>
                <div className="min-w-0 flex-1">
                    <p className="text-xs text-ink-3">{[recipe?.cuisine, recipe?.category].filter(Boolean).join(' · ') || 'Recipe'}</p>
                    <Link to={to} className="mt-1 line-clamp-2 block font-display text-lg leading-tight font-semibold text-ink transition-colors hover:text-primary">
                        {title}
                    </Link>
                    <p className="mt-1 text-xs text-ink-3">Written for {recipe?.servings ?? 4} servings</p>
                </div>
                <IconButton label={`Remove ${title} from the plan`} variant="ghost" size="sm" onClick={onRemove} disabled={isRemoving}>
                    <X className="size-4" />
                </IconButton>
            </div>
            <div className="flex flex-wrap items-center justify-between gap-3 border-t border-line pt-4">
                <span className="text-xs font-semibold tracking-[0.14em] text-ink-3 uppercase">Cook for</span>
                <ServingsControl itemId={item.id} currentServings={item.servings} />
            </div>
        </li>
    );
};

export const MealPlanPage: React.FC = () => {
    const navigate = useNavigate();
    const queryClient = useQueryClient();

    const { data, isLoading, error } = useQuery({ queryKey: ['mealPlan'], queryFn: () => mealPlanApi.get() });
    const items = data?.data.items ?? [];

    const removeMutation = useMutation({
        mutationFn: (itemId: number) => mealPlanApi.removeItem(itemId),
        onSuccess: () => queryClient.invalidateQueries({ queryKey: ['mealPlan'] }),
    });

    const generateMutation = useMutation({
        mutationFn: () => mealPlanApi.generateShoppingList(),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['shoppingList'] });
            navigate('/shopping-list');
        },
    });

    if (isLoading) return <LoadingSpinner message="Loading your plan…" />;
    if (error) {
        return <StatusPanel icon={AlertTriangle} tone="danger" title="Couldn’t load your plan" text="Refresh the page or try again in a moment." />;
    }

    const totalServings = items.reduce((sum, item) => sum + (item.servings || 0), 0);
    const cuisineCount = new Set(items.map((item) => item.recipe?.cuisine).filter((c): c is string => !!c)).size;
    const stats = [
        { value: items.length, label: items.length === 1 ? 'dish' : 'dishes' },
        { value: totalServings, label: 'servings' },
        { value: cuisineCount, label: cuisineCount === 1 ? 'cuisine' : 'cuisines' },
    ];

    return (
        <div className="mx-auto w-full max-w-7xl px-4 py-8 sm:px-6 lg:px-8 lg:py-12">
            <header className="flex animate-slide-up flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                <div className="max-w-2xl">
                    <p className="text-xs font-semibold tracking-[0.22em] text-primary uppercase">Meal plan</p>
                    <h1 className="mt-3 font-display text-4xl font-semibold tracking-tight text-ink text-balance sm:text-5xl lg:text-6xl">Your week’s table.</h1>
                    <p className="mt-4 text-base text-ink-2 sm:text-lg">Scale portions, swap dishes, and turn the whole week into one shopping run.</p>
                    <dl className="mt-5 flex flex-wrap gap-x-6 gap-y-2">
                        {stats.map(({ value, label }) => (
                            <div key={label} className="flex items-baseline gap-1.5">
                                <dd className="font-display text-2xl font-semibold text-ink tabular-nums">{value}</dd>
                                <dt className="text-sm text-ink-3">{label}</dt>
                            </div>
                        ))}
                    </dl>
                </div>
                <div className="flex flex-col gap-3 sm:flex-row lg:shrink-0">
                    {items.length > 0 && (
                        <Button size="lg" onClick={() => generateMutation.mutate()} isLoading={generateMutation.isPending}>
                            <ShoppingBasket className="size-4" aria-hidden="true" />
                            Build the shopping list
                        </Button>
                    )}
                    <ButtonLink to="/recipes" variant="outline" size="lg">
                        <Plus className="size-4" aria-hidden="true" />
                        Add dishes
                    </ButtonLink>
                </div>
            </header>

            {generateMutation.isError && (
                <Alert variant="error" className="mt-6">
                    The shopping list couldn’t be built just now. Please try again.
                </Alert>
            )}

            <section className="mt-10" aria-label="Planned dishes">
                {items.length === 0 ? (
                    <EmptyState
                        icon={Utensils}
                        title="Your plate is empty"
                        description="Add a few dishes from the catalogue and they’ll show up here, ready to scale and shop for."
                        actionLabel="Discover recipes"
                        onAction={() => navigate('/recipes')}
                    />
                ) : (
                    <ul className="grid grid-cols-1 gap-4 md:grid-cols-2">
                        {items.map((item) => (
                            <PlanItem
                                key={item.id}
                                item={item}
                                onRemove={() => removeMutation.mutate(item.id)}
                                isRemoving={removeMutation.isPending && removeMutation.variables === item.id}
                            />
                        ))}
                    </ul>
                )}
            </section>

            <Reveal as="section" className="mt-12 rounded-3xl border border-line bg-surface p-6 sm:p-8" aria-labelledby="merge-heading">
                <h2 id="merge-heading" className="font-display text-2xl font-semibold text-ink">
                    How the shopping list is built
                </h2>
                <ul className="mt-6 grid grid-cols-1 gap-6 sm:grid-cols-3">
                    {MERGE_RULES.map(({ icon: Icon, tone, title, text }) => (
                        <li key={title} className="flex gap-3">
                            <span className="inline-flex size-10 shrink-0 items-center justify-center rounded-full bg-surface-2">
                                <Icon className={`size-4 ${tone}`} aria-hidden="true" />
                            </span>
                            <span>
                                <span className="block font-semibold text-ink">{title}</span>
                                <span className="mt-1 block text-sm text-ink-2">{text}</span>
                            </span>
                        </li>
                    ))}
                </ul>
            </Reveal>
        </div>
    );
};
