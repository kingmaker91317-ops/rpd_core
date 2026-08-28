<?php
// Ensure session check
if (!session()->has('userid')) {
    return redirect()->to('login');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User | RapidCore</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" rel="stylesheet" crossorigin="anonymous">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Outfit', 'sans-serif'],
                        mono: ['Space Grotesk', 'monospace'],
                    },
                    animation: {
                        'blob': 'blob 10s infinite',
                    },
                    keyframes: {
                        blob: {
                            '0%': { transform: 'translate(0px, 0px) scale(1)' },
                            '33%': { transform: 'translate(30px, -50px) scale(1.1)' },
                            '66%': { transform: 'translate(-20px, 20px) scale(0.9)' },
                            '100%': { transform: 'translate(0px, 0px) scale(1)' }
                        }
                    }
                }
            }
        }
    </script>

    <style>
        body {
            background-color: #030712;
            color: #f8fafc;
        }
        
        /* Animated Background Blobs */
        .blob-1 { background: radial-gradient(circle, rgba(99,102,241,0.25) 0%, rgba(99,102,241,0) 70%); }
        .blob-2 { background: radial-gradient(circle, rgba(192,38,211,0.25) 0%, rgba(192,38,211,0) 70%); }
        .blob-3 { background: radial-gradient(circle, rgba(56,189,248,0.2) 0%, rgba(56,189,248,0) 70%); }
        
        .glass-panel {
            background: rgba(17, 24, 39, 0.6);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            box-shadow: inset 0 0 20px rgba(255, 255, 255, 0.02), 0 8px 32px rgba(0, 0, 0, 0.3);
        }

        .sidebar-link {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        
        .sidebar-link::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 0;
            background: linear-gradient(90deg, rgba(99, 102, 241, 0.15), transparent);
            transition: width 0.3s ease;
            z-index: 0;
        }

        .sidebar-link:hover::before, .sidebar-link.active::before {
            width: 100%;
        }
        
        .sidebar-link > * {
            position: relative;
            z-index: 1;
        }

        .sidebar-link:hover, .sidebar-link.active {
            color: white;
            border-right: 3px solid #a855f7;
            background: rgba(255, 255, 255, 0.05);
        }

        .card-enter {
            animation: slideIn 0.4s ease-out;
        }
        
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* CustomScrollbar */
        ::-webkit-scrollbar {
            width: 6px;
        }
        ::-webkit-scrollbar-track {
            background: transparent; 
        }
        ::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.1); 
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.2); 
        }
    </style>
