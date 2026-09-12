<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="color-scheme" content="dark">
        <meta name="theme-color" content="#14100D">
        <meta name="description" content="{{ $meta['description'] }}">
        <meta name="application-name" content="Panchforon">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <meta name="apple-mobile-web-app-title" content="Panchforon">
        <meta name="mobile-web-app-capable" content="yes">
        <link rel="manifest" href="/manifest.webmanifest">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">
        <title>{{ $meta['title'] }}</title>
        <link rel="canonical" href="{{ $meta['url'] }}">

        {{-- Link previews: resolved server-side, since the app itself renders in the browser. --}}
        <meta property="og:site_name" content="Panchforon">
        <meta property="og:type" content="{{ $meta['type'] }}">
        <meta property="og:title" content="{{ $meta['title'] }}">
        <meta property="og:description" content="{{ $meta['description'] }}">
        <meta property="og:url" content="{{ $meta['url'] }}">
        <meta name="twitter:card" content="{{ $meta['image'] ? 'summary_large_image' : 'summary' }}">
        <meta name="twitter:title" content="{{ $meta['title'] }}">
        <meta name="twitter:description" content="{{ $meta['description'] }}">
        @if ($meta['image'])
            <meta property="og:image" content="{{ $meta['image'] }}">
            <meta name="twitter:image" content="{{ $meta['image'] }}">
        @endif
        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/app.tsx'])
    </head>
    <body class="antialiased min-h-screen">
        <div id="root"></div>
    </body>
</html>
