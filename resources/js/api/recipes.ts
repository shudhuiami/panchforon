import { request } from './client';
import { CategoryCount, CuisineCount, PaginatedResponse, RecipeDetail, RecipeList } from '../types/api';

/**
 * What a save should do with the recipe: keep it private to its author, or
 * send it to a moderator. Left off, the API publishes, which is what every
 * recipe posted before drafts existed did.
 */
export type RecipeSaveStatus = 'draft' | 'published';

export interface RecipeQueryParams {
    cuisine?: string;
    category?: string;
    q?: string;
    sort?: 'bayesian' | 'rating' | 'latest' | 'title';
    page?: number;
    per_page?: number;
}

export const recipesApi = {
    list: (params: RecipeQueryParams = {}): Promise<PaginatedResponse<RecipeList>> => {
        const search = new URLSearchParams();
        if (params.cuisine) search.set('cuisine', params.cuisine);
        if (params.category) search.set('category', params.category);
        if (params.q) search.set('q', params.q);
        if (params.sort) search.set('sort', params.sort);
        if (params.page) search.set('page', params.page.toString());
        if (params.per_page) search.set('per_page', params.per_page.toString());

        const query = search.toString();
        return request<PaginatedResponse<RecipeList>>(`/recipes${query ? `?${query}` : ''}`);
    },

    get: (slug: string): Promise<{ data: RecipeDetail }> =>
        request<{ data: RecipeDetail }>(`/recipes/${slug}`),

    create: (data: {
        status?: RecipeSaveStatus;
        title: string;
        cuisine?: string;
        category?: string;
        instructions: string;
        image_url?: string;
        servings?: number;
        source_url?: string;
        ingredients: Array<{
            raw_text?: string;
            name?: string;
            quantity?: number;
            unit?: string;
        }>;
    }): Promise<{ data: RecipeDetail }> =>
        request<{ data: RecipeDetail }>('/recipes', {
            method: 'POST',
            body: JSON.stringify(data),
        }),

    update: (id: number, data: {
        status?: RecipeSaveStatus;
        title?: string;
        cuisine?: string;
        category?: string;
        instructions?: string;
        image_url?: string;
        servings?: number;
        source_url?: string;
        ingredients?: Array<{
            raw_text?: string;
            name?: string;
            quantity?: number;
            unit?: string;
        }>;
    }): Promise<{ data: RecipeDetail }> =>
        request<{ data: RecipeDetail }>(`/recipes/${id}`, {
            method: 'PUT',
            body: JSON.stringify(data),
        }),

    /** Send a draft to the moderators without editing it first. */
    publish: (id: number): Promise<{ data: RecipeDetail }> =>
        request<{ data: RecipeDetail }>(`/recipes/${id}/publish`, {
            method: 'POST',
        }),

    delete: (id: number): Promise<{ message: string }> =>
        request<{ message: string }>(`/recipes/${id}`, {
            method: 'DELETE',
        }),

    cuisines: (): Promise<{ data: CuisineCount[] }> =>
        request<{ data: CuisineCount[] }>('/cuisines'),

    categories: (): Promise<{ data: CategoryCount[] }> =>
        request<{ data: CategoryCount[] }>('/categories'),
};