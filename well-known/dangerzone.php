<?php
/**
 * Nexxa Project - Secure Kill-Switch Controller & Admin Dashboard
 * 
 * Instructions:
 * 1. Change the ADMIN_USER and ADMIN_PASS below.
 * 2. Upload this file to your hosting server.
 * 3. Access this file in your browser to login and toggle the lock status.
 */

// --- CONFIGURATION ---
define('ADMIN_USER', 'admin');          // Change your admin username here
define('ADMIN_PASS', 'admin1234');      // Change your admin password here
define('STATUS_FILE', 'status_config.json');

session_start();

// Get all version statuses
function get_all_statuses() {
    if (!file_exists(STATUS_FILE)) {
        file_put_contents(STATUS_FILE, json_encode(['global' => 'active']));
    }
    $data = json_decode(file_get_contents(STATUS_FILE), true);
    if (!is_array($data)) {
        $data = ['global' => 'active'];
    }
    // Migrate old format if exists
    if (isset($data['status'])) {
        $data['global'] = $data['status'];
        unset($data['status']);
        file_put_contents(STATUS_FILE, json_encode($data, JSON_PRETTY_PRINT));
    }
    if (!isset($data['global'])) {
        $data['global'] = 'active';
    }
    return $data;
}

// Get status for a specific version
function get_version_status($version) {
    $data = get_all_statuses();
    if (isset($data[$version])) {
        return $data[$version];
    }
    return $data['global'] ?? 'active';
}

// Set status for a specific version
function set_version_status($version, $status) {
    $data = get_all_statuses();
    $data[$version] = $status;
    file_put_contents(STATUS_FILE, json_encode($data, JSON_PRETTY_PRINT));
}

// Delete status for a specific version
function delete_version($version) {
    if ($version === 'global') return;
    $data = get_all_statuses();
    if (isset($data[$version])) {
        unset($data[$version]);
        file_put_contents(STATUS_FILE, json_encode($data, JSON_PRETTY_PRINT));
    }
}

// Handle Logout
if (isset($_GET['action']) && $_GET['action'] == 'logout') {
    unset($_SESSION['admin_logged']);
    session_destroy();
    header('Location: ' . strtok($_SERVER["REQUEST_URI"], '?'));
    exit;
}

// Handle Login POST
$login_error = '';
if (isset($_POST['login'])) {
    $user = $_POST['username'] ?? '';
    $pass = $_POST['password'] ?? '';
    if ($user === ADMIN_USER && $pass === ADMIN_PASS) {
        $_SESSION['admin_logged'] = true;
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $login_error = 'Invalid Username or Password!';
    }
}

// Handle AJAX: Toggle status of a specific version
if (isset($_POST['toggle_version_status']) && isset($_SESSION['admin_logged'])) {
    $version = $_POST['version'] ?? 'global';
    $current = get_version_status($version);
    $new_status = ($current === 'active') ? 'locked' : 'active';
    set_version_status($version, $new_status);
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'version' => $version, 'status' => $new_status]);
    exit;
}

// Handle AJAX: Add a new version
if (isset($_POST['add_version']) && isset($_SESSION['admin_logged'])) {
    $version = trim($_POST['version'] ?? '');
    // Sanitize version to allow alphanumeric, dots, dashes, and underscores
    $version = preg_replace('/[^a-zA-Z0-9\.\-_]/', '', $version);
    if ($version !== '') {
        if ($version === 'global') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Cannot overwrite global fallback key']);
            exit;
        }
        set_version_status($version, 'active');
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'version' => $version, 'status' => 'active']);
        exit;
    }
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Invalid version string']);
    exit;
}

// Handle AJAX: Delete a version
if (isset($_POST['delete_version']) && isset($_SESSION['admin_logged'])) {
    $version = $_POST['version'] ?? '';
    if ($version !== '' && $version !== 'global') {
        delete_version($version);
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'version' => $version]);
        exit;
    }
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Cannot delete global fallback version']);
    exit;
}

// --- API MODE (When APK requests the file) ---
$headers = getallheaders();
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

