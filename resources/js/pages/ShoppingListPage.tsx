import React, { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { mealPlanApi } from '../api/mealPlan';
import { useAuth } from '../context/AuthContext';
import { ShoppingListItem } from '../types/api';
import { ShoppingListGroup } from '../features/shopping-list/ShoppingListGroup';
import { Button } from '../components/ui/Button';
import { LoadingSpinner } from '../components/common/LoadingSpinner';
import { EmptyState } from '../components/common/EmptyState';
import {
    ShoppingBag,
    Printer,
    RefreshCw,
    ArrowLeft,
    CheckCircle2,
    Sparkles,
    Info,
    ShoppingBasket,
    ListChecks,
    PartyPopper,
    AlertTriangle,
    LogIn,
    Loader2,
} from 'lucide-react';

const RING_RADIUS = 42;
const RING_CIRCUMFERENCE = 2 * Math.PI * RING_RADIUS;

export const ShoppingListPage: React.FC = () => {
    const { token, demoLogin } = useAuth();
    const navigate = useNavigate();
    const queryClient = useQueryClient();
    const [isDemoLoading, setIsDemoLoading] = useState(false);

    // Fetch shopping list
    const { data, isLoading, error } = useQuery({
        queryKey: ['shoppingList'],
        queryFn: () => mealPlanApi.getShoppingList(),
        enabled: !!token,
    });

    const items: ShoppingListItem[] = data?.data || [];

    // Regenerate mutation
    const regenerateMutation = useMutation({
        mutationFn: () => mealPlanApi.generateShoppingList(),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['shoppingList'] });
        },
    });

    const handleQuickDemo = async () => {
        setIsDemoLoading(true);
        try {
            await demoLogin();
        } catch (err) {
            console.error('Demo login failed:', err);
        } finally {
            setIsDemoLoading(false);
        }
    };

    const handlePrint = () => {
        window.print();
    };

    if (!token) {
        return (
            <div className="max-w-xl mx-auto my-10 sm:my-16 animate-slide-up">
                <div className="relative bg-paper rounded-4xl p-8 sm:p-12 text-center space-y-6 pop-mint pop-hover overflow-hidden">
                    <div className="absolute -top-10 -left-10 w-40 h-40 bg-mint-soft blob-2 opacity-90" aria-hidden="true" />
                    <div className="absolute -bottom-12 -right-8 w-36 h-36 bg-saffron-soft blob-1 opacity-90" aria-hidden="true" />

                    <div className="relative">
                        <div className="w-20 h-20 bg-mint-soft text-mint-deep rounded-3xl flex items-center justify-center mx-auto sticker-r">
                            <ShoppingBasket className="w-10 h-10" />
                        </div>
                        <h2 className="mt-6 text-3xl sm:text-4xl font-display">
                            One smart <span className="text-sunrise">grocery list</span>
                        </h2>
                        <p className="mt-3 text-sm sm:text-base text-ink-2 leading-relaxed max-w-md mx-auto">
                            Quantities converted, merged across recipes and kept apart by unit dimension — so your list is short, exact and ready to tick off.
                        </p>
                    </div>

                    <div className="relative pt-2 space-y-3">
                        <button
                            type="button"
                            onClick={handleQuickDemo}
                            disabled={isDemoLoading}
                            className="w-full inline-flex items-center justify-center gap-2 rounded-full bg-mint text-ink font-display font-extrabold text-base px-7 py-4 shadow-glow-mint hover:-translate-y-0.5 hover:brightness-105 transition-all duration-200 cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed"
                        >
                            {isDemoLoading ? (
                                <Loader2 className="w-5 h-5 animate-spin" />
                            ) : (
                                <Sparkles className="w-5 h-5" />
                            )}
                            Try the demo list in one click
                        </button>

                        <div className="grid grid-cols-2 gap-3 pt-1">
                            <Link to="/login" className="block">
                                <Button variant="outline" size="md" className="w-full rounded-full">
                                    <LogIn className="w-4 h-4" />
                                    Sign in
                                </Button>
                            </Link>
                            <Link to="/register" className="block">
                                <Button variant="dark" size="md" className="w-full rounded-full">
                                    Register
                                </Button>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        );
    }

    if (isLoading) {
        return <LoadingSpinner message="Calculating dimensional merges and preparing grocery list..." />;
    }

    if (error) {
        return (
            <div className="max-w-md mx-auto my-16 text-center bg-chili-soft rounded-3xl p-8 space-y-3 animate-scale-in">
                <div className="w-14 h-14 mx-auto rounded-2xl bg-paper text-chili-deep flex items-center justify-center">
                    <AlertTriangle className="w-7 h-7" />
                </div>
                <p className="text-chili-deep font-display font-extrabold text-xl">Failed to load shopping list.</p>
                <p className="text-sm text-ink-2">Please refresh the page or try again in a moment.</p>
            </div>
        );
    }

    if (items.length === 0) {
        return (
            <div className="max-w-3xl mx-auto py-8 sm:py-12 animate-fade-in">
                <EmptyState
                    icon={ShoppingBag}
                    title="Nothing in the basket yet"
                    description="Your active meal plan hasn't generated a shopping list, or it has no recipes in it. Add a few dishes and we'll merge the groceries for you."
                    actionLabel="Go to weekly plan"
                    onAction={() => navigate('/meal-plan')}
                />
            </div>
        );
    }

    // Partition items by dimension and unmerged state
    const unmergedItems = items.filter((i) => i.is_unmerged);
    const mergedItems = items.filter((i) => !i.is_unmerged);

    // Group merged items by unit characteristics
    const massItems = mergedItems.filter(
        (i) => i.unit && ['kg', 'g', 'mg', 'oz', 'lb'].includes(i.unit.toLowerCase())
    );
    const volumeItems = mergedItems.filter(
        (i) => i.unit && ['l', 'ml', 'cup', 'tbsp', 'tsp', 'fl oz', 'pint', 'quart'].includes(i.unit.toLowerCase())
    );
    const countAndOtherItems = mergedItems.filter(
        (i) => !massItems.includes(i) && !volumeItems.includes(i)
    );

    // Calculate completion progress
    const totalCount = items.length;
    const checkedCount = items.filter((i) => i.is_checked).length;
    const progressPercent = totalCount > 0 ? Math.round((checkedCount / totalCount) * 100) : 0;
    const remainingCount = totalCount - checkedCount;
    const allDone = remainingCount === 0;
    const ringOffset = RING_CIRCUMFERENCE * (1 - progressPercent / 100);

    const groups = [
        { key: 'mass', title: 'Weight & mass', description: 'Produce, poultry & meat — merged and converted to kg / g', icon: 'mass' as const, items: massItems },
        { key: 'volume', title: 'Liquids & volume', description: 'Dairy, oils & measured spices — merged into litres / millilitres', icon: 'volume' as const, items: volumeItems },
        { key: 'count', title: 'Counts & pantry', description: 'Whole items rounded up so you never run short', icon: 'count' as const, items: countAndOtherItems },
        { key: 'unmerged', title: 'Seasonings & taste items', description: 'Pinches, "to taste" and frying oil kept exactly as written', icon: 'unmerged' as const, items: unmergedItems },
    ].filter((group) => group.items.length > 0);

    return (
        <div className="space-y-8 sm:space-y-10 pb-24 max-w-6xl mx-auto">
            {/* Header band */}
            <section className="relative overflow-hidden bg-mint-soft rounded-4xl px-6 py-8 sm:px-10 sm:py-10 print:hidden animate-slide-up">
                <div className="absolute inset-0 bg-dots opacity-50" aria-hidden="true" />
                <div className="absolute -top-14 -left-14 w-52 h-52 bg-mint/25 blob-1 animate-float" aria-hidden="true" />
                <div className="absolute -bottom-16 right-1/4 w-44 h-44 bg-saffron/20 blob-2" aria-hidden="true" />

                <div className="relative flex flex-col lg:flex-row lg:items-center justify-between gap-8">
                    <div className="space-y-5 max-w-xl">
                        <Link
                            to="/meal-plan"
                            className="inline-flex items-center gap-1.5 rounded-full bg-paper/80 text-ink-2 hover:text-ink hover:bg-paper text-xs font-extrabold uppercase tracking-wider px-3 py-1.5 transition-colors"
                        >
                            <ArrowLeft className="w-3.5 h-3.5" />
                            Back to weekly plan
                        </Link>

                        <div>
                            <h1 className="text-4xl sm:text-5xl lg:text-6xl font-display text-balance">
                                Your <span className="text-sunrise">grocery</span> board
                            </h1>
                            <p className="mt-3 text-base sm:text-lg text-ink-2 leading-relaxed text-pretty">
                                {allDone
                                    ? 'Everything is ticked off. Time to cook!'
                                    : `${remainingCount} ${remainingCount === 1 ? 'item' : 'items'} left to grab — tap a row to check it off.`}
                            </p>
                        </div>

                        <div className="flex flex-wrap items-center gap-3">
                            <button
                                type="button"
                                onClick={() => regenerateMutation.mutate()}
                                disabled={regenerateMutation.isPending}
                                aria-busy={regenerateMutation.isPending}
                                className="inline-flex items-center justify-center gap-2 rounded-full bg-mint text-ink font-display font-extrabold text-sm sm:text-base px-6 py-3.5 shadow-glow-mint hover:-translate-y-0.5 hover:brightness-105 active:scale-[0.98] transition-all duration-200 cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed disabled:hover:translate-y-0"
                            >
                                <RefreshCw className={`w-4 h-4 ${regenerateMutation.isPending ? 'animate-spin' : ''}`} />
                                Re-merge groceries
                            </button>

                            <button
                                type="button"
                                onClick={handlePrint}
                                className="inline-flex items-center justify-center gap-2 rounded-full bg-transparent text-ink font-bold text-sm px-5 py-3 border-2 border-ink/15 hover:border-ink hover:bg-paper transition-all duration-200 cursor-pointer"
                            >
                                <Printer className="w-4 h-4 text-mint-deep" />
                                Print list
                            </button>
                        </div>
                    </div>

                    {/* Progress ring card */}
                    <div className="shrink-0 bg-paper rounded-3xl p-5 sm:p-6 flex items-center gap-5 pop-mint animate-pop-in animation-delay-150 lg:min-w-[20rem]">
                        <div className="relative w-28 h-28 shrink-0">
                            <svg viewBox="0 0 100 100" className="w-full h-full -rotate-90" aria-hidden="true">
                                <circle cx="50" cy="50" r={RING_RADIUS} fill="none" stroke="var(--mint-soft)" strokeWidth="10" />
                                <circle
                                    cx="50"
                                    cy="50"
                                    r={RING_RADIUS}
                                    fill="none"
                                    stroke="var(--mint)"
                                    strokeWidth="10"
                                    strokeLinecap="round"
                                    strokeDasharray={RING_CIRCUMFERENCE}
                                    strokeDashoffset={ringOffset}
                                    className="transition-[stroke-dashoffset] duration-500 ease-out"
                                />
                            </svg>
                            <div className="absolute inset-0 flex flex-col items-center justify-center">
                                {allDone ? (
                                    <PartyPopper className="w-8 h-8 text-mint-deep animate-wiggle" aria-hidden="true" />
                                ) : (
                                    <>
                                        <span className="font-display font-extrabold text-2xl text-ink tabular-nums leading-none">
                                            {progressPercent}%
                                        </span>
                                        <span className="text-[10px] font-bold uppercase tracking-wider text-ink-3 mt-1">done</span>
                                    </>
                                )}
                            </div>
                        </div>

                        <div className="space-y-2 min-w-0">
                            <div className="flex items-center gap-2 text-mint-deep">
                                <CheckCircle2 className="w-5 h-5" aria-hidden="true" />
                                <span className="text-sm font-extrabold uppercase tracking-wider">Trip progress</span>
                            </div>
                            <p className="font-display font-extrabold text-2xl text-ink leading-tight tabular-nums">
                                {checkedCount}
                                <span className="text-ink-3 text-lg"> / {totalCount}</span>
                            </p>
                            <p className="text-xs text-ink-2 leading-snug" aria-live="polite">
                                {allDone ? 'Basket complete — nicely done.' : `${remainingCount} still to find`}
                            </p>
                            <div
                                className="h-2.5 w-full bg-mint-soft rounded-full overflow-hidden"
                                role="progressbar"
                                aria-valuemin={0}
                                aria-valuemax={100}
                                aria-valuenow={progressPercent}
                                aria-label="Shopping trip progress"
                            >
                                <div
                                    className="h-full bg-mint rounded-full transition-all duration-500 ease-out"
                                    style={{ width: `${progressPercent}%` }}
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {/* Printable Header (Visible only when printed) */}
            <div className="hidden print:block mb-8 pb-4 border-b border-line-strong">
                <h1 className="text-2xl font-display font-extrabold text-ink">Panchforon — Grocery Shopping List</h1>
                <p className="text-xs text-ink-2">Consolidated on {new Date().toLocaleDateString()} for your active weekly meal plan.</p>
            </div>

            {/* Group summary chips */}
            <div className="flex items-center gap-2 overflow-x-auto no-scrollbar px-1 print:hidden" aria-label="List sections">
                <span className="inline-flex items-center gap-1.5 text-xs font-extrabold uppercase tracking-wider text-ink-3 shrink-0">
                    <ListChecks className="w-4 h-4" aria-hidden="true" />
                    Sections
                </span>
                {groups.map((group) => (
                    <a
                        key={group.key}
                        href={`#group-${group.key}`}
                        className="shrink-0 inline-flex items-center gap-1.5 rounded-full bg-paper border-2 border-line hover:border-ink text-xs font-bold text-ink px-3 py-1.5 transition-colors"
                    >
                        {group.title}
                        <span className="text-ink-3 tabular-nums">{group.items.length}</span>
                    </a>
                ))}
            </div>

            {/* Dimensional groupings */}
            <div className="space-y-5 sm:space-y-6">
                {groups.map((group, index) => (
                    <div key={group.key} id={`group-${group.key}`} className="scroll-mt-24">
                        <ShoppingListGroup
                            title={group.title}
                            icon={group.icon}
                            description={group.description}
                            items={group.items}
                            index={index}
                        />
                    </div>
                ))}
            </div>

            {/* Merge engine invariant callout */}
            <div className="relative overflow-hidden bg-turmeric-soft rounded-3xl p-5 sm:p-6 flex items-start gap-4 print:hidden animate-fade-in">
                <div className="absolute -right-8 -bottom-8 w-32 h-32 bg-turmeric/30 blob-1" aria-hidden="true" />
                <div className="relative w-11 h-11 rounded-2xl bg-turmeric text-ink flex items-center justify-center shrink-0 sticker">
                    <Info className="w-5 h-5" />
                </div>
                <div className="relative text-sm text-ink-2 leading-relaxed">
                    <strong className="font-display font-extrabold text-ink text-base block mb-1">Merge engine invariant</strong>
                    Mass and volume never cross-contaminate — 200 g of chicken will never combine with 1 cup of chicken — and every line notes which dishes contributed to it.
                </div>
            </div>
        </div>
    );
};
