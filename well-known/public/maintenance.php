<?php
session_start();
include('conn.php');

// Credentials Configuration (You can change these)
$admin_user = "admin";
$admin_pass = "admin123";

$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$isBrowser = preg_match('/(Mozilla|Chrome|Safari|Firefox|Edge|Opera)/i', $userAgent);

// 1. API Endpoint for APK or direct API requests
if (!$isBrowser || isset($_GET['api'])) {
    header('Content-Type: application/json; charset=utf-8');
    $sql = "SELECT * FROM onoff WHERE id=1";
    $result = mysqli_query($conn, $sql);
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $isMaintenance = ($row['status'] == 'on');
        echo json_encode([
            'maintenance' => $isMaintenance,
            'message' => $row['myinput']
        ]);
    } else {
        echo json_encode([
            'maintenance' => false,
            'message' => 'Database error'
        ]);
    }
    exit;
}

// 2. Authentication Logic
$error = "";
if (isset($_POST['login'])) {
    $user = $_POST['username'] ?? '';
    $pass = $_POST['password'] ?? '';
    if ($user === $admin_user && $pass === $admin_pass) {
        $_SESSION['maint_logged_in'] = true;
    } else {
        $error = "Invalid Username or Password";
    }
}

if (isset($_POST['logout'])) {
    unset($_SESSION['maint_logged_in']);
    session_destroy();
    header("Location: maintenance.php");
    exit;
}

// 3. Save Settings Logic
$success_msg = "";
if (isset($_SESSION['maint_logged_in']) && $_SESSION['maint_logged_in'] === true && isset($_POST['save_settings'])) {
    $status = isset($_POST['status']) ? 'on' : 'off';
    $reason = $_POST['reason'] ?? '';
    
    $stmt = mysqli_prepare($conn, "UPDATE onoff SET status=?, myinput=? WHERE id=1");
    mysqli_stmt_bind_param($stmt, "ss", $status, $reason);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    $success_msg = "Settings updated successfully!";
}

