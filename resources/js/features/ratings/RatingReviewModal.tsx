import React, { useEffect, useState } from 'react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { Send, Sparkles, Star, Trash2 } from 'lucide-react';
import { ratingsApi } from '../../api/ratings';
import { Modal } from '../../components/ui/Modal';
import { Button } from '../../components/ui/Button';
import { Alert } from '../../components/ui/Alert';
import { Textarea } from '../../components/ui/Textarea';

interface RatingReviewModalProps {
    isOpen: boolean;
    onClose: () => void;
    recipeId: number;
    recipeTitle: string;
    currentRating?: number | null;
    currentReview?: string | null;
}

/** A label and tone for each star count. */
const STAR_MOODS: Record<number, { label: string; hint: string; chip: string }> = {
    1: { label: 'Not for me', hint: 'Wouldn’t cook it again', chip: 'bg-hot-soft text-hot' },
    2: { label: 'Could be better', hint: 'Needs some tweaks', chip: 'bg-primary-soft text-primary' },
    3: { label: 'Pretty good', hint: 'Solid weeknight dish', chip: 'bg-turmeric-soft text-turmeric' },
    4: { label: 'Loved it', hint: 'Going on repeat', chip: 'bg-mint-soft text-mint' },
    5: { label: 'Outstanding', hint: 'The whole table went quiet', chip: 'bg-plum-soft text-plum' },
};

export const RatingReviewModal: React.FC<RatingReviewModalProps> = ({ isOpen, onClose, recipeId, recipeTitle, currentRating, currentReview }) => {
    const queryClient = useQueryClient();
    const [stars, setStars] = useState(currentRating || 5);
    const [hoverStars, setHoverStars] = useState(0);
    const [review, setReview] = useState(currentReview || '');
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        if (isOpen) {
            setStars(currentRating || 5);
            setHoverStars(0);
            setReview(currentReview || '');
            setError(null);
        }
    }, [isOpen, currentRating, currentReview]);

    const settle = () => {
        queryClient.invalidateQueries({ queryKey: ['recipe'] });
        queryClient.invalidateQueries({ queryKey: ['recipes'] });
        queryClient.invalidateQueries({ queryKey: ['home'] });
        onClose();
    };

    const upsertMutation = useMutation({
        mutationFn: () => ratingsApi.upsert(recipeId, { stars, review: review.trim() || undefined }),
        onSuccess: settle,
        onError: (err: Error) => setError(err.message || 'Failed to submit your rating.'),
    });

    const deleteMutation = useMutation({
        mutationFn: () => ratingsApi.delete(recipeId),
        onSuccess: settle,
        onError: (err: Error) => setError(err.message || 'Failed to remove your rating.'),
    });

    const displayed = hoverStars || stars;
    const mood = STAR_MOODS[displayed] ?? STAR_MOODS[5];

    return (
        <Modal isOpen={isOpen} onClose={onClose} title={currentRating ? 'Update your rating' : 'Rate this recipe'}>
            <form
                onSubmit={(e) => {
                    e.preventDefault();
                    if (stars < 1 || stars > 5) {
                        setError('Pick between one and five stars.');
                        return;
                    }
                    upsertMutation.mutate();
                }}
                className="space-y-5"
            >
                <p className="truncate text-sm text-ink-2">
                    How was <span className="font-semibold text-ink">{recipeTitle}</span>?
                </p>

                <div className="rounded-3xl border border-line bg-surface-2 px-5 py-6 text-center">
                    <div className="flex items-center justify-center gap-1 sm:gap-2" role="radiogroup" aria-label="Star rating" onMouseLeave={() => setHoverStars(0)}>
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
                                    className={`cursor-pointer rounded-full p-1.5 transition-transform duration-150 ease-out hover:scale-125 active:scale-95 focus-visible:outline-3 focus-visible:outline-primary ${filled ? 'scale-110' : ''}`}
                                >
                                    <Star
                                        className={`size-10 transition-colors duration-150 sm:size-12 ${filled ? 'fill-turmeric text-turmeric drop-shadow-[0_4px_12px_rgba(255,196,46,0.45)]' : 'fill-surface-3 text-line-strong'}`}
                                        strokeWidth={2}
                                    />
                                </button>
                            );
                        })}
                    </div>
                    <div className="mt-4 flex flex-col items-center gap-1" aria-live="polite">
                        <span key={displayed} className={`inline-flex animate-pop-in items-center rounded-full px-3.5 py-1.5 font-display text-base font-semibold ${mood.chip}`}>
                            {mood.label}
                        </span>
                        <span className="text-xs text-ink-3">{mood.hint}</span>
                    </div>
                </div>

                <p className="flex items-start gap-2.5 rounded-2xl border border-line bg-surface px-4 py-3 text-xs leading-relaxed text-ink-3">
                    <Sparkles className="mt-0.5 size-4 shrink-0 text-mint" aria-hidden="true" />
                    Your rating feeds the Bayesian community score, so one outlier can’t skew a dish’s ranking.
                </p>

                <Textarea
                    id="rating-review"
                    label="Written review"
                    helperText="Optional. Spice swaps, portion tips, what made it sing."
                    value={review}
                    onChange={(e) => setReview(e.target.value)}
                    rows={3}
                />

                {error && (
                    <Alert variant="error" onClose={() => setError(null)}>
                        {error}
                    </Alert>
                )}

                <div className="flex flex-col-reverse gap-3 pt-1 sm:flex-row sm:items-center sm:justify-between">
                    {currentRating ? (
                        <button
                            type="button"
                            onClick={() => deleteMutation.mutate()}
                            disabled={deleteMutation.isPending}
                            className="inline-flex cursor-pointer items-center justify-center gap-1.5 rounded-full px-3 py-2 text-xs font-semibold text-hot transition-colors hover:bg-hot-soft disabled:cursor-not-allowed disabled:opacity-50 focus-visible:outline-3 focus-visible:outline-primary"
                        >
                            <Trash2 className="size-3.5" aria-hidden="true" />
                            {deleteMutation.isPending ? 'Removing…' : 'Remove my rating'}
                        </button>
                    ) : (
                        <span />
                    )}
                    <div className="flex items-center justify-end gap-2">
                        <Button type="button" variant="ghost" onClick={onClose}>
                            Cancel
                        </Button>
                        <Button type="submit" isLoading={upsertMutation.isPending}>
                            <Send className="size-4" aria-hidden="true" />
                            {currentRating ? 'Update rating' : 'Submit rating'}
                        </Button>
                    </div>
                </div>
            </form>
        </Modal>
    );
};
