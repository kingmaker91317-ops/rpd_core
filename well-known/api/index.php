<?php
// index.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KickassCrop Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background-color: #0d1117; color: #c9d1d9; font-family: 'Inter', sans-serif; }
        .card { background-color: #161b22; border: 1px solid #30363d; transition: transform 0.2s; }
        .card:hover { transform: translateY(-5px); }
        a { text-decoration: none; }
    </style>
</head>
<body class="min-h-screen flex flex-col items-center justify-center p-4">

    <h1 class="text-4xl font-extrabold mb-6 text-white">Welcome to KickassCrop Panel</h1>
    <p class="text-gray-400 mb-8 text-center max-w-xl">This panel allows you to manage keys, reset devices, and for admins, access the dashboard. Please navigate using the links below:</p>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

        <a href="keys_reset.php?token=" class="card p-6 rounded-lg shadow-lg text-center hover:bg-gray-700">
            <h2 class="text-xl font-bold mb-2 text-white">Key Reset & Management</h2>
            <p class="text-gray-300">Reset device bindings and view your keys. Requires a valid reset token.</p>
        </a>

        <a href="admin_dash.php?token=" class="card p-6 rounded-lg shadow-lg text-center hover:bg-gray-700">
            <h2 class="text-xl font-bold mb-2 text-white">Admin Dashboard</h2>
            <p class="text-gray-300">Admin-only panel to manage all keys and users. Access restricted to admins.</p>
        </a>

        <a href="#" class="card p-6 rounded-lg shadow-lg text-center hover:bg-gray-700">
            <h2 class="text-xl font-bold mb-2 text-white">About / Help</h2>
            <p class="text-gray-300">Learn more about how the panel works and your permissions.</p>
        </a>

    </div>

    <footer class="mt-12 text-gray-500 text-sm text-center">
        Deployed by UJJWALmOdZ
    </footer>

</body>
</html>
