import { request } from './client';
import { SiteSettings } from '../types/api';

export const settingsApi = {
    get: (): Promise<{ data: SiteSettings }> => request<{ data: SiteSettings }>('/settings'),
};
