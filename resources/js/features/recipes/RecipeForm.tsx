import React, { useState } from 'react';
import { AlertCircle, BookOpen, Carrot, ChefHat, ImageIcon, Lightbulb, Link2, Plus, Save, Users, X, type LucideIcon } from 'lucide-react';
import { Button } from '../../components/ui/Button';
import { Input } from '../../components/ui/Input';
import { Textarea } from '../../components/ui/Textarea';
import { IconButton } from '../../components/ui/IconButton';
import { RecipeDetail } from '../../types/api';

export interface RecipeFormData {
    title: string;
    cuisine: string;
    category: string;
    servings: number;
    image_url: string;
    source_url: string;
    instructions: string;
    ingredients: Array<{ name: string; quantity: string; unit: string; raw_text: string }>;
}

/** What the create and update endpoints accept, built the same way for both. */
export const toRecipePayload = (data: RecipeFormData) => ({
    title: data.title.trim(),
    cuisine: data.cuisine.trim() || undefined,
    category: data.category.trim() || undefined,
    servings: data.servings,
    image_url: data.image_url.trim() || undefined,
    source_url: data.source_url.trim() || undefined,
    instructions: data.instructions.trim(),
    ingredients: data.ingredients
        .filter((i) => i.name.trim() || i.raw_text.trim())
        .map((i) => ({
            name: i.name.trim() || undefined,
            quantity: i.quantity ? Number.parseFloat(i.quantity) : undefined,
            unit: i.unit.trim() || undefined,
            raw_text: i.raw_text.trim() || undefined,
        })),
});

interface RecipeFormProps {
    initialData?: RecipeDetail;
    onSubmit: (data: RecipeFormData) => Promise<void>;
    isSubmitting: boolean;
    onCancel: () => void;
}

const COMMON_UNITS = ['g', 'kg', 'ml', 'l', 'cup', 'tbsp', 'tsp', 'piece', 'clove', 'bunch', 'pinch', 'slice', 'can'];
const COMMON_CUISINES = ['Bangladeshi', 'Indian', 'Pakistani', 'Italian', 'Mexican', 'Chinese', 'Thai', 'Japanese', 'British', 'American', 'Mediterranean'];
const COMMON_CATEGORIES = ['Curry', 'Rice & Biryani', 'Seafood', 'Chicken', 'Beef & Mutton', 'Vegetarian', 'Dessert', 'Breakfast', 'Snack & Street Food'];

const emptyIngredient = () => ({ name: '', quantity: '', unit: '', raw_text: '' });

const ROW_INPUT_CLASS =
    'w-full rounded-xl border border-line bg-surface px-3 py-2 text-sm text-ink transition-colors placeholder:text-ink-3 hover:border-line-strong focus:border-primary/70 focus-visible:outline-none';

const Section: React.FC<{ icon: LucideIcon; tone: string; title: string; subtitle: string; aside?: React.ReactNode; children: React.ReactNode }> = ({
    icon: Icon,
    tone,
    title,
    subtitle,
    aside,
    children,
}) => (
    <section className="rounded-3xl border border-line bg-surface p-5 sm:p-7">
        <div className="flex flex-wrap items-start justify-between gap-3">
            <div className="flex items-start gap-3.5">
                <span className="inline-flex size-11 shrink-0 items-center justify-center rounded-full bg-surface-2">
                    <Icon className={`size-5 ${tone}`} aria-hidden="true" />
                </span>
                <div>
                    <h2 className="font-display text-xl font-semibold text-ink">{title}</h2>
                    <p className="mt-0.5 text-xs text-ink-3">{subtitle}</p>
                </div>
            </div>
            {aside}
        </div>
        <div className="mt-6">{children}</div>
    </section>
);

