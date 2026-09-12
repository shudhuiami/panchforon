import { request } from './client';
import { CurrentUser, MyRating, PaginatedResponse, RecipeList } from '../types/api';

export const accountApi = {
    updateProfile: (data: { name: string; email: string }): Promise<{ data: CurrentUser }> =>
        request<{ data: CurrentUser }>('/user', { method: 'PUT', body: JSON.stringify(data) }),

    updatePassword: (data: { current_password: string; password: string; password_confirmation: string }): Promise<{ message: string; token: string }> =>
        request<{ message: string; token: string }>('/user/password', { method: 'PUT', body: JSON.stringify(data) }),

    myRecipes: (page = 1): Promise<PaginatedResponse<RecipeList>> => request<PaginatedResponse<RecipeList>>(`/my/recipes?page=${page}`),

    myRatings: (page = 1): Promise<PaginatedResponse<MyRating>> => request<PaginatedResponse<MyRating>>(`/my/ratings?page=${page}`),
};
