import React, { useState } from 'react';
import {
    Plus,
    X,
    GripVertical,
    ChefHat,
    BookOpen,
    Carrot,
    ImageIcon,
    Link2,
    Users,
    Lightbulb,
    Save,
    AlertCircle,
} from 'lucide-react';
import { Button } from '../../components/ui/Button';
import { Input } from '../../components/ui/Input';
import { Textarea } from '../../components/ui/Textarea';
import { RecipeDetail } from '../../types/api';

export interface RecipeFormData {
    title: string;
    cuisine: string;
    category: string;
    servings: number;
    image_url: string;
    source_url: string;
    instructions: string;
    ingredients: Array<{
        name: string;
        quantity: string;
        unit: string;
        raw_text: string;
    }>;
}

interface RecipeFormProps {
    initialData?: RecipeDetail;
    onSubmit: (data: RecipeFormData) => Promise<void>;
    isSubmitting: boolean;
    onCancel: () => void;
}

const COMMON_UNITS = [
    '',
    'g',
    'kg',
    'ml',
    'l',
    'cup',
    'tbsp',
    'tsp',
    'piece',
    'clove',
    'bunch',
    'pinch',
    'slice',
    'can',
];

const COMMON_CUISINES = [
    'Bangladeshi',
    'Indian',
    'Pakistani',
    'Italian',
    'Mexican',
    'Chinese',
    'Thai',
    'Japanese',
    'British',
    'American',
    'Mediterranean',
];

const COMMON_CATEGORIES = [
    'Curry',
    'Rice & Biryani',
    'Seafood',
    'Chicken',
    'Beef & Mutton',
    'Vegetarian',
    'Dessert',
    'Breakfast',
    'Snack & Street Food',
];

/** Compact input used inside ingredient rows (composed inline so the row stays dense). */
const ROW_INPUT_CLASS =
    'w-full rounded-xl border border-line-strong bg-paper px-3 py-2 text-sm text-ink placeholder:text-ink-3 transition-colors hover:border-saffron focus:border-saffron focus-visible:outline-2 focus-visible:outline-saffron focus-visible:outline-offset-1';

const SectionHeading: React.FC<{
    icon: React.ReactNode;
    tint: string;
    title: React.ReactNode;
    subtitle: string;
    aside?: React.ReactNode;
}> = ({ icon, tint, title, subtitle, aside }) => (
    <div className="flex flex-wrap items-start justify-between gap-3">
        <div className="flex items-start gap-3.5">
            <div className={`flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl ${tint}`}>{icon}</div>
            <div>
                <h2 className="font-display text-xl font-extrabold tracking-tight text-ink">{title}</h2>
                <p className="mt-0.5 text-xs text-ink-2">{subtitle}</p>
            </div>
        </div>
        {aside}
    </div>
);