export const RecipeForm: React.FC<RecipeFormProps> = ({ initialData, onSubmit, isSubmitting, onCancel }) => {
    const [formData, setFormData] = useState<RecipeFormData>(() =>
        initialData
            ? {
                  title: initialData.title || '',
                  cuisine: initialData.cuisine || '',
                  category: initialData.category || '',
                  servings: initialData.servings || 4,
                  image_url: initialData.image_url || '',
                  source_url: initialData.source_url || '',
                  instructions: initialData.instructions || '',
                  ingredients: initialData.ingredients?.length
                      ? initialData.ingredients.map((ing) => ({
                            name: ing.ingredient?.canonical_name || '',
                            quantity: ing.quantity !== null && ing.quantity !== undefined ? String(ing.quantity) : '',
                            unit: ing.unit || '',
                            raw_text: ing.raw_text || '',
                        }))
                      : [emptyIngredient()],
              }
            : {
                  title: '',
                  cuisine: 'Bangladeshi',
                  category: 'Curry',
                  servings: 4,
                  image_url: '',
                  source_url: '',
                  instructions: '',
                  ingredients: [emptyIngredient(), emptyIngredient(), emptyIngredient()],
              },
    );
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [brokenPreviewUrl, setBrokenPreviewUrl] = useState<string | null>(null);

    const patch = (changes: Partial<RecipeFormData>) => setFormData((prev) => ({ ...prev, ...changes }));

    const changeIngredient = (index: number, field: keyof RecipeFormData['ingredients'][number], value: string) =>
        setFormData((prev) => ({
            ...prev,
            ingredients: prev.ingredients.map((ingredient, i) => (i === index ? { ...ingredient, [field]: value } : ingredient)),
        }));

    const removeIngredient = (index: number) => {
        if (formData.ingredients.length <= 1) return;
        setFormData((prev) => ({ ...prev, ingredients: prev.ingredients.filter((_, i) => i !== index) }));
    };

    const validate = (): boolean => {
        const next: Record<string, string> = {};
        if (!formData.title.trim()) next.title = 'Give the recipe a title.';
        if (!formData.instructions.trim()) next.instructions = 'Write the method, one step per line.';
        if (formData.servings < 1 || formData.servings > 50) next.servings = 'Servings must be between 1 and 50.';
        if (!formData.ingredients.some((i) => i.name.trim() || i.raw_text.trim())) next.ingredients = 'Add at least one ingredient.';
        setErrors(next);
        return Object.keys(next).length === 0;
    };

    const filledCount = formData.ingredients.filter((i) => i.name.trim() || i.raw_text.trim()).length;
    const previewUrl = formData.image_url.trim();
    const previewBroken = previewUrl !== '' && brokenPreviewUrl === previewUrl;

    return (
        <form
            onSubmit={async (e) => {
                e.preventDefault();
                if (validate()) await onSubmit(formData);
            }}
            className="relative pb-6"
        >
            <div className="grid gap-6 lg:grid-cols-[minmax(0,1.05fr)_minmax(0,1fr)] lg:items-start lg:gap-8">
                <div className="animate-slide-up space-y-6 lg:sticky lg:top-24 lg:self-start">
                    <Section icon={ChefHat} tone="text-primary" title="The dish" subtitle="Name it, place it, and say how many it feeds.">
                        <div className="space-y-5">
                            <Input
                                id="recipe-title"
                                label="Title"
                                type="text"
                                value={formData.title}
                                onChange={(e) => patch({ title: e.target.value })}
                                placeholder="e.g. Bhuna khichuri with fried eggplant"
                                errorMessage={errors.title}
                                className="font-semibold"
                            />
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-[1fr_1fr_6.5rem]">
                                <Input id="recipe-cuisine" label="Cuisine" type="text" list="cuisines-list" value={formData.cuisine} onChange={(e) => patch({ cuisine: e.target.value })} placeholder="e.g. Bangladeshi" />
                                <datalist id="cuisines-list">
                                    {COMMON_CUISINES.map((c) => (
                                        <option key={c} value={c} />
                                    ))}
                                </datalist>
                                <Input id="recipe-category" label="Category" type="text" list="categories-list" value={formData.category} onChange={(e) => patch({ category: e.target.value })} placeholder="e.g. Curry" />
                                <datalist id="categories-list">
                                    {COMMON_CATEGORIES.map((c) => (
                                        <option key={c} value={c} />
                                    ))}
                                </datalist>
                                <Input
                                    id="recipe-servings"
                                    label="Serves"
                                    type="number"
                                    min="1"
                                    max="50"
                                    value={formData.servings}
                                    onChange={(e) => patch({ servings: Number.parseInt(e.target.value, 10) || 1 })}
                                    leftIcon={<Users className="size-4" />}
                                    errorMessage={errors.servings}
                                />
                            </div>
                        </div>
                    </Section>

                    <Section
                        icon={ImageIcon}
                        tone="text-plum"
                        title="Photo and credit"
                        subtitle="Optional, but a photo makes a recipe far more likely to get planned."
                        aside={<span className="rounded-full bg-surface-2 px-2.5 py-1 text-[11px] font-semibold text-ink-3">Optional</span>}
                    >
                        <div className="grid gap-5 sm:grid-cols-[7.5rem_minmax(0,1fr)]">
                            <div
                                className={`relative flex aspect-square w-full items-center justify-center overflow-hidden rounded-2xl border sm:w-30 ${
                                    previewUrl && !previewBroken ? 'border-line bg-surface-2' : 'border-dashed border-line-strong bg-surface-2 bg-dots'
                                }`}
                                aria-live="polite"
                            >
                                {previewUrl && !previewBroken ? (
                                    <img src={previewUrl} alt="Recipe preview" className="h-full w-full object-cover" onError={() => setBrokenPreviewUrl(previewUrl)} />
                                ) : (
                                    <span className="flex flex-col items-center gap-1 px-2 text-center text-[11px] font-medium">
                                        {previewBroken ? (
                                            <>
                                                <AlertCircle className="size-5 text-hot" aria-hidden="true" />
                                                <span className="text-hot">Couldn’t load</span>
                                            </>
                                        ) : (
                                            <>
                                                <ImageIcon className="size-5 text-ink-3" aria-hidden="true" />
                                                <span className="text-ink-3">Preview</span>
                                            </>
                                        )}
                                    </span>
                                )}
                            </div>
                            <div className="space-y-4">
                                <Input
                                    id="recipe-image-url"
                                    label="Image URL"
                                    type="url"
                                    value={formData.image_url}
                                    onChange={(e) => {
                                        setBrokenPreviewUrl(null);
                                        patch({ image_url: e.target.value });
                                    }}
                                    placeholder="https://…/photo.jpg"
                                    leftIcon={<ImageIcon className="size-4" />}
                                    helperText="A direct link to a JPG or PNG. The preview updates as you type."
                                />
                                <Input
                                    id="recipe-source-url"
                                    label="Source"
                                    type="url"
                                    value={formData.source_url}
                                    onChange={(e) => patch({ source_url: e.target.value })}
                                    placeholder="https://…"
                                    leftIcon={<Link2 className="size-4" />}
                                    helperText="Adapted from a blog or a cookbook? Give them credit."
                                />
                            </div>
                        </div>
                    </Section>
                </div>

                <div className="animate-slide-up space-y-6 animation-delay-100">
                    <Section
                        icon={Carrot}
                        tone="text-mint"
                        title="Ingredients"
                        subtitle="Quantity and unit, or a free line like “salt to taste”."
                        aside={<span className="rounded-full bg-surface-2 px-2.5 py-1 text-[11px] font-semibold text-ink-2 tabular-nums">{filledCount} filled</span>}
                    >
                        {errors.ingredients && (
                            <p role="alert" className="mb-4 flex items-center gap-2 text-sm text-hot">
                                <AlertCircle className="size-4 shrink-0" aria-hidden="true" />
                                {errors.ingredients}
                            </p>
                        )}
                        <ol className="space-y-2.5" aria-label="Ingredient rows">
                            {formData.ingredients.map((ingredient, index) => (
                                <li
                                    key={index}
                                    className="grid grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto] items-center gap-2 rounded-2xl bg-surface-2 p-2.5 sm:grid-cols-[1.75rem_4.75rem_6.25rem_minmax(0,1fr)_auto]"
                                >
                                    <span className="hidden size-7 items-center justify-center rounded-full bg-surface text-[11px] font-semibold text-ink-3 tabular-nums sm:flex" aria-hidden="true">
                                        {index + 1}
                                    </span>
                                    <input
                                        type="text"
                                        inputMode="decimal"
                                        value={ingredient.quantity}
                                        onChange={(e) => changeIngredient(index, 'quantity', e.target.value)}
                                        placeholder="Qty"
                                        aria-label={`Ingredient ${index + 1} quantity`}
                                        className={`${ROW_INPUT_CLASS} font-semibold`}
                                    />
                                    <input
                                        type="text"
                                        list="units-list"
                                        value={ingredient.unit}
                                        onChange={(e) => changeIngredient(index, 'unit', e.target.value)}
                                        placeholder="Unit"
                                        aria-label={`Ingredient ${index + 1} unit`}
                                        className={ROW_INPUT_CLASS}
                                    />
                                    <input
                                        type="text"
                                        value={ingredient.name}
                                        onChange={(e) => changeIngredient(index, 'name', e.target.value)}
                                        placeholder="Ingredient, e.g. boneless chicken"
                                        aria-label={`Ingredient ${index + 1} name`}
                                        className={`${ROW_INPUT_CLASS} col-span-2 sm:col-span-1`}
                                    />
                                    <span className="col-start-3 row-start-1 sm:col-start-auto sm:row-start-auto">
                                        <IconButton label={`Remove ingredient ${index + 1}`} variant="ghost" size="sm" onClick={() => removeIngredient(index)} disabled={formData.ingredients.length <= 1}>
                                            <X className="size-4" />
                                        </IconButton>
                                    </span>
                                </li>
                            ))}
                        </ol>
                        <datalist id="units-list">
                            {COMMON_UNITS.map((u) => (
                                <option key={u} value={u} />
                            ))}
                        </datalist>
                        <button
                            type="button"
                            onClick={() => setFormData((prev) => ({ ...prev, ingredients: [...prev.ingredients, emptyIngredient()] }))}
                            className="mt-3 flex w-full cursor-pointer items-center justify-center gap-2 rounded-2xl border border-dashed border-line-strong px-4 py-3 text-sm font-medium text-ink-2 transition-colors hover:border-primary hover:text-primary focus-visible:outline-3 focus-visible:outline-primary focus-visible:outline-offset-2"
                        >
                            <Plus className="size-4" aria-hidden="true" />
                            Add an ingredient
                        </button>
                    </Section>

                    <Section icon={BookOpen} tone="text-hot" title="Method" subtitle="One step per line; the numbering is added for you.">
                        <Textarea
                            id="recipe-instructions"
                            rows={10}
                            value={formData.instructions}
                            onChange={(e) => patch({ instructions: e.target.value })}
                            placeholder={'Heat mustard oil in a heavy pot.\nAdd the panch phoron until it crackles.\nSauté the onions and ginger-garlic paste…'}
                            errorMessage={errors.instructions}
                        />
                        <p className="mt-4 flex items-start gap-3 rounded-2xl bg-surface-2 px-4 py-3 text-xs text-ink-2">
                            <Lightbulb className="mt-0.5 size-4 shrink-0 text-turmeric" aria-hidden="true" />
                            Mention heat levels and timings (“medium heat, 8 minutes”) so beginners can follow along.
                        </p>
                    </Section>
                </div>
            </div>

            <div className="sticky bottom-[calc(4.5rem+env(safe-area-inset-bottom))] z-20 mt-8 flex justify-center lg:bottom-4">
                <div className="glass flex w-full max-w-2xl items-center justify-between gap-3 rounded-full py-2 pr-2 pl-5 shadow-xl">
                    <span className="hidden min-w-0 truncate text-sm text-ink-2 sm:block">{formData.title.trim() || (initialData ? 'Editing recipe' : 'New recipe')}</span>
                    <div className="flex w-full items-center justify-end gap-2 sm:w-auto">
                        <Button type="button" variant="ghost" onClick={onCancel}>
                            Cancel
                        </Button>
                        <Button type="submit" size="lg" isLoading={isSubmitting}>
                            <Save className="size-4" aria-hidden="true" />
                            {initialData ? 'Save changes' : 'Publish recipe'}
                        </Button>
                    </div>
                </div>
            </div>
        </form>
    );
};
