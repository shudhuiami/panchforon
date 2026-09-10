import React from 'react';
import { createRoot } from 'react-dom/client';
import { RootApp } from './RootApp';

const container = document.getElementById('root');

if (container) {
    const root = createRoot(container);
    root.render(
        <React.StrictMode>
            <RootApp />
        </React.StrictMode>
    );
}
