import { useQuery } from '@tanstack/react-query';
import { settingsApi } from '../../api/settings';
import { SiteSettings } from '../../types/api';

const FALLBACK: SiteSettings = {
    site_name: 'Panchforon',
    contact_email: '',
    registration_open: true,
    submissions_open: true,
    social_logins: [],
};

/**
 * Site-wide settings an admin can change: name, contact address, and whether
 * registration and submissions are open. Cached for the session; the
 * fallback keeps the shell rendering before the first response.
 */
export function useSiteSettings(): SiteSettings {
    const { data } = useQuery({
        queryKey: ['site-settings'],
        queryFn: () => settingsApi.get(),
        staleTime: 1000 * 60 * 30,
    });

    return data?.data ?? FALLBACK;
}
