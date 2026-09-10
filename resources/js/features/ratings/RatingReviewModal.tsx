import React, { useState, useEffect } from 'react';
import { Modal } from '../../components/ui/Modal';
import { Button } from '../../components/ui/Button';
import { Sparkles, Trash2, Star, Send, PenLine } from 'lucide-react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { ratingsApi } from '../../api/ratings';

interface RatingReviewModalProps {
    isOpen: boolean;
    onClose: () => void;
    recipeId: number;
    recipeTitle: string;
    currentRating?: number | null;
    currentReview?: string | null;
}

/** Friendly label + mood colour for each star count. */
const STAR_MOODS: Record<number, { label: string; hint: string; chip: string }> = {
    1: { label: 'Meh', hint: 'Not my thing', chip: 'bg-chili-soft text-chili-deep' },
    2: { label: 'Could be better', hint: 'Needs some tweaks', chip: 'bg-saffron-soft text-saffron-deep' },
    3: { label: 'Pretty good', hint: 'Solid weeknight dish', chip: 'bg-turmeric-soft text-turmeric-deep' },
    4: { label: 'Loved it', hint: 'Going on repeat', chip: 'bg-mint-soft text-mint-deep' },
    5: { label: "Chef's kiss!", hint: 'Absolutely outstanding', chip: 'bg-plum-soft text-plum-deep' },
};

