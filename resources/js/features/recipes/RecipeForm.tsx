import React, { useState } from 'react';
import { AlertCircle, BookOpen, Carrot, Check, ChefHat, CookingPot, ImageIcon, Languages, Lightbulb, Link2, Plus, Save, Send, Timer, Users, X, type LucideIcon } from 'lucide-react';
import { Button } from '../../components/ui/Button';
import { Input, fieldLabelClass } from '../../components/ui/Input';
import { Textarea } from '../../components/ui/Textarea';
import { IconButton } from '../../components/ui/IconButton';
import { RecipeDetail } from '../../types/api';
import { formatMinutes, SPICE_LEVELS, SPICE_META, type SpiceLevelValue } from './RecipeCard';

/** One row of the ingredient editor. Everything is a string except the toggle. */
export interface RecipeFormIngredient {
    name: string;
    quantity: string;
    unit: string;
    raw_text: string;
    /** The cook's aside for this line — “3 medium, halved”. */
    note: string;
    is_optional: boolean;
}

export interface RecipeFormData {
    title: string;
    /** The dish's name in Bangla, left empty when it has none. */
    name_bn: string;
    cuisine: string;
    category: string;
    servings: number;
    prep_minutes: string;
    cook_minutes: string;
    spice_level: '' | SpiceLevelValue;
    image_url: string;
    source_url: string;
    instructions: string;
    ingredients: RecipeFormIngredient[];
}

/** Minutes as the API wants them: a whole number, or null to clear the field. */
const toMinutes = (value: string): number | null => {
    const parsed = Number.parseInt(value, 10);
    return Number.isFinite(parsed) && parsed >= 0 ? parsed : null;
};

/**
 * What the create and update endpoints accept, built the same way for both.
 * The fields a recipe may not have travel as null rather than being left out,
 * so clearing one on an edit actually clears it.
 */
export const toRecipePayload = (data: RecipeFormData) => ({
    title: data.title.trim(),
    name_bn: data.name_bn.trim() || null,
    cuisine: data.cuisine.trim() || undefined,
    category: data.category.trim() || undefined,
    servings: data.servings,
    prep_minutes: toMinutes(data.prep_minutes),
    cook_minutes: toMinutes(data.cook_minutes),
    spice_level: data.spice_level || null,
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
            is_optional: i.is_optional,
            note: i.note.trim() || undefined,
        })),
});

/** Which of the two save buttons was pressed. */
export type SaveIntent = 'draft' | 'publish';

interface RecipeFormProps {
    initialData?: RecipeDetail;
    onSubmit: (data: RecipeFormData, intent: SaveIntent) => Promise<void>;
    isSubmitting: boolean;
    onCancel: () => void;
    /** False while the admin has submissions paused: drafts only, no publishing. */
    canPublish?: boolean;
}

const COMMON_UNITS = ['g', 'kg', 'ml', 'l', 'cup', 'tbsp', 'tsp', 'piece', 'clove', 'bunch', 'pinch', 'slice', 'can'];
const COMMON_CUISINES = ['Bangladeshi', 'Indian', 'Pakistani', 'Italian', 'Mexican', 'Chinese', 'Thai', 'Japanese', 'British', 'American', 'Mediterranean'];
const COMMON_CATEGORIES = ['Curry', 'Rice & Biryani', 'Seafood', 'Chicken', 'Beef & Mutton', 'Vegetarian', 'Dessert', 'Breakfast', 'Snack & Street Food'];

const emptyIngredient = (): RecipeFormIngredient => ({ name: '', quantity: '', unit: '', raw_text: '', note: '', is_optional: false });

/** A row only earns its note field and its toggle once there is a line to describe. */
const rowHasContent = (ingredient: RecipeFormIngredient): boolean =>
    Boolean(ingredient.name.trim() || ingredient.raw_text.trim() || ingredient.quantity.trim() || ingredient.note.trim() || ingredient.is_optional);

const ROW_INPUT_CLASS =
    'w-full rounded-xl border border-line bg-surface px-3 py-2 text-sm text-ink transition-colors placeholder:text-ink-3 hover:border-line-strong focus:border-primary/70 focus-visible:outline-none';

/** The spice picker's choices, with the empty one first so it can be cleared. */
const SPICE_CHOICES: Array<{ value: '' | SpiceLevelValue; label: string }> = [
    { value: '', label: 'Not set' },
    ...SPICE_LEVELS.map((level) => ({ value: level, label: SPICE_META[level].label })),
];

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

/** Whatever the API sent, narrowed to a value the picker can hold. */
const toSpiceValue = (value: unknown): '' | SpiceLevelValue =>
    typeof value === 'string' && (SPICE_LEVELS as readonly string[]).includes(value) ? (value as SpiceLevelValue) : '';