if (strpos($userAgent, 'AppNativeGuard') !== false || isset($_GET['api_test'])) {
    header('Content-Type: application/json; charset=utf-8');
    
    // Read version parameter from query string
    $client_version = $_GET['v'] ?? 'unknown';
    
    // Fetch version-specific status
    $status = get_version_status($client_version);
    
    // Secret cryptographic salt (Must match salt in Login.h exactly!)
    $salt = "NexxaGuardian2026!#";
    
    // Generate secure signature: md5(status + version + salt)
    $sig = md5($status . $client_version . $salt);
    
    echo json_encode([
        'status' => $status,
        'version' => $client_version,
        'sig' => $sig
    ]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nexxa App Protection Console</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-primary: #080c14;
            --bg-secondary: #0f172a;
            --bg-card: rgba(15, 23, 42, 0.65);
            --border-color: rgba(51, 65, 85, 0.5);
            --border-hover: rgba(59, 130, 246, 0.5);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --accent-blue: #3b82f6;
            --accent-indigo: #6366f1;
            --accent-green: #10b981;
            --accent-red: #ef4444;
            --glow-green: rgba(16, 185, 129, 0.2);
            --glow-red: rgba(239, 68, 68, 0.2);
        }

        * {
            box-sizing: border-box;
            font-family: 'Outfit', sans-serif;
            margin: 0;
            padding: 0;
            transition: background-color 0.2s, border-color 0.2s, box-shadow 0.2s;
        }

        body {
            background-color: var(--bg-primary);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
        }

        /* Ambient Glow Background elements */
        .ambient-glow-1 {
            position: absolute;
            width: 400px;
            height: 400px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(99, 102, 241, 0.12) 0%, rgba(0,0,0,0) 70%);
            top: -100px;
            right: -100px;
            z-index: -1;
            pointer-events: none;
        }

        .ambient-glow-2 {
            position: absolute;
            width: 500px;
            height: 500px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(59, 130, 246, 0.08) 0%, rgba(0,0,0,0) 70%);
            bottom: -150px;
            left: -150px;
            z-index: -1;
            pointer-events: none;
        }

        /* Authentication Page Layout */
        .auth-container {
            display: flex;
            justify-content: center;
            align-items: center;
            flex-grow: 1;
            padding: 20px;
        }

        .auth-card {
            background: var(--bg-card);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 40px;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.5);
            text-align: center;
            animation: fadeInUp 0.5s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .auth-header {
            margin-bottom: 30px;
        }

        .auth-logo {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, var(--accent-indigo), var(--accent-blue));
            border-radius: 16px;
            margin-bottom: 16px;
            box-shadow: 0 8px 20px rgba(99, 102, 241, 0.3);
        }

        .auth-logo svg {
            width: 32px;
            height: 32px;
            fill: white;
        }

        .auth-card h2 {
            font-size: 24px;
            font-weight: 600;
            color: var(--text-main);
            letter-spacing: 0.5px;
        }

        .auth-card p {
            font-size: 14px;
            color: var(--text-muted);
            margin-top: 6px;
        }

        /* Form Controls */
        .input-group {
            margin-bottom: 20px;
            text-align: left;
        }

        .input-group label {
            display: block;
            font-size: 13px;
            color: var(--text-muted);
            margin-bottom: 8px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper input {
            width: 100%;
            padding: 14px 16px;
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            color: var(--text-main);
            font-size: 15px;
            outline: none;
            transition: all 0.3s;
        }

        .input-wrapper input:focus {
            border-color: var(--accent-blue);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
        }

        .btn {
            width: 100%;
            padding: 14px 24px;
            background: linear-gradient(135deg, var(--accent-indigo) 0%, var(--accent-blue) 100%);
            border: none;
            border-radius: 10px;
            color: white;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.2);
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(99, 102, 241, 0.35);
            opacity: 0.95;
        }

        .btn:active {
            transform: translateY(0);
        }

        .error-banner {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.25);
            color: var(--accent-red);
            padding: 12px;
            border-radius: 10px;
            font-size: 14px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        /* Console Dashboard Layout */
        .dashboard {
            width: 100%;
            max-width: 900px;
            margin: 0 auto;
            padding: 40px 20px;
            animation: fadeIn 0.4s ease-out;
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
        }

        .brand-title {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .brand-icon {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 44px;
            height: 44px;
            background: linear-gradient(135deg, var(--accent-indigo), var(--accent-blue));
            border-radius: 12px;
        }

        .brand-icon svg {
            width: 22px;
            height: 22px;
            fill: white;
        }

        .brand-title h1 {
            font-size: 22px;
            font-weight: 700;
            letter-spacing: 0.5px;
            background: linear-gradient(to right, #ffffff, #94a3b8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .btn-logout {
            padding: 10px 18px;
            background: rgba(30, 41, 59, 0.5);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            color: var(--text-muted);
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
        }

        .btn-logout:hover {
            color: var(--text-main);
            background: rgba(239, 68, 68, 0.1);
            border-color: rgba(239, 68, 68, 0.3);
        }

        /* Widgets Grid */
        .widgets-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .widget-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 25px -10px rgba(0, 0, 0, 0.3);
        }

        .widget-info h3 {
            font-size: 13px;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 6px;
            font-weight: 600;
        }

        .widget-value {
            font-size: 28px;
            font-weight: 700;
        }

        .widget-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: rgba(30, 41, 59, 0.4);
        }

        .widget-icon svg {
            width: 24px;
            height: 24px;
            fill: var(--text-muted);
        }

        /* Version controls container */
        .panel-row {
            display: grid;
            grid-template-columns: 1fr;
            gap: 30px;
        }

        @media (min-width: 768px) {
            .panel-row {
                grid-template-columns: 280px 1fr;
            }
        }

        .control-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 10px 25px -10px rgba(0, 0, 0, 0.3);
            height: fit-content;
        }

        .control-card h2 {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--text-main);
        }

        /* Version List Styling */
        .version-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .version-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(30, 41, 59, 0.3);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 16px 20px;
            animation: scaleIn 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .version-item:hover {
            border-color: var(--border-hover);
        }

        .version-meta {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .version-tag {
            font-size: 16px;
            font-weight: 600;
            color: var(--text-main);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .fallback-badge {
            font-size: 10px;
            background: rgba(99, 102, 241, 0.15);
            color: var(--accent-indigo);
            padding: 2px 6px;
            border-radius: 4px;
            border: 1px solid rgba(99, 102, 241, 0.3);
            text-transform: uppercase;
            font-weight: 600;
        }

        .status-badge {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            padding: 4px 10px;
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            letter-spacing: 0.5px;
        }

        .status-badge::before {
            content: '';
            display: inline-block;
            width: 6px;
            height: 6px;
            border-radius: 50%;
        }

        .status-active-badge {
            background: rgba(16, 185, 129, 0.12);
            color: var(--accent-green);
            border: 1px solid rgba(16, 185, 129, 0.25);
        }

        .status-active-badge::before {
            background-color: var(--accent-green);
            box-shadow: 0 0 8px var(--accent-green);
            animation: pulse-green 2s infinite;
        }

        .status-locked-badge {
            background: rgba(239, 68, 68, 0.12);
            color: var(--accent-red);
            border: 1px solid rgba(239, 68, 68, 0.25);
        }

        .status-locked-badge::before {
            background-color: var(--accent-red);
            box-shadow: 0 0 8px var(--accent-red);
            animation: pulse-red 2s infinite;
        }

        .version-actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-icon {
            background: rgba(30, 41, 59, 0.6);
            border: 1px solid var(--border-color);
            color: var(--text-main);
            width: 38px;
            height: 38px;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }

        .btn-icon:hover {
            background: rgba(59, 130, 246, 0.15);
            border-color: var(--accent-blue);
            color: var(--accent-blue);
        }

        .btn-icon.btn-toggle-active:hover {
            background: rgba(16, 185, 129, 0.15);
            border-color: var(--accent-green);
            color: var(--accent-green);
        }

        .btn-icon.btn-toggle-lock:hover {
            background: rgba(239, 68, 68, 0.15);
            border-color: var(--accent-red);
            color: var(--accent-red);
        }

        .btn-icon.btn-delete:hover {
            background: rgba(239, 68, 68, 0.2);
            border-color: var(--accent-red);
            color: var(--accent-red);
        }

        .btn-icon svg {
            width: 18px;
            height: 18px;
            fill: currentColor;
        }

        /* Toast Notification */
        #toastContainer {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .toast {
            background: rgba(15, 23, 42, 0.9);
            border: 1px solid var(--border-color);
            border-left: 4px solid var(--accent-blue);
            border-radius: 8px;
            padding: 16px 20px;
            color: var(--text-main);
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(8px);
            animation: slideInLeft 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            min-width: 250px;
        }

        .toast.toast-success {
            border-left-color: var(--accent-green);
        }

        .toast.toast-error {
            border-left-color: var(--accent-red);
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes scaleIn {
            from {
                opacity: 0;
                transform: scale(0.95);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(100%);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes pulse-green {
            0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.4); }
            70% { box-shadow: 0 0 0 6px rgba(16, 185, 129, 0); }
            100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }

        @keyframes pulse-red {
            0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4); }
            70% { box-shadow: 0 0 0 6px rgba(239, 68, 68, 0); }
            100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
        }
    </style>