export const RatingReviewModal: React.FC<RatingReviewModalProps> = ({
    isOpen,
    onClose,
    recipeId,
    recipeTitle,
    currentRating,
    currentReview,
}) => {
    const queryClient = useQueryClient();
    const [stars, setStars] = useState<number>(currentRating || 5);
    const [hoverStars, setHoverStars] = useState<number>(0);
    const [review, setReview] = useState<string>(currentReview || '');
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        if (isOpen) {
            setStars(currentRating || 5);
            setHoverStars(0);
            setReview(currentReview || '');
            setError(null);
        }
    }, [isOpen, currentRating, currentReview]);

    const upsertMutation = useMutation({
        mutationFn: () =>
            ratingsApi.upsert(recipeId, {
                stars,
                review: review.trim() || undefined,
            }),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['recipe', recipeId] });
            queryClient.invalidateQueries({ queryKey: ['recipes'] });
            onClose();
        },
        onError: (err: any) => {
            setError(err.message || 'Failed to submit rating.');
        },
    });

    const deleteMutation = useMutation({
        mutationFn: () => ratingsApi.delete(recipeId),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['recipe', recipeId] });
            queryClient.invalidateQueries({ queryKey: ['recipes'] });
            onClose();
        },
        onError: (err: any) => {
            setError(err.message || 'Failed to remove rating.');
        },
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (stars < 1 || stars > 5) {
            setError('Please select a star rating between 1 and 5.');
            return;
        }
        upsertMutation.mutate();
    };

    const displayed = hoverStars || stars;
    const mood = STAR_MOODS[displayed] ?? STAR_MOODS[5];

    return (
        <Modal isOpen={isOpen} onClose={onClose} title={currentRating ? 'Update your rating' : 'Rate & review'}>
            <form onSubmit={handleSubmit} className="space-y-5">
                {/* Recipe being rated */}
                <div className="flex items-center gap-3 rounded-2xl bg-cream-2 px-4 py-3">
                    <span className="w-10 h-10 rounded-xl bg-saffron text-ink flex items-center justify-center shrink-0">
                        <PenLine className="w-5 h-5" />
                    </span>
                    <div className="min-w-0">
                        <p className="text-[10px] font-extrabold uppercase tracking-wider text-ink-3">Recipe</p>
                        <p className="font-display font-extrabold text-base text-ink truncate">{recipeTitle}</p>
                    </div>
                </div>

                {/* Interactive star picker */}
                <div className="relative overflow-hidden rounded-3xl bg-turmeric-soft px-5 py-6 text-center">
                    <div
                        className="absolute -top-10 -left-10 w-32 h-32 rounded-full bg-turmeric/30"
                        aria-hidden="true"
                    />
                    <div
                        className="absolute -bottom-12 -right-8 w-36 h-36 rounded-full bg-saffron/20"
                        aria-hidden="true"
                    />
                    <p className="relative text-xs font-bold text-turmeric-deep uppercase tracking-wider mb-4">
                        How was it?
                    </p>

                    <div
                        className="relative flex items-center justify-center gap-1 sm:gap-2"
                        role="radiogroup"
                        aria-label="Star rating"
                        onMouseLeave={() => setHoverStars(0)}
                    >
                        {[1, 2, 3, 4, 5].map((value) => {
                            const filled = value <= displayed;
                            return (
                                <button
                                    key={value}
                                    type="button"
                                    role="radio"
                                    aria-checked={stars === value}
                                    aria-label={`${value} ${value === 1 ? 'star' : 'stars'}`}
                                    onClick={() => setStars(value)}
                                    onMouseEnter={() => setHoverStars(value)}
                                    onFocus={() => setHoverStars(value)}
                                    onBlur={() => setHoverStars(0)}
                                    className={`group cursor-pointer rounded-full p-1.5 transition-transform duration-150 ease-out hover:scale-125 active:scale-95 focus-visible:outline-3 focus-visible:outline-saffron ${
                                        filled ? 'scale-110' : ''
                                    }`}
                                >
                                    <Star
                                        className={`w-10 h-10 sm:w-12 sm:h-12 transition-all duration-150 ${
                                            filled
                                                ? 'text-turmeric fill-turmeric drop-shadow-[0_4px_10px_rgba(255,196,46,0.55)]'
                                                : 'text-turmeric-deep/30 fill-paper'
                                        }`}
                                        strokeWidth={2.2}
                                    />
                                </button>
                            );
                        })}
                    </div>

                    <div className="relative mt-4 flex flex-col items-center gap-1" aria-live="polite">
                        <span
                            key={displayed}
                            className={`inline-flex items-center gap-1.5 rounded-full px-3.5 py-1.5 font-display font-extrabold text-base animate-pop-in ${mood.chip}`}
                        >
                            {mood.label}
                        </span>
                        <span className="text-xs font-semibold text-ink-2">{mood.hint}</span>
                    </div>
                </div>

                {/* Bayesian hint */}
                <div className="flex items-start gap-2.5 rounded-2xl bg-mint-soft px-4 py-3 text-xs text-mint-deep">
                    <Sparkles className="w-4 h-4 shrink-0 mt-0.5" />
                    <span className="leading-relaxed">
                        Your rating feeds the Bayesian weighted score (v/(v+m)&middot;R + m/(v+m)&middot;C), so one
                        outlier can&rsquo;t skew a dish&rsquo;s ranking.
                    </span>
                </div>

                {/* Optional review text */}
                <div className="space-y-2">
                    <label htmlFor="rating-review" className="flex items-baseline justify-between text-xs font-extrabold text-ink uppercase tracking-wider">
                        <span>Written review</span>
                        <span className="text-ink-3 font-semibold normal-case tracking-normal">Optional</span>
                    </label>
                    <textarea
                        id="rating-review"
                        value={review}
                        onChange={(e) => setReview(e.target.value)}
                        placeholder="Share your spice swaps, portion tips or what made it sing..."
                        rows={3}
                        className="w-full bg-paper text-ink placeholder:text-ink-3 border-2 border-line-strong rounded-2xl px-4 py-3 text-sm leading-relaxed resize-y transition-colors focus:border-saffron focus-visible:outline-none focus:shadow-[0_0_0_4px_rgba(255,122,26,0.25)]"
                    />
                </div>

                {error && (
                    <p
                        role="alert"
                        className="text-xs font-bold text-chili-deep bg-chili-soft px-4 py-3 rounded-2xl border border-chili/30"
                    >
                        {error}
                    </p>
                )}

                <div className="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-between gap-3 pt-1">
                    {currentRating ? (
                        <button
                            type="button"
                            onClick={() => deleteMutation.mutate()}
                            disabled={deleteMutation.isPending}
                            className="inline-flex items-center justify-center gap-1.5 text-xs font-extrabold text-chili-deep rounded-full px-3 py-2 hover:bg-chili-soft transition-colors cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed focus-visible:outline-3 focus-visible:outline-saffron"
                        >
                            <Trash2 className="w-3.5 h-3.5" />
                            {deleteMutation.isPending ? 'Removing...' : 'Remove my rating'}
                        </button>
                    ) : (
                        <div />
                    )}

                    <div className="flex items-center justify-end gap-2">
                        <Button type="button" variant="ghost" onClick={onClose} className="rounded-full">
                            Cancel
                        </Button>
                        <Button
                            type="submit"
                            variant="primary"
                            isLoading={upsertMutation.isPending}
                            className="rounded-full shadow-glow-saffron"
                        >
                            <Send className="w-4 h-4" />
                            {currentRating ? 'Update rating' : 'Submit rating'}
                        </Button>
                    </div>
                </div>
            </form>
        </Modal>
    );
};
