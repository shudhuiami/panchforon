import React, { useState } from 'react';
import { useParams, Link, useNavigate, useLocation } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { recipesApi } from '../api/recipes';
import { mealPlanApi } from '../api/mealPlan';
import { useAuth } from '../context/AuthContext';
import { Button } from '../components/ui/Button';
import { Breadcrumb } from '../components/ui/Breadcrumb';
import { QuantityStepper } from '../components/ui/QuantityStepper';
import { StarRating } from '../components/ui/StarRating';
import { RatingReviewModal } from '../features/ratings/RatingReviewModal';
import {
    ArrowLeft,
    Users,
    Plus,
    Check,
    Sparkles,
    ExternalLink,
    Edit3,
    Trash2,
    ChefHat,
    MessageSquare,
    Scale,
    Globe,
    ListChecks,
    Flame,
    Star,
    Loader2,
    CalendarPlus,
    Quote,
    Utensils,
    SearchX,
} from 'lucide-react';

/** Rotating spice tones for avatars and ingredient dots. */
const AVATAR_TONES = [
    'bg-saffron text-ink',
    'bg-plum text-white',
    'bg-mint text-ink',
    'bg-chili text-white',
    'bg-turmeric text-ink',
];
const DOT_TONES = ['bg-saffron', 'bg-chili', 'bg-mint', 'bg-plum', 'bg-turmeric'];

const formatQuantity = (q: number | null | undefined): string => {
    if (q === null || q === undefined) return '';
    const n = Number(q);
    if (Number.isNaN(n)) return String(q);
    return String(Math.round(n * 100) / 100);
};

const initialsOf = (name?: string | null): string => {
    if (!name) return 'U';
    return name
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((p) => p[0].toUpperCase())
        .join('');
};

