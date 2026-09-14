import { request } from './client';
import { CategoryCount, CuisineCount, PaginatedResponse, RecipeDetail, RecipeList, SpiceLevel } from '../types/api';

/**
 * What a save should do with the recipe: keep it private to its author, or
 * send it to a moderator. Left off, the API publishes, which is what every
 * recipe posted before drafts existed did.
 */
export type RecipeSaveStatus = 'draft' | 'published';

/** One ingredient row as the form sends it. */
export interface RecipeIngredientInput {
    raw_text?: string;
    name?: string;
    quantity?: number | null;
    unit?: string | null;
    /** Optional in this recipe, which is not a property of the ingredient itself. */
    is_optional?: boolean;
    /** The cook's aside: "3 medium, halved". */
    note?: string | null;
}

/**
 * Everything a recipe carries when it is written. The update call takes the
 * same shape partially, since the API leaves absent keys alone and clears the
 * ones sent as null.
 */
export interface RecipeInput {
    status?: RecipeSaveStatus;
    title: string;
    name_bn?: string | null;
    cuisine?: string | null;
    category?: string | null;
    instructions: string;
    image_url?: string | null;
    servings?: number;
    prep_minutes?: number | null;
    cook_minutes?: number | null;
    spice_level?: SpiceLevel | null;
    source_url?: string | null;
    ingredients: RecipeIngredientInput[];
}

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

    create: (data: RecipeInput): Promise<{ data: RecipeDetail }> =>
        request<{ data: RecipeDetail }>('/recipes', {
            method: 'POST',
            body: JSON.stringify(data),
        }),

    update: (id: number, data: Partial<RecipeInput>): Promise<{ data: RecipeDetail }> =>
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