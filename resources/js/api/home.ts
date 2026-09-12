import { request } from './client';
import { HomeFeed } from '../types/api';

export const homeApi = {
    feed: (): Promise<{ data: HomeFeed }> => request<{ data: HomeFeed }>('/home'),
};