export const RecipeForm: React.FC<RecipeFormProps> = ({
    initialData,
    onSubmit,
    isSubmitting,
    onCancel,
}) => {
    const [formData, setFormData] = useState<RecipeFormData>(() => {
        if (initialData) {
            return {
                title: initialData.title || '',
                cuisine: initialData.cuisine || '',
                category: initialData.category || '',
                servings: initialData.servings || 4,
                image_url: initialData.image_url || '',
                source_url: initialData.source_url || '',
                instructions: initialData.instructions || '',
                ingredients: initialData.ingredients?.map((ing) => ({
                    name: ing.ingredient?.canonical_name || '',
                    quantity: ing.quantity !== null && ing.quantity !== undefined ? ing.quantity.toString() : '',
                    unit: ing.unit || '',
                    raw_text: ing.raw_text || '',
                })) || [
                    { name: '', quantity: '', unit: '', raw_text: '' },
                ],
            };
        }
        return {
            title: '',
            cuisine: 'Bangladeshi',
            category: 'Curry',
            servings: 4,
            image_url: '',
            source_url: '',
            instructions: '',
            ingredients: [
                { name: '', quantity: '', unit: '', raw_text: '' },
                { name: '', quantity: '', unit: '', raw_text: '' },
                { name: '', quantity: '', unit: '', raw_text: '' },
            ],
        };
    });

    const [errors, setErrors] = useState<{ [key: string]: string }>({});
    const [brokenPreviewUrl, setBrokenPreviewUrl] = useState<string | null>(null);

    const handleAddIngredient = () => {
        setFormData((prev) => ({
            ...prev,
            ingredients: [...prev.ingredients, { name: '', quantity: '', unit: '', raw_text: '' }],
        }));
    };

    const handleRemoveIngredient = (index: number) => {
        if (formData.ingredients.length <= 1) return;
        setFormData((prev) => ({
            ...prev,
            ingredients: prev.ingredients.filter((_, i) => i !== index),
        }));
    };

    const handleIngredientChange = (
        index: number,
        field: 'name' | 'quantity' | 'unit' | 'raw_text',
        value: string
    ) => {
        setFormData((prev) => {
            const nextIngredients = [...prev.ingredients];
            nextIngredients[index] = {
                ...nextIngredients[index],
                [field]: value,
            };
            return { ...prev, ingredients: nextIngredients };
        });
    };

    const validate = (): boolean => {
        const newErrors: { [key: string]: string } = {};

        if (!formData.title.trim()) {
            newErrors.title = 'Recipe title is required.';
        }
        if (!formData.instructions.trim()) {
            newErrors.instructions = 'Preparation & cooking instructions are required.';
        }
        if (formData.servings < 1 || formData.servings > 50) {
            newErrors.servings = 'Servings must be between 1 and 50.';
        }

        const validIngredients = formData.ingredients.filter(
            (i) => i.name.trim() || i.raw_text.trim()
        );
        if (validIngredients.length === 0) {
            newErrors.ingredients = 'Please provide at least one ingredient.';
        }

        setErrors(newErrors);
        return Object.keys(newErrors).length === 0;
    };

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!validate()) return;
        await onSubmit(formData);
    };

    const filledIngredientCount = formData.ingredients.filter(
        (i) => i.name.trim() || i.raw_text.trim()
    ).length;

    const trimmedImageUrl = formData.image_url.trim();
    const previewIsBroken = trimmedImageUrl !== '' && brokenPreviewUrl === trimmedImageUrl;

    return (
        <form onSubmit={handleSubmit} className="relative pb-6">
            <div className="grid gap-6 lg:grid-cols-[minmax(0,1.05fr)_minmax(0,1fr)] lg:items-start lg:gap-8">
                {/* ============================================================ */}
                {/* LEFT: recipe identity                                          */}
                {/* ============================================================ */}
                <div className="space-y-6 lg:sticky lg:top-24 lg:self-start animate-slide-up">
                    <section className="rounded-3xl border border-line bg-paper p-5 shadow-md sm:p-7">
                        <SectionHeading
                            icon={<ChefHat className="h-5 w-5" />}
                            tint="bg-saffron-soft text-saffron-deep"
                            title="The dish"
                            subtitle="Name it, place it, and say how many it feeds."
                            aside={
                                <span className="sticker-r rounded-full bg-turmeric px-2.5 py-1 font-display text-[10px] font-extrabold uppercase tracking-wider text-ink">
                                    Step 1
                                </span>
                            }
                        />

                        <div className="mt-6 space-y-5">
                            <Input
                                id="recipe-title"
                                label="Recipe title *"
                                type="text"
                                value={formData.title}
                                onChange={(e) => setFormData({ ...formData, title: e.target.value })}
                                placeholder="e.g. Traditional Bangladeshi Bhuna Khichuri"
                                errorMessage={errors.title}
                                className="py-3 text-base font-semibold"
                            />

                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-[1fr_1fr_6.5rem]">
                                <Input
                                    id="recipe-cuisine"
                                    label="Cuisine"
                                    type="text"
                                    list="cuisines-list"
                                    value={formData.cuisine}
                                    onChange={(e) => setFormData({ ...formData, cuisine: e.target.value })}
                                    placeholder="e.g. Bangladeshi"
                                />
                                <datalist id="cuisines-list">
                                    {COMMON_CUISINES.map((c) => (
                                        <option key={c} value={c} />
                                    ))}
                                </datalist>

                                <Input
                                    id="recipe-category"
                                    label="Category"
                                    type="text"
                                    list="categories-list"
                                    value={formData.category}
                                    onChange={(e) => setFormData({ ...formData, category: e.target.value })}
                                    placeholder="e.g. Curry"
                                />
                                <datalist id="categories-list">
                                    {COMMON_CATEGORIES.map((cat) => (
                                        <option key={cat} value={cat} />
                                    ))}
                                </datalist>

                                <Input
                                    id="recipe-servings"
                                    label="Serves"
                                    type="number"
                                    min="1"
                                    max="50"
                                    value={formData.servings}
                                    onChange={(e) => setFormData({ ...formData, servings: parseInt(e.target.value) || 1 })}
                                    leftIcon={<Users className="h-4 w-4" />}
                                    errorMessage={errors.servings}
                                    className="font-bold"
                                />
                            </div>
                        </div>
                    </section>

                    <section className="rounded-3xl border border-line bg-paper p-5 shadow-md sm:p-7">
                        <SectionHeading
                            icon={<ImageIcon className="h-5 w-5" />}
                            tint="bg-plum-soft text-plum-deep"
                            title="Looks & credits"
                            subtitle="Optional, but a photo makes your recipe 3x more likely to get planned."
                            aside={
                                <span className="sticker rounded-full bg-plum-soft px-2.5 py-1 font-display text-[10px] font-extrabold uppercase tracking-wider text-plum-deep">
                                    Optional
                                </span>
                            }
                        />

                        <div className="mt-6 grid gap-5 sm:grid-cols-[7.5rem_minmax(0,1fr)]">
                            {/* Live image preview */}
                            <div
                                className={`relative flex aspect-square w-full items-center justify-center overflow-hidden rounded-2xl border-2 sm:w-[7.5rem] ${
                                    trimmedImageUrl && !previewIsBroken
                                        ? 'border-ink bg-ink shadow-pop-sm'
                                        : 'border-dashed border-line-strong bg-cream-2 bg-dots'
                                }`}
                                aria-live="polite"
                            >
                                {trimmedImageUrl && !previewIsBroken ? (
                                    <img
                                        src={trimmedImageUrl}
                                        alt="Recipe preview"
                                        className="h-full w-full object-cover"
                                        onError={() => setBrokenPreviewUrl(trimmedImageUrl)}
                                    />
                                ) : (
                                    <div className="flex flex-col items-center gap-1 px-2 text-center">
                                        {previewIsBroken ? (
                                            <>
                                                <AlertCircle className="h-5 w-5 text-chili-deep" />
                                                <span className="text-[11px] font-bold text-chili-deep">Couldn&apos;t load</span>
                                            </>
                                        ) : (
                                            <>
                                                <ImageIcon className="h-5 w-5 text-ink-3" />
                                                <span className="text-[11px] font-bold text-ink-3">Preview</span>
                                            </>
                                        )}
                                    </div>
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
                                        setFormData({ ...formData, image_url: e.target.value });
                                    }}
                                    placeholder="https://images.unsplash.com/..."
                                    leftIcon={<ImageIcon className="h-4 w-4" />}
                                    helperText="Paste a direct link to a JPG or PNG. The preview updates live."
                                />

                                <Input
                                    id="recipe-source-url"
                                    label="Source reference URL"
                                    type="url"
                                    value={formData.source_url}
                                    onChange={(e) => setFormData({ ...formData, source_url: e.target.value })}
                                    placeholder="https://recipe-source.com/..."
                                    leftIcon={<Link2 className="h-4 w-4" />}
                                    helperText="Adapted from a blog or cookbook? Give them credit."
                                />
                            </div>
                        </div>
                    </section>
                </div>

                {/* ============================================================ */}
                {/* RIGHT: ingredients builder + instructions                      */}
                {/* ============================================================ */}
                <div className="space-y-6 animate-slide-up animation-delay-100">
                    <section className="rounded-3xl border border-line bg-paper p-5 shadow-md sm:p-7">
                        <SectionHeading
                            icon={<Carrot className="h-5 w-5" />}
                            tint="bg-mint-soft text-mint-deep"
                            title="Ingredients"
                            subtitle='Structured qty + unit, or free-form lines like "salt to taste".'
                            aside={
                                <span className="inline-flex items-center gap-1.5 rounded-full bg-mint-soft px-3 py-1 font-display text-xs font-extrabold text-mint-deep">
                                    <span className="h-1.5 w-1.5 rounded-full bg-mint" />
                                    {filledIngredientCount} filled
                                </span>
                            }
                        />

                        {errors.ingredients && (
                            <div
                                role="alert"
                                className="mt-5 flex items-center gap-2.5 rounded-2xl border border-chili/40 bg-chili-soft px-4 py-3 text-sm font-semibold text-chili-deep animate-pop-in"
                            >
                                <AlertCircle className="h-4 w-4 shrink-0" />
                                {errors.ingredients}
                            </div>
                        )}

                        <ol className="mt-5 space-y-2.5" aria-label="Ingredient rows">
                            {formData.ingredients.map((ingredient, index) => (
                                <li
                                    key={index}
                                    className="grid grid-cols-[auto_minmax(0,1fr)_minmax(0,1fr)_auto] items-center gap-2 rounded-2xl bg-cream-2 p-2.5 transition-shadow focus-within:shadow-glow-mint sm:grid-cols-[auto_4.75rem_6.25rem_minmax(0,1fr)_auto]"
                                >
                                    {/* Handle + row number */}
                                    <div className="flex items-center gap-1 pl-0.5 text-ink-3">
                                        <GripVertical className="h-4 w-4" aria-hidden="true" />
                                        <span className="flex h-6 w-6 items-center justify-center rounded-full bg-paper font-display text-[11px] font-extrabold text-ink-2">
                                            {index + 1}
                                        </span>
                                    </div>

                                    {/* Quantity */}
                                    <input
                                        type="text"
                                        inputMode="decimal"
                                        value={ingredient.quantity}
                                        onChange={(e) => handleIngredientChange(index, 'quantity', e.target.value)}
                                        placeholder="Qty"
                                        aria-label={`Ingredient ${index + 1} quantity`}
                                        className={`${ROW_INPUT_CLASS} font-semibold`}
                                    />

                                    {/* Unit */}
                                    <input
                                        type="text"
                                        list="units-list"
                                        value={ingredient.unit}
                                        onChange={(e) => handleIngredientChange(index, 'unit', e.target.value)}
                                        placeholder="Unit"
                                        aria-label={`Ingredient ${index + 1} unit`}
                                        className={ROW_INPUT_CLASS}
                                    />

                                    {/* Ingredient name (wraps to its own line on mobile) */}
                                    <input
                                        type="text"
                                        value={ingredient.name}
                                        onChange={(e) => handleIngredientChange(index, 'name', e.target.value)}
                                        placeholder="Ingredient, e.g. Boneless chicken"
                                        aria-label={`Ingredient ${index + 1} name`}
                                        className={`${ROW_INPUT_CLASS} col-span-2 col-start-2 sm:col-span-1 sm:col-start-auto`}
                                    />

                                    {/* Remove */}
                                    <button
                                        type="button"
                                        disabled={formData.ingredients.length <= 1}
                                        onClick={() => handleRemoveIngredient(index)}
                                        className="col-start-4 row-start-1 flex h-9 w-9 items-center justify-center rounded-full bg-chili-soft text-chili-deep transition-all hover:bg-chili hover:text-white hover:shadow-glow-chili disabled:cursor-not-allowed disabled:opacity-30 disabled:hover:bg-chili-soft disabled:hover:text-chili-deep disabled:hover:shadow-none cursor-pointer sm:col-start-auto sm:row-start-auto"
                                        title="Remove ingredient line"
                                        aria-label={`Remove ingredient ${index + 1}`}
                                    >
                                        <X className="h-4 w-4" />
                                    </button>
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
                            onClick={handleAddIngredient}
                            className="mt-3 flex w-full items-center justify-center gap-2 rounded-2xl border-2 border-dashed border-mint/60 bg-mint-soft/40 px-4 py-3 text-sm font-extrabold text-mint-deep transition-all hover:border-mint hover:bg-mint-soft hover:-translate-y-0.5 cursor-pointer"
                        >
                            <span className="flex h-6 w-6 items-center justify-center rounded-full bg-mint text-white">
                                <Plus className="h-4 w-4" />
                            </span>
                            Add ingredient
                        </button>
                    </section>

                    <section className="rounded-3xl border border-line bg-paper p-5 shadow-md sm:p-7">
                        <SectionHeading
                            icon={<BookOpen className="h-5 w-5" />}
                            tint="bg-chili-soft text-chili-deep"
                            title="How to cook it *"
                            subtitle="Chronological steps. Paragraph breaks and numbers are formatted for you."
                        />

                        <div className="mt-5">
                            <Textarea
                                id="recipe-instructions"
                                rows={10}
                                value={formData.instructions}
                                onChange={(e) => setFormData({ ...formData, instructions: e.target.value })}
                                placeholder={
                                    '1. Heat mustard oil in a heavy-bottomed pot.\n2. Add the panchforon five-spice blend until fragrant.\n3. Sauté onions and ginger-garlic paste...'
                                }
                                errorMessage={errors.instructions}
                                className="rounded-2xl leading-relaxed"
                            />
                        </div>

                        <div className="mt-4 flex items-start gap-3 rounded-2xl bg-turmeric-soft px-4 py-3 text-xs text-ink-2">
                            <span className="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-turmeric text-ink">
                                <Lightbulb className="h-4 w-4" />
                            </span>
                            <p className="leading-relaxed">
                                <span className="font-extrabold text-ink">Tip:</span> start each step on a new line. Mention heat levels
                                and timings (&quot;medium heat, 8 minutes&quot;) so beginners can follow along.
                            </p>
                        </div>
                    </section>
                </div>
            </div>

            {/* ============================================================ */}
            {/* Sticky action bar                                              */}
            {/* ============================================================ */}
            <div className="sticky bottom-4 z-20 mt-8 flex justify-center">
                <div className="glass flex w-full max-w-2xl items-center justify-between gap-3 rounded-full py-2 pl-3 pr-2 shadow-xl sm:pl-5">
                    <div className="hidden min-w-0 items-center gap-2 text-xs font-bold text-ink-2 sm:flex">
                        <span className="flex items-center gap-1">
                            {['bg-saffron', 'bg-chili', 'bg-turmeric', 'bg-mint', 'bg-plum'].map((dot) => (
                                <span key={dot} className={`h-1.5 w-1.5 rounded-full ${dot}`} />
                            ))}
                        </span>
                        <span className="truncate">
                            {formData.title.trim() ? formData.title.trim() : initialData ? 'Editing recipe' : 'New recipe'}
                        </span>
                    </div>
                    <div className="flex w-full items-center justify-end gap-2 sm:w-auto">
                        <Button type="button" variant="ghost" size="md" onClick={onCancel} className="rounded-full">
                            Cancel
                        </Button>
                        <Button
                            type="submit"
                            variant="primary"
                            size="lg"
                            isLoading={isSubmitting}
                            className="rounded-full shadow-glow-saffron hover:-translate-y-0.5"
                        >
                            <Save className="h-4 w-4" />
                            {initialData ? 'Save Recipe Changes' : 'Publish Recipe'}
                        </Button>
                    </div>
                </div>
            </div>
        </form>
    );
};
