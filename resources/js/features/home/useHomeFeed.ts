import { useQuery } from '@tanstack/react-query';
import { homeApi } from '../../api/home';

/** One request for the whole home page; the server caches it too. */
export const useHomeFeed = () =>
    useQuery({
        queryKey: ['home'],
        queryFn: () => homeApi.feed(),
        select: (res) => res.data,
        staleTime: 1000 * 60 * 5,
    });