const toMinutesField = (value: number | null | undefined): string => (value === null || value === undefined ? '' : String(value));

export const RecipeForm: React.FC<RecipeFormProps> = ({ initialData, onSubmit, isSubmitting, onCancel, canPublish = true }) => {
    const [formData, setFormData] = useState<RecipeFormData>(() =>
        initialData
            ? {
                  title: initialData.title || '',
                  name_bn: initialData.name_bn || '',
                  cuisine: initialData.cuisine || '',
                  category: initialData.category || '',
                  servings: initialData.servings || 4,
                  prep_minutes: toMinutesField(initialData.prep_minutes),
                  cook_minutes: toMinutesField(initialData.cook_minutes),
                  spice_level: toSpiceValue(initialData.spice_level),
                  image_url: initialData.image_url || '',
                  source_url: initialData.source_url || '',
                  instructions: initialData.instructions || '',
                  ingredients: initialData.ingredients?.length
                      ? initialData.ingredients.map((ing) => ({
                            name: ing.ingredient?.canonical_name || '',
                            quantity: ing.quantity !== null && ing.quantity !== undefined ? String(ing.quantity) : '',
                            unit: ing.unit || '',
                            raw_text: ing.raw_text || '',
                            note: ing.note || '',
                            is_optional: Boolean(ing.is_optional),
                        }))
                      : [emptyIngredient()],
              }
            : {
                  title: '',
                  name_bn: '',
                  cuisine: 'Bangladeshi',
                  category: 'Curry',
                  servings: 4,
                  prep_minutes: '',
                  cook_minutes: '',
                  spice_level: '',
                  image_url: '',
                  source_url: '',
                  instructions: '',
                  ingredients: [emptyIngredient(), emptyIngredient(), emptyIngredient()],
              },
    );
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [brokenPreviewUrl, setBrokenPreviewUrl] = useState<string | null>(null);
    const [pendingIntent, setPendingIntent] = useState<SaveIntent | null>(null);

    const patch = (changes: Partial<RecipeFormData>) => setFormData((prev) => ({ ...prev, ...changes }));

    const patchIngredient = (index: number, changes: Partial<RecipeFormIngredient>) =>
        setFormData((prev) => ({
            ...prev,
            ingredients: prev.ingredients.map((ingredient, i) => (i === index ? { ...ingredient, ...changes } : ingredient)),
        }));

    const removeIngredient = (index: number) => {
        if (formData.ingredients.length <= 1) return;
        setFormData((prev) => ({ ...prev, ingredients: prev.ingredients.filter((_, i) => i !== index) }));
    };

    /** Blank is fine; anything else has to be whole minutes the column can hold. */
    const minutesProblem = (value: string): string | undefined => {
        if (!value.trim()) return undefined;
        const parsed = Number(value);
        return Number.isInteger(parsed) && parsed >= 0 && parsed <= 1440 ? undefined : 'Give whole minutes, from 0 to 1440.';
    };

    const validate = (): boolean => {
        const next: Record<string, string> = {};
        if (!formData.title.trim()) next.title = 'Give the recipe a title.';
        if (!formData.instructions.trim()) next.instructions = 'Write the method, one step per line.';
        if (formData.servings < 1 || formData.servings > 50) next.servings = 'Servings must be between 1 and 50.';
        const prepProblem = minutesProblem(formData.prep_minutes);
        if (prepProblem) next.prep_minutes = prepProblem;
        const cookProblem = minutesProblem(formData.cook_minutes);
        if (cookProblem) next.cook_minutes = cookProblem;
        if (!formData.ingredients.some((i) => i.name.trim() || i.raw_text.trim())) next.ingredients = 'Add at least one ingredient.';
        setErrors(next);
        return Object.keys(next).length === 0;
    };

    const filledCount = formData.ingredients.filter((i) => i.name.trim() || i.raw_text.trim()).length;
    const totalTime = formatMinutes((toMinutes(formData.prep_minutes) ?? 0) + (toMinutes(formData.cook_minutes) ?? 0));
    const previewUrl = formData.image_url.trim();
    const previewBroken = previewUrl !== '' && brokenPreviewUrl === previewUrl;

    /**
     * A recipe can be kept private while it is new or still a draft. Once it
     * has been submitted there is only one save, so editing it can never quietly
     * take it off the site.
     */
    const keepsDraft = !initialData || initialData.moderation_status === 'draft';
    const offersBoth = keepsDraft && canPublish;
    const primaryIntent: SaveIntent = keepsDraft && !canPublish ? 'draft' : 'publish';
    const primaryLabel = !keepsDraft ? 'Save changes' : primaryIntent === 'draft' ? 'Save draft' : 'Publish';
    const PrimaryIcon = primaryIntent === 'publish' && keepsDraft ? Send : Save;

    const save = async (intent: SaveIntent) => {
        if (!validate()) return;
        setPendingIntent(intent);
        try {
            await onSubmit(formData, intent);
        } catch {
            // The page reports the failure; the form only has to stop waiting.
        } finally {
            setPendingIntent(null);
        }
    };

    return (
        <form
            onSubmit={(e) => {
                e.preventDefault();
                void save(primaryIntent);
            }}
            className="relative pb-6"
        >
            <div className="grid gap-6 lg:grid-cols-[minmax(0,1.05fr)_minmax(0,1fr)] lg:items-start lg:gap-8">
                <div className="animate-slide-up space-y-6 lg:sticky lg:top-24 lg:self-start">
                    <Section
                        icon={ChefHat}
                        tone="text-primary"
                        title="The dish"
                        subtitle="Name it, place it, time it, and say how many it feeds."
                        aside={
                            totalTime ? (
                                <span className="inline-flex items-center gap-1.5 rounded-full bg-surface-2 px-2.5 py-1 text-[11px] font-semibold text-ink-2">
                                    <Timer className="size-3.5 text-plum" aria-hidden="true" />
                                    {totalTime} total
                                </span>
                            ) : undefined
                        }
                    >
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
                            <Input
                                id="recipe-name-bn"
                                label="Bangla name"
                                type="text"
                                lang="bn"
                                value={formData.name_bn}
                                onChange={(e) => patch({ name_bn: e.target.value })}
                                placeholder="ভুনা খিচুড়ি"
                                leftIcon={<Languages className="size-4" />}
                                helperText="Optional. The name the dish goes by at home, shown beside the title."
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

                            {/* Two short numbers sit side by side even on the narrowest phone. */}
                            <div className="grid grid-cols-2 gap-4">
                                <Input
                                    id="recipe-prep-minutes"
                                    label="Prep (min)"
                                    type="number"
                                    inputMode="numeric"
                                    min="0"
                                    max="1440"
                                    value={formData.prep_minutes}
                                    onChange={(e) => patch({ prep_minutes: e.target.value })}
                                    placeholder="20"
                                    leftIcon={<Timer className="size-4" />}
                                    errorMessage={errors.prep_minutes}
                                />
                                <Input
                                    id="recipe-cook-minutes"
                                    label="Cook (min)"
                                    type="number"
                                    inputMode="numeric"
                                    min="0"
                                    max="1440"
                                    value={formData.cook_minutes}
                                    onChange={(e) => patch({ cook_minutes: e.target.value })}
                                    placeholder="45"
                                    leftIcon={<CookingPot className="size-4" />}
                                    errorMessage={errors.cook_minutes}
                                />
                            </div>

                            <fieldset>
                                <legend className={fieldLabelClass}>Spice level</legend>
                                <div className="flex flex-wrap gap-2">
                                    {SPICE_CHOICES.map((choice) => {
                                        const isChosen = formData.spice_level === choice.value;
                                        const chosenClass = choice.value === '' ? 'border-line-strong bg-surface-3 text-ink' : SPICE_META[choice.value].chip;
                                        return (
                                            <label
                                                key={choice.value || 'unset'}
                                                className={`inline-flex h-10 cursor-pointer items-center rounded-full border px-4 text-sm font-semibold transition-colors select-none has-[:focus-visible]:outline-3 has-[:focus-visible]:outline-primary has-[:focus-visible]:outline-offset-2 ${
                                                    isChosen ? chosenClass : 'border-line bg-surface text-ink-3 hover:border-line-strong hover:text-ink-2'
                                                }`}
                                            >
                                                <input
                                                    type="radio"
                                                    name="recipe-spice-level"
                                                    value={choice.value}
                                                    checked={isChosen}
                                                    onChange={() => patch({ spice_level: choice.value })}
                                                    className="sr-only"
                                                />
                                                {choice.label}
                                            </label>
                                        );
                                    })}
                                </div>
                            </fieldset>
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
                        subtitle="Quantity and unit, or a free line like “salt to taste”. A note and an optional toggle appear as you fill a row."
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
                                <li key={index} className="rounded-2xl bg-surface-2 p-2.5">
                                    <div className="grid grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto] items-center gap-2 sm:grid-cols-[1.75rem_4.75rem_6.25rem_minmax(0,1fr)_auto]">
                                        <span className="hidden size-7 items-center justify-center rounded-full bg-surface text-[11px] font-semibold text-ink-3 tabular-nums sm:flex" aria-hidden="true">
                                            {index + 1}
                                        </span>
                                        <input
                                            type="text"
                                            inputMode="decimal"
                                            value={ingredient.quantity}
                                            onChange={(e) => patchIngredient(index, { quantity: e.target.value })}
                                            placeholder="Qty"
                                            aria-label={`Ingredient ${index + 1} quantity`}
                                            className={`${ROW_INPUT_CLASS} font-semibold`}
                                        />
                                        <input
                                            type="text"
                                            list="units-list"
                                            value={ingredient.unit}
                                            onChange={(e) => patchIngredient(index, { unit: e.target.value })}
                                            placeholder="Unit"
                                            aria-label={`Ingredient ${index + 1} unit`}
                                            className={ROW_INPUT_CLASS}
                                        />
                                        <input
                                            type="text"
                                            value={ingredient.name}
                                            onChange={(e) => patchIngredient(index, { name: e.target.value })}
                                            placeholder="Ingredient, e.g. boneless chicken"
                                            aria-label={`Ingredient ${index + 1} name`}
                                            className={`${ROW_INPUT_CLASS} col-span-2 sm:col-span-1`}
                                        />
                                        <span className="col-start-3 row-start-1 sm:col-start-auto sm:row-start-auto">
                                            <IconButton label={`Remove ingredient ${index + 1}`} variant="ghost" size="sm" onClick={() => removeIngredient(index)} disabled={formData.ingredients.length <= 1}>
                                                <X className="size-4" />
                                            </IconButton>
                                        </span>
                                    </div>
                                    {rowHasContent(ingredient) && (
                                        <div className="mt-2 flex items-center gap-2 sm:pl-9">
                                            <input
                                                type="text"
                                                value={ingredient.note}
                                                onChange={(e) => patchIngredient(index, { note: e.target.value })}
                                                placeholder="Note, e.g. 3 medium, halved"
                                                aria-label={`Ingredient ${index + 1} note`}
                                                className={`${ROW_INPUT_CLASS} min-w-0 flex-1`}
                                            />
                                            <label
                                                className={`inline-flex h-10 shrink-0 cursor-pointer items-center gap-2 rounded-xl border px-3 text-xs font-semibold transition-colors select-none has-[:focus-visible]:outline-3 has-[:focus-visible]:outline-primary has-[:focus-visible]:outline-offset-2 ${
                                                    ingredient.is_optional ? 'border-turmeric/30 bg-turmeric-soft text-turmeric' : 'border-line bg-surface text-ink-3 hover:border-line-strong'
                                                }`}
                                            >
                                                <input
                                                    type="checkbox"
                                                    checked={ingredient.is_optional}
                                                    onChange={(e) => patchIngredient(index, { is_optional: e.target.checked })}
                                                    aria-label={`Optional — ingredient ${index + 1}`}
                                                    className="sr-only"
                                                />
                                                <span
                                                    className={`flex size-4 items-center justify-center rounded-md border transition-colors ${
                                                        ingredient.is_optional ? 'border-turmeric bg-turmeric text-on-primary' : 'border-line-strong text-transparent'
                                                    }`}
                                                    aria-hidden="true"
                                                >
                                                    <Check className="size-3 stroke-[3]" />
                                                </span>
                                                Optional
                                            </label>
                                        </div>
                                    )}
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
                <div className="glass flex w-full max-w-2xl items-center justify-between gap-3 rounded-full py-2 pr-2 pl-3 shadow-xl sm:pl-5">
                    <span className="hidden min-w-0 truncate text-sm text-ink-2 sm:block">{formData.title.trim() || (initialData ? 'Editing recipe' : 'New recipe')}</span>
                    <div className="flex w-full items-center justify-end gap-2 sm:w-auto">
                        <Button type="button" variant="ghost" size="sm" className="sm:h-11 sm:px-5" onClick={onCancel} disabled={isSubmitting}>
                            Cancel
                        </Button>
                        {offersBoth && (
                            <Button
                                type="button"
                                variant="secondary"
                                onClick={() => void save('draft')}
                                isLoading={isSubmitting && pendingIntent === 'draft'}
                                disabled={isSubmitting}
                            >
                                <Save className="size-4" aria-hidden="true" />
                                <span className="sm:hidden">Draft</span>
                                <span className="hidden sm:inline">Save draft</span>
                            </Button>
                        )}
                        <Button
                            type="submit"
                            className="sm:h-13 sm:px-7 sm:text-base"
                            isLoading={isSubmitting && pendingIntent === primaryIntent}
                            disabled={isSubmitting}
                        >
                            <PrimaryIcon className="size-4" aria-hidden="true" />
                            {primaryLabel}
                        </Button>
                    </div>
                </div>
            </div>
        </form>
    );
};
