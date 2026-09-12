import React from 'react';

export const LoadingSpinner: React.FC<{ message?: string; className?: string }> = ({ message = 'Loading…', className = '' }) => (
    <div role="status" aria-live="polite" className={`flex flex-col items-center justify-center gap-4 py-24 ${className}`}>
        <span className="size-10 animate-spin rounded-full border-[3px] border-line-strong border-t-primary" aria-hidden="true" />
        <p className="text-sm text-ink-3">{message}</p>
    </div>
);