</head>
<body class="flex h-screen overflow-hidden text-sm bg-[#030712] relative text-slate-200">

    <!-- Animated Background -->
    <div class="fixed inset-0 w-full h-full overflow-hidden pointer-events-none z-0">
        <div class="absolute top-[-10%] left-[-10%] w-[500px] h-[500px] blob-1 rounded-full mix-blend-screen filter blur-[80px] opacity-60 animate-blob"></div>
        <div class="absolute top-[20%] right-[-10%] w-[500px] h-[500px] blob-2 rounded-full mix-blend-screen filter blur-[80px] opacity-60 animate-blob" style="animation-delay: 2s;"></div>
        <div class="absolute bottom-[-20%] left-[20%] w-[600px] h-[600px] blob-3 rounded-full mix-blend-screen filter blur-[100px] opacity-50 animate-blob" style="animation-delay: 4s;"></div>
    </div>

    <!-- Mobile Sidebar Overlay -->
    <div id="sidebarOverlay" class="fixed inset-0 bg-black/60 z-40 hidden md:hidden glass-overlay backdrop-blur-sm" onclick="toggleSidebar()"></div>

    <!-- Sidebar -->
    <aside id="sidebar" class="fixed inset-y-0 left-0 z-50 w-64 glass-panel border-r border-white/5 transform -translate-x-full transition-transform duration-300 md:translate-x-0 md:static md:flex flex-col justify-between h-full">
        <div>
            <!-- Logo -->
            <div class="h-20 flex items-center px-6 border-b border-white/5">
                <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center mr-3 shadow-lg shadow-indigo-500/20">
                    <i class="fas fa-shield-alt text-white text-sm"></i>
                </div>
                <div>
                    <h1 class="font-bold text-white text-base leading-tight">RapidCore</h1>
                    <span class="text-[10px] text-slate-400 font-mono tracking-wider">LICENSE MANAGER</span>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="mt-6 px-3 space-y-1">
                <div class="px-3 mb-2 text-[10px] font-bold text-slate-500 uppercase tracking-wider">Main</div>
                
                <a href="<?= site_url('dashboard') ?>" class="sidebar-link flex items-center px-3 py-3 rounded-lg text-slate-300 group">
                    <i class="fas fa-chart-pie w-6 text-center mr-2 text-slate-400 group-hover:text-indigo-400 transition-colors"></i>
                    <span class="font-medium">Overview</span>
                </a>
                
                <a href="<?= site_url('keys/generate') ?>" class="sidebar-link flex items-center px-3 py-3 rounded-lg text-slate-300 group">
                    <i class="fas fa-bolt w-6 text-center mr-2 text-slate-400 group-hover:text-indigo-400 transition-colors"></i>
                    <span class="font-medium">Generate Keys</span>
                </a>
                
                <a href="<?= site_url('keys') ?>" class="sidebar-link flex items-center px-3 py-3 rounded-lg text-slate-300 group">
                    <i class="fas fa-key w-6 text-center mr-2 text-slate-400 group-hover:text-indigo-400 transition-colors"></i>
                    <span class="font-medium">License Manager</span>
                </a>

                <div class="px-3 mt-6 mb-2 text-[10px] font-bold text-slate-500 uppercase tracking-wider">Configuration</div>
                
                <a href="<?= site_url('settings') ?>" class="sidebar-link flex items-center px-3 py-3 rounded-lg text-slate-300 group">
                    <i class="fas fa-cog w-6 text-center mr-2 text-slate-400 group-hover:text-indigo-400 transition-colors"></i>
                    <span class="font-medium">Settings</span>
                </a>
                
                <?php if ($user->uplink == 'PROFESSOR' || $user->level == 1 || $user->level == 2) : ?>
                <a href="<?= site_url('admin/manage-users') ?>" class="sidebar-link active flex items-center px-3 py-3 rounded-lg text-slate-300 group">
                    <i class="fas fa-users w-6 text-center mr-2 text-indigo-400 transition-colors"></i>
                    <span class="font-medium">Manage Users</span>
                </a>

                <a href="<?= site_url('admin/create-referral') ?>" class="sidebar-link flex items-center px-3 py-3 rounded-lg text-slate-300 group">
                    <i class="fas fa-user-plus w-6 text-center mr-2 text-slate-400 group-hover:text-indigo-400 transition-colors"></i>
                    <span class="font-medium">Create User</span>
                </a>
                <?php if ($user->level == 1) : ?>
                <a href="<?= site_url('admin/server-management') ?>" class="sidebar-link flex items-center px-3 py-3 rounded-lg text-slate-300 group">
                    <i class="fas fa-server w-6 text-center mr-2 text-slate-400 group-hover:text-indigo-400 transition-colors"></i>
                    <span class="font-medium">Server Management</span>
                </a>
                <?php endif; ?>
                <?php endif; ?>
            </nav>
        </div>

        <!-- User Profile -->
        <div class="p-4 border-t border-white/5">
            <div class="flex items-center gap-3 mb-4 px-2">
                <div class="w-10 h-10 rounded-full bg-slate-700 flex items-center justify-center text-white font-bold border border-white/10">
                    <?= substr($user->username, 0, 1) ?>
                </div>
                <div class="overflow-hidden">
                    <h4 class="text-white font-medium truncate"><?= $user->username ?></h4>
                    <div class="flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        <span class="text-xs text-slate-400">Online</span>
                    </div>
                </div>
            </div>
            <a href="<?= site_url('logout') ?>" class="flex items-center justify-center w-full py-2.5 px-4 rounded-xl border border-red-500/20 bg-red-500/10 text-red-400 hover:bg-red-500/20 transition-all font-medium text-xs">
                <i class="fas fa-power-off mr-2"></i> LOGOUT SESSION
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col overflow-hidden relative">
        <!-- Top Bar -->
        <header class="h-20 flex items-center justify-between px-4 md:px-8 border-b border-white/5 bg-slate-900/50 backdrop-blur-sm z-10">
            <div class="flex items-center gap-3">
                <button onclick="toggleSidebar()" class="md:hidden p-2 text-white hover:bg-white/10 rounded-lg transition-colors">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <div class="flex items-center gap-3">
                    <a href="<?= site_url('admin/manage-users') ?>" class="w-10 h-10 rounded-xl bg-slate-800/50 flex items-center justify-center text-slate-400 hover:text-white transition-colors border border-white/10">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                    <div>
                        <h2 class="text-2xl font-bold text-white">Edit User</h2>
                        <p class="text-slate-400 text-xs mt-1">Management for: <span class="text-indigo-400 font-bold"><?= $target->username ?></span></p>
                    </div>
                </div>
            </div>
            
            <div class="flex items-center gap-4">
                <div class="glass-panel px-4 py-2 rounded-xl flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-amber-500/10 flex items-center justify-center text-amber-500">
                        <i class="fas fa-coins text-sm"></i>
                    </div>
                    <div class="text-right">
                        <p class="text-[10px] text-slate-400 uppercase font-bold tracking-wider">Balance</p>
                        <p class="text-white font-mono font-medium">₹<?= number_format($user->saldo, 2) ?></p>
                    </div>
                </div>
            </div>
        </header>

        <!-- Scrollable Content -->
        <div class="flex-1 overflow-y-auto p-4 md:p-8">
            <div class="max-w-4xl mx-auto card-enter">
                
                <!-- Page Messages -->
                <?php if (session()->getFlashdata('msgDanger')) : ?>
                    <div class="bg-red-500/10 border border-red-500/20 text-red-400 px-4 py-3 rounded-xl mb-6 text-sm flex items-center gap-2 alert-box">
                        <i class="fas fa-exclamation-circle"></i>
                        <?= session()->getFlashdata('msgDanger') ?>
                    </div>
                <?php elseif (session()->getFlashdata('msgSuccess')) : ?>
                    <div class="bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 px-4 py-3 rounded-xl mb-6 text-sm flex items-center gap-2 alert-box">
                        <i class="fas fa-check-circle"></i>
                        <?= session()->getFlashdata('msgSuccess') ?>
                    </div>
                <?php endif; ?>

                <div class="glass-panel rounded-3xl p-6 md:p-8">
                    <div class="flex items-center gap-4 mb-8">
                        <div class="w-12 h-12 rounded-2xl bg-indigo-500/10 flex items-center justify-center text-indigo-400 border border-indigo-500/20">
                            <i class="fas fa-user-edit text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-white">Account Information</h3>
                            <p class="text-slate-400 text-xs">Update account settings and access privileges</p>
                        </div>
                    </div>

                    <?= form_open() ?>
                    <input type="hidden" name="user_id" value="<?= $target->id_users ?>">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        
                        <!-- Username -->
                        <div class="space-y-2">
                            <label class="text-xs font-bold text-slate-400 uppercase tracking-wider ml-1">Account Username</label>
                            <div class="relative group">
                                <i class="fas fa-user absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 group-focus-within:text-indigo-400 transition-colors"></i>
                                <input type="text" name="username" id="username" value="<?= old('username') ?: $target->username ?>" 
                                    class="w-full bg-slate-800/50 border border-white/10 rounded-2xl py-3 pl-12 pr-4 text-white focus:outline-none focus:border-indigo-500 transition-all placeholder-slate-600">
                            </div>
                            <?php if ($validation->hasError('username')) : ?>
                                <p class="text-[11px] text-red-400 ml-1"><?= $validation->getError('username') ?></p>
                            <?php endif; ?>
                        </div>

                        <!-- Full Name -->
                        <div class="space-y-2">
                            <label class="text-xs font-bold text-slate-400 uppercase tracking-wider ml-1">Display Name</label>
                            <div class="relative group">
                                <i class="fas fa-id-card absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 group-focus-within:text-indigo-400 transition-colors"></i>
                                <input type="text" name="fullname" id="fullname" value="<?= old('fullname') ?: $target->fullname ?>" 
                                    class="w-full bg-slate-800/50 border border-white/10 rounded-2xl py-3 pl-12 pr-4 text-white focus:outline-none focus:border-indigo-500 transition-all placeholder-slate-600">
                            </div>
                            <?php if ($validation->hasError('fullname')) : ?>
                                <p class="text-[11px] text-red-400 ml-1"><?= $validation->getError('fullname') ?></p>
                            <?php endif; ?>
                        </div>

                        <!-- Roles -->
                        <div class="space-y-2">
                            <label class="text-xs font-bold text-slate-400 uppercase tracking-wider ml-1">Account Role</label>
                            <div class="relative group">
                                <i class="fas fa-user-shield absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 group-focus-within:text-indigo-400 transition-colors"></i>
                                <select name="level" id="level" class="w-full bg-slate-800/50 border border-white/10 rounded-2xl py-3 pl-12 pr-4 text-white appearance-none focus:outline-none focus:border-indigo-500 transition-all cursor-pointer">
                                    <?php if ($user->level == 1) : ?>
                                    <option value="1" <?= $target->level == 1 ? 'selected' : '' ?>>Owner (Full Control)</option>
                                    <option value="2" <?= $target->level == 2 ? 'selected' : '' ?>>Admin (Management)</option>
                                    <?php endif; ?>
                                    <option value="3" <?= $target->level == 3 ? 'selected' : '' ?>>Reseller (Limited)</option>
                                </select>
                                <i class="fas fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-slate-500 pointer-events-none text-xs"></i>
                            </div>
                        </div>

                        <!-- Status -->
                        <div class="space-y-2">
                            <label class="text-xs font-bold text-slate-400 uppercase tracking-wider ml-1">Account status</label>
                            <div class="relative group">
                                <i class="fas fa-circle-check absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 group-focus-within:text-indigo-400 transition-colors"></i>
                                <select name="status" id="status" class="w-full bg-slate-800/50 border border-white/10 rounded-2xl py-3 pl-12 pr-4 text-white appearance-none focus:outline-none focus:border-indigo-500 transition-all cursor-pointer">
                                    <option value="1" <?= $target->status == 1 ? 'selected' : '' ?>>Active (Enabled)</option>
                                    <option value="2" <?= $target->status == 2 ? 'selected' : '' ?>>Banned (Blocked)</option>
                                    <option value="3" <?= $target->status == 3 ? 'selected' : '' ?>>Expired (Lock)</option>
                                </select>
                                <i class="fas fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-slate-500 pointer-events-none text-xs"></i>
                            </div>
                        </div>

                        <!-- Saldo -->
                        <div class="space-y-2">
                            <label class="text-xs font-bold text-slate-400 uppercase tracking-wider ml-1">
                                Point Balance (₹)
                                <?php if ($user->level == 2): ?>
                                    <span class="ml-2 px-1.5 py-0.5 rounded text-[9px] font-black bg-amber-500/20 text-amber-400 border border-amber-500/30 uppercase tracking-wider">Max 30</span>
                                <?php endif; ?>
                            </label>
                            <div class="relative group">
                                <i class="fas fa-coins absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 group-focus-within:text-indigo-400 transition-colors"></i>
                                <input type="number" name="saldo" id="saldo" value="<?= old('saldo') ?: $target->saldo ?>" <?= ($user->level == 2) ? 'max="30"' : '' ?>
                                    class="w-full bg-slate-800/50 border border-white/10 rounded-2xl py-3 pl-12 pr-4 text-white focus:outline-none focus:border-indigo-500 transition-all font-mono">
                            </div>
                            <?php if ($user->level == 2): ?>
                                <p class="text-amber-400/70 text-[10px] ml-1 flex items-center gap-1">
                                    <i class="fas fa-info-circle"></i> Admins are restricted to a maximum of 30 points per reseller.
                                </p>
                            <?php endif; ?>
                            <?php if ($validation->hasError('saldo')) : ?>
                                <p class="text-[11px] text-red-400 ml-1"><?= $validation->getError('saldo') ?></p>
                            <?php endif; ?>
                        </div>

                        <!-- Uplink -->
                        <div class="space-y-2">
                            <label class="text-xs font-bold text-slate-400 uppercase tracking-wider ml-1">Uplink Reference</label>
                            <div class="relative group">
                                <i class="fas fa-link absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 group-focus-within:text-indigo-400 transition-colors"></i>
                                <input type="text" name="uplink" id="uplink" value="<?= old('uplink') ?: $target->uplink ?>" 
                                    class="w-full bg-slate-800/50 border border-white/10 rounded-2xl py-3 pl-12 pr-4 text-white focus:outline-none focus:border-indigo-500 transition-all">
                            </div>
                            <?php if ($validation->hasError('uplink')) : ?>
                                <p class="text-[11px] text-red-400 ml-1"><?= $validation->getError('uplink') ?></p>
                            <?php endif; ?>
                        </div>

                        <!-- Expiration -->
                        <div class="col-span-1 md:col-span-2 space-y-2">
                            <label class="text-xs font-bold text-slate-400 uppercase tracking-wider ml-1">Account Expiration Timestamp</label>
                            <div class="relative group">
                                <i class="fas fa-hourglass-half absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 group-focus-within:text-indigo-400 transition-colors"></i>
                                <input type="text" name="expiration" id="expiration" value="<?= old('expiration') ?: $target->expiration_date ?>" 
                                    class="w-full bg-slate-800/50 border border-white/10 rounded-2xl py-3 pl-12 pr-4 text-white focus:outline-none focus:border-indigo-500 transition-all font-mono">
                            </div>
                            <?php if ($validation->hasError('expiration')) : ?>
                                <p class="text-[11px] text-red-400 ml-1"><?= $validation->getError('expiration') ?></p>
                            <?php endif; ?>
                        </div>

                        <?php if ($user->level == 1) : ?>
                        <!-- Telegram Chat ID for 2FA OTP (Owner Only Control) -->
                        <div id="twoFactorChatIdWrapper" class="col-span-1 md:col-span-2 space-y-2" style="display: none;">
                            <label class="text-xs font-bold text-amber-400 uppercase tracking-wider ml-1 flex items-center gap-1.5">
                                <i class="fab fa-telegram"></i> 2-Step OTP Telegram Chat ID
                                <span class="px-2 py-0.5 rounded text-[9px] font-black bg-amber-500/20 text-amber-300 border border-amber-500/30 uppercase tracking-wider">Owner Controlled</span>
                            </label>
                            <div class="relative group">
                                <i class="fab fa-telegram-plane absolute left-4 top-1/2 -translate-y-1/2 text-amber-500 group-focus-within:text-amber-400 transition-colors"></i>
                                <input type="text" name="two_factor_chat_id" id="two_factor_chat_id" value="<?= old('two_factor_chat_id') ?: esc($target->two_factor_chat_id ?? '') ?>" placeholder="e.g. 123456789 (Leave empty to disable 2FA for this user)" 
                                    class="w-full bg-slate-800/50 border border-amber-500/30 rounded-2xl py-3 pl-12 pr-4 text-white focus:outline-none focus:border-amber-500 transition-all font-mono placeholder-slate-600">
                            </div>
                            <p class="text-[11px] text-slate-400 ml-1">
                                Used ONLY for sending 2-Step OTP codes when this Owner/Admin logs in. Set strictly by Owner.
                            </p>
                        </div>
                        <?php endif; ?>

                        <!-- Actions -->
                        <div class="col-span-1 md:col-span-2 pt-4 flex gap-4">
                            <button type="submit" class="flex-1 py-4 rounded-2xl bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-bold hover:shadow-lg hover:shadow-indigo-500/25 transition-all text-sm uppercase tracking-widest">
                                Update User Account
                            </button>
                            <a href="<?= site_url('admin/manage-users') ?>" class="py-4 px-8 rounded-2xl bg-slate-800/50 border border-white/10 text-slate-400 font-bold hover:text-white hover:bg-slate-700/50 transition-all text-sm uppercase tracking-widest text-center">
                                Cancel
                            </a>
                        </div>
                        
                        <!-- Danger Zone -->
                        <div class="col-span-1 md:col-span-2 pt-8 border-t border-red-500/20 mt-4">
                            <h4 class="text-red-400 font-bold mb-4 flex items-center gap-2"><i class="fas fa-exclamation-triangle"></i> Danger Zone</h4>
                            
                            <!-- Key Stats -->
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                                <div class="glass-panel p-4 rounded-xl border border-white/5 bg-white/5">
                                    <div class="text-[10px] text-slate-400 uppercase font-bold tracking-wider mb-1">Total Keys Created</div>
                                    <div class="text-xl font-bold font-mono text-indigo-400"><?= $stats['total_keys'] ?></div>
                                </div>
                                <div class="glass-panel p-4 rounded-xl border border-white/5 bg-white/5">
                                    <div class="text-[10px] text-slate-400 uppercase font-bold tracking-wider mb-1">Active Keys (Used)</div>
                                    <div class="text-xl font-bold font-mono text-emerald-400"><?= $stats['active_keys'] ?></div>
                                </div>
                                <div class="glass-panel p-4 rounded-xl border border-white/5 bg-white/5">
                                    <div class="text-[10px] text-slate-400 uppercase font-bold tracking-wider mb-1">Paused Keys</div>
                                    <div class="text-xl font-bold font-mono text-amber-400"><?= $stats['paused_keys'] ?></div>
                                </div>
                            </div>

                            <div class="space-y-4">
                                <!-- Delete All User Keys -->
                                <div class="glass-panel p-6 rounded-2xl border border-red-500/20 bg-red-500/5">
                                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                        <div>
                                            <h5 class="text-white font-bold">Delete All User Keys</h5>
                                            <p class="text-slate-400 text-xs mt-1">Permanently delete all keys generated by <strong><?= $target->username ?></strong>.</p>
                                        </div>
                                        <button type="button" id="deleteUserKeysBtn" class="px-6 py-3 rounded-xl bg-red-500/10 text-red-400 border border-red-500/50 hover:bg-red-500 hover:text-white transition-all text-sm font-bold flex items-center gap-2">
                                            <i class="fas fa-trash"></i> Delete Keys
                                        </button>
                                    </div>
                                </div>

                                <?php if ($user->level == 1) : ?>
                                <!-- Pause / Resume User Keys -->
                                <div class="glass-panel p-6 rounded-2xl border border-amber-500/20 bg-amber-500/5">
                                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                        <div>
                                            <h5 class="text-white font-bold">Pause / Resume User Keys</h5>
                                            <p class="text-slate-400 text-xs mt-1">Pause or resume all license keys generated by <strong><?= $target->username ?></strong>.</p>
                                        </div>
                                        <div class="flex gap-3">
                                            <button type="button" id="pauseUserKeysBtn" class="px-5 py-3 rounded-xl bg-amber-500/10 text-amber-400 border border-amber-500/50 hover:bg-amber-500 hover:text-white transition-all text-sm font-bold flex items-center gap-2">
                                                <i class="fas fa-pause"></i> Pause Keys
                                            </button>
                                            <button type="button" id="resumeUserKeysBtn" class="px-5 py-3 rounded-xl bg-emerald-500/10 text-emerald-400 border border-emerald-500/50 hover:bg-emerald-500 hover:text-white transition-all text-sm font-bold flex items-center gap-2">
                                                <i class="fas fa-play"></i> Resume Keys
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?= form_close() ?>
                </div>

                <!-- Footer Hint -->
                <div class="mt-8 text-center px-4">
                    <p class="text-slate-500 text-[10px] uppercase font-bold tracking-[0.2em]">RapidCoreSecurity Management v4.2</p>
                </div>
            </div>
        </div>
    </main>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.js" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/11.1.0/sweetalert2.all.min.js"></script>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            
            if (sidebar.classList.contains('-translate-x-full')) {
                sidebar.classList.remove('-translate-x-full');
                overlay.classList.remove('hidden');
            } else {
                sidebar.classList.add('-translate-x-full');
                overlay.classList.add('hidden');
            }
        }

        $(document).ready(function() {
            // Auto-hide alert boxes
            setTimeout(function() {
                $('.alert-box').fadeOut('slow');
            }, 5000);

            function toggle2FAVisibility() {
                const level = $('#level').val();
                if (level == '1' || level == '2') {
                    $('#twoFactorChatIdWrapper').slideDown();
                } else {
                    $('#twoFactorChatIdWrapper').slideUp();
                }
            }

            $('#level').on('change', toggle2FAVisibility);
            toggle2FAVisibility();

            $('#deleteUserKeysBtn').click(function() {
                Swal.fire({
                    title: 'Delete All Keys?',
                    text: "WARNING: This will permanently delete all keys created by <?= $target->username ?>!",
                    icon: 'error',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Yes, Delete',
                    background: '#1e293b',
                    color: '#fff'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.getJSON("<?= site_url('admin/delete-user-keys/'.$target->id_users) ?>", {}, function(data) {
                            if (data.success) {
                                Swal.fire({ title: 'Deleted', text: data.message, icon: 'success', background: '#1e293b', color: '#fff' }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire({ title: 'Error', text: data.message, icon: 'error', background: '#1e293b', color: '#fff' });
                            }
                        });
                    }
                });
            });

            $('#pauseUserKeysBtn').click(function() {
                Swal.fire({
                    title: 'Pause All Keys?',
                    text: "This will pause all keys created by <?= $target->username ?>!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#f59e0b',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Yes, Pause',
                    background: '#1e293b',
                    color: '#fff'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.getJSON("<?= site_url('admin/pause-user-keys/'.$target->id_users) ?>", {}, function(data) {
                            if (data.success) {
                                Swal.fire({ title: 'Paused', text: data.message, icon: 'success', background: '#1e293b', color: '#fff' }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire({ title: 'Error', text: data.message, icon: 'error', background: '#1e293b', color: '#fff' });
                            }
                        });
                    }
                });
            });

            $('#resumeUserKeysBtn').click(function() {
                Swal.fire({
                    title: 'Resume All Keys?',
                    text: "This will resume all keys created by <?= $target->username ?>!",
                    icon: 'info',
                    showCancelButton: true,
                    confirmButtonColor: '#10b981',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Yes, Resume',
                    background: '#1e293b',
                    color: '#fff'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.getJSON("<?= site_url('admin/unpause-user-keys/'.$target->id_users) ?>", {}, function(data) {
                            if (data.success) {
                                Swal.fire({ title: 'Resumed', text: data.message, icon: 'success', background: '#1e293b', color: '#fff' }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire({ title: 'Error', text: data.message, icon: 'error', background: '#1e293b', color: '#fff' });
                            }
                        });
                    }
                });
            });
        });
    </script>
</body>
</html>