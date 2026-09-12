import React from 'react';
import { useSiteSettings } from '../site/useSiteSettings';

/**
 * Provider buttons, rendered only where a deployment has configured one.
 * The links are plain anchors: the flow leaves the app and comes back.
 */
export const SocialLogins: React.FC<{ className?: string }> = ({ className = '' }) => {
    const { social_logins: providers } = useSiteSettings();

    if (providers.length === 0) return null;

    return (
        <div className={className}>
            <div className="my-6 flex items-center gap-3" aria-hidden="true">
                <span className="h-px flex-1 bg-line" />
                <span className="text-xs tracking-[0.18em] text-ink-3 uppercase">or continue with</span>
                <span className="h-px flex-1 bg-line" />
            </div>
            <div className={`grid gap-2 ${providers.length > 1 ? 'sm:grid-cols-2' : ''}`}>
                {providers.map((provider) => (
                    <a
                        key={provider.key}
                        href={`/auth/${provider.key}/redirect`}
                        className="inline-flex h-12 items-center justify-center gap-2 rounded-full border border-line bg-surface-2 px-5 text-sm font-medium text-ink transition-colors hover:border-line-strong hover:bg-surface-3 focus-visible:outline-3 focus-visible:outline-primary focus-visible:outline-offset-2"
                    >
                        {provider.label}
                    </a>
                ))}
            </div>
        </div>
    );
};
