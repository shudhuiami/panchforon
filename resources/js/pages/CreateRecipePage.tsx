import React, { useState } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { recipesApi } from '../api/recipes';
import { RecipeForm, RecipeFormData } from '../features/recipes/RecipeForm';
import { useAuth } from '../context/AuthContext';
import { Button } from '../components/ui/Button';
import { Alert } from '../components/ui/Alert';
import { Breadcrumb } from '../components/ui/Breadcrumb';
import { LogIn, Sparkles, ChefHat, Utensils, ArrowRight } from 'lucide-react';

export const CreateRecipePage: React.FC = () => {
    const { user, demoLogin } = useAuth();
    const navigate = useNavigate();
    const queryClient = useQueryClient();
    const [isDemoLoading, setIsDemoLoading] = useState(false);
    const [submitError, setSubmitError] = useState<string | null>(null);

    const createMutation = useMutation({
        mutationFn: (data: RecipeFormData) => {
            const formattedIngredients = data.ingredients
                .filter((i) => i.name.trim() || i.raw_text.trim())
                .map((i) => ({
                    name: i.name.trim() || undefined,
                    quantity: i.quantity ? parseFloat(i.quantity) : undefined,
                    unit: i.unit.trim() || undefined,
                    raw_text: i.raw_text.trim() || undefined,
                }));

            return recipesApi.create({
                title: data.title.trim(),
                cuisine: data.cuisine.trim() || undefined,
                category: data.category.trim() || undefined,
                servings: data.servings,
                image_url: data.image_url.trim() || undefined,
                source_url: data.source_url.trim() || undefined,
                instructions: data.instructions.trim(),
                ingredients: formattedIngredients,
            });
        },
        onSuccess: (res) => {
            queryClient.invalidateQueries({ queryKey: ['recipes'] });
            queryClient.invalidateQueries({ queryKey: ['cuisines'] });
            queryClient.invalidateQueries({ queryKey: ['categories'] });
            navigate(`/recipes/${res.data.slug}`);
        },
        onError: (err: any) => {
            setSubmitError(err.message || 'Failed to create recipe. Please check your inputs.');
        },
    });

    const handleQuickDemo = async () => {
        setIsDemoLoading(true);
        try {
            await demoLogin();
        } catch (err) {
            console.error('Demo login error:', err);
        } finally {
            setIsDemoLoading(false);
        }
    };

    if (!user) {
        return (
            <div className="mx-auto w-full max-w-lg px-4 py-12 sm:py-20 animate-slide-up">
                <div className="pop relative overflow-hidden rounded-4xl bg-paper p-7 text-center sm:p-10">
                    <div className="absolute -right-10 -top-10 h-40 w-40 rounded-full bg-turmeric-soft blur-2xl" />
                    <div className="absolute -bottom-12 -left-10 h-40 w-40 rounded-full bg-plum-soft blur-2xl" />

                    <div className="relative space-y-5">
                        <div className="sticker mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-saffron text-ink shadow-glow-saffron">
                            <LogIn className="h-8 w-8" />
                        </div>
                        <div className="space-y-2">
                            <h2 className="font-display text-3xl font-extrabold tracking-tight text-ink">
                                Sign in to <span className="text-sunrise">add recipes</span>
                            </h2>
                            <p className="text-sm leading-relaxed text-ink-2">
                                Contributing your culinary creations lets you edit them later and lets the community rate and plan
                                meals with them.
                            </p>
                        </div>
                        <div className="space-y-2.5 pt-1">
                            <Button
                                variant="primary"
                                size="lg"
                                onClick={handleQuickDemo}
                                isLoading={isDemoLoading}
                                className="w-full rounded-full shadow-glow-saffron"
                            >
                                <Sparkles className="h-4 w-4" />
                                1-Click Demo Login
                            </Button>
                            <Link to="/login" className="block">
                                <Button variant="outline" size="lg" className="w-full rounded-full">
                                    Sign In with Email
                                    <ArrowRight className="h-4 w-4" />
                                </Button>
                            </Link>
                        </div>
                    </div>
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
                <div className="absolute -bottom-20 right-1/3 h-48 w-48 blob-1 bg-saffron/30 blur-2xl" />

                <div className="relative space-y-4">
                    <Breadcrumb items={[{ label: 'Recipes', to: '/recipes' }, { label: 'New recipe' }]} />

                    <div className="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
                        <div className="flex items-start gap-4">
                            <div className="sticker hidden h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-turmeric text-ink shadow-pop-sm sm:flex">
                                <ChefHat className="h-8 w-8" />
                            </div>
                            <div>
                                <h1 className="font-display text-3xl font-extrabold tracking-tight text-ink sm:text-4xl lg:text-5xl">
                                    Share a <span className="text-sunrise">recipe</span>
                                </h1>
                                <p className="mt-2 max-w-xl text-sm text-ink-2 sm:text-base">
                                    Add the dish, its ingredients and the steps. Once published, the community can rate it and drop it
                                    straight into their weekly meal plan.
                                </p>
                            </div>
                        </div>

                        <div className="flex flex-wrap gap-2">
                            <span className="inline-flex items-center gap-1.5 rounded-full bg-paper px-3 py-1.5 text-xs font-bold text-ink shadow-sm">
                                <Utensils className="h-3.5 w-3.5 text-saffron-deep" />
                                Posting as {user.name}
                            </span>
                        </div>
                    </div>
                </div>
            </header>

            {submitError && (
                <Alert variant="error" onClose={() => setSubmitError(null)} className="mb-6 animate-pop-in">
                    {submitError}
                </Alert>
            )}

            <RecipeForm
                onSubmit={async (data) => {
                    setSubmitError(null);
                    await createMutation.mutateAsync(data);
                }}
                isSubmitting={createMutation.isPending}
                onCancel={() => navigate(-1)}
            />
        </div>
    );
};
