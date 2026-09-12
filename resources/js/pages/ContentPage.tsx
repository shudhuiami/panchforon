import React from 'react';
import { useParams } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { FileQuestion } from 'lucide-react';
import { contentApi } from '../api/content';
import { LoadingSpinner } from '../components/common/LoadingSpinner';
import { StatusPanel } from '../components/common/StatusPanel';

export const ContentPage: React.FC = () => {
    const { slug } = useParams<{ slug: string }>();
    const { data, isLoading, error } = useQuery({
        queryKey: ['page', slug],
        queryFn: () => contentApi.page(slug!),
        enabled: !!slug,
    });

    if (isLoading) return <LoadingSpinner message="Loading the page…" />;

    if (error || !data) {
        return <StatusPanel icon={FileQuestion} title="Page not found" text="This page may have been unpublished, or the link is wrong." action={{ label: 'Back home', to: '/' }} />;
    }

    const page = data.data;
    const updated = page.updated_at ? new Date(page.updated_at).toLocaleDateString(undefined, { year: 'numeric', month: 'long', day: 'numeric' }) : null;

    return (
        <article className="mx-auto w-full max-w-3xl px-4 py-10 sm:px-6 lg:py-16">
            <header className="animate-slide-up">
                <h1 className="font-display text-4xl font-semibold tracking-tight text-ink text-balance sm:text-5xl">{page.title}</h1>
                {page.excerpt && <p className="mt-4 text-lg text-ink-2">{page.excerpt}</p>}
                {updated && <p className="mt-4 text-xs text-ink-3">Last updated {updated}</p>}
            </header>

            {/*
              The server renders this markdown with HTML input escaped, so an
              editor cannot inject markup into the storefront.
            */}
            <div className="prose mt-10" dangerouslySetInnerHTML={{ __html: page.html }} />
        </article>
    );
};