</head>
<body>
    <div class="ambient-glow-1"></div>
    <div class="ambient-glow-2"></div>

    <?php if (!isset($_SESSION['admin_logged'])): ?>
        <!-- Login Screen -->
        <div class="auth-container">
            <div class="auth-card">
                <div class="auth-header">
                    <div class="auth-logo">
                        <svg viewBox="0 0 24 24">
                            <path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/>
                        </svg>
                    </div>
                    <h2>Nexxa Guard Panel</h2>
                    <p>Enter administrator credentials to authenticate</p>
                </div>
                
                <?php if ($login_error): ?>
                    <div class="error-banner">
                        <svg style="width:20px;height:20px;fill:currentColor" viewBox="0 0 24 24">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/>
                        </svg>
                        <?php echo $login_error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="input-group">
                        <label for="username">Username</label>
                        <div class="input-wrapper">
                            <input type="text" id="username" name="username" required autocomplete="off">
                        </div>
                    </div>
                    <div class="input-group">
                        <label for="password">Password</label>
                        <div class="input-wrapper">
                            <input type="password" id="password" name="password" required>
                        </div>
                    </div>
                    <button type="submit" name="login" class="btn">
                        <span>Authenticate</span>
                        <svg style="width:18px;height:18px;fill:currentColor" viewBox="0 0 24 24">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1.79 12.91l-2.83-2.83 1.41-1.41 1.41 1.41 3.54-3.54 1.41 1.41-4.94 4.96z"/>
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    <?php else: ?>
        <!-- Dashboard Screen -->
        <div class="dashboard">
            <header>
                <div class="brand-title">
                    <div class="brand-icon">
                        <svg viewBox="0 0 24 24">
                            <path d="M12 22c5.52 0 10-4.48 10-10S17.52 2 12 2 2 6.48 2 12s4.48 10 10 10zm1-15h-2v6h2V7zm0 8h-2v2h2v-2z"/>
                        </svg>
                    </div>
                    <h1>Nexxa Protection Console</h1>
                </div>
                <a href="?action=logout" class="btn-logout">
                    <svg style="width:16px;height:16px;fill:currentColor" viewBox="0 0 24 24">
                        <path d="M10.09 15.59L11.5 17l5-5-5-5-1.41 1.41L12.67 11H3v2h9.67l-2.58 2.59zM19 3H5c-1.11 0-2 .9-2 2v4h2V5h14v14H5v-4H3v4c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2z"/>
                    </svg>
                    Logout
                </a>
            </header>

            <?php
            $statuses = get_all_statuses();
            $global_status = $statuses['global'] ?? 'active';
            $num_versions = count($statuses) - 1; // Exclude 'global'
            ?>

            <!-- Widgets -->
            <div class="widgets-grid">
                <div class="widget-card">
                    <div class="widget-info">
                        <h3>Global Fallback Status</h3>
                        <div class="widget-value" style="color: <?php echo ($global_status === 'active') ? 'var(--accent-green)' : 'var(--accent-red)'; ?>">
                            <?php echo strtoupper($global_status); ?>
                        </div>
                    </div>
                    <div class="widget-icon">
                        <svg viewBox="0 0 24 24" style="fill: <?php echo ($global_status === 'active') ? 'var(--accent-green)' : 'var(--accent-red)'; ?>">
                            <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm-2 15l-3-3 1.41-1.41L10 13.17l5.59-5.59L17 9l-7 7z"/>
                        </svg>
                    </div>
                </div>

                <div class="widget-card">
                    <div class="widget-info">
                        <h3>Monitored Versions</h3>
                        <div class="widget-value"><?php echo $num_versions; ?></div>
                    </div>
                    <div class="widget-icon">
                        <svg viewBox="0 0 24 24">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 17h-2v-2h2v2zm2.07-7.75l-.9.92C13.45 12.9 13 13.5 13 15h-2v-.5c0-1.1.45-2.1 1.17-2.83l1.24-1.26c.37-.36.59-.86.59-1.41 0-1.1-.9-2-2-2s-2 .9-2 2H7c0-2.76 2.24-5 5-5s5 2.24 5 5c0 1.04-.42 1.99-1.07 2.75z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Content Panel Grid -->
            <div class="panel-row">
                <!-- Add Version Form -->
                <div class="control-card">
                    <h2>
                        <svg style="width:18px;height:18px;fill:currentColor" viewBox="0 0 24 24">
                            <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
                        </svg>
                        Add Version
                    </h2>
                    <form id="addVersionForm" onsubmit="addVersion(event)">
                        <div class="input-group">
                            <label for="new_version">Version Label</label>
                            <div class="input-wrapper">
                                <input type="text" id="new_version" placeholder="e.g. 1.0, 1.1" required autocomplete="off">
                            </div>
                        </div>
                        <button type="submit" class="btn">
                            <span>Register Version</span>
                        </button>
                    </form>
                </div>

                <!-- Versions List -->
                <div class="control-card">
                    <h2>
                        <svg style="width:18px;height:18px;fill:currentColor" viewBox="0 0 24 24">
                            <path d="M4 6H2v14c0 1.1.9 2 2 2h14v-2H4V6zm16-4H8c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H8V4h12v12z"/>
                        </svg>
                        Version Protection Status
                    </h2>
                    <div class="version-list" id="versionList">
                        <?php foreach ($statuses as $verName => $verStatus): ?>
                            <div class="version-item" id="ver_row_<?php echo str_replace('.', '_', $verName); ?>">
                                <div class="version-meta">
                                    <div class="version-tag">
                                        <?php echo htmlspecialchars($verName); ?>
                                        <?php if ($verName === 'global'): ?>
                                            <span class="fallback-badge">Fallback</span>
                                        <?php endif; ?>
                                    </div>
                                    <span id="ver_badge_<?php echo str_replace('.', '_', $verName); ?>" class="status-badge <?php echo ($verStatus === 'active') ? 'status-active-badge' : 'status-locked-badge'; ?>">
                                        <?php echo strtoupper($verStatus); ?>
                                    </span>
                                </div>
                                <div class="version-actions">
                                    <!-- Toggle Status Button -->
                                    <button onclick="toggleVersionStatus('<?php echo htmlspecialchars($verName); ?>')" 
                                            class="btn-icon <?php echo ($verStatus === 'active') ? 'btn-toggle-lock' : 'btn-toggle-active'; ?>" 
                                            title="<?php echo ($verStatus === 'active') ? 'Lock APK' : 'Unlock APK'; ?>"
                                            id="btn_toggle_<?php echo str_replace('.', '_', $verName); ?>">
                                        <?php if ($verStatus === 'active'): ?>
                                            <!-- Lock Icon -->
                                            <svg viewBox="0 0 24 24"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg>
                                        <?php else: ?>
                                            <!-- Unlock Icon -->
                                            <svg viewBox="0 0 24 24"><path d="M12 17c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm6-9h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6h1.9c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm0 12H6V10h12v10z"/></svg>
                                        <?php endif; ?>
                                    </button>

                                    <!-- Delete Version Button -->
                                    <?php if ($verName !== 'global'): ?>
                                        <button onclick="deleteVersion('<?php echo htmlspecialchars($verName); ?>')" 
                                                class="btn-icon btn-delete" 
                                                title="Remove Version">
                                            <svg viewBox="0 0 24 24"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div id="toastContainer"></div>

    <script>
        // Helper to format Row IDs (escapes dots in CSS-friendly manner)
        function getRowId(ver) {
            return ver.replace(/\./g, '_');
        }

        // Show premium toast feedback notifications
        function showToast(message, type = 'success') {
            const container = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            
            let icon = '';
            if (type === 'success') {
                icon = `<svg style="width:20px;height:20px;fill:var(--accent-green)" viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>`;
            } else {
                icon = `<svg style="width:20px;height:20px;fill:var(--accent-red)" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>`;
            }

            toast.innerHTML = `
                ${icon}
                <span>${message}</span>
            `;
            container.appendChild(toast);
            
            setTimeout(() => {
                toast.style.animation = 'fadeIn 0.3s ease reverse forwards';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }

        // Add a new version
        function addVersion(e) {
            e.preventDefault();
            const input = document.getElementById('new_version');
            const version = input.value.trim();
            if (!version) return;

            const formData = new FormData();
            formData.append('add_version', '1');
            formData.append('version', version);

            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showToast(`Version ${data.version} registered successfully!`);
                    input.value = '';
                    
                    // Reload window or insert dynamically
                    setTimeout(() => window.location.reload(), 800);
                } else {
                    showToast(data.error || 'Failed to add version', 'error');
                }
            })
            .catch(err => {
                console.error(err);
                showToast('Network error occurred.', 'error');
            });
        }

        // Toggle Lock/Active status for a version
        function toggleVersionStatus(version) {
            const rowId = getRowId(version);
            const btn = document.getElementById(`btn_toggle_${rowId}`);
            if (btn) btn.disabled = true;

            const formData = new FormData();
            formData.append('toggle_version_status', '1');
            formData.append('version', version);

            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const badge = document.getElementById(`ver_badge_${rowId}`);
                    badge.innerText = data.status.toUpperCase();
                    
                    if (data.status === 'active') {
                        badge.className = 'status-badge status-active-badge';
                        btn.className = 'btn-icon btn-toggle-lock';
                        btn.title = 'Lock APK';
                        btn.innerHTML = '<svg viewBox="0 0 24 24"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg>';
                        showToast(`Version ${version} is now UNLOCKED (Active).`);
                    } else {
                        badge.className = 'status-badge status-locked-badge';
                        btn.className = 'btn-icon btn-toggle-active';
                        btn.title = 'Unlock APK';
                        btn.innerHTML = '<svg viewBox="0 0 24 24"><path d="M12 17c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm6-9h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6h1.9c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm0 12H6V10h12v10z"/></svg>';
                        showToast(`Version ${version} is now LOCKED (Crash on Open).`, 'error');
                    }
                    
                    // If toggling the global version, reload the header widget quickly
                    if (version === 'global') {
                        setTimeout(() => window.location.reload(), 1000);
                    }
                } else {
                    showToast(data.error || 'Failed to toggle status', 'error');
                }
            })
            .catch(err => {
                console.error(err);
                showToast('Network error occurred.', 'error');
            })
            .finally(() => {
                if (btn) btn.disabled = false;
            });
        }

        // Delete a version
        function deleteVersion(version) {
            if (version === 'global') return;
            if (!confirm(`Are you sure you want to remove version ${version} from protection control?`)) return;

            const rowId = getRowId(version);
            const formData = new FormData();
            formData.append('delete_version', '1');
            formData.append('version', version);

            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showToast(`Version ${version} has been removed.`);
                    const row = document.getElementById(`ver_row_${rowId}`);
                    if (row) {
                        row.style.animation = 'scaleIn 0.3s ease reverse forwards';
                        setTimeout(() => {
                            row.remove();
                            // Reload to update stats count
                            window.location.reload();
                        }, 300);
                    }
                } else {
                    showToast(data.error || 'Failed to delete version', 'error');
                }
            })
            .catch(err => {
                console.error(err);
                showToast('Network error occurred.', 'error');
            });
        }
    </script>
</body>
</html>
