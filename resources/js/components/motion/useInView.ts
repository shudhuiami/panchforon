import { useCallback, useEffect, useState } from 'react';

interface UseInViewOptions {
    /** Stop observing after the first time the element shows (default). */
    once?: boolean;
    rootMargin?: string;
    threshold?: number;
}

/**
 * Whether the element has scrolled into the viewport. Returns a callback ref
 * so an element that mounts later (after data arrives) is still observed.
 */
export function useInView<T extends HTMLElement>({ once = true, rootMargin = '0px 0px -8% 0px', threshold = 0.12 }: UseInViewOptions = {}) {
    const [node, setNode] = useState<T | null>(null);
    const [inView, setInView] = useState(false);
    const ref = useCallback((element: T | null) => setNode(element), []);

    useEffect(() => {
        if (!node) return;
        if (typeof IntersectionObserver === 'undefined') {
            setInView(true);
            return;
        }
        const observer = new IntersectionObserver(
            ([entry]) => {
                if (entry.isIntersecting) {
                    setInView(true);
                    if (once) observer.disconnect();
                } else if (!once) {
                    setInView(false);
                }
            },
            { rootMargin, threshold },
        );
        observer.observe(node);
        return () => observer.disconnect();
    }, [node, once, rootMargin, threshold]);

    return { ref, inView } as const;
}
