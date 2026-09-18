import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { ChefHat } from 'lucide-react';
import { recipesApi } from '../api/recipes';
import { RecipeForm, RecipeFormData, SaveIntent, toRecipePayload } from '../features/recipes/RecipeForm';
import { useAuth } from '../context/AuthContext';
import { useSiteSettings } from '../features/site/useSiteSettings';
import { StatusPanel } from '../components/common/StatusPanel';
import { Alert } from '../components/ui/Alert';
import { Avatar } from '../components/ui/Avatar';
import { Breadcrumb } from '../components/ui/Breadcrumb';

export const CreateRecipePage: React.FC = () => {
    const { user } = useAuth();
    const settings = useSiteSettings();
    const navigate = useNavigate();
    const queryClient = useQueryClient();
    const [submitError, setSubmitError] = useState<string | null>(null);

    const createMutation = useMutation({
        mutationFn: ({ data, intent }: { data: RecipeFormData; intent: SaveIntent }) =>
            recipesApi.create({ ...toRecipePayload(data), status: intent === 'draft' ? 'draft' : 'published' }),
        onSuccess: (res, { intent }) => {
            for (const key of ['recipes', 'cuisines', 'categories', 'home', 'my-recipes']) queryClient.invalidateQueries({ queryKey: [key] });
            navigate(intent === 'draft' ? '/account/recipes' : `/recipes/${res.data.slug}`);
        },
        onError: (err: Error) => setSubmitError(err.message || 'The recipe couldn’t be saved. Please check the form.'),
    });

    if (!user) return null;

    // Writing the catalogue is a creator's job now. A member keeps everything
    // they have already posted; what they need before posting again is the role.
    if (user.role === 'member') {
        return (
            <StatusPanel
                icon={ChefHat}
                title="Recipes come from our creators"
                text="Posting is open to cooks we’ve read and trusted with the catalogue. Tell us what you cook and we’ll take a look."
                action={{ label: 'Become a creator', to: '/become-a-creator' }}
            />
        );
    }

    return (
        <div className="mx-auto w-full max-w-7xl px-4 py-8 sm:px-6 lg:px-8 lg:py-12">
            <header className="mb-8 animate-slide-up">
                <Breadcrumb items={[{ label: 'Recipes', to: '/recipes' }, { label: 'New recipe' }]} />
                <div className="mt-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div className="max-w-2xl">
                        <p className="text-xs font-semibold tracking-[0.22em] text-primary uppercase">New recipe</p>
                        <h1 className="mt-3 font-display text-4xl font-semibold tracking-tight text-ink text-balance sm:text-5xl">Put a dish on the table.</h1>
                        <p className="mt-4 text-base text-ink-2 sm:text-lg">
                            Add the dish, its ingredients and the steps. Save it as a draft to come back to, or publish it: it goes straight onto the site for the community to
                            rate and plan.
                        </p>
                    </div>
                    <span className="inline-flex shrink-0 items-center gap-2 self-start rounded-full border border-line bg-surface py-1.5 pr-4 pl-1.5 text-sm text-ink-2 sm:self-auto">
                        <Avatar name={user.name} size="sm" />
                        Posting as <span className="font-semibold text-ink">{user.name}</span>
                    </span>
                </div>
            </header>

            {!settings.submissions_open && (
                <Alert variant="warning" title="Publishing is paused" className="mb-6">
                    New recipes aren’t being accepted right now. You can still write this one up and save it as a draft — only you will see it — and publish it once submissions
                    reopen.
                </Alert>
            )}

            {submitError && (
                <Alert variant="error" onClose={() => setSubmitError(null)} className="mb-6">
                    {submitError}
                </Alert>
            )}

            <RecipeForm
                onSubmit={async (data, intent) => {
                    setSubmitError(null);
                    await createMutation.mutateAsync({ data, intent });
                }}
                isSubmitting={createMutation.isPending}
                onCancel={() => navigate(-1)}
                canPublish={settings.submissions_open}
            />
        </div>
    );
};
