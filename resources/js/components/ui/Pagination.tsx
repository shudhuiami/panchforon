import React from 'react';
import { ChevronLeft, ChevronRight } from 'lucide-react';

export interface PaginationProps {
    currentPage: number;
    totalPages: number;
    onPageChange: (page: number) => void;
    className?: string;
}

export const Pagination: React.FC<PaginationProps> = ({
    currentPage,
    totalPages,
    onPageChange,
    className = '',
}) => {
    if (totalPages <= 1) return null;

    const pages: (number | string)[] = [];
    if (totalPages <= 7) {
        for (let i = 1; i <= totalPages; i++) pages.push(i);
    } else {
        pages.push(1);
        if (currentPage > 3) pages.push('...');
        const start = Math.max(2, currentPage - 1);
        const end = Math.min(totalPages - 1, currentPage + 1);
        for (let i = start; i <= end; i++) pages.push(i);
        if (currentPage < totalPages - 2) pages.push('...');
        pages.push(totalPages);
    }

    const navBtn =
        'inline-flex items-center justify-center gap-1 h-11 px-4 rounded-full bg-paper border-2 border-line-strong text-ink font-display font-bold text-sm hover:border-ink hover:bg-turmeric-soft hover:-translate-y-0.5 transition-all duration-200 cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:translate-y-0 disabled:hover:bg-paper disabled:hover:border-line-strong focus-visible:outline-3 focus-visible:outline-saffron';

    return (
        <nav
            aria-label="Pagination"
            className={`flex items-center justify-center flex-wrap gap-2 select-none ${className}`}
        >
            <button
                type="button"
                onClick={() => onPageChange(currentPage - 1)}
                disabled={currentPage <= 1}
                aria-label="Previous Page"
                className={navBtn}
            >
                <ChevronLeft className="w-4 h-4 stroke-[2.5]" />
                <span className="hidden sm:inline">Prev</span>
            </button>

            <div className="flex items-center gap-1.5 px-1.5 py-1.5 rounded-full bg-cream-2 border border-line">
                {pages.map((p, idx) => {
                    if (typeof p === 'string') {
                        return (
                            <span
                                key={`ellipsis-${idx}`}
                                className="w-6 text-center text-ink-3 font-display font-bold tracking-widest"
                                aria-hidden="true"
                            >
                                …
                            </span>
                        );
                    }

                    const isActive = p === currentPage;
                    return (
                        <button
                            key={p}
                            type="button"
                            onClick={() => onPageChange(p)}
                            aria-current={isActive ? 'page' : undefined}
                            className={`w-10 h-10 rounded-full font-display font-bold text-sm tabular-nums transition-all duration-200 cursor-pointer focus-visible:outline-3 focus-visible:outline-saffron ${
                                isActive
                                    ? 'bg-primary text-on-primary shadow-glow-primary scale-105'
                                    : 'bg-transparent text-ink-2 hover:bg-paper hover:text-ink hover:shadow-md'
                            }`}
                        >
                            {p}
                        </button>
                    );
                })}
            </div>

            <button
                type="button"
                onClick={() => onPageChange(currentPage + 1)}
                disabled={currentPage >= totalPages}
                aria-label="Next Page"
                className={navBtn}
            >
                <span className="hidden sm:inline">Next</span>
                <ChevronRight className="w-4 h-4 stroke-[2.5]" />
            </button>
        </nav>
    );
};
