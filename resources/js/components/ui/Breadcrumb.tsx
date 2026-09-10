import React from 'react';
import { Link } from 'react-router-dom';
import { ChevronRight, Home } from 'lucide-react';

export interface BreadcrumbItem {
    label: string;
    to?: string;
}

export interface BreadcrumbProps {
    items: BreadcrumbItem[];
    className?: string;
}

export const Breadcrumb: React.FC<BreadcrumbProps> = ({ items, className = '' }) => {
    return (
        <nav aria-label="Breadcrumb" className={`select-none ${className}`}>
            <ol className="flex items-center flex-wrap gap-1.5 text-xs font-semibold">
                <li>
                    <Link
                        to="/"
                        className="inline-flex items-center justify-center w-8 h-8 rounded-full bg-saffron-soft text-saffron-deep hover:bg-saffron hover:text-ink transition-colors focus-visible:outline-3 focus-visible:outline-saffron"
                        aria-label="Home"
                    >
                        <Home className="w-3.5 h-3.5 stroke-[2.5]" />
                    </Link>
                </li>

                {items.map((item, idx) => {
                    const isLast = idx === items.length - 1;
                    return (
                        <li key={idx} className="flex items-center gap-1.5 min-w-0">
                            <ChevronRight className="w-3.5 h-3.5 text-ink-3 shrink-0" aria-hidden="true" />
                            {item.to && !isLast ? (
                                <Link
                                    to={item.to}
                                    className="inline-flex items-center h-8 px-3 rounded-full bg-paper border border-line text-ink-2 hover:text-ink hover:border-ink hover:bg-turmeric-soft transition-colors focus-visible:outline-3 focus-visible:outline-saffron truncate max-w-40 sm:max-w-xs"
                                >
                                    {item.label}
                                </Link>
                            ) : (
                                <span
                                    aria-current={isLast ? 'page' : undefined}
                                    className="inline-flex items-center h-8 px-3 rounded-full bg-ink text-white font-bold truncate max-w-48 sm:max-w-md"
                                >
                                    {item.label}
                                </span>
                            )}
                        </li>
                    );
                })}
            </ol>
        </nav>
    );
};
