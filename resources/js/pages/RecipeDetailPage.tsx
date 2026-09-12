import React, { useEffect, useState } from 'react';
import { Link, useLocation, useNavigate, useParams } from 'react-router-dom';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { CalendarPlus, Check, Edit3, ExternalLink, Globe, ListChecks, MessageSquare, Plus, Quote, SearchX, Sparkles, Star, Trash2, Users, Utensils, type LucideIcon } from 'lucide-react';
import { recipesApi } from '../api/recipes';
import { mealPlanApi } from '../api/mealPlan';
import { useAuth } from '../context/AuthContext';
import { Button, ButtonLink } from '../components/ui/Button';
import { Breadcrumb } from '../components/ui/Breadcrumb';
import { QuantityStepper } from '../components/ui/QuantityStepper';
import { StarRating } from '../components/ui/StarRating';
import { Avatar } from '../components/ui/Avatar';
import { Photo } from '../components/ui/Photo';
import { StatusPanel } from '../components/common/StatusPanel';
import { Reveal } from '../components/motion/Reveal';
import { RecipeFallback } from '../features/recipes/RecipeCard';
import { RatingReviewModal } from '../features/ratings/RatingReviewModal';
import { SaveButton } from '../features/saves/SaveButton';

const formatQuantity = (quantity: number | null | undefined): string => {
    if (quantity === null || quantity === undefined) return '';
    const n = Number(quantity);
    return Number.isNaN(n) ? String(quantity) : String(Math.round(n * 100) / 100);
};

/** Seeded and pasted methods often number their own lines; the list already does that. */
const stripStepNumber = (step: string): string => step.trim().replace(/^(step\s*)?\d+\s*[.):-]\s*/i, '');

const formatDate = (iso?: string): string =>
    iso ? new Date(iso).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' }) : '';

const Meta: React.FC<{ icon: LucideIcon; tone: string; children: React.ReactNode }> = ({ icon: Icon, tone, children }) => (
    <span className="inline-flex items-center gap-2 rounded-full border border-line bg-surface px-3.5 py-2 text-sm font-medium text-ink-2">
        <Icon className={`size-4 ${tone}`} aria-hidden="true" />
        {children}
    </span>
);

const DetailSkeleton: React.FC = () => (
    <div className="mx-auto w-full max-w-7xl animate-fade-in px-4 py-6 sm:px-6 lg:px-8 lg:py-10" aria-busy="true" aria-live="polite">
        <p className="sr-only">Loading the recipe…</p>
        <div className="skeleton-shimmer h-5 w-64 max-w-full rounded-full" />
        <div className="mt-8 grid grid-cols-1 gap-8 lg:grid-cols-12 lg:gap-12">
            <div className="skeleton-shimmer aspect-4/3 rounded-[2rem] lg:col-span-6" />
            <div className="space-y-4 lg:col-span-6">
                <div className="skeleton-shimmer h-4 w-20 rounded-full" />
                <div className="skeleton-shimmer h-12 w-5/6 rounded-2xl" />
                <div className="skeleton-shimmer h-12 w-2/3 rounded-2xl" />
                <div className="skeleton-shimmer h-10 w-48 rounded-full" />
                <div className="grid grid-cols-2 gap-4 pt-4">
                    <div className="skeleton-shimmer h-40 rounded-3xl" />
                    <div className="skeleton-shimmer h-40 rounded-3xl" />
                </div>
            </div>
        </div>
    </div>
);

