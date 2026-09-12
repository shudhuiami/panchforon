import React, { useState } from 'react';
import { Link, useLocation, useNavigate } from 'react-router-dom';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { Check, Loader2, Plus, Sparkles, Users } from 'lucide-react';
import { RecipeList } from '../../types/api';
import { mealPlanApi } from '../../api/mealPlan';
import { useAuth } from '../../context/AuthContext';
import { StarRating } from '../../components/ui/StarRating';
import { Photo } from '../../components/ui/Photo';
import { SaveButton } from '../saves/SaveButton';

export const FALLBACK_GRADIENTS = ['bg-spice-gradient', 'bg-plum-gradient', 'bg-mint-gradient', 'bg-sunrise-gradient', 'bg-ink-gradient'];

/** Colourful stand-in for a missing photo: a gradient, the dot pattern and the dish's initial. */
export const RecipeFallback: React.FC<{ recipe: Pick<RecipeList, 'id' | 'title'>; className?: string }> = ({ recipe, className = '' }) => (
    <div className={`relative h-full w-full overflow-hidden ${FALLBACK_GRADIENTS[recipe.id % FALLBACK_GRADIENTS.length]} bg-dots-light ${className}`} aria-hidden="true">
        <span className="absolute -right-2 -bottom-8 font-display text-[10rem] leading-none font-medium text-canvas/30 italic select-none">
            {recipe.title.charAt(0)}
        </span>
    </div>
);

export interface RecipeCardProps {
    recipe: RecipeList;
    className?: string;
}

export const RecipeCard: React.FC<RecipeCardProps> = ({ recipe, className = '' }) => {
    const { token } = useAuth();
    const navigate = useNavigate();
    const location = useLocation();
    const queryClient = useQueryClient();
    const [justAdded, setJustAdded] = useState(false);

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
            navigate('/login', { state: { from: location } });
            return;
        }
        addMutation.mutate();
    };

    return (
        <article
            className={`group relative flex flex-col overflow-hidden rounded-3xl border border-line bg-surface transition-[transform,border-color,box-shadow] duration-500 ease-out hover:-translate-y-1.5 hover:border-line-strong hover:shadow-xl ${className}`}
        >
            <Link
                to={`/recipes/${recipe.slug}`}
                className="relative block aspect-4/5 overflow-hidden focus-visible:outline-3 focus-visible:-outline-offset-3 focus-visible:outline-primary"
                aria-label={recipe.title}
            >
                <Photo
                    src={recipe.image_url}
                    loading="lazy"
                    className="h-full w-full object-cover transition-transform duration-700 ease-out group-hover:scale-105"
                    fallback={<RecipeFallback recipe={recipe} className="transition-transform duration-700 ease-out group-hover:scale-105" />}
                />
                <div className="img-fade pointer-events-none absolute inset-0" />

                {recipe.cuisine && (
                    <span className="absolute top-3 left-3 rounded-full bg-canvas/60 px-2.5 py-1 text-[11px] font-semibold text-ink backdrop-blur sm:top-4 sm:left-4">
                        {recipe.cuisine}
                    </span>
                )}
                {recipe.bayesian_score !== null && recipe.bayesian_score > 0 && (
                    <span className="absolute top-3 right-3 inline-flex items-center gap-1 rounded-full bg-turmeric px-2.5 py-1 font-display text-xs font-semibold text-on-primary sm:top-4 sm:right-4">
                        <Sparkles className="size-3" aria-hidden="true" />
                        {recipe.bayesian_score.toFixed(1)}
                    </span>
                )}

                <span className="absolute top-13 right-3 sm:top-14 sm:right-4">
                    <SaveButton recipeId={recipe.id} recipeTitle={recipe.title} />
                </span>

                <div className="absolute inset-x-0 bottom-0 p-4 pr-14 sm:p-5 sm:pr-16">
                    {recipe.category && (
                        <span className="mb-1 block text-[11px] font-semibold tracking-[0.14em] text-turmeric uppercase">{recipe.category}</span>
                    )}
                    <h3 className="line-clamp-2 font-display text-lg leading-tight font-semibold text-ink sm:text-xl">{recipe.title}</h3>
                </div>
            </Link>

            <div className="relative flex items-center justify-between gap-3 px-4 pt-3.5 pr-16 pb-4 sm:px-5 sm:pr-[4.5rem]">
                <button
                    type="button"
                    onClick={handleAddToPlan}
                    disabled={addMutation.isPending}
                    title={token ? 'Add to meal plan' : 'Sign in to plan this recipe'}
                    aria-label={justAdded ? 'Added to meal plan' : `Add ${recipe.title} to meal plan`}
                    className={`absolute -top-5 right-4 z-10 inline-flex size-11 cursor-pointer items-center justify-center rounded-full transition-all duration-200 active:scale-90 disabled:cursor-wait focus-visible:outline-3 focus-visible:outline-primary focus-visible:outline-offset-2 sm:right-5 ${
                        justAdded ? 'bg-mint text-on-primary shadow-glow-mint' : 'bg-primary text-on-primary shadow-glow-primary hover:scale-110 hover:bg-primary-hover'
                    }`}
                >
                    {justAdded ? (
                        <Check className="size-5 stroke-[3]" />
                    ) : addMutation.isPending ? (
                        <Loader2 className="size-5 animate-spin" />
                    ) : (
                        <Plus className="size-5 stroke-[2.75]" />
                    )}
                </button>

                <StarRating score={recipe.ratings_avg} count={recipe.ratings_count} size="sm" />
                <span className="hidden items-center gap-1 text-xs text-ink-3 sm:inline-flex">
                    <Users className="size-3.5" aria-hidden="true" />
                    {recipe.servings}
                </span>
            </div>
        </article>
    );
};
