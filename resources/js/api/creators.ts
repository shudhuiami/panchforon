import { request } from './client';
import { CreatorApplication } from '../types/api';

export interface CreatorApplicationPayload {
    pitch: string;
    youtube_channel_url?: string | null;
}

export const creatorsApi = {
    /** The applicant's latest attempt, or null when they have never applied. */
    myApplication: (): Promise<{ data: CreatorApplication | null }> => request<{ data: CreatorApplication | null }>('/creator-applications/me'),

    apply: (data: CreatorApplicationPayload): Promise<{ message: string; data: CreatorApplication }> =>
        request<{ message: string; data: CreatorApplication }>('/creator-applications', { method: 'POST', body: JSON.stringify(data) }),
};