export const RecipeDetailPage: React.FC = () => {
    const { slug } = useParams<{ slug: string }>();
    const navigate = useNavigate();
    const location = useLocation();
    const queryClient = useQueryClient();
    const { user, token } = useAuth();

    const [isRatingModalOpen, setIsRatingModalOpen] = useState(false);
    const [selectedServings, setSelectedServings] = useState<number>(4);
    const [justAddedToPlan, setJustAddedToPlan] = useState(false);
    const [imgError, setImgError] = useState(false);

    const { data, isLoading, error } = useQuery({
        queryKey: ['recipe', slug],
        queryFn: () => recipesApi.get(slug!),
        enabled: !!slug,
    });

    const recipe = data?.data;

    // Set initial servings once recipe loads
    React.useEffect(() => {
        if (recipe?.servings) {
            setSelectedServings(recipe.servings);
        }
    }, [recipe?.servings]);

    // Add to plan mutation
    const addToPlanMutation = useMutation({
        mutationFn: () => mealPlanApi.addItem(recipe!.id, selectedServings),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['mealPlan'] });
            setJustAddedToPlan(true);
            setTimeout(() => setJustAddedToPlan(false), 2000);
        },
    });

    // Delete mutation
    const deleteRecipeMutation = useMutation({
        mutationFn: () => recipesApi.delete(recipe!.id),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['recipes'] });
            navigate('/');
        },
    });

    const handleAddToPlan = () => {
        if (!token) {
            navigate('/login', { state: { from: location } });
            return;
        }
        addToPlanMutation.mutate();
    };

    const handleDelete = () => {
        if (window.confirm('Are you sure you want to delete this recipe? This cannot be undone.')) {
            deleteRecipeMutation.mutate();
        }
    };

    /* ------------------------------------------------------------------ */
    /* Loading skeleton                                                     */
    /* ------------------------------------------------------------------ */
    if (isLoading) {
        return (
            <div
                className="max-w-6xl mx-auto pb-24 space-y-8 animate-fade-in"
                aria-busy="true"
                aria-live="polite"
            >
                <div className="h-9 w-72 max-w-full rounded-full skeleton-shimmer" />
                <div className="grid grid-cols-1 lg:grid-cols-12 gap-8">
                    <div className="lg:col-span-6 aspect-4/3 rounded-4xl skeleton-shimmer" />
                    <div className="lg:col-span-6 space-y-4">
                        <div className="h-4 w-24 rounded-full skeleton-shimmer" />
                        <div className="h-12 w-5/6 rounded-2xl skeleton-shimmer" />
                        <div className="h-12 w-2/3 rounded-2xl skeleton-shimmer" />
                        <div className="h-10 w-48 rounded-full skeleton-shimmer" />
                        <div className="grid grid-cols-2 gap-4 pt-4">
                            <div className="h-36 rounded-3xl skeleton-shimmer" />
                            <div className="h-36 rounded-3xl skeleton-shimmer" />
                        </div>
                    </div>
                </div>
                <div className="grid grid-cols-1 lg:grid-cols-12 gap-8">
                    <div className="lg:col-span-5 h-80 rounded-3xl skeleton-shimmer" />
                    <div className="lg:col-span-7 space-y-4">
                        <div className="h-24 rounded-2xl skeleton-shimmer" />
                        <div className="h-24 rounded-2xl skeleton-shimmer" />
                        <div className="h-24 rounded-2xl skeleton-shimmer" />
                    </div>
                </div>
                <div className="flex justify-center">
                    <span className="inline-flex items-center gap-2 rounded-full bg-paper border border-line px-4 py-2 text-sm font-bold text-ink-2 shadow-md">
                        <ChefHat className="w-4 h-4 text-saffron-deep animate-wiggle" />
                        Curating recipe details and ingredient measurements...
                    </span>
                </div>
            </div>
        );
    }

    /* ------------------------------------------------------------------ */
    /* Error / not found                                                    */
    /* ------------------------------------------------------------------ */
    if (error || !recipe) {
        return (
            <div className="max-w-md mx-auto py-16 sm:py-24 animate-slide-up">
                <div className="relative rounded-4xl bg-paper pop p-8 sm:p-10 text-center space-y-5 overflow-hidden">
                    <div className="absolute -top-10 -right-10 w-40 h-40 bg-chili-soft blob-1" aria-hidden="true" />
                    <div className="relative mx-auto w-20 h-20 rounded-3xl bg-chili-soft flex items-center justify-center">
                        <SearchX className="w-10 h-10 text-chili-deep" />
                    </div>
                    <div className="relative space-y-2">
                        <h2 className="font-display text-3xl text-ink">Recipe not found</h2>
                        <p className="text-sm text-ink-2 leading-relaxed">
                            The dish you are looking for is no longer available. It may have been removed or renamed.
                        </p>
                    </div>
                    <div className="relative">
                        <Link to="/">
                            <Button variant="primary" size="lg" className="rounded-full">
                                <ArrowLeft className="w-4 h-4" />
                                Back to all recipes
                            </Button>
                        </Link>
                    </div>
                </div>
            </div>
        );
    }

    const isAuthor = user && recipe.user_id && user.id === recipe.user_id;
    const userRatingObj = recipe.ratings?.find((r) => user && r.user?.id === user.id);

    const ratingsCount = recipe.stat?.ratings_count ?? recipe.ratings?.length ?? 0;
    const ratingsAvg = recipe.stat?.ratings_avg ?? null;
    const bayesian = recipe.stat?.bayesian_score;
    const hasBayesian = bayesian !== null && bayesian !== undefined;
    const headlineScore = hasBayesian ? bayesian : ratingsAvg;

    const steps = recipe.instructions
        ? recipe.instructions.split(/\r?\n/).filter((step) => step.trim().length > 0)
        : [];

    const distribution = [5, 4, 3, 2, 1].map((s) => ({
        stars: s,
        count: recipe.ratings?.filter((r) => r.stars === s).length ?? 0,
    }));
    const reviewTotal = recipe.ratings?.length ?? 0;

    const openRatingModal = () => {
        if (!token) {
            navigate('/login', { state: { from: location } });
            return;
        }
        setIsRatingModalOpen(true);
    };

    return (
        <div className="max-w-6xl mx-auto pb-24 space-y-12 sm:space-y-16">
            {/* ============================================================ */}
            {/* Top bar: breadcrumb pill + owner actions                      */}
            {/* ============================================================ */}
            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 animate-fade-in">
                <div className="inline-flex max-w-full rounded-full bg-paper border border-line px-4 py-2 shadow-sm">
                    <Breadcrumb
                        items={[
                            { label: 'Recipes', to: '/' },
                            { label: recipe.cuisine || 'Dishes', to: recipe.cuisine ? `/?cuisine=${encodeURIComponent(recipe.cuisine)}` : '/' },
                            { label: recipe.title },
                        ]}
                    />
                </div>

                {isAuthor && (
                    <div className="flex items-center gap-2 shrink-0">
                        <Link to={`/recipes/${recipe.slug}/edit`}>
                            <Button variant="outline" size="sm" className="rounded-full">
                                <Edit3 className="w-3.5 h-3.5" />
                                Edit recipe
                            </Button>
                        </Link>
                        <Button
                            variant="danger"
                            size="sm"
                            onClick={handleDelete}
                            isLoading={deleteRecipeMutation.isPending}
                            className="rounded-full"
                        >
                            <Trash2 className="w-3.5 h-3.5" />
                            Delete
                        </Button>
                    </div>
                )}
            </div>

            {/* ============================================================ */}
            {/* Hero                                                          */}
            {/* ============================================================ */}
            <section className="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-center">
                {/* Image with floating stickers */}
                <div className="lg:col-span-6 relative isolate animate-scale-in">
                    <div
                        className="absolute -top-6 -left-6 w-36 h-36 sm:w-44 sm:h-44 bg-turmeric/50 blob-1 -z-10 animate-float"
                        aria-hidden="true"
                    />
                    <div
                        className="absolute -bottom-8 -right-6 w-40 h-40 sm:w-52 sm:h-52 bg-plum/25 blob-2 -z-10 animate-float animation-delay-300"
                        aria-hidden="true"
                    />

                    <div className="relative aspect-4/3 rounded-4xl overflow-hidden pop-saffron bg-cream-2">
                        {recipe.image_url && !imgError ? (
                            <img
                                src={recipe.image_url}
                                alt={recipe.title}
                                onError={() => setImgError(true)}
                                className="w-full h-full object-cover"
                            />
                        ) : (
                            <div className="w-full h-full flex flex-col items-center justify-center bg-sunrise-gradient bg-dots-light text-white p-6 text-center">
                                <div className="w-20 h-20 rounded-3xl bg-white/20 flex items-center justify-center mb-4">
                                    <ChefHat className="w-10 h-10" />
                                </div>
                                <span className="font-display font-extrabold text-xl leading-tight drop-shadow-sm">
                                    {recipe.title}
                                </span>
                            </div>
                        )}

                        {/* Sticker badges */}
                        {(recipe.cuisine || recipe.category) && (
                            <div className="absolute top-4 left-4 sm:top-5 sm:left-5 flex flex-col items-start gap-2.5">
                                {recipe.cuisine && (
                                    <span className="sticker inline-flex items-center gap-1.5 rounded-full bg-turmeric text-ink font-display font-extrabold text-xs uppercase tracking-wider px-3.5 py-1.5 pop-sm">
                                        <Globe className="w-3.5 h-3.5" />
                                        {recipe.cuisine}
                                    </span>
                                )}
                                {recipe.category && (
                                    <span className="sticker-r inline-flex items-center gap-1.5 rounded-full bg-chili text-white font-display font-extrabold text-xs uppercase tracking-wider px-3.5 py-1.5 pop-sm">
                                        <Utensils className="w-3.5 h-3.5" />
                                        {recipe.category}
                                    </span>
                                )}
                            </div>
                        )}

                        {/* Glass rating pill */}
                        <div className="absolute bottom-4 left-4 sm:bottom-5 sm:left-5 glass rounded-full pl-3 pr-4 py-2 inline-flex items-center gap-2 shadow-md">
                            <span className="w-7 h-7 rounded-full bg-turmeric flex items-center justify-center">
                                <Star className="w-4 h-4 text-ink fill-ink" />
                            </span>
                            <span className="font-display font-extrabold text-ink text-base leading-none">
                                {ratingsAvg !== null ? Number(ratingsAvg).toFixed(1) : 'New'}
                            </span>
                            <span className="text-xs font-semibold text-ink-2 leading-none">
                                {ratingsCount} {ratingsCount === 1 ? 'rating' : 'ratings'}
                            </span>
                        </div>
                    </div>
                </div>

                {/* Title, author, meta, score, actions */}
                <div className="lg:col-span-6 space-y-6 animate-slide-up animation-delay-100">
                    <div className="space-y-4">
                        <span className="inline-flex items-center gap-1.5 text-xs font-extrabold uppercase tracking-[0.18em] text-saffron-deep">
                            <Flame className="w-4 h-4" />
                            Recipe
                        </span>
                        <h1 className="font-display text-4xl md:text-5xl leading-[1.02] text-balance text-ink">
                            {recipe.title}
                        </h1>

                        {/* Author chip */}
                        {recipe.source === 'user' && recipe.author ? (
                            <div className="inline-flex items-center gap-3 rounded-full bg-paper border border-line pl-1.5 pr-4 py-1.5 shadow-sm">
                                <span className="w-9 h-9 rounded-full bg-plum text-white font-display font-extrabold text-sm flex items-center justify-center">
                                    {initialsOf(recipe.author.name)}
                                </span>
                                <span className="flex flex-col leading-tight">
                                    <span className="text-[10px] font-bold uppercase tracking-wider text-ink-3">
                                        Curated by
                                    </span>
                                    <span className="text-sm font-extrabold text-ink">{recipe.author.name}</span>
                                </span>
                            </div>
                        ) : (
                            <div className="inline-flex items-center gap-3 rounded-full bg-paper border border-line pl-1.5 pr-4 py-1.5 shadow-sm">
                                <span className="w-9 h-9 rounded-full bg-plum text-white flex items-center justify-center">
                                    <Globe className="w-4 h-4" />
                                </span>
                                <span className="flex flex-col leading-tight">
                                    <span className="text-[10px] font-bold uppercase tracking-wider text-ink-3">
                                        From
                                    </span>
                                    <span className="text-sm font-extrabold text-ink">TheMealDB Global Collection</span>
                                </span>
                            </div>
                        )}
                    </div>

                    {/* Meta chips */}
                    <div className="flex flex-wrap gap-2.5">
                        <span className="inline-flex items-center gap-2 rounded-full bg-mint-soft text-mint-deep text-sm font-bold px-3.5 py-2">
                            <Users className="w-4 h-4" />
                            {recipe.servings} servings
                        </span>
                        <span className="inline-flex items-center gap-2 rounded-full bg-turmeric-soft text-turmeric-deep text-sm font-bold px-3.5 py-2">
                            <ListChecks className="w-4 h-4" />
                            {recipe.ingredients?.length || 0} ingredients
                        </span>
                        <span className="inline-flex items-center gap-2 rounded-full bg-plum-soft text-plum-deep text-sm font-bold px-3.5 py-2">
                            <Utensils className="w-4 h-4" />
                            {steps.length} {steps.length === 1 ? 'step' : 'steps'}
                        </span>
                        {recipe.source_url && (
                            <a
                                href={recipe.source_url}
                                target="_blank"
                                rel="noreferrer"
                                className="inline-flex items-center gap-2 rounded-full bg-saffron-soft text-saffron-deep text-sm font-bold px-3.5 py-2 hover:bg-saffron hover:text-ink transition-colors"
                            >
                                <ExternalLink className="w-4 h-4" />
                                Original source
                            </a>
                        )}
                    </div>

                    {/* Score card + Plan card */}
                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        {/* Score card */}
                        <div className="relative overflow-hidden rounded-3xl bg-mint-soft p-5 sm:p-6 flex flex-col justify-between gap-4">
                            <div
                                className="absolute -top-8 -right-8 w-28 h-28 rounded-full bg-mint/30"
                                aria-hidden="true"
                            />
                            <div className="relative flex items-center gap-2">
                                <span className="w-8 h-8 rounded-xl bg-mint text-ink flex items-center justify-center">
                                    <Sparkles className="w-4 h-4" />
                                </span>
                                <span className="text-xs font-extrabold uppercase tracking-wider text-mint-deep">
                                    {hasBayesian ? 'Community score' : 'Average rating'}
                                </span>
                            </div>
                            <div className="relative">
                                <div className="font-display font-extrabold text-5xl text-mint-deep leading-none tracking-tight">
                                    {headlineScore !== null && headlineScore !== undefined
                                        ? Number(headlineScore).toFixed(hasBayesian ? 2 : 1)
                                        : '—'}
                                </div>
                                <div className="mt-3 flex flex-wrap items-center gap-x-2 gap-y-1">
                                    <StarRating score={ratingsAvg} count={ratingsCount} size="sm" />
                                </div>
                                {hasBayesian && (
                                    <p className="mt-2 text-[11px] font-semibold text-mint-deep/80 leading-snug">
                                        Bayesian-weighted so one review can&rsquo;t swing it.
                                    </p>
                                )}
                            </div>
                        </div>

                        {/* Plan card */}
                        <div className="rounded-3xl bg-paper border-2 border-ink shadow-pop p-5 sm:p-6 flex flex-col gap-4">
                            <div className="flex items-center justify-between gap-2">
                                <div className="flex items-center gap-2">
                                    <span className="w-8 h-8 rounded-xl bg-saffron-soft text-saffron-deep flex items-center justify-center">
                                        <CalendarPlus className="w-4 h-4" />
                                    </span>
                                    <span className="text-xs font-extrabold uppercase tracking-wider text-ink">
                                        Plan it
                                    </span>
                                </div>
                                <span className="text-[10px] font-bold uppercase tracking-wider text-ink-3 bg-cream-2 rounded-full px-2.5 py-1">
                                    Scaled merge
                                </span>
                            </div>

                            <div className="flex items-center justify-between gap-3">
                                <span className="text-sm font-bold text-ink-2">Servings</span>
                                <QuantityStepper
                                    value={selectedServings}
                                    onChange={setSelectedServings}
                                    min={1}
                                    max={50}
                                    size="md"
                                    ariaLabel="Recipe Servings"
                                />
                            </div>

                            <button
                                type="button"
                                onClick={handleAddToPlan}
                                disabled={addToPlanMutation.isPending}
                                aria-busy={addToPlanMutation.isPending}
                                className={`group inline-flex w-full items-center justify-center gap-2 rounded-full px-5 py-3.5 font-display font-extrabold text-base transition-all duration-200 cursor-pointer active:scale-[0.97] disabled:opacity-60 disabled:cursor-not-allowed ${
                                    justAddedToPlan
                                        ? 'bg-mint text-ink shadow-glow-mint'
                                        : 'bg-saffron text-ink shadow-glow-saffron hover:-translate-y-0.5 hover:bg-saffron-deep hover:text-white'
                                }`}
                            >
                                {addToPlanMutation.isPending ? (
                                    <Loader2 className="w-5 h-5 animate-spin" aria-hidden="true" />
                                ) : justAddedToPlan ? (
                                    <Check className="w-5 h-5 stroke-[3]" aria-hidden="true" />
                                ) : (
                                    <Plus className="w-5 h-5 stroke-[3] transition-transform group-hover:rotate-90" aria-hidden="true" />
                                )}
                                <span>{justAddedToPlan ? 'Added to meal plan!' : 'Add to meal plan'}</span>
                            </button>
                            {addToPlanMutation.isError && (
                                <p className="text-xs font-semibold text-chili-deep">
                                    Couldn&rsquo;t add this dish right now. Please try again.
                                </p>
                            )}
                        </div>
                    </div>
                </div>
            </section>

            {/* ============================================================ */}
            {/* Body: Ingredients (sticky) + Method                          */}
            {/* ============================================================ */}
            <section className="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-10 items-start">
                {/* Ingredients */}
                <aside className="lg:col-span-5 lg:sticky lg:top-24 animate-slide-up animation-delay-150">
                    <div className="relative rounded-3xl bg-turmeric-soft p-6 sm:p-8 overflow-hidden">
                        <div
                            className="absolute -bottom-12 -right-12 w-44 h-44 rounded-full bg-turmeric/30"
                            aria-hidden="true"
                        />
                        <div className="relative flex items-center justify-between gap-3 mb-5">
                            <div className="flex items-center gap-3">
                                <span className="w-11 h-11 rounded-2xl bg-turmeric text-ink flex items-center justify-center shadow-glow-turmeric">
                                    <Scale className="w-5 h-5" />
                                </span>
                                <h2 className="font-display text-2xl text-ink">Ingredients</h2>
                            </div>
                            <span className="sticker-r rounded-full bg-ink text-white text-xs font-extrabold px-3 py-1.5">
                                {recipe.ingredients?.length || 0} items
                            </span>
                        </div>

                        {recipe.ingredients && recipe.ingredients.length > 0 ? (
                            <ul className="relative space-y-1.5">
                                {recipe.ingredients.map((ing, idx) => {
                                    const name = ing.ingredient?.canonical_name || ing.raw_text;
                                    const hasMeasure = (ing.quantity !== null && ing.quantity !== undefined) || !!ing.unit;
                                    return (
                                        <li
                                            key={ing.id}
                                            className="group flex items-center gap-3 rounded-2xl bg-paper/70 hover:bg-paper px-3.5 py-2.5 transition-colors"
                                        >
                                            <span
                                                className={`w-2.5 h-2.5 rounded-full shrink-0 ${DOT_TONES[idx % DOT_TONES.length]} group-hover:scale-125 transition-transform`}
                                                aria-hidden="true"
                                            />
                                            <span className="flex-1 min-w-0 text-sm text-ink leading-snug">
                                                {hasMeasure && (
                                                    <>
                                                        <span className="font-display font-extrabold text-base text-turmeric-deep">
                                                            {formatQuantity(ing.quantity)}
                                                        </span>
                                                        {ing.unit && (
                                                            <span className="font-bold text-ink-2 ml-1">{ing.unit}</span>
                                                        )}{' '}
                                                    </>
                                                )}
                                                <span className={`font-semibold ${ing.ingredient ? 'capitalize' : ''}`}>
                                                    {name}
                                                </span>
                                            </span>
                                        </li>
                                    );
                                })}
                            </ul>
                        ) : (
                            <p className="relative text-sm text-ink-2 italic">No ingredients listed for this dish.</p>
                        )}
                    </div>
                </aside>

                {/* Method */}
                <div className="lg:col-span-7 space-y-6 animate-slide-up animation-delay-200">
                    <div className="flex items-end justify-between gap-3">
                        <div className="flex items-center gap-3">
                            <span className="w-11 h-11 rounded-2xl bg-plum-soft text-plum-deep flex items-center justify-center">
                                <Utensils className="w-5 h-5" />
                            </span>
                            <div>
                                <h2 className="font-display text-2xl sm:text-3xl text-ink leading-none">Method</h2>
                                <p className="text-xs font-semibold text-ink-3 mt-1">Step-by-step, at your pace</p>
                            </div>
                        </div>
                        {steps.length > 0 && (
                            <span className="hidden sm:inline-flex rounded-full bg-plum text-white text-xs font-extrabold px-3 py-1.5">
                                {steps.length} {steps.length === 1 ? 'step' : 'steps'}
                            </span>
                        )}
                    </div>

                    {steps.length > 0 ? (
                        <ol className="relative space-y-4">
                            <div
                                className="absolute left-6 top-8 bottom-8 border-l-2 border-dashed border-plum/30"
                                aria-hidden="true"
                            />
                            {steps.map((step, idx) => (
                                <li
                                    key={idx}
                                    className="relative flex items-start gap-4 rounded-2xl bg-paper border border-line p-4 sm:p-5 shadow-sm hover:shadow-glow-plum hover:-translate-y-0.5 transition-all duration-200"
                                >
                                    <span className="w-12 h-12 rounded-full bg-plum text-white font-display font-extrabold text-xl flex items-center justify-center shrink-0 shadow-glow-plum">
                                        {idx + 1}
                                    </span>
                                    <p className="flex-1 pt-2.5 text-[15px] leading-relaxed text-ink-2">{step.trim()}</p>
                                </li>
                            ))}
                        </ol>
                    ) : (
                        <div className="rounded-3xl border-2 border-dashed border-line-strong bg-paper/60 p-8 text-center">
                            <p className="text-sm text-ink-2 italic">No preparation instructions provided.</p>
                        </div>
                    )}

                    {recipe.source_url && (
                        <div className="flex justify-end">
                            <a
                                href={recipe.source_url}
                                target="_blank"
                                rel="noreferrer"
                                className="inline-flex items-center gap-2 rounded-full bg-paper border-2 border-ink px-4 py-2 text-sm font-extrabold text-ink hover:bg-ink hover:text-white transition-colors"
                            >
                                Original recipe source
                                <ExternalLink className="w-4 h-4" />
                            </a>
                        </div>
                    )}
                </div>
            </section>

            {/* ============================================================ */}
            {/* Reviews                                                       */}
            {/* ============================================================ */}
            <section className="space-y-6 animate-slide-up animation-delay-300">
                <div className="flex items-center gap-3">
                    <span className="w-11 h-11 rounded-2xl bg-chili-soft text-chili-deep flex items-center justify-center">
                        <MessageSquare className="w-5 h-5" />
                    </span>
                    <div>
                        <h2 className="font-display text-2xl sm:text-3xl text-ink leading-none">Reviews</h2>
                        <p className="text-xs font-semibold text-ink-3 mt-1">
                            {reviewTotal} {reviewTotal === 1 ? 'review' : 'reviews'} from the community
                        </p>
                    </div>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                    {/* Rating summary */}
                    <div className="lg:col-span-4 lg:sticky lg:top-24 rounded-3xl bg-ink-gradient bg-dots-light text-white p-6 sm:p-8 space-y-6 overflow-hidden relative">
                        <div
                            className="absolute -top-10 -right-10 w-40 h-40 rounded-full bg-chili/30 blur-2xl"
                            aria-hidden="true"
                        />
                        <div className="relative flex items-end gap-3">
                            <span className="font-display font-extrabold text-6xl leading-none text-turmeric">
                                {ratingsAvg !== null ? Number(ratingsAvg).toFixed(1) : '—'}
                            </span>
                            <span className="pb-1.5 text-sm font-semibold text-white/70">
                                out of 5
                                <br />
                                {ratingsCount} {ratingsCount === 1 ? 'rating' : 'ratings'}
                            </span>
                        </div>

                        <ul className="relative space-y-2" aria-label="Rating distribution">
                            {distribution.map(({ stars, count }) => {
                                const pct = reviewTotal > 0 ? Math.round((count / reviewTotal) * 100) : 0;
                                return (
                                    <li key={stars} className="flex items-center gap-3 text-xs font-bold">
                                        <span className="w-4 text-right text-white/80">{stars}</span>
                                        <Star className="w-3.5 h-3.5 text-turmeric fill-turmeric shrink-0" aria-hidden="true" />
                                        <span className="flex-1 h-2.5 rounded-full bg-white/15 overflow-hidden">
                                            <span
                                                className="block h-full rounded-full bg-sunrise-gradient transition-all duration-500"
                                                style={{ width: `${pct}%` }}
                                            />
                                        </span>
                                        <span className="w-6 text-right text-white/70">{count}</span>
                                    </li>
                                );
                            })}
                        </ul>

                        <div className="relative space-y-3">
                            <button
                                type="button"
                                onClick={openRatingModal}
                                className={`inline-flex w-full items-center justify-center gap-2 rounded-full px-5 py-3 font-display font-extrabold text-base transition-all duration-200 cursor-pointer active:scale-[0.97] hover:-translate-y-0.5 ${
                                    userRatingObj
                                        ? 'bg-white text-ink hover:bg-turmeric'
                                        : 'bg-saffron text-ink shadow-glow-saffron hover:bg-turmeric'
                                }`}
                            >
                                {userRatingObj ? <Edit3 className="w-4 h-4" /> : <Star className="w-4 h-4 fill-current" />}
                                {userRatingObj ? 'Edit my rating' : 'Rate this recipe'}
                            </button>
                            {!token && (
                                <p className="text-[11px] text-white/60 text-center font-medium">
                                    You&rsquo;ll be asked to sign in first.
                                </p>
                            )}
                        </div>

                        {/* Bayesian explainer */}
                        <details className="relative group rounded-2xl bg-white/8 border border-white/10 open:bg-white/12 transition-colors">
                            <summary className="flex items-center gap-2 cursor-pointer list-none px-4 py-3 text-xs font-bold text-white/85 select-none [&::-webkit-details-marker]:hidden">
                                <Sparkles className="w-4 h-4 text-turmeric shrink-0" />
                                <span className="flex-1">How the community score works</span>
                                <span className="text-white/50 group-open:rotate-45 transition-transform text-base leading-none">+</span>
                            </summary>
                            <div className="px-4 pb-4 space-y-2">
                                <p className="font-mono text-[11px] text-turmeric bg-ink/60 rounded-xl px-3 py-2">
                                    W = (v / (v + m)) &times; R + (m / (v + m)) &times; C
                                </p>
                                <p className="text-[11px] leading-relaxed text-white/70">
                                    <strong className="text-white">v</strong> is this recipe&rsquo;s review count,{' '}
                                    <strong className="text-white">m</strong> a confidence threshold,{' '}
                                    <strong className="text-white">R</strong> its average rating and{' '}
                                    <strong className="text-white">C</strong> the global community mean. A single
                                    glowing review can&rsquo;t leapfrog well-loved dishes.
                                </p>
                            </div>
                        </details>
                    </div>

                    {/* Review cards */}
                    <div className="lg:col-span-8">
                        {recipe.ratings && recipe.ratings.length > 0 ? (
                            <ul className="grid grid-cols-1 md:grid-cols-2 gap-5">
                                {recipe.ratings.map((rating, idx) => {
                                    const isMine = !!user && rating.user?.id === user.id;
                                    const tone = AVATAR_TONES[idx % AVATAR_TONES.length];
                                    return (
                                        <li
                                            key={rating.id}
                                            className={`relative rounded-3xl p-5 sm:p-6 space-y-4 transition-all duration-200 hover:-translate-y-0.5 after:content-[''] after:absolute after:-bottom-2 after:left-8 after:w-4 after:h-4 after:rotate-45 ${
                                                isMine
                                                    ? 'bg-saffron-soft border-2 border-saffron shadow-glow-saffron after:bg-saffron-soft after:border-saffron after:border-b-2 after:border-r-2'
                                                    : 'bg-paper border border-line shadow-sm hover:shadow-md after:bg-paper after:border-line after:border-b after:border-r'
                                            }`}
                                        >
                                            {isMine && (
                                                <span className="sticker absolute -top-3 right-5 rounded-full bg-saffron text-ink text-[10px] font-extrabold uppercase tracking-wider px-2.5 py-1 pop-sm">
                                                    Your review
                                                </span>
                                            )}
                                            <div className="flex items-start justify-between gap-3">
                                                <div className="flex items-center gap-3 min-w-0">
                                                    <span
                                                        className={`w-10 h-10 rounded-2xl ${tone} font-display font-extrabold text-sm flex items-center justify-center shrink-0`}
                                                    >
                                                        {initialsOf(rating.user?.name)}
                                                    </span>
                                                    <div className="min-w-0">
                                                        <p className="text-sm font-extrabold text-ink truncate">
                                                            {rating.user?.name || 'Anonymous Cook'}
                                                        </p>
                                                        <p className="text-[11px] font-semibold text-ink-3">
                                                            {rating.created_at
                                                                ? new Date(rating.created_at).toLocaleDateString(undefined, {
                                                                      year: 'numeric',
                                                                      month: 'short',
                                                                      day: 'numeric',
                                                                  })
                                                                : 'Verified Cook'}
                                                        </p>
                                                    </div>
                                                </div>
                                                <StarRating score={rating.stars} size="sm" />
                                            </div>

                                            {rating.review ? (
                                                <div className="relative pl-6">
                                                    <Quote
                                                        className="absolute left-0 top-0.5 w-4 h-4 text-chili fill-chili/20"
                                                        aria-hidden="true"
                                                    />
                                                    <p className="text-sm leading-relaxed text-ink-2">{rating.review}</p>
                                                </div>
                                            ) : (
                                                <p className="text-xs italic text-ink-3">Rated without a written review.</p>
                                            )}
                                        </li>
                                    );
                                })}
                            </ul>
                        ) : (
                            <div className="relative rounded-3xl border-2 border-dashed border-line-strong bg-paper/60 p-8 sm:p-12 text-center space-y-4 overflow-hidden">
                                <div className="absolute -bottom-10 -left-10 w-36 h-36 bg-chili-soft blob-2" aria-hidden="true" />
                                <div className="relative mx-auto w-16 h-16 rounded-3xl bg-chili-soft text-chili-deep flex items-center justify-center animate-float">
                                    <MessageSquare className="w-7 h-7" />
                                </div>
                                <div className="relative space-y-1">
                                    <h3 className="font-display text-xl text-ink">No reviews yet</h3>
                                    <p className="text-sm text-ink-2 max-w-sm mx-auto">
                                        Be the first cook to review this dish and shape its community score.
                                    </p>
                                </div>
                                <div className="relative">
                                    <Button variant="primary" size="md" onClick={openRatingModal} className="rounded-full">
                                        <Star className="w-4 h-4 fill-current" />
                                        Write the first review
                                    </Button>
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </section>

            {/* Rating Modal */}
            <RatingReviewModal
                isOpen={isRatingModalOpen}
                onClose={() => setIsRatingModalOpen(false)}
                recipeId={recipe.id}
                recipeTitle={recipe.title}
                currentRating={userRatingObj?.stars}
                currentReview={userRatingObj?.review}
            />
        </div>
    );
};
