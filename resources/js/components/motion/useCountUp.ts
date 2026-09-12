import { useEffect, useState } from 'react';

/** Counts from 0 to `target` once `active` turns true; instant under reduced motion. */
export function useCountUp(target: number, active: boolean, duration = 1400): number {
    const [value, setValue] = useState(0);

    useEffect(() => {
        if (!active) return;
        const reduceMotion = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches ?? false;
        if (reduceMotion || target === 0) {
            setValue(target);
            return;
        }
        let frame = 0;
        const start = performance.now();
        const tick = (now: number) => {
            const progress = Math.min(1, (now - start) / duration);
            const eased = 1 - Math.pow(1 - progress, 4);
            setValue(Math.round(target * eased));
            if (progress < 1) frame = requestAnimationFrame(tick);
        };
        frame = requestAnimationFrame(tick);
        return () => cancelAnimationFrame(frame);
    }, [target, active, duration]);

    return value;
}
