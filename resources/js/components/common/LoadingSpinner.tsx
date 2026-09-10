import React from 'react';

export const LoadingSpinner: React.FC<{ message?: string }> = ({
    message = 'Curating regional recipes and ratings...',
}) => (
    <div className="flex flex-col items-center justify-center py-24 text-center" role="status" aria-live="polite">
        {/* Five bouncing spice dots */}
        <div className="flex items-end gap-2 h-10 mb-6" aria-hidden="true">
            <span className="w-3.5 h-3.5 rounded-full bg-saffron animate-bounce shadow-glow-saffron" />
            <span className="w-3.5 h-3.5 rounded-full bg-chili animate-bounce shadow-glow-chili [animation-delay:120ms]" />
            <span className="w-3.5 h-3.5 rounded-full bg-turmeric animate-bounce shadow-glow-turmeric [animation-delay:240ms]" />
            <span className="w-3.5 h-3.5 rounded-full bg-mint animate-bounce shadow-glow-mint [animation-delay:360ms]" />
            <span className="w-3.5 h-3.5 rounded-full bg-plum animate-bounce shadow-glow-plum [animation-delay:480ms]" />
        </div>
        <p className="font-display font-extrabold text-lg text-ink tracking-tight">{message}</p>
        <span className="text-sm text-ink-3 mt-1.5">Five spices blending in harmony</span>
    </div>
);
