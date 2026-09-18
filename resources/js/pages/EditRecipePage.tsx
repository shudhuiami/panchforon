import React, { useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Eye, SearchX, ShieldAlert } from 'lucide-react';
import { recipesApi } from '../api/recipes';
import { RecipeForm, RecipeFormData, SaveIntent, toRecipePayload } from '../features/recipes/RecipeForm';
import { useAuth } from '../context/AuthContext';
import { useSiteSettings } from '../features/site/useSiteSettings';
import { LoadingSpinner } from '../components/common/LoadingSpinner';
import { StatusPanel } from '../components/common/StatusPanel';
import { Alert } from '../components/ui/Alert';
import { Breadcrumb } from '../components/ui/Breadcrumb';
import { ButtonLink } from '../components/ui/Button';

export const EditRecipePage: React.FC = () => {
    const { slug } = useParams<{ slug: string }>();
    const navigate = useNavigate();
    const queryClient = useQueryClient();
    const { user } = useAuth();
    const settings = useSiteSettings();
    const [submitError, setSubmitError] = useState<string | null>(null);

    // The public show endpoint resolves slugs and lets an author read their own
    // unpublished recipe, so no separate by-id lookup is needed.
    const { data, isLoading } = useQuery({
        queryKey: ['recipe', slug],
        queryFn: () => recipesApi.get(slug!),
        enabled: !!slug,
    });
    const recipe = data?.data;

    const isDraft = recipe?.moderation_status === 'draft';

    /**
     * The status only travels while the recipe is still a draft, so saving a
     * live recipe can never take it off the site.
     */
    const updateMutation = useMutation({
        mutationFn: ({ formData, intent }: { formData: RecipeFormData; intent: SaveIntent }) =>
            recipesApi.update(recipe!.id, {
                ...toRecipePayload(formData),
                ...(isDraft ? { status: intent === 'draft' ? ('draft' as const) : ('published' as const) } : {}),
            }),
        onSuccess: (res, { intent }) => {
            for (const key of ['recipe', 'recipes', 'cuisines', 'categories', 'home', 'my-recipes']) queryClient.invalidateQueries({ queryKey: [key] });
            navigate(isDraft && intent === 'draft' ? '/account/recipes' : `/recipes/${res.data.slug}`);
        },
        onError: (err: Error) => setSubmitError(err.message || 'The changes couldn’t be saved.'),
    });

    if (isLoading) return <LoadingSpinner message="Loading the recipe…" />;

    if (!recipe) {
        return <StatusPanel icon={SearchX} title="Recipe not found" text="We looked in every pot. This recipe doesn’t seem to exist." action={{ label: 'Browse recipes', to: '/recipes' }} />;
    }

    if (!user || user.id !== recipe.user_id) {
        return (
            <StatusPanel
                icon={ShieldAlert}
                tone="danger"
                title="Not your recipe"
                text="Only the cook who posted a recipe can edit it."
                action={{ label: 'View the recipe', to: `/recipes/${recipe.slug}` }}
            />
        );
    }

    /**
     * Editing a recipe you already own stays open to everyone, so a member who
     * posted before the role existed keeps their work. Putting one live is the
     * creator's half, and the API refuses it, so the form must not offer it.
     */
    const canPostRecipes = user.role === 'creator' || user.role === 'admin';

    return (
        <div className="mx-auto w-full max-w-7xl px-4 py-8 sm:px-6 lg:px-8 lg:py-12">
            <header className="mb-8 animate-slide-up">
                <Breadcrumb items={[{ label: 'Recipes', to: '/recipes' }, { label: recipe.title, to: `/recipes/${recipe.slug}` }, { label: 'Edit' }]} />
                <div className="mt-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div className="max-w-2xl">
                        <p className="text-xs font-semibold tracking-[0.22em] text-primary uppercase">{isDraft ? 'Draft' : 'Editing'}</p>
                        <h1 className="mt-3 font-display text-4xl font-semibold tracking-tight text-ink text-balance sm:text-5xl">{recipe.title}</h1>
                        <p className="mt-4 text-base text-ink-2 sm:text-lg">
                            {isDraft
                                ? 'Only you can see this one. Keep saving it as a draft, and publish it when it’s ready to go up.'
                                : 'Tweak the ingredients, tidy the steps or swap the photo. Ratings and meal-plan slots stay attached.'}
                        </p>
                    </div>
                    <ButtonLink to={`/recipes/${recipe.slug}`} variant="outline" size="sm" className="self-start sm:self-auto">
                        <Eye className="size-4" aria-hidden="true" />
                        View recipe
                    </ButtonLink>
                </div>
            </header>

            {isDraft && !canPostRecipes && (
                <Alert variant="info" title="Publishing is for creators" className="mb-6">
                    Your draft is safe here and yours to keep editing. Putting it on the site is a creator’s job —{' '}
                    <Link to="/become-a-creator" className="font-semibold text-primary underline-offset-2 hover:underline">
                        apply to become one
                    </Link>{' '}
                    and you can publish it yourself.
                </Alert>
            )}

            {isDraft && canPostRecipes && !settings.submissions_open && (
                <Alert variant="warning" title="Publishing is paused" className="mb-6">
                    New recipes aren’t being accepted right now. Your draft is safe here, and the publish button comes back when submissions reopen.
                </Alert>
            )}

            {submitError && (
                <Alert variant="error" onClose={() => setSubmitError(null)} className="mb-6">
                    {submitError}
                </Alert>
            )}

            <RecipeForm
                initialData={recipe}
                onSubmit={async (formData, intent) => {
                    setSubmitError(null);
                    await updateMutation.mutateAsync({ formData, intent });
                }}
                isSubmitting={updateMutation.isPending}
                onCancel={() => navigate(-1)}
                canPublish={canPostRecipes && settings.submissions_open}
            />
        </div>
    );
};
