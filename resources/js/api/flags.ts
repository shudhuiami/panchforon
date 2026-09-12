import { request } from './client';

export interface FlagReason {
    value: string;
    label: string;
}

export const flagsApi = {
    reasons: (): Promise<{ data: FlagReason[] }> => request<{ data: FlagReason[] }>('/flag-reasons'),

    report: (recipeId: number, data: { reason: string; note?: string }): Promise<{ message: string }> =>
        request<{ message: string }>(`/recipes/${recipeId}/flag`, { method: 'POST', body: JSON.stringify(data) }),
};
