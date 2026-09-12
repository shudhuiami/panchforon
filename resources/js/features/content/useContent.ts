import { useQuery } from '@tanstack/react-query';
import { contentApi, type ContentBlocks, type FooterPage } from '../../api/content';

/** Wording the storefront falls back to before the API answers. */
const FALLBACK_BLOCKS: ContentBlocks = {
    home_hero_eyebrow: 'Community recipes, ranked fairly',
    home_hero_body:
        'Recipes from home cooks in Bangladesh and far beyond, ranked by the people who cooked them. Plan the week in a tap and shop from one merged list.',
    home_cta_title: 'Cooked something great? Put it on the table.',
    home_cta_body: 'Post the recipe, get rated by people who actually cooked it, and help someone find their new favourite dinner.',
    footer_blurb: 'Community recipes with deep South Asian roots, honest ratings, and a meal planner that turns a week of cooking into one merged shopping list.',
};

/** Editable storefront strings, with the built-in wording until they load. */
export function useContentBlocks(): (key: keyof typeof FALLBACK_BLOCKS | string) => string {
    const { data } = useQuery({
        queryKey: ['content-blocks'],
        queryFn: () => contentApi.blocks(),
        select: (res) => res.data,
        staleTime: 1000 * 60 * 30,
    });

    return (key) => data?.[key] ?? FALLBACK_BLOCKS[key] ?? '';
}

/** The pages an admin chose to list in the footer. */
export function useFooterPages(): FooterPage[] {
    const { data } = useQuery({
        queryKey: ['footer-pages'],
        queryFn: () => contentApi.footerPages(),
        select: (res) => res.data,
        staleTime: 1000 * 60 * 30,
    });

    return data ?? [];
}
