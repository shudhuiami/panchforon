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

export const Breadcrumb: React.FC<BreadcrumbProps> = ({ items, className = '' }) => (
    <nav aria-label="Breadcrumb" className={className}>
        <ol className="flex flex-wrap items-center gap-1.5 text-sm text-ink-3">
            <li>
                <Link to="/" aria-label="Home" className="inline-flex items-center rounded-full transition-colors hover:text-ink">
                    <Home className="size-4" aria-hidden="true" />
                </Link>
            </li>
            {items.map((item, index) => {
                const isLast = index === items.length - 1;
                return (
                    <li key={`${item.label}-${index}`} className="flex min-w-0 items-center gap-1.5">
                        <ChevronRight className="size-3.5 shrink-0" aria-hidden="true" />
                        {item.to && !isLast ? (
                            <Link to={item.to} className="max-w-40 truncate transition-colors hover:text-ink sm:max-w-xs">
                                {item.label}
                            </Link>
                        ) : (
                            <span aria-current={isLast ? 'page' : undefined} className="max-w-56 truncate font-medium text-ink sm:max-w-md">
                                {item.label}
                            </span>
                        )}
                    </li>
                );
            })}
        </ol>
    </nav>
);