export const RecipeDetailPage: React.FC = () => {
    const { slug } = useParams<{ slug: string }>();
    const navigate = useNavigate();
    const location = useLocation();
    const queryClient = useQueryClient();
    const { user, token } = useAuth();

    const [isRatingModalOpen, setIsRatingModalOpen] = useState(false);
    const [selectedServings, setSelectedServings] = useState(4);
    const [justAddedToPlan, setJustAddedToPlan] = useState(false);

    const { data, isLoading, error } = useQuery({
        queryKey: ['recipe', slug],
        queryFn: () => recipesApi.get(slug!),
        enabled: !!slug,
    });
    const recipe = data?.data;

    useEffect(() => {
        if (recipe?.servings) setSelectedServings(recipe.servings);
    }, [recipe?.servings]);

    const addToPlanMutation = useMutation({
        mutationFn: () => mealPlanApi.addItem(recipe!.id, selectedServings),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['mealPlan'] });
            setJustAddedToPlan(true);
            setTimeout(() => setJustAddedToPlan(false), 2000);
        },
    });

    const deleteRecipeMutation = useMutation({
        mutationFn: () => recipesApi.delete(recipe!.id),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['recipes'] });
            navigate('/recipes');
        },
    });

    const requireSignIn = (): boolean => {
        if (token) return false;
        navigate('/login', { state: { from: location } });
        return true;
    };

    if (isLoading) return <DetailSkeleton />;

    if (error || !recipe) {
        return (
            <StatusPanel
                icon={SearchX}
                title="Recipe not found"
                text="It may have been removed or renamed. Every other dish is still where you left it."
                action={{ label: 'Browse recipes', to: '/recipes' }}
            />
        );
    }

    const isAuthor = !!user && !!recipe.user_id && user.id === recipe.user_id;
    const myRating = user ? recipe.ratings?.find((r) => r.user?.id === user.id) : undefined;
    const ratingsCount = recipe.stat?.ratings_count ?? recipe.ratings?.length ?? 0;
    const ratingsAvg = recipe.stat?.ratings_avg ?? null;
    const bayesian = recipe.stat?.bayesian_score ?? null;
    const headlineScore = bayesian ?? ratingsAvg;
    const steps = (recipe.instructions ?? '').split(/\r?\n/).filter((step) => step.trim().length > 0);
    const reviews = recipe.ratings ?? [];
    const distribution = [5, 4, 3, 2, 1].map((stars) => ({ stars, count: reviews.filter((r) => r.stars === stars).length }));

    return (
        <div className="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 lg:px-8 lg:py-10">
            <div className="flex animate-fade-in flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <Breadcrumb
                    items={[
                        { label: 'Recipes', to: '/recipes' },
                        ...(recipe.cuisine ? [{ label: recipe.cuisine, to: `/recipes?cuisine=${encodeURIComponent(recipe.cuisine)}` }] : []),
                        { label: recipe.title },
                    ]}
                />
                {isAuthor && (
                    <div className="flex shrink-0 items-center gap-2">
                        <ButtonLink to={`/recipes/${recipe.slug}/edit`} variant="outline" size="sm">
                            <Edit3 className="size-3.5" aria-hidden="true" /> Edit
                        </ButtonLink>
                        <Button
                            variant="danger"
                            size="sm"
                            isLoading={deleteRecipeMutation.isPending}
                            onClick={() => {
                                if (window.confirm('Delete this recipe? This cannot be undone.')) deleteRecipeMutation.mutate();
                            }}
                        >
                            <Trash2 className="size-3.5" aria-hidden="true" /> Delete
                        </Button>
                    </div>
                )}
            </div>

            {/* Hero: photo beside the title, score and planner */}
            <section className="mt-6 grid grid-cols-1 gap-8 lg:mt-10 lg:grid-cols-12 lg:items-center lg:gap-12">
                <div className="animate-scale-in lg:col-span-6">
                    <div className="relative aspect-4/3 overflow-hidden rounded-[2rem] border border-line bg-surface-2">
                        <Photo src={recipe.image_url} alt={recipe.title} className="h-full w-full object-cover" fallback={<RecipeFallback recipe={recipe} />} />
                        <div className="img-fade pointer-events-none absolute inset-0" />
                        <div className="absolute top-4 left-4 flex flex-wrap gap-2 sm:top-5 sm:left-5">
                            {recipe.cuisine && (
                                <span className="rounded-full bg-canvas/60 px-3 py-1 text-xs font-semibold text-ink backdrop-blur">{recipe.cuisine}</span>
                            )}
                            {recipe.category && (
                                <span className="rounded-full bg-canvas/60 px-3 py-1 text-xs font-semibold text-turmeric backdrop-blur">{recipe.category}</span>
                            )}
                        </div>
                        <div className="absolute bottom-4 left-4 inline-flex items-center gap-2 rounded-full bg-canvas/60 py-1.5 pr-3.5 pl-1.5 backdrop-blur sm:bottom-5 sm:left-5">
                            <span className="inline-flex size-7 items-center justify-center rounded-full bg-turmeric text-on-primary">
                                <Star className="size-3.5 fill-current" aria-hidden="true" />
                            </span>
                            <span className="font-display text-base leading-none font-semibold text-ink">{ratingsAvg !== null ? Number(ratingsAvg).toFixed(1) : 'New'}</span>
                            <span className="text-xs leading-none text-ink-2">
                                {ratingsCount} {ratingsCount === 1 ? 'rating' : 'ratings'}
                            </span>
                        </div>
                    </div>
                </div>

                <div className="animate-slide-up space-y-6 animation-delay-100 lg:col-span-6">
                    <div>
                        <p className="text-xs font-semibold tracking-[0.22em] text-primary uppercase">Recipe</p>
                        <h1 className="mt-3 font-display text-4xl leading-[1.02] font-semibold tracking-tight text-ink text-balance sm:text-5xl lg:text-6xl">
                            {recipe.title}
                        </h1>
                        <div className="mt-5 inline-flex items-center gap-3 rounded-full border border-line bg-surface py-1.5 pr-4 pl-1.5">
                            {recipe.source === 'user' && recipe.author ? (
                                <Avatar name={recipe.author.name} size="md" />
                            ) : (
                                <span className="inline-flex size-9 items-center justify-center rounded-full bg-plum-soft text-plum">
                                    <Globe className="size-4" aria-hidden="true" />
                                </span>
                            )}
                            <span className="flex flex-col leading-tight">
                                <span className="text-[10px] font-semibold tracking-[0.14em] text-ink-3 uppercase">
                                    {recipe.source === 'user' && recipe.author ? 'Posted by' : 'From'}
                                </span>
                                {recipe.source === 'user' && recipe.author ? (
                                    <Link to={`/cooks/${recipe.author.id}`} className="text-sm font-semibold text-ink transition-colors hover:text-primary">
                                        {recipe.author.name}
                                    </Link>
                                ) : (
                                    <span className="text-sm font-semibold text-ink">TheMealDB collection</span>
                                )}
                            </span>
                        </div>
                    </div>

                    <div className="flex flex-wrap gap-2">
                        <Meta icon={Users} tone="text-mint">
                            {recipe.servings} servings
                        </Meta>
                        <Meta icon={ListChecks} tone="text-turmeric">
                            {recipe.ingredients?.length ?? 0} ingredients
                        </Meta>
                        <Meta icon={Utensils} tone="text-plum">
                            {steps.length} {steps.length === 1 ? 'step' : 'steps'}
                        </Meta>
                        {recipe.source_url && (
                            <a
                                href={recipe.source_url}
                                target="_blank"
                                rel="noreferrer"
                                className="inline-flex items-center gap-2 rounded-full border border-line bg-surface px-3.5 py-2 text-sm font-medium text-ink-2 transition-colors hover:border-primary/60 hover:text-primary"
                            >
                                <ExternalLink className="size-4" aria-hidden="true" />
                                Original source
                            </a>
                        )}
                    </div>

                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div className="rounded-3xl border border-line bg-surface p-5 sm:p-6">
                            <p className="inline-flex items-center gap-2 text-xs font-semibold tracking-[0.14em] text-mint uppercase">
                                <Sparkles className="size-4" aria-hidden="true" />
                                {bayesian !== null ? 'Community score' : 'Average rating'}
                            </p>
                            <p className="mt-4 font-display text-5xl leading-none font-semibold text-ink tabular-nums">
                                {headlineScore !== null ? Number(headlineScore).toFixed(bayesian !== null ? 2 : 1) : '—'}
                            </p>
                            <div className="mt-3">
                                <StarRating score={ratingsAvg} count={ratingsCount} />
                            </div>
                            {bayesian !== null && <p className="mt-3 text-xs text-ink-3">Bayesian-weighted, so one review can’t swing it.</p>}
                        </div>

                        <div className="rounded-3xl border border-line bg-surface-2 p-5 sm:p-6">
                            <p className="inline-flex items-center gap-2 text-xs font-semibold tracking-[0.14em] text-primary uppercase">
                                <CalendarPlus className="size-4" aria-hidden="true" />
                                Plan it
                            </p>
                            <div className="mt-4 flex items-center justify-between gap-3">
                                <span className="text-sm text-ink-2">Servings</span>
                                <QuantityStepper value={selectedServings} onChange={setSelectedServings} min={1} max={50} size="md" ariaLabel="Servings" />
                            </div>
                            <Button
                                onClick={() => {
                                    if (!requireSignIn()) addToPlanMutation.mutate();
                                }}
                                isLoading={addToPlanMutation.isPending}
                                variant={justAddedToPlan ? 'secondary' : 'primary'}
                                className="mt-4 w-full"
                            >
                                {justAddedToPlan ? <Check className="size-4 stroke-[3]" aria-hidden="true" /> : <Plus className="size-4 stroke-[3]" aria-hidden="true" />}
                                {justAddedToPlan ? 'Added to your plan' : 'Add to meal plan'}
                            </Button>
                            {addToPlanMutation.isError && <p className="mt-2 text-xs text-hot">Couldn’t add this dish right now. Please try again.</p>}
                            <div className="mt-3">
                                <SaveButton recipeId={recipe.id} recipeTitle={recipe.title} variant="inline" className="w-full justify-center" />
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {/* Ingredients beside the method */}
            <section className="mt-14 grid grid-cols-1 gap-8 lg:mt-20 lg:grid-cols-12 lg:items-start lg:gap-10">
                <Reveal as="aside" className="lg:sticky lg:top-24 lg:col-span-5">
                    <div className="rounded-3xl border border-line bg-surface p-6 sm:p-8">
                        <div className="flex items-center justify-between gap-3">
                            <h2 className="font-display text-2xl font-semibold text-ink">Ingredients</h2>
                            <span className="rounded-full bg-surface-2 px-3 py-1 text-xs font-semibold text-ink-2">{recipe.ingredients?.length ?? 0} items</span>
                        </div>
                        {recipe.ingredients && recipe.ingredients.length > 0 ? (
                            <ul className="mt-4 divide-y divide-line">
                                {recipe.ingredients.map((ing) => {
                                    const hasMeasure = (ing.quantity !== null && ing.quantity !== undefined) || !!ing.unit;
                                    return (
                                        <li key={ing.id} className="flex items-baseline gap-3 py-2.5">
                                            {hasMeasure && (
                                                <span className="w-20 shrink-0 font-display text-base font-semibold text-turmeric tabular-nums">
                                                    {formatQuantity(ing.quantity)} {ing.unit && <span className="text-xs font-medium text-ink-3">{ing.unit}</span>}
                                                </span>
                                            )}
                                            <span className={`text-sm text-ink ${ing.ingredient ? 'capitalize' : ''}`}>{ing.ingredient?.canonical_name || ing.raw_text}</span>
                                        </li>
                                    );
                                })}
                            </ul>
                        ) : (
                            <p className="mt-4 text-sm text-ink-3">No ingredients listed for this dish.</p>
                        )}
                    </div>
                </Reveal>

                <Reveal className="lg:col-span-7">
                    <div className="flex items-end justify-between gap-3">
                        <div>
                            <h2 className="font-display text-2xl font-semibold text-ink sm:text-3xl">Method</h2>
                            <p className="mt-1 text-sm text-ink-3">Step by step, at your pace</p>
                        </div>
                        {steps.length > 0 && (
                            <span className="rounded-full bg-surface-2 px-3 py-1 text-xs font-semibold text-ink-2">
                                {steps.length} {steps.length === 1 ? 'step' : 'steps'}
                            </span>
                        )}
                    </div>
                    {steps.length > 0 ? (
                        <ol className="mt-6 space-y-3">
                            {steps.map((step, index) => (
                                <li key={index} className="flex gap-4 rounded-3xl border border-line bg-surface p-4 sm:p-5">
                                    <span className="inline-flex size-10 shrink-0 items-center justify-center rounded-full bg-primary-soft font-display text-lg font-semibold text-primary">
                                        {index + 1}
                                    </span>
                                    <p className="pt-1.5 text-[15px] leading-relaxed text-ink-2">{stripStepNumber(step)}</p>
                                </li>
                            ))}
                        </ol>
                    ) : (
                        <p className="mt-6 rounded-3xl border border-dashed border-line-strong bg-surface p-8 text-center text-sm text-ink-3">No method written for this dish yet.</p>
                    )}
                </Reveal>
            </section>

            {/* Reviews */}
            <Reveal as="section" className="mt-14 lg:mt-20" aria-labelledby="reviews-heading">
                <h2 id="reviews-heading" className="font-display text-2xl font-semibold text-ink sm:text-3xl">
                    Reviews
                </h2>
                <p className="mt-1 text-sm text-ink-3">
                    {reviews.length} {reviews.length === 1 ? 'review' : 'reviews'} from the community
                </p>

                <div className="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-12 lg:items-start">
                    <div className="rounded-3xl border border-line bg-surface-2 p-6 sm:p-8 lg:sticky lg:top-24 lg:col-span-4">
                        <div className="flex items-end gap-3">
                            <span className="font-display text-6xl leading-none font-semibold text-turmeric tabular-nums">
                                {ratingsAvg !== null ? Number(ratingsAvg).toFixed(1) : '—'}
                            </span>
                            <span className="pb-1 text-sm leading-snug text-ink-3">
                                out of 5
                                <br />
                                {ratingsCount} {ratingsCount === 1 ? 'rating' : 'ratings'}
                            </span>
                        </div>
                        <ul className="mt-6 space-y-2" aria-label="Rating distribution">
                            {distribution.map(({ stars, count }) => (
                                <li key={stars} className="flex items-center gap-3 text-xs text-ink-2">
                                    <span className="w-3 text-right">{stars}</span>
                                    <Star className="size-3.5 shrink-0 fill-turmeric text-turmeric" aria-hidden="true" />
                                    <span className="h-2 flex-1 overflow-hidden rounded-full bg-surface-3">
                                        <span
                                            className="block h-full rounded-full bg-primary transition-[width] duration-500"
                                            style={{ width: `${reviews.length > 0 ? Math.round((count / reviews.length) * 100) : 0}%` }}
                                        />
                                    </span>
                                    <span className="w-5 text-right text-ink-3">{count}</span>
                                </li>
                            ))}
                        </ul>
                        <Button
                            onClick={() => {
                                if (!requireSignIn()) setIsRatingModalOpen(true);
                            }}
                            variant={myRating ? 'secondary' : 'primary'}
                            className="mt-6 w-full"
                        >
                            {myRating ? <Edit3 className="size-4" aria-hidden="true" /> : <Star className="size-4 fill-current" aria-hidden="true" />}
                            {myRating ? 'Edit my rating' : 'Rate this recipe'}
                        </Button>
                        {!token && <p className="mt-2 text-center text-xs text-ink-3">You’ll be asked to sign in first.</p>}

                        <details className="group mt-6 rounded-2xl border border-line bg-surface">
                            <summary className="flex cursor-pointer list-none items-center gap-2 px-4 py-3 text-xs font-semibold text-ink-2 select-none [&::-webkit-details-marker]:hidden">
                                <Sparkles className="size-4 shrink-0 text-turmeric" aria-hidden="true" />
                                <span className="flex-1">How the community score works</span>
                                <span className="text-base leading-none text-ink-3 transition-transform group-open:rotate-45" aria-hidden="true">
                                    +
                                </span>
                            </summary>
                            <div className="space-y-2 px-4 pb-4">
                                <p className="rounded-xl bg-canvas px-3 py-2 font-mono text-[11px] text-turmeric">W = (v / (v + m)) × R + (m / (v + m)) × C</p>
                                <p className="text-[11px] leading-relaxed text-ink-3">
                                    <strong className="text-ink-2">v</strong> is this recipe’s review count, <strong className="text-ink-2">m</strong> a confidence threshold,{' '}
                                    <strong className="text-ink-2">R</strong> its average rating and <strong className="text-ink-2">C</strong> the community mean. A single glowing
                                    review can’t leapfrog well-loved dishes.
                                </p>
                            </div>
                        </details>
                    </div>

                    <div className="lg:col-span-8">
                        {reviews.length > 0 ? (
                            <ul className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                {reviews.map((rating) => {
                                    const isMine = !!user && rating.user?.id === user.id;
                                    return (
                                        <li key={rating.id} className={`rounded-3xl border p-5 sm:p-6 ${isMine ? 'border-primary/50 bg-primary-soft/40' : 'border-line bg-surface'}`}>
                                            <div className="flex items-start justify-between gap-3">
                                                <div className="flex min-w-0 items-center gap-3">
                                                    <Avatar name={rating.user?.name ?? 'Anonymous cook'} size="md" />
                                                    <div className="min-w-0">
                                                        {rating.user ? (
                                                            <Link to={`/cooks/${rating.user.id}`} className="block truncate text-sm font-semibold text-ink transition-colors hover:text-primary">
                                                                {rating.user.name}
                                                            </Link>
                                                        ) : (
                                                            <p className="truncate text-sm font-semibold text-ink">Anonymous cook</p>
                                                        )}
                                                        <p className="text-xs text-ink-3">{formatDate(rating.created_at) || 'Verified cook'}</p>
                                                    </div>
                                                </div>
                                                <div className="flex shrink-0 flex-col items-end gap-1.5">
                                                    <StarRating score={rating.stars} size="sm" />
                                                    {isMine && (
                                                        <span className="rounded-full bg-primary px-2 py-0.5 text-[10px] font-semibold tracking-wider text-on-primary uppercase">Your review</span>
                                                    )}
                                                </div>
                                            </div>
                                            {rating.review ? (
                                                <div className="relative mt-4 pl-6">
                                                    <Quote className="absolute top-0.5 left-0 size-4 text-primary" aria-hidden="true" />
                                                    <p className="text-sm leading-relaxed text-ink-2">{rating.review}</p>
                                                </div>
                                            ) : (
                                                <p className="mt-4 text-xs text-ink-3">Rated without a written review.</p>
                                            )}
                                        </li>
                                    );
                                })}
                            </ul>
                        ) : (
                            <div className="rounded-3xl border border-dashed border-line-strong bg-surface p-8 text-center sm:p-12">
                                <span className="mx-auto mb-4 inline-flex size-14 items-center justify-center rounded-full bg-surface-2 text-ink-2">
                                    <MessageSquare className="size-6" aria-hidden="true" />
                                </span>
                                <h3 className="font-display text-xl font-semibold text-ink">No reviews yet</h3>
                                <p className="mx-auto mt-1 max-w-sm text-sm text-ink-2">Be the first to cook it and say how it went.</p>
                            </div>
                        )}
                    </div>
                </div>
            </Reveal>

            <RatingReviewModal
                isOpen={isRatingModalOpen}
                onClose={() => setIsRatingModalOpen(false)}
                recipeId={recipe.id}
                recipeTitle={recipe.title}
                currentRating={myRating?.stars}
                currentReview={myRating?.review}
            />
        </div>
    );
};
