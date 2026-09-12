import { request } from './client';
import { CookProfile } from '../types/api';

export const cooksApi = {
    get: (id: number, page = 1): Promise<{ data: CookProfile }> => request<{ data: CookProfile }>(`/cooks/${id}?page=${page}`),
};
