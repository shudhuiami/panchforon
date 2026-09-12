import React from 'react';
import { ChevronLeft, ChevronRight } from 'lucide-react';

export interface PaginationProps {
    currentPage: number;
    totalPages: number;
    onPageChange: (page: number) => void;
    className?: string;
}

const navButtonClass =
    'inline-flex h-10 cursor-pointer items-center justify-center gap-1 rounded-full border border-line bg-surface px-4 text-sm font-medium text-ink transition-colors hover:border-line-strong hover:bg-surface-2 disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:border-line disabled:hover:bg-surface focus-visible:outline-3 focus-visible:outline-primary focus-visible:outline-offset-2';

export const Pagination: React.FC<PaginationProps> = ({ currentPage, totalPages, onPageChange, className = '' }) => {
    if (totalPages <= 1) return null;

    const pages: Array<number | '…'> = [];
    if (totalPages <= 7) {
        for (let i = 1; i <= totalPages; i++) pages.push(i);
    } else {
        pages.push(1);
        if (currentPage > 3) pages.push('…');
        for (let i = Math.max(2, currentPage - 1); i <= Math.min(totalPages - 1, currentPage + 1); i++) pages.push(i);
        if (currentPage < totalPages - 2) pages.push('…');
        pages.push(totalPages);
    }

    return (
        <nav aria-label="Pagination" className={`flex flex-wrap items-center justify-center gap-2 select-none ${className}`}>
            <button type="button" onClick={() => onPageChange(currentPage - 1)} disabled={currentPage <= 1} aria-label="Previous page" className={navButtonClass}>
                <ChevronLeft className="size-4" aria-hidden="true" />
                <span className="hidden sm:inline">Prev</span>
            </button>

            <div className="flex items-center gap-1 rounded-full border border-line bg-surface p-1">
                {pages.map((page, index) =>
                    page === '…' ? (
                        <span key={`gap-${index}`} className="w-6 text-center text-ink-3" aria-hidden="true">
                            …
                        </span>
                    ) : (
                        <button
                            key={page}
                            type="button"
                            onClick={() => onPageChange(page)}
                            aria-current={page === currentPage ? 'page' : undefined}
                            className={`size-9 cursor-pointer rounded-full text-sm font-medium tabular-nums transition-colors focus-visible:outline-3 focus-visible:outline-primary ${
                                page === currentPage ? 'bg-primary text-on-primary' : 'text-ink-2 hover:bg-surface-2 hover:text-ink'
                            }`}
                        >
                            {page}
                        </button>
                    ),
                )}
            </div>

            <button type="button" onClick={() => onPageChange(currentPage + 1)} disabled={currentPage >= totalPages} aria-label="Next page" className={navButtonClass}>
                <span className="hidden sm:inline">Next</span>
                <ChevronRight className="size-4" aria-hidden="true" />
            </button>
        </nav>
    );
};
