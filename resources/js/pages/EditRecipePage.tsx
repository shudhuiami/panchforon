import React, { useState } from 'react';
import { useParams, useNavigate, Link } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { recipesApi } from '../api/recipes';
import { RecipeForm, RecipeFormData } from '../features/recipes/RecipeForm';
import { useAuth } from '../context/AuthContext';
import { LoadingSpinner } from '../components/common/LoadingSpinner';
import { Alert } from '../components/ui/Alert';
import { Breadcrumb } from '../components/ui/Breadcrumb';
import { PencilLine, ShieldAlert, SearchX, ArrowLeft, Eye } from 'lucide-react';

export const EditRecipePage: React.FC = () => {
    const { slug } = useParams<{ slug: string }>();
    const navigate = useNavigate();
    const queryClient = useQueryClient();
    const { user } = useAuth();
    const [submitError, setSubmitError] = useState<string | null>(null);

    // The public show endpoint resolves slugs and already lets an author read
    // their own unpublished recipe, so no separate by-id lookup is needed.
    const { data, isLoading } = useQuery({
        queryKey: ['recipe', slug],
        queryFn: () => recipesApi.get(slug!),
        enabled: !!slug,
    });

    const recipe = data?.data;

    const updateMutation = useMutation({
        mutationFn: (formData: RecipeFormData) => {
            const formattedIngredients = formData.ingredients
                .filter((i) => i.name.trim() || i.raw_text.trim())
                .map((i) => ({
                    name: i.name.trim() || undefined,
                    quantity: i.quantity ? parseFloat(i.quantity) : undefined,
                    unit: i.unit.trim() || undefined,
                    raw_text: i.raw_text.trim() || undefined,
                }));

            return recipesApi.update(recipe!.id, {
                title: formData.title.trim(),
                cuisine: formData.cuisine.trim() || undefined,
                category: formData.category.trim() || undefined,
                servings: formData.servings,
                image_url: formData.image_url.trim() || undefined,
                source_url: formData.source_url.trim() || undefined,
                instructions: formData.instructions.trim(),
                ingredients: formattedIngredients,
            });
        },
        onSuccess: (res) => {
            queryClient.invalidateQueries({ queryKey: ['recipe'] });
            queryClient.invalidateQueries({ queryKey: ['recipes'] });
            navigate(`/recipes/${res.data.slug}`);
        },
        onError: (err: any) => {
            setSubmitError(err.message || 'Failed to update recipe.');
        },
    });

    if (isLoading) {
        return <LoadingSpinner message="Loading recipe details..." />;
    }

    if (!recipe) {
        return (
            <div className="mx-auto w-full max-w-lg px-4 py-16 sm:py-24 animate-slide-up">
                <div className="pop rounded-4xl bg-paper p-8 text-center sm:p-10">
                    <div className="sticker mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-2xl bg-plum-soft text-plum-deep">
                        <SearchX className="h-8 w-8" />
                    </div>
                    <h2 className="font-display text-3xl font-extrabold tracking-tight text-ink">Recipe Not Found</h2>
                    <p className="mt-2 text-sm text-ink-2">We looked in every pot. This recipe doesn&apos;t seem to exist.</p>
                    <Link
                        to="/"
                        className="mt-6 inline-flex items-center gap-2 rounded-full bg-ink px-5 py-2.5 text-sm font-extrabold text-white transition-all hover:-translate-y-0.5 hover:shadow-glow-plum"
                    >
                        <ArrowLeft className="h-4 w-4" />
                        Back to recipes
                    </Link>
                </div>
            </div>
        );
    }

    if (!user || user.id !== recipe.user_id) {
        return (
            <div className="mx-auto w-full max-w-lg px-4 py-16 sm:py-24 animate-slide-up">
                <div className="pop-chili rounded-4xl bg-paper p-8 text-center sm:p-10">
                    <div className="sticker-r mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-2xl bg-chili-soft text-chili-deep">
                        <ShieldAlert className="h-8 w-8" />
                    </div>
                    <h2 className="font-display text-3xl font-extrabold tracking-tight text-chili-deep">Unauthorized</h2>
                    <p className="mt-2 text-sm text-ink-2">You can only edit recipes that you personally created.</p>
                    <Link
                        to={`/recipes/${recipe.slug}`}
                        className="mt-6 inline-flex items-center gap-2 rounded-full bg-ink px-5 py-2.5 text-sm font-extrabold text-white transition-all hover:-translate-y-0.5 hover:shadow-glow-chili"
                    >
                        <Eye className="h-4 w-4" />
                        View the recipe instead
                    </Link>
                </div>
            </div>
        );
    }

    return (
        <div className="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 sm:py-8">
            {/* Header band */}
            <header className="relative mb-6 overflow-hidden rounded-4xl bg-turmeric-soft p-6 sm:p-8 lg:p-10 animate-fade-in">
                <div className="absolute inset-0 bg-dots opacity-40" />
                <div className="absolute -right-16 -top-16 h-56 w-56 rounded-full bg-turmeric/50 blur-2xl" />
                <div className="absolute -bottom-20 right-1/3 h-48 w-48 blob-2 bg-mint/30 blur-2xl" />

                <div className="relative space-y-4">
                    <Breadcrumb
                        items={[
                            { label: 'Recipes', to: '/recipes' },
                            { label: recipe.title, to: `/recipes/${recipe.slug}` },
                            { label: 'Edit' },
                        ]}
                    />

                    <div className="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
                        <div className="flex items-start gap-4">
                            <div className="sticker-r hidden h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-turmeric text-ink shadow-pop-sm sm:flex">
                                <PencilLine className="h-8 w-8" />
                            </div>
                            <div>
                                <p className="font-display text-xs font-extrabold uppercase tracking-widest text-turmeric-deep">
                                    Editing
                                </p>
                                <h1 className="mt-1 font-display text-3xl font-extrabold tracking-tight text-ink sm:text-4xl lg:text-5xl">
                                    {recipe.title}
                                </h1>
                                <p className="mt-2 max-w-xl text-sm text-ink-2 sm:text-base">
                                    Tweak the ingredients, tidy up the steps or swap the photo. Ratings and meal-plan slots stay
                                    attached to this recipe.
                                </p>
                            </div>
                        </div>

                        <Link
                            to={`/recipes/${recipe.slug}`}
                            className="inline-flex items-center gap-1.5 self-start rounded-full bg-paper px-4 py-2 text-xs font-extrabold text-ink shadow-sm transition-all hover:-translate-y-0.5 hover:shadow-md sm:self-auto"
                        >
                            <Eye className="h-3.5 w-3.5 text-saffron-deep" />
                            View recipe
                        </Link>
                    </div>
                </div>
            </header>

            {submitError && (
                <Alert variant="error" onClose={() => setSubmitError(null)} className="mb-6 animate-pop-in">
                    {submitError}
                </Alert>
            )}

            <RecipeForm
                initialData={recipe}
                onSubmit={async (data) => {
                    setSubmitError(null);
                    await updateMutation.mutateAsync(data);
                }}
                isSubmitting={updateMutation.isPending}
                onCancel={() => navigate(-1)}
            />
        </div>
    );
};
