import React from 'react';
import { Star } from 'lucide-react';

export interface StarRatingProps {
    score: number | null;
    count?: number;
    interactive?: boolean;
    onRate?: (rating: number) => void;
    size?: 'sm' | 'md' | 'lg';
}

export const StarRating: React.FC<StarRatingProps> = ({
    score,
    count,
    interactive = false,
    onRate,
    size = 'sm',
}) => {
    const starSizes = {
        sm: 'w-4 h-4',
        md: 'w-5 h-5',
        lg: 'w-7 h-7',
    };

    const effectiveScore = score ?? 0;

    return (
        <div className="inline-flex items-center gap-2 select-none">
            <div className={`flex items-center ${interactive ? 'gap-0.5' : 'gap-px'}`}>
                {[1, 2, 3, 4, 5].map((star) => {
                    const isFilled = star <= Math.round(effectiveScore);
                    return (
                        <button
                            key={star}
                            type="button"
                            disabled={!interactive}
                            onClick={() => interactive && onRate && onRate(star)}
                            className={`rounded-full ${
                                interactive
                                    ? 'group/star cursor-pointer p-1 -m-0.5 transition-transform duration-200 ease-spring hover:scale-140 hover:rotate-12 active:scale-110 focus-visible:outline-3 focus-visible:outline-saffron'
                                    : 'cursor-default'
                            }`}
                            aria-label={interactive ? `Rate ${star} stars` : undefined}
                        >
                            <Star
                                className={`${starSizes[size]} transition-colors duration-150 ${
                                    isFilled
                                        ? 'text-turmeric fill-turmeric'
                                        : 'text-line-strong fill-cream-2'
                                } ${interactive ? 'group-hover/star:text-turmeric group-hover/star:fill-turmeric' : ''}`}
                            />
                        </button>
                    );
                })}
            </div>
            {effectiveScore > 0 && (
                <span className="font-display font-extrabold text-sm text-ink tabular-nums leading-none">
                    {effectiveScore.toFixed(1)}
                </span>
            )}
            {count !== undefined && (
                <span className="text-xs font-semibold text-ink-3 leading-none">
                    ({count})
                </span>
            )}
        </div>
    );
};
