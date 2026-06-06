<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @routes
    @vite(['resources/js/app.js', 'resources/css/app.css'])
    <x-inertia::head />
    <title>nghCRM</title>
</head>
<body>
<x-inertia::app />
</body>
</html>