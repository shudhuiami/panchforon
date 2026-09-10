import { request } from './client';
import { Rating, RecipeStat } from '../types/api';

export const ratingsApi = {
    upsert: (recipeId: number, data: { stars: number; review?: string }): Promise<{ rating: Rating; stat: RecipeStat }> =>
        request<{ rating: Rating; stat: RecipeStat }>(`/recipes/${recipeId}/rating`, {
            method: 'PUT',
            body: JSON.stringify(data),
        }),

    delete: (recipeId: number): Promise<{ message: string; stat: RecipeStat }> =>
        request<{ message: string; stat: RecipeStat }>(`/recipes/${recipeId}/rating`, {
            method: 'DELETE',
        }),
};