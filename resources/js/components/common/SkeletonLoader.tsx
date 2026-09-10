import React from 'react';

export interface SkeletonProps {
    variant?: 'card' | 'text' | 'title' | 'circle' | 'image';
    count?: number;
    className?: string;
}

const shimmer = 'skeleton-shimmer';

export const SkeletonLoader: React.FC<SkeletonProps> = ({
    variant = 'text',
    count = 1,
    className = '',
}) => {
    const items = Array.from({ length: count });

    if (variant === 'card') {
        return (
            <div
                className={`grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 ${className}`}
                role="status"
                aria-label="Loading content"
            >
                {items.map((_, i) => (
                    <div
                        key={i}
                        className="bg-paper rounded-3xl border border-line overflow-hidden shadow-sm p-3 space-y-4 animate-fade-in"
                        style={{ animationDelay: `${i * 60}ms` }}
                    >
                        <div className={`relative aspect-4/3 rounded-2xl w-full ${shimmer}`}>
                            {/* faux sticker badge */}
                            <div className="absolute top-3 left-3 h-6 w-20 rounded-full bg-paper/70" />
                        </div>
                        <div className="space-y-2.5 px-2">
                            <div className={`h-5 rounded-full w-1/3 ${shimmer}`} />
                            <div className={`h-7 rounded-lg w-4/5 ${shimmer}`} />
                            <div className={`h-4 rounded-full w-1/2 ${shimmer}`} />
                        </div>
                        <div className="flex items-center gap-3 px-2 pb-2">
                            <div className={`h-11 rounded-full flex-1 ${shimmer}`} />
                            <div className={`h-11 w-11 rounded-full ${shimmer}`} />
                        </div>
                    </div>
                ))}
            </div>
        );
    }

    if (variant === 'circle') {
        return (
            <div className={`w-12 h-12 rounded-full ${shimmer} ${className}`} aria-hidden="true" />
        );
    }

    if (variant === 'image') {
        return (
            <div className={`aspect-4/3 rounded-3xl w-full ${shimmer} ${className}`} aria-hidden="true" />
        );
    }

    if (variant === 'title') {
        return (
            <div className={`h-9 rounded-xl w-2/3 ${shimmer} ${className}`} aria-hidden="true" />
        );
    }

    return (
        <div className={`space-y-2.5 ${className}`} aria-hidden="true">
            {items.map((_, i) => (
                <div
                    key={i}
                    className={`h-4 rounded-full ${shimmer}`}
                    style={{ width: i === items.length - 1 && items.length > 1 ? '70%' : '100%' }}
                />
            ))}
        </div>
    );
};
