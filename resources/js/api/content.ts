import { request } from './client';

export interface FooterPage {
    slug: string;
    title: string;
}

export interface PageContent {
    slug: string;
    title: string;
    excerpt?: string | null;
    meta_description?: string | null;
    /** Already rendered and escaped on the server. */
    html: string;
    updated_at?: string | null;
}

export type ContentBlocks = Record<string, string>;

export const contentApi = {
    footerPages: (): Promise<{ data: FooterPage[] }> => request<{ data: FooterPage[] }>('/pages'),

    page: (slug: string): Promise<{ data: PageContent }> => request<{ data: PageContent }>(`/pages/${slug}`),

    blocks: (): Promise<{ data: ContentBlocks }> => request<{ data: ContentBlocks }>('/content-blocks'),
};
