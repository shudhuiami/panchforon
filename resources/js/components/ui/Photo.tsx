import React, { useState } from 'react';

interface PhotoProps extends Omit<React.ImgHTMLAttributes<HTMLImageElement>, 'src' | 'onError'> {
    src?: string | null;
    /** Rendered instead of the image when there is no source or it fails to load. */
    fallback: React.ReactNode;
}

/** An image that never shows a broken-image icon: no source or a load error renders the fallback. */
export const Photo: React.FC<PhotoProps> = ({ src, fallback, alt = '', ...rest }) => {
    const [failed, setFailed] = useState(false);

    if (!src || failed) return <>{fallback}</>;

    return <img src={src} alt={alt} onError={() => setFailed(true)} {...rest} />;
};
