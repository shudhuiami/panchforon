import { request } from './client';
import { AuthResponse, User } from '../types/api';

export const authApi = {
    register: (data: { name: string; email: string; password: string }): Promise<AuthResponse> =>
        request<AuthResponse>('/register', {
            method: 'POST',
            body: JSON.stringify(data),
        }),

    login: (data: { email: string; password: string }): Promise<AuthResponse> =>
        request<AuthResponse>('/login', {
            method: 'POST',
            body: JSON.stringify(data),
        }),

    logout: (): Promise<{ message: string }> =>
        request<{ message: string }>('/logout', {
            method: 'POST',
        }),

    me: (): Promise<{ data: User }> =>
        request<{ data: User }>('/user'),
};