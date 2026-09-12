import { request } from './client';
import { PaginatedResponse, RecipeList } from '../types/api';

export const savesApi = {
    list: (page = 1): Promise<PaginatedResponse<RecipeList>> => request<PaginatedResponse<RecipeList>>(`/saved-recipes?page=${page}`),

    ids: (): Promise<{ data: number[] }> => request<{ data: number[] }>('/saved-recipes/ids'),

    save: (recipeId: number): Promise<unknown> => request(`/recipes/${recipeId}/save`, { method: 'POST' }),

    unsave: (recipeId: number): Promise<unknown> => request(`/recipes/${recipeId}/save`, { method: 'DELETE' }),
};
