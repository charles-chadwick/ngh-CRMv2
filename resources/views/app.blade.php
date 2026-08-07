<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- Resolve the stored theme before first paint so dark mode never flashes light. --}}
    <script>
        (function () {
            try {
                var stored = localStorage.getItem('crm-theme');
                var dark = stored === 'dark' || ((!stored || stored === 'system')
                    && window.matchMedia('(prefers-color-scheme: dark)').matches);
                document.documentElement.classList.toggle('dark', dark);
            } catch (e) {
                // Storage unavailable (private mode, blocked cookies) - fall back to light.
            }
        })();
    </script>

    @routes
    @vite(['resources/js/app.js', 'resources/css/app.css'])
    <x-inertia::head />
    <title>nghCRM</title>
</head>
<body>
<x-inertia::app />
</body>
</html>