import React, { useEffect, useState } from 'react';
import { ArrowRight, Search, X } from 'lucide-react';

export interface SearchBoxProps {
    onSubmit: (query: string) => void;
    initialValue?: string;
    placeholder?: string;
    size?: 'sm' | 'lg';
    autoFocus?: boolean;
    className?: string;
}

/**
 * One search control for the whole app. The header hands it a navigate-to-
 * results callback; the browse page hands it the URL filter setter.
 */
export const SearchBox: React.FC<SearchBoxProps> = ({
    onSubmit,
    initialValue = '',
    placeholder = 'Search recipes, ingredients, spices…',
    size = 'sm',
    autoFocus = false,
    className = '',
}) => {
    const [draft, setDraft] = useState(initialValue);

    useEffect(() => {
        setDraft(initialValue);
    }, [initialValue]);

    const large = size === 'lg';

    return (
        <form
            role="search"
            className={`group flex items-center gap-1 rounded-full border border-line bg-surface-2 pl-3 transition-colors focus-within:border-primary/60 ${large ? 'h-14 pr-2' : 'h-10 pr-1'} ${className}`}
            onSubmit={(e) => {
                e.preventDefault();
                onSubmit(draft.trim());
            }}
        >
            <Search className={`shrink-0 text-ink-3 ${large ? 'size-5' : 'size-4'}`} aria-hidden="true" />
            <input
                type="search"
                value={draft}
                onChange={(e) => setDraft(e.target.value)}
                placeholder={placeholder}
                aria-label="Search recipes"
                autoFocus={autoFocus}
                className={`min-w-0 flex-1 bg-transparent text-ink placeholder:text-ink-3 focus:outline-none ${large ? 'text-base' : 'text-sm'}`}
            />
            {draft && (
                <button
                    type="button"
                    onClick={() => setDraft('')}
                    aria-label="Clear search"
                    className="inline-flex size-7 shrink-0 cursor-pointer items-center justify-center rounded-full text-ink-3 hover:bg-surface-3 hover:text-ink"
                >
                    <X className="size-4" />
                </button>
            )}
            <button
                type="submit"
                aria-label="Search"
                className={`inline-flex shrink-0 cursor-pointer items-center justify-center rounded-full bg-primary text-on-primary transition-colors hover:bg-primary-hover ${large ? 'size-10' : 'size-8'}`}
            >
                <ArrowRight className={large ? 'size-5' : 'size-4'} />
            </button>
        </form>
    );
};
