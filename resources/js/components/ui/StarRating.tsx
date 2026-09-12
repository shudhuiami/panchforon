import React from 'react';
import { Star } from 'lucide-react';

export interface StarRatingProps {
    score: number | null;
    count?: number;
    interactive?: boolean;
    onRate?: (rating: number) => void;
    size?: 'sm' | 'md' | 'lg';
}

const STAR_SIZES = { sm: 'size-3.5', md: 'size-5', lg: 'size-7' };

export const StarRating: React.FC<StarRatingProps> = ({ score, count, interactive = false, onRate, size = 'sm' }) => {
    const effectiveScore = score ?? 0;
    const starClass = (filled: boolean) => `${STAR_SIZES[size]} ${filled ? 'fill-turmeric text-turmeric' : 'fill-surface-3 text-line-strong'}`;

    return (
        <div
            className="inline-flex items-center gap-1.5 select-none"
            role={interactive ? undefined : 'img'}
            aria-label={interactive ? undefined : effectiveScore > 0 ? `Rated ${effectiveScore.toFixed(1)} out of 5` : 'Not rated yet'}
        >
            <div className={`flex items-center ${interactive ? 'gap-0.5' : 'gap-px'}`}>
                {[1, 2, 3, 4, 5].map((star) => {
                    const filled = star <= Math.round(effectiveScore);
                    return interactive ? (
                        <button
                            key={star}
                            type="button"
                            onClick={() => onRate?.(star)}
                            aria-label={`Rate ${star} ${star === 1 ? 'star' : 'stars'}`}
                            className="cursor-pointer rounded-full p-0.5 transition-transform duration-200 hover:scale-125 focus-visible:outline-3 focus-visible:outline-primary"
                        >
                            <Star className={starClass(filled)} />
                        </button>
                    ) : (
                        <Star key={star} className={starClass(filled)} aria-hidden="true" />
                    );
                })}
            </div>
            {effectiveScore > 0 && <span className="font-display text-sm leading-none font-semibold text-ink tabular-nums">{effectiveScore.toFixed(1)}</span>}
            {count !== undefined && <span className="text-xs leading-none text-ink-3">({count})</span>}
        </div>
    );
};
