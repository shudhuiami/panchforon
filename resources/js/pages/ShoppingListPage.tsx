import React from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { AlertTriangle, ArrowLeft, Info, PartyPopper, Printer, RefreshCw, ShoppingBag } from 'lucide-react';
import { mealPlanApi } from '../api/mealPlan';
import { ShoppingListItem } from '../types/api';
import { Button } from '../components/ui/Button';
import { LoadingSpinner } from '../components/common/LoadingSpinner';
import { EmptyState } from '../components/common/EmptyState';
import { StatusPanel } from '../components/common/StatusPanel';
import { ShoppingListGroup, ShoppingListGroupKind } from '../features/shopping-list/ShoppingListGroup';

const RING_RADIUS = 42;
const RING_CIRCUMFERENCE = 2 * Math.PI * RING_RADIUS;
const MASS_UNITS = ['kg', 'g', 'mg', 'oz', 'lb'];
const VOLUME_UNITS = ['l', 'ml', 'cup', 'tbsp', 'tsp', 'fl oz', 'pint', 'quart'];

const unitOf = (item: ShoppingListItem) => item.unit?.toLowerCase() ?? '';

export const ShoppingListPage: React.FC = () => {
    const navigate = useNavigate();
    const queryClient = useQueryClient();

    const { data, isLoading, error } = useQuery({ queryKey: ['shoppingList'], queryFn: () => mealPlanApi.getShoppingList() });
    const items = data?.data ?? [];

    const regenerateMutation = useMutation({
        mutationFn: () => mealPlanApi.generateShoppingList(),
        onSuccess: () => queryClient.invalidateQueries({ queryKey: ['shoppingList'] }),
    });

    if (isLoading) return <LoadingSpinner message="Merging your groceries…" />;
    if (error) {
        return <StatusPanel icon={AlertTriangle} tone="danger" title="Couldn’t load the list" text="Refresh the page or try again in a moment." />;
    }
    if (items.length === 0) {
        return (
            <div className="mx-auto w-full max-w-3xl px-4 py-8 sm:px-6 sm:py-12">
                <EmptyState
                    icon={ShoppingBag}
                    title="Nothing in the basket yet"
                    description="Your plan hasn’t been turned into a list, or it has no dishes in it. Add a few and build the list from the meal plan."
                    actionLabel="Go to the meal plan"
                    onAction={() => navigate('/meal-plan')}
                />
            </div>
        );
    }

    const merged = items.filter((i) => !i.is_unmerged);
    const groups: Array<{ kind: ShoppingListGroupKind; title: string; description: string; items: ShoppingListItem[] }> = [
        { kind: 'mass' as const, title: 'By weight', description: 'Produce, meat and fish, merged and converted to kilograms and grams.', items: merged.filter((i) => MASS_UNITS.includes(unitOf(i))) },
        { kind: 'volume' as const, title: 'By volume', description: 'Dairy, oils and measured spices, merged into litres and millilitres.', items: merged.filter((i) => VOLUME_UNITS.includes(unitOf(i))) },
        { kind: 'count' as const, title: 'By count', description: 'Whole items, rounded up so you never run short.', items: merged.filter((i) => !MASS_UNITS.includes(unitOf(i)) && !VOLUME_UNITS.includes(unitOf(i))) },
        { kind: 'unmerged' as const, title: 'As written', description: 'Pinches, “to taste” and frying oil, kept exactly as the cook wrote them.', items: items.filter((i) => i.is_unmerged) },
    ].filter((group) => group.items.length > 0);

    const checkedCount = items.filter((i) => i.is_checked).length;
    const remaining = items.length - checkedCount;
    const percent = Math.round((checkedCount / items.length) * 100);
    const allDone = remaining === 0;

    return (
        <div className="mx-auto w-full max-w-7xl px-4 py-8 sm:px-6 lg:px-8 lg:py-12">
            <header className="flex animate-slide-up flex-col gap-6 lg:flex-row lg:items-end lg:justify-between print:hidden">
                <div className="max-w-2xl">
                    <Link to="/meal-plan" className="inline-flex items-center gap-1.5 text-sm text-ink-3 transition-colors hover:text-ink">
                        <ArrowLeft className="size-4" aria-hidden="true" />
                        Meal plan
                    </Link>
                    <p className="mt-4 text-xs font-semibold tracking-[0.22em] text-primary uppercase">Shopping list</p>
                    <h1 className="mt-3 font-display text-4xl font-semibold tracking-tight text-ink text-balance sm:text-5xl lg:text-6xl">Your grocery run.</h1>
                    <p className="mt-4 text-base text-ink-2 sm:text-lg" aria-live="polite">
                        {allDone ? 'Everything is ticked off. Time to cook.' : `${remaining} ${remaining === 1 ? 'item' : 'items'} left to grab. Tap a row to check it off.`}
                    </p>
                    <div className="mt-5 flex flex-wrap gap-3">
                        <Button variant="secondary" onClick={() => regenerateMutation.mutate()} isLoading={regenerateMutation.isPending}>
                            <RefreshCw className="size-4" aria-hidden="true" />
                            Rebuild from the plan
                        </Button>
                        <Button variant="outline" onClick={() => window.print()}>
                            <Printer className="size-4" aria-hidden="true" />
                            Print
                        </Button>
                    </div>
                </div>

                <div className="flex items-center gap-5 rounded-3xl border border-line bg-surface p-5 sm:p-6 lg:min-w-80">
                    <div className="relative size-24 shrink-0">
                        <svg viewBox="0 0 100 100" className="size-full -rotate-90" aria-hidden="true">
                            <circle cx="50" cy="50" r={RING_RADIUS} fill="none" stroke="var(--surface-3)" strokeWidth="10" />
                            <circle
                                cx="50"
                                cy="50"
                                r={RING_RADIUS}
                                fill="none"
                                stroke="var(--mint)"
                                strokeWidth="10"
                                strokeLinecap="round"
                                strokeDasharray={RING_CIRCUMFERENCE}
                                strokeDashoffset={RING_CIRCUMFERENCE * (1 - percent / 100)}
                                className="transition-[stroke-dashoffset] duration-500 ease-out"
                            />
                        </svg>
                        <div className="absolute inset-0 flex items-center justify-center">
                            {allDone ? (
                                <PartyPopper className="size-7 text-mint" aria-hidden="true" />
                            ) : (
                                <span className="font-display text-xl font-semibold text-ink tabular-nums">{percent}%</span>
                            )}
                        </div>
                    </div>
                    <div>
                        <p className="text-xs font-semibold tracking-[0.14em] text-mint uppercase">Trip progress</p>
                        <p className="mt-1 font-display text-2xl font-semibold text-ink tabular-nums">
                            {checkedCount}
                            <span className="text-base text-ink-3"> / {items.length}</span>
                        </p>
                        <p className="mt-1 text-xs text-ink-3">{allDone ? 'Basket complete.' : `${remaining} still to find`}</p>
                    </div>
                </div>
            </header>

            <div className="mb-8 hidden border-b border-line pb-4 print:block">
                <h1 className="font-display text-2xl font-semibold text-ink">Panchforon shopping list</h1>
                <p className="text-xs text-ink-2">Built on {new Date().toLocaleDateString()} from your meal plan.</p>
            </div>

            <nav className="no-scrollbar mt-8 flex items-center gap-2 overflow-x-auto print:hidden" aria-label="List sections">
                {groups.map((group) => (
                    <a
                        key={group.kind}
                        href={`#group-${group.kind}`}
                        className="inline-flex shrink-0 items-center gap-1.5 rounded-full border border-line bg-surface px-3 py-1.5 text-xs font-medium text-ink-2 transition-colors hover:border-line-strong hover:text-ink"
                    >
                        {group.title}
                        <span className="text-ink-3 tabular-nums">{group.items.length}</span>
                    </a>
                ))}
            </nav>

            <div className="mt-5 space-y-5">
                {groups.map((group) => (
                    <div key={group.kind} id={`group-${group.kind}`} className="scroll-mt-24">
                        <ShoppingListGroup kind={group.kind} title={group.title} description={group.description} items={group.items} />
                    </div>
                ))}
            </div>

            <p className="mt-8 flex items-start gap-3 rounded-3xl border border-line bg-surface-2 p-5 text-sm text-ink-2 print:hidden">
                <Info className="mt-0.5 size-4 shrink-0 text-turmeric" aria-hidden="true" />
                Weights and volumes never merge with each other, and every line notes which dishes it came from.
            </p>
        </div>
    );
};