// Fetch current status
$sql = "SELECT * FROM onoff WHERE id=1";
$result = mysqli_query($conn, $sql);
$current_status = 'off';
$current_reason = '';
if ($result) {
    $row = mysqli_fetch_assoc($result);
    $current_status = $row['status'];
    $current_reason = $row['myinput'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance Controller | RapidCore</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Outfit', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <style>
        body {
            background-color: #090d16;
            background-image: 
                radial-gradient(at 0% 0%, rgba(99, 102, 241, 0.15) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(168, 85, 247, 0.15) 0px, transparent 50%);
        }
        .glass-card {
            background: rgba(17, 25, 40, 0.75);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
        }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen text-slate-100 p-4">

    <?php if (!isset($_SESSION['maint_logged_in']) || $_SESSION['maint_logged_in'] !== true): ?>
    <!-- Login Card -->
    <div class="w-full max-w-md glass-card rounded-3xl p-8 transition-all duration-300">
        <div class="text-center mb-8">
            <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center mx-auto mb-4 shadow-lg shadow-indigo-500/20">
                <i class="fas fa-tools text-white text-2xl"></i>
            </div>
            <h1 class="text-2xl font-bold text-white tracking-tight">Maintenance Panel</h1>
            <p class="text-slate-400 text-sm mt-1">Access control for app updates</p>
        </div>

        <?php if (!empty($error)): ?>
        <div class="mb-6 p-4 rounded-xl bg-red-500/10 border border-red-500/20 text-red-400 text-sm flex items-center gap-3">
            <i class="fas fa-exclamation-circle"></i>
            <span><?= htmlspecialchars($error) ?></span>
        </div>
        <?php endif; ?>

        <form action="" method="POST" class="space-y-6">
            <div>
                <label class="block text-slate-400 text-xs font-semibold uppercase tracking-wider mb-2">Username</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500">
                        <i class="fas fa-user text-sm"></i>
                    </div>
                    <input type="text" name="username" required class="w-full bg-slate-900/60 border border-white/10 rounded-xl py-3 pl-10 pr-4 text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all text-sm" placeholder="Enter username">
                </div>
            </div>

            <div>
                <label class="block text-slate-400 text-xs font-semibold uppercase tracking-wider mb-2">Password</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500">
                        <i class="fas fa-lock text-sm"></i>
                    </div>
                    <input type="password" name="password" required class="w-full bg-slate-900/60 border border-white/10 rounded-xl py-3 pl-10 pr-4 text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all text-sm" placeholder="Enter password">
                </div>
            </div>

            <button type="submit" name="login" class="w-full py-3.5 rounded-xl bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 text-white font-semibold shadow-lg shadow-indigo-500/20 hover:shadow-indigo-500/35 transition-all text-sm tracking-wide">
                AUTHENTICATE
            </button>
        </form>
    </div>

    <?php else: ?>
    <!-- Admin Management Card -->
    <div class="w-full max-w-lg glass-card rounded-3xl p-8 transition-all duration-300">
        <div class="flex items-center justify-between border-b border-white/5 pb-6 mb-6">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-indigo-500/10 flex items-center justify-center text-indigo-400 border border-indigo-500/20">
                    <i class="fas fa-sliders-h"></i>
                </div>
                <div>
                    <h2 class="text-lg font-bold text-white leading-tight">Controls</h2>
                    <p class="text-slate-400 text-xs mt-0.5">Toggle maintenance system</p>
                </div>
            </div>
            <form action="" method="POST">
                <button type="submit" name="logout" class="py-2 px-4 rounded-xl border border-red-500/20 bg-red-500/10 text-red-400 hover:bg-red-500/20 transition-all font-medium text-xs flex items-center gap-2">
                    <i class="fas fa-sign-out-alt"></i> LOGOUT
                </button>
            </form>
        </div>

        <?php if (!empty($success_msg)): ?>
        <div class="mb-6 p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-sm flex items-center gap-3">
            <i class="fas fa-check-circle"></i>
            <span><?= htmlspecialchars($success_msg) ?></span>
        </div>
        <?php endif; ?>

        <form action="" method="POST" class="space-y-6">
            <!-- Status Switcher -->
            <div class="flex items-center justify-between p-4 bg-slate-900/40 border border-white/5 rounded-2xl">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center text-cyan-400 <?= $current_status === 'on' ? 'bg-cyan-500/10 border border-cyan-500/20' : 'bg-slate-800 text-slate-400' ?>" id="statusIcon">
                        <i class="fas fa-power-off"></i>
                    </div>
                    <div>
                        <p class="text-sm font-bold text-white">Maintenance Status</p>
                        <p class="text-[10px] text-slate-400 uppercase tracking-wider font-semibold" id="statusLabel">
                            <?= $current_status === 'on' ? 'Enabled (App Blocked)' : 'Disabled (App Open)' ?>
                        </p>
                    </div>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="status" id="maintenanceToggle" class="sr-only peer" <?= $current_status === 'on' ? 'checked' : '' ?> onchange="updateUIStatus(this)">
                    <div class="w-14 h-7 bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-cyan-500"></div>
                </label>
            </div>

            <!-- Reason Input -->
            <div>
                <label class="block text-slate-400 text-xs font-semibold uppercase tracking-wider mb-2">Notice Message (Shown to users)</label>
                <textarea name="reason" rows="4" class="w-full bg-slate-900/60 border border-white/10 rounded-xl p-4 text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all text-sm" placeholder="E.g., Server is currently under maintenance. Please try again later."><?= htmlspecialchars($current_reason) ?></textarea>
            </div>

            <button type="submit" name="save_settings" class="w-full py-3.5 rounded-xl bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 text-white font-semibold shadow-lg shadow-indigo-500/20 hover:shadow-indigo-500/35 transition-all text-sm tracking-wide flex items-center justify-center gap-2">
                <i class="fas fa-save"></i> SAVE CONFIGURATION
            </button>
        </form>
    </div>
    <script>
        function updateUIStatus(checkbox) {
            const label = document.getElementById('statusLabel');
            const icon = document.getElementById('statusIcon');
            if (checkbox.checked) {
                label.innerText = 'Enabled (App Blocked)';
                label.classList.add('text-cyan-400');
                icon.className = 'w-10 h-10 rounded-xl flex items-center justify-center text-cyan-400 bg-cyan-500/10 border border-cyan-500/20';
            } else {
                label.innerText = 'Disabled (App Open)';
                label.classList.remove('text-cyan-400');
                icon.className = 'w-10 h-10 rounded-xl flex items-center justify-center text-slate-400 bg-slate-800';
            }
        }
    </script>
    <?php endif; ?>

</body>
</html>
