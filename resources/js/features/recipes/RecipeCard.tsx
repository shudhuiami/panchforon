import React, { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { Users, Plus, Check, Sparkles, ChefHat, Loader2 } from 'lucide-react';
import { RecipeList } from '../../types/api';
import { StarRating } from '../../components/ui/StarRating';
import { Badge } from '../../components/ui/Badge';
import { useAuth } from '../../context/AuthContext';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { mealPlanApi } from '../../api/mealPlan';

export interface RecipeCardProps {
    recipe: RecipeList;
    className?: string;
}

/** Colourful gradient fallbacks, cycled by recipe id so the grid stays lively without images. */
const FALLBACK_GRADIENTS = [
    'bg-sunrise-gradient',
    'bg-plum-gradient',
    'bg-mint-gradient',
    'bg-spice-gradient',
    'bg-ink-gradient',
];

export const RecipeCard: React.FC<RecipeCardProps> = ({ recipe, className = '' }) => {
    const { token } = useAuth();
    const navigate = useNavigate();
    const queryClient = useQueryClient();
    const [justAdded, setJustAdded] = useState(false);
    const [imgError, setImgError] = useState(false);

    const addMutation = useMutation({
        mutationFn: () => mealPlanApi.addItem(recipe.id, recipe.servings || 4),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['mealPlan'] });
            setJustAdded(true);
            setTimeout(() => setJustAdded(false), 2000);
        },
    });

    const handleAddToPlan = (e: React.MouseEvent) => {
        e.preventDefault();
        e.stopPropagation();

        if (!token) {
            navigate('/login');
            return;
        }

        addMutation.mutate();
    };

    const fallbackGradient = FALLBACK_GRADIENTS[recipe.id % FALLBACK_GRADIENTS.length];
    const hasImage = !!recipe.image_url && !imgError;

    return (
        <article
            className={`group relative bg-paper rounded-3xl border border-line overflow-hidden flex flex-col transition-all duration-300 ease-out hover:-translate-y-2 hover:shadow-lg hover:border-line-strong ${className}`}
        >
            {/* Image hero: covers the top ~60% of the card */}
            <Link
                to={`/recipes/${recipe.slug}`}
                className="relative block aspect-4/5 sm:aspect-5/6 overflow-hidden focus-visible:outline-3 focus-visible:outline-saffron focus-visible:-outline-offset-3"
                aria-label={recipe.title}
            >
                {hasImage ? (
                    <img
                        src={recipe.image_url ?? undefined}
                        alt={recipe.title}
                        onError={() => setImgError(true)}
                        loading="lazy"
                        className="w-full h-full object-cover transition-transform duration-700 ease-out group-hover:scale-110"
                    />
                ) : (
                    <div
                        className={`w-full h-full ${fallbackGradient} bg-dots-light flex items-center justify-center transition-transform duration-700 ease-out group-hover:scale-110`}
                    >
                        <div className="w-24 h-24 rounded-full bg-white/25 backdrop-blur-sm flex items-center justify-center text-white shadow-lg">
                            <ChefHat className="w-12 h-12 drop-shadow" strokeWidth={1.75} />
                        </div>
                    </div>
                )}

                {/* Ink fade so the title is legible over any photo */}
                <div className="img-fade absolute inset-0 pointer-events-none" />

                {/* Top-left: cuisine sticker */}
                {recipe.cuisine && (
                    <div className="absolute top-4 left-4 sticker">
                        <Badge variant="cuisine" size="sm" className="shadow-md">
                            {recipe.cuisine}
                        </Badge>
                    </div>
                )}

                {/* Top-right: Bayesian score pill */}
                {recipe.bayesian_score !== null && recipe.bayesian_score > 0 && (
                    <span className="absolute top-4 right-4 inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-display font-extrabold bg-turmeric text-ink shadow-glow-turmeric">
                        <Sparkles className="w-3 h-3 fill-current" />
                        {recipe.bayesian_score.toFixed(1)}
                    </span>
                )}

                {/* Title over the image bottom */}
                <div className="absolute inset-x-0 bottom-0 p-5 pr-20">
                    {recipe.category && (
                        <span className="inline-block mb-1.5 text-[11px] font-extrabold uppercase tracking-[0.14em] text-turmeric">
                            {recipe.category}
                        </span>
                    )}
                    <h3 className="font-display font-extrabold text-white text-xl leading-tight line-clamp-2 drop-shadow-md">
                        {recipe.title}
                    </h3>
                </div>
            </Link>

            {/* Footer: rating + servings, with the round "add to plan" button riding the image edge */}
            <div className="relative px-5 pt-4 pb-5 flex items-center justify-between gap-3 bg-paper">
                <button
                    type="button"
                    onClick={handleAddToPlan}
                    disabled={addMutation.isPending}
                    title={token ? 'Add to Meal Plan' : 'Login to plan this recipe'}
                    aria-label={justAdded ? 'Added to weekly plan' : `Add ${recipe.title} to meal plan`}
                    className={`absolute -top-6 right-4 z-10 w-12 h-12 rounded-full flex items-center justify-center cursor-pointer transition-all duration-200 active:scale-90 disabled:cursor-wait focus-visible:outline-3 focus-visible:outline-saffron focus-visible:outline-offset-2 ${
                        justAdded
                            ? 'bg-mint text-white shadow-glow-mint scale-105'
                            : 'bg-white text-ink shadow-glow-saffron hover:bg-saffron hover:scale-110'
                    }`}
                >
                    {justAdded ? (
                        <Check className="w-5 h-5 stroke-[3]" />
                    ) : addMutation.isPending ? (
                        <Loader2 className="w-5 h-5 animate-spin" />
                    ) : (
                        <Plus className="w-5 h-5 stroke-[2.75]" />
                    )}
                </button>

                <div className="flex items-center gap-1.5 min-w-0">
                    <StarRating score={recipe.ratings_avg} count={recipe.ratings_count} size="sm" />
                </div>

                <span className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-mint-soft text-mint-deep text-xs font-extrabold whitespace-nowrap">
                    <Users className="w-3.5 h-3.5" />
                    {recipe.servings}
                    <span className="sr-only sm:not-sr-only"> {recipe.servings === 1 ? 'serving' : 'servings'}</span>
                </span>
            </div>
        </article>
    );
};
