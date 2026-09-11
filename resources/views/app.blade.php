<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="color-scheme" content="dark">
        <meta name="theme-color" content="#14100D">
        <meta name="description" content="Panchforon — community recipes with deep South Asian coverage, honest ratings, and a meal planner that turns your week into one shopping list.">
        <title>Panchforon — Recipe & Meal Planner</title>
        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/app.tsx'])
    </head>
    <body class="antialiased min-h-screen">
        <div id="root"></div>
    </body>
</html>
