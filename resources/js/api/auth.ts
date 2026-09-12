import { request } from './client';
import { AuthResponse, CurrentUser } from '../types/api';

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

    me: (): Promise<{ data: CurrentUser }> =>
        request<{ data: CurrentUser }>('/user'),

    forgotPassword: (email: string): Promise<{ message: string }> =>
        request<{ message: string }>('/forgot-password', {
            method: 'POST',
            body: JSON.stringify({ email }),
        }),

    resetPassword: (data: { token: string; email: string; password: string; password_confirmation: string }): Promise<{ message: string }> =>
        request<{ message: string }>('/reset-password', {
            method: 'POST',
            body: JSON.stringify(data),
        }),
};