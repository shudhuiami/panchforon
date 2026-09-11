import React, { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { mealPlanApi } from '../api/mealPlan';
import { useAuth } from '../context/AuthContext';
import { ServingsControl } from '../features/meal-plan/ServingsControl';
import { Button } from '../components/ui/Button';
import { LoadingSpinner } from '../components/common/LoadingSpinner';
import { EmptyState } from '../components/common/EmptyState';
import {
    CalendarDays,
    ShoppingBag,
    X,
    Sparkles,
    Plus,
    LogIn,
    ArrowRight,
    Utensils,
    ChefHat,
    Layers,
    Users,
    Globe2,
    Loader2,
    Scale,
    Ban,
    Leaf,
    AlertTriangle,
} from 'lucide-react';

const accentCycle = [
    { chip: 'bg-saffron-soft text-saffron-deep', glow: 'hover:shadow-glow-saffron', fallback: 'bg-sunrise-gradient' },
    { chip: 'bg-plum-soft text-plum-deep', glow: 'hover:shadow-glow-plum', fallback: 'bg-plum-gradient' },
    { chip: 'bg-mint-soft text-mint-deep', glow: 'hover:shadow-glow-mint', fallback: 'bg-mint-gradient' },
    { chip: 'bg-chili-soft text-chili-deep', glow: 'hover:shadow-glow-chili', fallback: 'bg-spice-gradient' },
    { chip: 'bg-turmeric-soft text-turmeric-deep', glow: 'hover:shadow-glow-turmeric', fallback: 'bg-sunrise-gradient' },
];

const statDelays = ['animation-delay-100', 'animation-delay-200', 'animation-delay-300'];

export const MealPlanPage: React.FC = () => {
    const { token, demoLogin } = useAuth();
    const navigate = useNavigate();
    const queryClient = useQueryClient();
    const [isDemoLoading, setIsDemoLoading] = useState(false);

    // Fetch active meal plan
    const { data, isLoading, error } = useQuery({
        queryKey: ['mealPlan'],
        queryFn: () => mealPlanApi.get(),
        enabled: !!token,
    });

    const mealPlan = data?.data;
    const items = mealPlan?.items || [];

    // Remove item mutation
    const removeMutation = useMutation({
        mutationFn: (itemId: number) => mealPlanApi.removeItem(itemId),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['mealPlan'] });
        },
    });

    // Generate shopping list mutation
    const generateShoppingMutation = useMutation({
        mutationFn: () => mealPlanApi.generateShoppingList(),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['shoppingList'] });
            navigate('/shopping-list');
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

    if (!token) {
        return (
            <div className="max-w-xl mx-auto my-10 sm:my-16 animate-slide-up">
                <div className="relative bg-paper rounded-4xl p-8 sm:p-12 text-center space-y-6 pop pop-hover overflow-hidden">
                    <div className="absolute -top-10 -right-10 w-40 h-40 bg-plum-soft blob-1 opacity-80" aria-hidden="true" />
                    <div className="absolute -bottom-12 -left-8 w-36 h-36 bg-turmeric-soft blob-2 opacity-80" aria-hidden="true" />

                    <div className="relative">
                        <div className="w-20 h-20 bg-plum-soft text-plum-deep rounded-3xl flex items-center justify-center mx-auto sticker">
                            <CalendarDays className="w-10 h-10" />
                        </div>
                        <h2 className="mt-6 text-3xl sm:text-4xl font-display">
                            Plan your <span className="text-spice">week's meals</span>
                        </h2>
                        <p className="mt-3 text-sm sm:text-base text-ink-2 leading-relaxed max-w-md mx-auto">
                            Pick your dishes, scale portions and let our merge engine turn it all into one tidy grocery list with zero duplicate ingredients.
                        </p>
                    </div>

                    <div className="relative pt-2 space-y-3">
                        <button
                            type="button"
                            onClick={handleQuickDemo}
                            disabled={isDemoLoading}
                            className="w-full inline-flex items-center justify-center gap-2 rounded-full bg-saffron text-ink font-display font-extrabold text-base px-7 py-4 shadow-glow-saffron hover:-translate-y-0.5 hover:bg-turmeric transition-all duration-200 cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed"
                        >
                            {isDemoLoading ? (
                                <Loader2 className="w-5 h-5 animate-spin" />
                            ) : (
                                <Sparkles className="w-5 h-5" />
                            )}
                            Try the demo plan in one click
                        </button>

                        <div className="grid grid-cols-2 gap-3 pt-1">
                            <Link to="/login" className="block">
                                <Button variant="outline" size="md" className="w-full rounded-full">
                                    <LogIn className="w-4 h-4" />
                                    Sign in
                                </Button>
                            </Link>
                            <Link to="/register" className="block">
                                <Button variant="secondary" size="md" className="w-full rounded-full">
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
        return <LoadingSpinner message="Fetching your active weekly meal plan..." />;
    }

    if (error) {
        return (
            <div className="max-w-md mx-auto my-16 text-center bg-chili-soft rounded-3xl p-8 space-y-3 animate-scale-in">
                <div className="w-14 h-14 mx-auto rounded-2xl bg-paper text-chili-deep flex items-center justify-center">
                    <AlertTriangle className="w-7 h-7" />
                </div>
                <p className="text-chili-deep font-display font-extrabold text-xl">Failed to load meal plan.</p>
                <p className="text-sm text-ink-2">Please refresh the page or try again in a moment.</p>
            </div>
        );
    }

    const mealCount = items.length;
    const totalServings = items.reduce((sum, item) => sum + (item.servings || 0), 0);
    const cuisineCount = new Set(
        items.map((item) => item.recipe?.cuisine).filter((c): c is string => !!c)
    ).size;

    const stats = [
        { label: mealCount === 1 ? 'Meal' : 'Meals', value: mealCount, icon: Utensils, tint: 'bg-saffron-soft text-saffron-deep' },
        { label: 'Servings', value: totalServings, icon: Users, tint: 'bg-mint-soft text-mint-deep' },
        { label: cuisineCount === 1 ? 'Cuisine' : 'Cuisines', value: cuisineCount, icon: Globe2, tint: 'bg-chili-soft text-chili-deep' },
    ];

    return (
        <div className="space-y-8 sm:space-y-10 pb-24 max-w-6xl mx-auto">
            {/* Header band */}
            <section className="relative overflow-hidden bg-plum-soft rounded-4xl px-6 py-8 sm:px-10 sm:py-12 animate-slide-up">
                <div className="absolute inset-0 bg-dots opacity-60" aria-hidden="true" />
                <div className="absolute -top-16 -right-16 w-56 h-56 bg-plum/20 blob-2 animate-float" aria-hidden="true" />
                <div className="absolute -bottom-20 left-1/3 w-48 h-48 bg-turmeric/25 blob-1" aria-hidden="true" />

                <div className="relative flex flex-col lg:flex-row lg:items-end justify-between gap-8">
                    <div className="space-y-5 max-w-xl">
                        <div className="flex items-center gap-2 flex-wrap">
                            <span className="inline-flex items-center gap-1.5 rounded-full bg-paper text-plum-deep text-xs font-extrabold uppercase tracking-wider px-3 py-1.5 sticker shadow-sm">
                                <CalendarDays className="w-3.5 h-3.5" />
                                Active plan
                            </span>
                            <span className="text-xs font-bold text-ink-2">
                                {mealPlan?.name || 'Weekly Table'}
                            </span>
                        </div>

                        <div>
                            <h1 className="text-4xl sm:text-5xl lg:text-6xl font-display text-balance">
                                Your week's <span className="text-spice">plan</span>
                            </h1>
                            <p className="mt-3 text-base sm:text-lg text-ink-2 leading-relaxed text-pretty">
                                Scale portions, swap dishes, and turn the whole week into one smart grocery run.
                            </p>
                        </div>

                        <div className="flex flex-wrap gap-2.5">
                            {stats.map((stat, index) => {
                                const Icon = stat.icon;
                                return (
                                    <div
                                        key={stat.label}
                                        className={`inline-flex items-center gap-2.5 bg-paper rounded-full pl-1.5 pr-4 py-1.5 shadow-sm animate-pop-in ${statDelays[index] ?? ''}`}
                                    >
                                        <span className={`w-8 h-8 rounded-full flex items-center justify-center ${stat.tint}`}>
                                            <Icon className="w-4 h-4" />
                                        </span>
                                        <span className="font-display font-extrabold text-xl text-ink tabular-nums leading-none">
                                            {stat.value}
                                        </span>
                                        <span className="text-xs font-bold uppercase tracking-wider text-ink-3">
                                            {stat.label}
                                        </span>
                                    </div>
                                );
                            })}
                        </div>
                    </div>

                    <div className="flex flex-col sm:flex-row lg:flex-col gap-3 shrink-0">
                        {items.length > 0 && (
                            <button
                                type="button"
                                onClick={() => generateShoppingMutation.mutate()}
                                disabled={generateShoppingMutation.isPending}
                                aria-busy={generateShoppingMutation.isPending}
                                className="inline-flex items-center justify-center gap-2.5 rounded-full bg-mint text-ink font-display font-extrabold text-base px-7 py-4 shadow-glow-mint hover:-translate-y-0.5 hover:brightness-105 active:scale-[0.98] transition-all duration-200 cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed disabled:hover:translate-y-0"
                            >
                                {generateShoppingMutation.isPending ? (
                                    <Loader2 className="w-5 h-5 animate-spin" />
                                ) : (
                                    <ShoppingBag className="w-5 h-5" />
                                )}
                                Generate shopping list
                                <ArrowRight className="w-4 h-4" />
                            </button>
                        )}
                        <Link
                            to="/"
                            className="inline-flex items-center justify-center gap-2 rounded-full bg-transparent text-ink font-bold text-sm px-6 py-3.5 border-2 border-ink/15 hover:border-ink hover:bg-paper transition-all duration-200"
                        >
                            <Plus className="w-4 h-4 text-saffron-deep" />
                            Browse recipes
                        </Link>
                    </div>
                </div>
            </section>

            {/* Items */}
            {items.length === 0 ? (
                <div className="animate-fade-in">
                    <EmptyState
                        icon={Utensils}
                        title="Your plate is empty (for now)"
                        description="No recipes in this week's plan yet. Wander through our regional dishes and add a few favourites to get cooking."
                        actionLabel="Discover recipes"
                        onAction={() => navigate('/')}
                    />
                </div>
            ) : (
                <section className="space-y-5" aria-labelledby="plan-dishes-heading">
                    <div className="flex items-end justify-between gap-4 px-1">
                        <div>
                            <h2 id="plan-dishes-heading" className="text-2xl sm:text-3xl font-display">
                                This week's dishes
                            </h2>
                            <p className="text-sm text-ink-2 mt-1">
                                {items.length} {items.length === 1 ? 'dish' : 'dishes'} · tap the stepper to scale portions
                            </p>
                        </div>
                        <span className="hidden sm:inline-flex items-center gap-1.5 rounded-full bg-turmeric-soft text-turmeric-deep text-xs font-extrabold uppercase tracking-wider px-3 py-1.5 sticker-r">
                            <Sparkles className="w-3.5 h-3.5" />
                            Auto-merged
                        </span>
                    </div>

                    <ul className="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-5 list-none p-0 m-0">
                        {items.map((item, index) => {
                            const recipe = item.recipe;
                            const accent = accentCycle[index % accentCycle.length];
                            const isRemoving = removeMutation.isPending && removeMutation.variables === item.id;

                            return (
                                <li
                                    key={item.id}
                                    className={`group relative bg-paper rounded-3xl p-4 sm:p-5 border-2 border-line hover:border-ink/80 hover:-translate-y-1 ${accent.glow} transition-all duration-300 animate-slide-up ${
                                        isRemoving ? 'opacity-50 pointer-events-none' : ''
                                    }`}
                                    style={{ animationDelay: `${Math.min(index, 6) * 60}ms` }}
                                >
                                    <button
                                        type="button"
                                        onClick={() => removeMutation.mutate(item.id)}
                                        disabled={removeMutation.isPending}
                                        className="absolute top-3.5 right-3.5 w-9 h-9 rounded-full bg-chili-soft text-chili-deep flex items-center justify-center hover:bg-chili hover:text-white hover:rotate-90 transition-all duration-200 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed focus-visible:outline-3 focus-visible:outline-chili"
                                        title="Remove recipe from plan"
                                        aria-label={`Remove ${recipe?.title || 'recipe'} from plan`}
                                    >
                                        {isRemoving ? <Loader2 className="w-4 h-4 animate-spin" /> : <X className="w-4 h-4 stroke-[2.5]" />}
                                    </button>

                                    <div className="flex items-start gap-4">
                                        <Link
                                            to={`/recipes/${recipe?.slug || item.recipe_id}`}
                                            className="block w-20 h-20 sm:w-24 sm:h-24 rounded-2xl overflow-hidden shrink-0 border-2 border-line group-hover:border-ink/80 transition-colors"
                                            tabIndex={-1}
                                            aria-hidden="true"
                                        >
                                            {recipe?.image_url ? (
                                                <img
                                                    src={recipe.image_url}
                                                    alt=""
                                                    className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                                />
                                            ) : (
                                                <div className={`w-full h-full flex items-center justify-center text-white ${accent.fallback}`}>
                                                    <ChefHat className="w-9 h-9 drop-shadow" />
                                                </div>
                                            )}
                                        </Link>

                                        <div className="min-w-0 flex-1 pr-10 space-y-2">
                                            <div className="flex items-center gap-1.5 flex-wrap">
                                                {recipe?.cuisine && (
                                                    <span className={`inline-flex items-center rounded-full text-[11px] font-extrabold uppercase tracking-wider px-2.5 py-1 sticker ${accent.chip}`}>
                                                        {recipe.cuisine}
                                                    </span>
                                                )}
                                                {recipe?.category && (
                                                    <span className="inline-flex items-center rounded-full bg-cream-2 text-ink-2 text-[11px] font-bold px-2.5 py-1">
                                                        {recipe.category}
                                                    </span>
                                                )}
                                            </div>

                                            <Link
                                                to={`/recipes/${recipe?.slug || item.recipe_id}`}
                                                className="block font-display font-extrabold text-lg sm:text-xl text-ink leading-tight hover:text-saffron-deep transition-colors line-clamp-2"
                                            >
                                                {recipe?.title || `Recipe #${item.recipe_id}`}
                                            </Link>

                                            <p className="text-xs font-semibold text-ink-3">
                                                Base yield: {recipe?.servings || 4} servings
                                            </p>
                                        </div>
                                    </div>

                                    <div className="mt-4 pt-4 border-t-2 border-dashed border-line flex items-center justify-between gap-3 flex-wrap">
                                        <span className="text-[11px] font-extrabold uppercase tracking-wider text-ink-3">
                                            Scale servings
                                        </span>
                                        <ServingsControl
                                            itemId={item.id}
                                            currentServings={item.servings}
                                        />
                                    </div>
                                </li>
                            );
                        })}
                    </ul>
                </section>
            )}

            {/* Merge engine explainer */}
            <section className="relative overflow-hidden bg-ink-gradient text-white rounded-4xl p-6 sm:p-10 animate-fade-in">
                <div className="absolute inset-0 bg-dots-light opacity-40" aria-hidden="true" />
                <div className="absolute -right-10 -bottom-10 w-52 h-52 bg-saffron/30 blob-1 blur-2xl" aria-hidden="true" />

                <div className="relative flex flex-col lg:flex-row lg:items-center gap-8">
                    <div className="flex items-start gap-4 flex-1">
                        <div className="w-14 h-14 rounded-2xl bg-saffron text-ink flex items-center justify-center shrink-0 sticker shadow-glow-saffron">
                            <Layers className="w-7 h-7" />
                        </div>
                        <div className="space-y-2">
                            <div className="flex items-center gap-2 flex-wrap">
                                <h3 className="font-display font-extrabold text-xl sm:text-2xl text-white">
                                    Intelligent merge engine
                                </h3>
                                <span className="inline-flex items-center gap-1 rounded-full bg-mint text-ink text-[11px] font-extrabold uppercase tracking-wider px-2.5 py-1">
                                    <span className="w-1.5 h-1.5 rounded-full bg-ink animate-pulse" aria-hidden="true" />
                                    Active
                                </span>
                            </div>
                            <p className="text-sm text-white/75 leading-relaxed max-w-xl">
                                Portions scale proportionally, dimensions never mix, counts round up so you never run short, and &ldquo;to taste&rdquo; stays exactly that.
                            </p>
                        </div>
                    </div>

                    <ul className="grid grid-cols-1 sm:grid-cols-3 gap-3 list-none p-0 m-0 lg:w-[26rem] shrink-0">
                        {[
                            { icon: Scale, title: 'Scaled', text: 'Portions multiply cleanly', tint: 'bg-turmeric text-ink' },
                            { icon: Ban, title: 'Isolated', text: 'kg never meets ml', tint: 'bg-chili text-white' },
                            { icon: Leaf, title: 'Preserved', text: 'Taste notes kept intact', tint: 'bg-mint text-ink' },
                        ].map((feature) => {
                            const Icon = feature.icon;
                            return (
                                <li key={feature.title} className="glass-dark rounded-2xl p-3.5 flex sm:flex-col items-center sm:items-start gap-3">
                                    <span className={`w-9 h-9 rounded-xl flex items-center justify-center shrink-0 ${feature.tint}`}>
                                        <Icon className="w-4.5 h-4.5" />
                                    </span>
                                    <span>
                                        <span className="block font-display font-extrabold text-sm text-white">{feature.title}</span>
                                        <span className="block text-xs text-white/65">{feature.text}</span>
                                    </span>
                                </li>
                            );
                        })}
                    </ul>
                </div>

                {items.length > 0 && (
                    <div className="relative mt-6 pt-6 border-t border-white/10 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <p className="text-sm text-white/70">
                            Ready when you are — consolidate the whole week into one list.
                        </p>
                        <button
                            type="button"
                            onClick={() => generateShoppingMutation.mutate()}
                            disabled={generateShoppingMutation.isPending}
                            aria-busy={generateShoppingMutation.isPending}
                            className="inline-flex items-center justify-center gap-2 rounded-full bg-paper text-ink font-display font-extrabold text-sm px-6 py-3 hover:bg-turmeric transition-colors duration-200 cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed shrink-0"
                        >
                            {generateShoppingMutation.isPending ? (
                                <Loader2 className="w-4 h-4 animate-spin" />
                            ) : (
                                <ShoppingBag className="w-4 h-4" />
                            )}
                            Consolidate list
                        </button>
                    </div>
                )}
            </section>
        </div>
    );
};
