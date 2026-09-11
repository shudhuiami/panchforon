/* Self-hosted variable fonts: bundled by Vite, no third-party request on page load. */
import '@fontsource-variable/fraunces/opsz.css';
import '@fontsource-variable/fraunces/opsz-italic.css';
import '@fontsource-variable/instrument-sans';

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
