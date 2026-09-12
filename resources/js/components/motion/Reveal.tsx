import React from 'react';
import { useInView } from './useInView';

interface RevealProps extends React.HTMLAttributes<HTMLElement> {
    as?: 'div' | 'section' | 'article' | 'li' | 'header';
    /** Milliseconds, for staggering siblings. */
    delay?: number;
}

/** Fades and lifts its children in the first time they scroll into view. */
export const Reveal: React.FC<RevealProps> = ({ as = 'div', delay = 0, className = '', style, children, ...rest }) => {
    const { ref, inView } = useInView<HTMLElement>();
    const Tag = as as React.ElementType;

    return (
        <Tag
            ref={ref}
            className={`reveal ${inView ? 'is-visible' : ''} ${className}`}
            style={{ ...style, transitionDelay: delay ? `${delay}ms` : undefined }}
            {...rest}
        >
            {children}
        </Tag>
    );
};
