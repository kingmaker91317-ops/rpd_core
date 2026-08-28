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
    <title>Account Settings | RapidCore</title>
    
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
                
                <a href="<?= site_url('settings') ?>" class="sidebar-link active flex items-center px-3 py-3 rounded-lg text-slate-300 group">
                    <i class="fas fa-cog w-6 text-center mr-2 text-indigo-400 transition-colors"></i>
                    <span class="font-medium">Settings</span>
                </a>
                
                <?php if ($user->uplink == 'PROFESSOR' || $user->level == 1 || $user->level == 2) : ?>
                <a href="<?= site_url('admin/manage-users') ?>" class="sidebar-link flex items-center px-3 py-3 rounded-lg text-slate-300 group">
                    <i class="fas fa-users w-6 text-center mr-2 text-slate-400 group-hover:text-indigo-400 transition-colors"></i>
                    <span class="font-medium">Manage Users</span>
                </a>
                <a href="<?= site_url('admin/create-referral') ?>" class="sidebar-link flex items-center px-3 py-3 rounded-lg text-slate-300 group">
                    <i class="fas fa-user-plus w-6 text-center mr-2 text-slate-400 group-hover:text-indigo-400 transition-colors"></i>
                    <span class="font-medium">CREATE NEW USER</span>
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
                <h2 class="text-2xl font-bold text-white">Account Settings</h2>
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
            <div class="max-w-5xl mx-auto card-enter">
                
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

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    
                    <!-- Change Password Card -->
                    <div class="glass-panel rounded-3xl p-6 md:p-8">
                        <div class="flex items-center gap-4 mb-8">
                            <div class="w-12 h-12 rounded-2xl bg-indigo-500/10 flex items-center justify-center text-indigo-400 border border-indigo-500/20">
                                <i class="fas fa-lock text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-white">Change Password</h3>
                                <p class="text-slate-400 text-xs">Update your security credentials</p>
                            </div>
                        </div>

                        <?= form_open() ?>
                        <input type="hidden" name="password_form" value="1">
                        
                        <div class="space-y-6">
                            <div class="space-y-2">
                                <label class="text-xs font-bold text-slate-400 uppercase tracking-wider ml-1">Current Password</label>
                                <div class="relative group">
                                    <i class="fas fa-shield absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 group-focus-within:text-indigo-400 transition-colors"></i>
                                    <input type="password" name="current" id="current" placeholder="Enter current password"
                                        class="w-full bg-slate-800/50 border border-white/10 rounded-2xl py-3 pl-12 pr-4 text-white focus:outline-none focus:border-indigo-500 transition-all placeholder-slate-600">
                                </div>
                                <?php if ($validation->hasError('current')) : ?>
                                    <p class="text-[11px] text-red-400 ml-1"><?= $validation->getError('current') ?></p>
                                <?php endif; ?>
                            </div>

                            <div class="space-y-2">
                                <label class="text-xs font-bold text-slate-400 uppercase tracking-wider ml-1">New Password</label>
                                <div class="relative group">
                                    <i class="fas fa-key absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 group-focus-within:text-indigo-400 transition-colors"></i>
                                    <input type="password" name="password" id="password" placeholder="Min 6 characters"
                                        class="w-full bg-slate-800/50 border border-white/10 rounded-2xl py-3 pl-12 pr-4 text-white focus:outline-none focus:border-indigo-500 transition-all placeholder-slate-600">
                                </div>
                                <?php if ($validation->hasError('password')) : ?>
                                    <p class="text-[11px] text-red-400 ml-1"><?= $validation->getError('password') ?></p>
                                <?php endif; ?>
                            </div>

                            <div class="space-y-2">
                                <label class="text-xs font-bold text-slate-400 uppercase tracking-wider ml-1">Confirm New Password</label>
                                <div class="relative group">
                                    <i class="fas fa-check-double absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 group-focus-within:text-indigo-400 transition-colors"></i>
                                    <input type="password" name="password2" id="password2" placeholder="Repeat new password"
                                        class="w-full bg-slate-800/50 border border-white/10 rounded-2xl py-3 pl-12 pr-4 text-white focus:outline-none focus:border-indigo-500 transition-all placeholder-slate-600">
                                </div>
                                <?php if ($validation->hasError('password2')) : ?>
                                    <p class="text-[11px] text-red-400 ml-1"><?= $validation->getError('password2') ?></p>
                                <?php endif; ?>
                            </div>

                            <button type="submit" class="w-full py-4 rounded-2xl bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-bold hover:shadow-lg hover:shadow-indigo-500/25 transition-all text-sm uppercase tracking-widest mt-4">
                                Change Password
                            </button>
                        </div>
                        <?= form_close() ?>
                    </div>

                    <!-- Account Information Card -->
                    <div class="glass-panel rounded-3xl p-6 md:p-8">
                        <div class="flex items-center gap-4 mb-8">
                            <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 flex items-center justify-center text-emerald-400 border border-emerald-500/20">
                                <i class="fas fa-user-circle text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-white">Profile Details</h3>
                                <p class="text-slate-400 text-xs">Manage your personal identification</p>
                            </div>
                        </div>

                        <?= form_open() ?>
                        <input type="hidden" name="fullname_form" value="1">
                        
                        <div class="space-y-6">
                            <div class="space-y-2">
                                <label class="text-xs font-bold text-slate-400 uppercase tracking-wider ml-1">Full Name</label>
                                <div class="relative group">
                                    <i class="fas fa-id-card absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 group-focus-within:text-emerald-400 transition-colors"></i>
                                    <input type="text" name="fullname" id="fullname" value="<?= old('fullname') ?: ($user->fullname ?: '') ?>" placeholder="Your display name"
                                        class="w-full bg-slate-800/50 border border-white/10 rounded-2xl py-3 pl-12 pr-4 text-white focus:outline-none focus:border-emerald-500 transition-all placeholder-slate-600">
                                </div>
                                <?php if ($validation->hasError('fullname')) : ?>
                                    <p class="text-[11px] text-red-400 ml-1"><?= $validation->getError('fullname') ?></p>
                                <?php endif; ?>
                            </div>

                            <div class="space-y-2">
                                <label class="text-xs font-bold text-slate-400 uppercase tracking-wider ml-1">Seller Key</label>
                                <div class="flex items-center gap-2">
                                    <div class="relative group flex-1">
                                        <input type="password" id="seller_key" value="<?= $user->seller_key ?>" readonly
                                            class="w-full bg-slate-800/50 border border-white/10 rounded-xl py-3 px-4 text-white font-mono text-sm focus:outline-none focus:border-indigo-500 transition-all">
                                    </div>
                                    <button type="button" onclick="toggleSellerKey()" class="w-12 h-12 flex items-center justify-center rounded-xl bg-slate-800/80 border border-indigo-500/20 text-slate-400 hover:text-white hover:bg-slate-700 transition-all ring-1 ring-indigo-500/30">
                                        <i class="fas fa-eye-slash" id="keyIcon"></i>
                                    </button>
                                    <button type="button" onclick="copySellerKey()" class="w-12 h-12 flex items-center justify-center rounded-xl bg-slate-800/80 border border-white/5 text-slate-400 hover:text-white hover:bg-slate-700 transition-all">
                                        <i class="far fa-copy"></i>
                                    </button>
                                </div>
                                <p class="text-[11px] text-slate-500 mt-2 ml-1"><i class="fas fa-robot text-indigo-400"></i> Send <code class="bg-slate-800 px-1.5 py-0.5 rounded text-indigo-300">/start <?= $user->seller_key ?></code> to the Telegram bot to securely link your account</p>
                                
                                <div class="pt-4 mt-4 border-t border-white/5 flex items-center justify-between">
                                    <div class="flex flex-col">
                                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">API Management</span>
                                        <p class="text-[10px] text-slate-500">Regenerate your secret key</p>
                                    </div>
                                    <a href="<?= site_url('settings/' . $user->username . '/api/reset') ?>" 
                                       onclick="return confirm('Are you sure you want to reset your API key? This will invalidate your current connection with the Telegram bot.')"
                                       class="px-4 py-2 rounded-xl bg-red-500/10 text-red-400 border border-red-500/20 hover:bg-red-500/20 transition-all text-[10px] font-bold uppercase tracking-wider flex items-center gap-2">
                                        <i class="fas fa-sync-alt text-xs"></i>
                                        Reset API Key
                                    </a>
                                </div>
                            </div>

                            <div class="p-6 rounded-2xl bg-slate-800/30 border border-white/5 space-y-4">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-slate-400 font-medium">Username</span>
                                    <span class="text-xs text-white font-mono font-bold"><?= $user->username ?></span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-slate-400 font-medium">Access Level</span>
                                    <span class="text-[10px] px-2 py-0.5 rounded-full bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 font-bold uppercase tracking-wider">
                                        <?= $user->level == 1 ? 'Owner' : ($user->level == 2 ? 'Admin' : 'Reseller') ?>
                                    </span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-slate-400 font-medium">Uplink</span>
                                    <span class="text-xs text-slate-400 font-medium italic"><?= $user->uplink ?: 'None' ?></span>
                                </div>
                            </div>

                            <button type="submit" class="w-full py-4 rounded-2xl bg-gradient-to-r from-emerald-600 to-teal-600 text-white font-bold hover:shadow-lg hover:shadow-emerald-500/25 transition-all text-sm uppercase tracking-widest mt-4">
                                Update Profile
                            </button>
                        </div>
                        <?= form_close() ?>
                    </div>

                </div>

                <!-- ══════════════════════════════════════════════════ -->
                <!-- TELEGRAM BOT SETUP CARD                           -->
                <!-- ══════════════════════════════════════════════════ -->
                <div class="mt-8 glass-panel rounded-3xl p-6 md:p-8 card-enter" id="telegram-bot-section">
                    <div class="flex items-center gap-4 mb-8">
                        <div class="w-12 h-12 rounded-2xl bg-sky-500/10 flex items-center justify-center text-sky-400 border border-sky-500/20">
                            <i class="fab fa-telegram text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-white">My Telegram Reset Bot</h3>
                            <p class="text-slate-400 text-xs">Connect your own Telegram bot — only your keys will be reset</p>
                        </div>
                    </div>

                    <!-- Step guide -->
                    <div class="mb-6 p-4 rounded-2xl bg-sky-500/5 border border-sky-500/10 space-y-2">
                        <p class="text-xs font-bold text-sky-400 uppercase tracking-wider mb-3">How to setup</p>
                        <div class="flex items-start gap-3 text-xs text-slate-400">
                            <span class="w-5 h-5 rounded-full bg-sky-500/20 text-sky-400 flex-shrink-0 flex items-center justify-center text-[10px] font-bold">1</span>
                            <span>Open <a href="https://t.me/BotFather" target="_blank" class="text-sky-400 hover:underline">@BotFather</a> in Telegram → create a new bot with <code class="bg-slate-800 px-1 rounded">/newbot</code></span>
                        </div>
                        <div class="flex items-start gap-3 text-xs text-slate-400">
                            <span class="w-5 h-5 rounded-full bg-sky-500/20 text-sky-400 flex-shrink-0 flex items-center justify-center text-[10px] font-bold">2</span>
                            <span>Copy the <b class="text-white">HTTP API Token</b> from BotFather and paste it below</span>
                        </div>
                        <div class="flex items-start gap-3 text-xs text-slate-400">
                            <span class="w-5 h-5 rounded-full bg-sky-500/20 text-sky-400 flex-shrink-0 flex items-center justify-center text-[10px] font-bold">3</span>
                            <span>Click <b class="text-white">Save Token</b>, then <b class="text-white">Register Webhook</b> — done! Your bot is live.</span>
                        </div>
                    </div>

                    <div class="space-y-5">
                        <!-- Token Input -->
                        <div class="space-y-2">
                            <label class="text-xs font-bold text-slate-400 uppercase tracking-wider ml-1">Bot Token (from BotFather)</label>
                            <div class="flex items-center gap-2">
                                <div class="relative group flex-1">
                                    <i class="fab fa-telegram absolute left-4 top-1/2 -translate-y-1/2 text-sky-500"></i>
                                    <input type="text" id="tg_bot_token"
                                        value="<?= htmlspecialchars($user->telegram_bot_token ?? '') ?>"
                                        placeholder="123456789:ABCDefGhIjKlMnOpQrStUvWxYz..."
                                        class="w-full bg-slate-800/50 border border-white/10 rounded-2xl py-3 pl-12 pr-4 text-white font-mono text-xs focus:outline-none focus:border-sky-500 transition-all placeholder-slate-600">
                                </div>
                            </div>
                        </div>

                        <!-- Owner Telegram ID Input -->
                        <div class="space-y-2">
                            <label class="text-xs font-bold text-slate-400 uppercase tracking-wider ml-1">Your Telegram User / Chat ID (For Unlimited Resets)</label>
                            <div class="relative group">
                                <i class="fas fa-id-badge absolute left-4 top-1/2 -translate-y-1/2 text-sky-500"></i>
                                <input type="text" id="tg_owner_id"
                                    value="<?= htmlspecialchars($user->telegram_id ?? '') ?>"
                                    placeholder="e.g. 123456789 (Send a message to @userinfobot to get your ID)"
                                    class="w-full bg-slate-800/50 border border-white/10 rounded-2xl py-3 pl-12 pr-4 text-white font-mono text-xs focus:outline-none focus:border-sky-500 transition-all placeholder-slate-600">
                            </div>
                            <p class="text-[10px] text-slate-500 ml-1"><i class="fas fa-shield-alt text-sky-400"></i> <b>Owner ID</b> = Unlimited resets. Other users = Max 2 resets/day per key.</p>
                            <!-- Test result -->
                            <div id="tg_test_result" class="hidden mt-2 px-4 py-2 rounded-xl text-xs font-medium"></div>
                        </div>

                        <!-- Action Buttons Row -->
                        <div class="flex flex-wrap gap-3">
                            <!-- Test -->
                            <button type="button" id="tg_test_btn" onclick="testTelegramToken()"
                                class="px-5 py-2.5 rounded-xl bg-slate-800 border border-white/10 text-slate-300 hover:text-white hover:border-sky-500/40 transition-all text-xs font-bold uppercase tracking-wider flex items-center gap-2">
                                <i class="fas fa-plug text-sky-400"></i> Test Token
                            </button>
                            <!-- Save -->
                            <button type="button" id="tg_save_btn" onclick="saveTelegramToken()"
                                class="px-5 py-2.5 rounded-xl bg-sky-600 hover:bg-sky-500 text-white transition-all text-xs font-bold uppercase tracking-wider flex items-center gap-2">
                                <i class="fas fa-save"></i> Save Token
                            </button>
                            <!-- Clear -->
                            <button type="button" onclick="clearTelegramToken()"
                                class="px-5 py-2.5 rounded-xl bg-red-500/10 border border-red-500/20 text-red-400 hover:bg-red-500/20 transition-all text-xs font-bold uppercase tracking-wider flex items-center gap-2">
                                <i class="fas fa-trash"></i> Clear
                            </button>
                        </div>

                        <!-- Webhook URL + Register -->
                        <div class="pt-5 border-t border-white/5">
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider ml-1 mb-3">Your Webhook URL</p>
                            <div class="flex items-center gap-2">
                                <div class="relative flex-1">
                                    <i class="fas fa-link absolute left-4 top-1/2 -translate-y-1/2 text-slate-500"></i>
                                    <input type="text" id="tg_webhook_url" readonly
                                        value="<?= rtrim(base_url(), '/') ?>/webhook/<?= htmlspecialchars($user->seller_key ?? '') ?>"
                                        class="w-full bg-slate-900/60 border border-white/5 rounded-2xl py-3 pl-12 pr-4 text-slate-400 font-mono text-xs focus:outline-none cursor-text select-all">
                                </div>
                                <button type="button" onclick="copyWebhookUrl()" title="Copy"
                                    class="w-12 h-12 flex items-center justify-center rounded-xl bg-slate-800 border border-white/5 text-slate-400 hover:text-white hover:bg-slate-700 transition-all flex-shrink-0">
                                    <i class="far fa-copy" id="tg_copy_icon"></i>
                                </button>
                            </div>
                            <div class="mt-4">
                                <button type="button" id="tg_register_btn" onclick="registerWebhook()"
                                    class="w-full py-3 rounded-2xl bg-gradient-to-r from-sky-600 to-indigo-600 text-white font-bold hover:shadow-lg hover:shadow-sky-500/25 transition-all text-sm uppercase tracking-widest flex items-center justify-center gap-2">
                                    <i class="fas fa-satellite-dish"></i> Register Webhook Automatically
                                </button>
                                <p class="text-[10px] text-slate-500 mt-2 ml-1">This will tell Telegram to send all messages from your bot to this URL</p>
                            </div>
                            <!-- Register result -->
                            <div id="tg_register_result" class="hidden mt-3 px-4 py-2 rounded-xl text-xs font-medium"></div>
                        </div>

                        <!-- Current status badge -->
                        <?php if (!empty($user->telegram_bot_token)): ?>
                        <div class="flex items-center gap-2 p-3 rounded-xl bg-emerald-500/5 border border-emerald-500/10">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            <span class="text-xs text-emerald-400 font-medium">Bot token is configured</span>
                        </div>
                        <?php else: ?>
                        <div class="flex items-center gap-2 p-3 rounded-xl bg-slate-800/40 border border-white/5">
                            <span class="w-2 h-2 rounded-full bg-slate-500"></span>
                            <span class="text-xs text-slate-500 font-medium">No bot token configured yet</span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Reset Links Section -->
                <div class="mt-8 glass-panel rounded-3xl p-6 md:p-8 card-enter">
                    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-2xl bg-purple-500/10 flex items-center justify-center text-purple-400 border border-purple-500/20">
                                <i class="fas fa-link text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-white">Reset Links</h3>
                                <p class="text-slate-400 text-xs">Create temporary or permanent links for remote HWID resets</p>
                            </div>
                        </div>
                        <button type="button" onclick="openResetModal()" class="px-6 py-3 rounded-xl bg-gradient-to-r from-purple-600 to-indigo-600 text-white font-bold hover:shadow-lg hover:shadow-purple-500/25 transition-all text-xs uppercase tracking-widest flex items-center gap-2">
                            <i class="fas fa-plus"></i>
                            Create Reset Link
                        </button>
                    </div>
                </div>

                <!-- Reset Links List -->
                <?php if (!empty($reset_links)) : ?>
                <div class="mt-8 space-y-4 card-enter">
                    <div class="px-6 flex items-center justify-between">
                        <h4 class="text-xs font-bold text-slate-500 uppercase tracking-widest">Active Reset Links</h4>
                        <span class="px-2 py-0.5 rounded-md bg-slate-800 text-slate-400 text-[10px] font-bold"><?= count($reset_links) ?> Links</span>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <?php foreach ($reset_links as $link) : ?>
                        <?php 
                            $isExpired = $link['expires_at'] && strtotime($link['expires_at']) < time();
                            $statusLabel = $isExpired ? 'Expired' : 'Active';
                            $statusClass = $isExpired ? 'bg-red-500/10 text-red-400 border-red-500/20' : 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20';
                            $resetUrl = site_url('reset?token=' . $link['token']);
                        ?>
                        <div class="glass-panel rounded-2xl p-5 border-white/5 hover:border-purple-500/30 transition-all group">
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-purple-500/10 flex items-center justify-center text-purple-400">
                                        <i class="fas fa-link"></i>
                                    </div>
                                    <div>
                                        <h5 class="text-white font-bold text-sm">Reset Link</h5>
                                        <span class="text-[10px] px-2 py-0.5 rounded-full border <?= $statusClass ?> font-bold uppercase tracking-tighter">
                                            <?= $statusLabel ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="flex gap-2">
                                    <button onclick="copySpecificUrl('<?= $resetUrl ?>', this)" class="w-8 h-8 rounded-lg bg-slate-800 text-slate-400 hover:text-white transition-colors flex items-center justify-center">
                                        <i class="far fa-copy text-xs"></i>
                                    </button>
                                    <a href="<?= site_url('settings/delete-reset-link/' . $link['id']) ?>" 
                                       onclick="return confirm('Are you sure you want to delete this reset link?')"
                                       class="w-8 h-8 rounded-lg bg-red-500/10 text-red-400 hover:bg-red-500 hover:text-white transition-all flex items-center justify-center">
                                        <i class="fas fa-trash text-xs"></i>
                                    </a>
                                </div>
                            </div>
                            
                            <div class="space-y-3">
                                <div class="flex items-center gap-2 text-slate-400">
                                    <i class="far fa-calendar-alt text-[10px] w-4"></i>
                                    <span class="text-[10px] font-medium">Created: <?= date('d/m/Y, H:i:s', strtotime($link['created_at'])) ?></span>
                                </div>
                                <div class="flex items-center gap-2 text-slate-400">
                                    <i class="far fa-clock text-[10px] w-4"></i>
                                    <span class="text-[10px] font-medium">Expires: <?= $link['expires_at'] ? date('d/m/Y, H:i:s', strtotime($link['expires_at'])) : 'Never' ?></span>
                                </div>
                                
                                <div class="relative mt-4">
                                    <input type="text" value="<?= $resetUrl ?>" readonly 
                                        class="w-full bg-slate-900/50 border border-white/5 rounded-lg py-2 px-3 text-[10px] text-slate-400 font-mono focus:outline-none truncate pr-10">
                                    <div class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-600">
                                        <i class="fas fa-external-link-alt text-[8px]"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <div class="mt-12 text-center">
                    <p class="text-slate-500 text-[10px] uppercase font-bold tracking-[0.2em]">RapidCore Security v2.0</p>
                </div>
            </div>
        </div>
    </main>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.js" crossorigin="anonymous"></script>

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

        function toggleSellerKey() {
            const input = document.getElementById('seller_key');
            const icon = document.getElementById('keyIcon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            }
        }

        function copySellerKey() {
            const input = document.getElementById('seller_key');
            input.type = 'text'; // temporarily show to copy
            input.select();
            input.setSelectionRange(0, 99999); 
            document.execCommand("copy");
            input.type = 'password'; // hide again
            
            // Notification
            alert("Seller Key copied to clipboard!");
        }

        $(document).ready(function() {
            // Auto-hide alert boxes
            setTimeout(function() {
                $('.alert-box').fadeOut('slow');
            }, 5000);
        });

        // Reset Link Modal Functions
        function openResetModal() {
            $('#resetModal').removeClass('hidden').addClass('flex');
            $('#resetFormStep').show();
            $('#resetResultStep').hide();
        }

        function closeResetModal() {
            $('#resetModal').removeClass('flex').addClass('hidden');
        }

        function toggleExpiration() {
            const type = $('#linkType').val();
            if (type === 'temporary') {
                $('#expirationGroup').slideDown();
            } else {
                $('#expirationGroup').slideUp();
            }
        }

        function createResetLink() {
            const type = $('#linkType').val();
            const expiration = $('#expirationTime').val();
            const btn = $('#createBtn');
            const originalContent = btn.html();

            btn.html('<i class="fas fa-spinner fa-spin mr-2"></i> CREATING...').prop('disabled', true);

            $.post('<?= site_url('settings/create-reset-link') ?>', {
                <?= csrf_token() ?>: '<?= csrf_hash() ?>',
                type: type,
                expiration: expiration
            }, function(response) {
                btn.html(originalContent).prop('disabled', false);
                if (response.success) {
                    $('#resetUrl').val(response.url);
                    $('#expiresAtText').text(response.expires_at);
                    
                    if (response.expires_at === 'Permanent') {
                        $('#linkExpiryInfo').hide();
                    } else {
                        $('#linkExpiryInfo').show();
                    }

                    $('#resetFormStep').fadeOut(200, function() {
                        $('#resetResultStep').fadeIn(200);
                    });
                } else {
                    alert(response.message || 'Failed to create link');
                }
            }).fail(function() {
                btn.html(originalContent).prop('disabled', false);
                alert('Server error occurred');
            });
        }

        function copyResetUrl() {
            const input = document.getElementById('resetUrl');
            input.select();
            input.setSelectionRange(0, 99999);
            document.execCommand("copy");
            
            const copyBtn = $('#copyResetBtn');
            const originalIcon = copyBtn.html();
            copyBtn.html('<i class="fas fa-check"></i>').addClass('bg-emerald-500').removeClass('bg-indigo-600');
            
            setTimeout(() => {
                copyBtn.html(originalIcon).removeClass('bg-emerald-500').addClass('bg-indigo-600');
            }, 2000);
        }

        function copySpecificUrl(url, btnElement) {
            const tempInput = document.createElement("input");
            tempInput.value = url;
            document.body.appendChild(tempInput);
            tempInput.select();
            document.execCommand("copy");
            document.body.removeChild(tempInput);
            
            const icon = $(btnElement).find('i');
            const originalClass = icon.attr('class');
            
            icon.attr('class', 'fas fa-check text-emerald-400');
            $(btnElement).addClass('bg-emerald-500/10 border-emerald-500/20');
            
            setTimeout(() => {
                icon.attr('class', originalClass);
                $(btnElement).removeClass('bg-emerald-500/10 border-emerald-500/20');
            }, 2000);
        }

        /* ═══════════════════════════════════════════════════
           TELEGRAM BOT FUNCTIONS
        ═══════════════════════════════════════════════════ */
        function showTgResult(elId, msg, success) {
            const el = document.getElementById(elId);
            el.innerHTML = msg;
            el.className = success
                ? 'mt-2 px-4 py-2 rounded-xl text-xs font-medium bg-emerald-500/10 border border-emerald-500/20 text-emerald-400'
                : 'mt-2 px-4 py-2 rounded-xl text-xs font-medium bg-red-500/10 border border-red-500/20 text-red-400';
            el.classList.remove('hidden');
        }

        function setTgBtnLoading(id, loading) {
            const btn = document.getElementById(id);
            if (!btn) return;
            if (loading) {
                btn.dataset.orig = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Please wait...';
                btn.disabled = true;
            } else {
                btn.innerHTML = btn.dataset.orig || btn.innerHTML;
                btn.disabled = false;
            }
        }

        function testTelegramToken() {
            const token = document.getElementById('tg_bot_token').value.trim();
            if (!token) { alert('Please enter a bot token first.'); return; }

            setTgBtnLoading('tg_test_btn', true);
            $.post('<?= site_url('settings/test-telegram') ?>', {
                telegram_bot_token: token,
                '<?= csrf_token() ?>': '<?= csrf_hash() ?>'
            }, function(data) {
                setTgBtnLoading('tg_test_btn', false);
                showTgResult('tg_test_result', data.message, data.success);
            }, 'json').fail(function(xhr) {
                setTgBtnLoading('tg_test_btn', false);
                showTgResult('tg_test_result', '❌ Error: ' + (xhr.responseJSON ? xhr.responseJSON.message : 'Connection failed'), false);
            });
        }

        function saveTelegramToken() {
            const token   = document.getElementById('tg_bot_token').value.trim();
            const ownerId = document.getElementById('tg_owner_id').value.trim();
            setTgBtnLoading('tg_save_btn', true);
            $.post('<?= site_url('settings/save-telegram') ?>', {
                telegram_bot_token: token,
                telegram_id: ownerId,
                '<?= csrf_token() ?>': '<?= csrf_hash() ?>'
            }, function(data) {
                setTgBtnLoading('tg_save_btn', false);
                showTgResult('tg_test_result', data.success ? '✅ ' + data.message : '❌ ' + data.message, data.success);
                if (data.success) {
                    const badge = document.querySelector('#telegram-bot-section .flex.items-center.gap-2.p-3');
                    if (badge) {
                        badge.className = 'flex items-center gap-2 p-3 rounded-xl bg-emerald-500/5 border border-emerald-500/10';
                        badge.innerHTML = '<span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span><span class="text-xs text-emerald-400 font-medium">Bot token is configured</span>';
                    }
                }
            }, 'json').fail(function(xhr) {
                setTgBtnLoading('tg_save_btn', false);
                showTgResult('tg_test_result', '❌ Error: ' + (xhr.responseJSON ? xhr.responseJSON.message : 'Connection failed'), false);
            });
        }

        function clearTelegramToken() {
            if (!confirm('Clear your Telegram bot token?')) return;
            document.getElementById('tg_bot_token').value = '';
            saveTelegramToken();
        }

        function copyWebhookUrl() {
            const input = document.getElementById('tg_webhook_url');
            input.select();
            input.setSelectionRange(0, 99999);
            document.execCommand('copy');

            const icon = document.getElementById('tg_copy_icon');
            icon.className = 'fas fa-check text-emerald-400';
            setTimeout(() => { icon.className = 'far fa-copy'; }, 2000);
        }

        function registerWebhook() {
            setTgBtnLoading('tg_register_btn', true);
            const token = document.getElementById('tg_bot_token').value.trim();
            $.post('<?= site_url('settings/register-webhook') ?>', {
                telegram_bot_token: token,
                '<?= csrf_token() ?>': '<?= csrf_hash() ?>'
            }, function(data) {
                setTgBtnLoading('tg_register_btn', false);
                showTgResult('tg_register_result', data.success ? '✅ ' + data.message : '❌ ' + data.message, data.success);
            }, 'json').fail(function(xhr) {
                setTgBtnLoading('tg_register_btn', false);
                showTgResult('tg_register_result', '❌ Error: ' + (xhr.responseJSON ? xhr.responseJSON.message : 'Connection failed'), false);
            });
        }
    </script>

    <!-- Reset Link Modal -->
    <div id="resetModal" class="fixed inset-0 z-[100] hidden items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
        <div class="glass-panel w-full max-w-md rounded-3xl overflow-hidden shadow-2xl border-white/10 animate-in fade-in zoom-in duration-300">
            <!-- Modal Header -->
            <div class="p-6 border-b border-white/5 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-purple-500/10 flex items-center justify-center text-purple-400">
                        <i class="fas fa-link text-lg"></i>
                    </div>
                    <h3 class="text-lg font-bold text-white">Create Reset Link</h3>
                </div>
                <button onclick="closeResetModal()" class="text-slate-400 hover:text-white transition-colors">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <!-- Modal Body: Form -->
            <div id="resetFormStep" class="p-6 space-y-6">
                <p class="text-slate-400 text-sm">Generate a temporary or permanent link that allows users to reset their HWID without logging in.</p>
                
                <div class="space-y-2">
                    <label class="text-xs font-bold text-slate-400 uppercase tracking-wider ml-1">Link Type</label>
                    <select id="linkType" onchange="toggleExpiration()" class="w-full bg-slate-800 border border-white/10 rounded-2xl py-3 px-4 text-white focus:outline-none focus:border-purple-500 transition-all">
                        <option value="temporary">Temporary (with expiration)</option>
                        <option value="permanent">Permanent (no expiration)</option>
                    </select>
                </div>

                <div id="expirationGroup" class="space-y-2">
                    <label class="text-xs font-bold text-slate-400 uppercase tracking-wider ml-1">Expiration Time</label>
                    <select id="expirationTime" class="w-full bg-slate-800 border border-white/10 rounded-2xl py-3 px-4 text-white focus:outline-none focus:border-purple-500 transition-all">
                        <option value="1">1 Hour</option>
                        <option value="6">6 Hours</option>
                        <option value="12">12 Hours</option>
                        <option value="24" selected>24 Hours</option>
                        <option value="48">48 Hours</option>
                        <option value="168">7 Days</option>
                    </select>
                    <p class="text-[11px] text-slate-500 ml-1">The link will automatically expire after this period.</p>
                </div>

                <div class="flex gap-3 pt-2">
                    <button onclick="closeResetModal()" class="flex-1 py-3.5 rounded-2xl bg-slate-800 text-slate-300 font-bold hover:bg-slate-700 transition-all text-xs uppercase tracking-widest">
                        Cancel
                    </button>
                    <button id="createBtn" onclick="createResetLink()" class="flex-2 px-8 py-3.5 rounded-2xl bg-gradient-to-r from-purple-600 to-indigo-600 text-white font-bold hover:shadow-lg hover:shadow-purple-500/25 transition-all text-xs uppercase tracking-widest">
                        Create Link
                    </button>
                </div>
            </div>

            <!-- Modal Body: Success Result -->
            <div id="resetResultStep" class="p-8 space-y-6 hidden text-center">
                <div class="w-20 h-20 rounded-full bg-emerald-500/10 flex items-center justify-center text-emerald-400 mx-auto border border-emerald-500/20 mb-2">
                    <i class="fas fa-check text-4xl"></i>
                </div>
                
                <div>
                    <h4 class="text-2xl font-bold text-white mb-1">Link Created!</h4>
                    <p class="text-slate-400 text-sm">Your reset link has been generated successfully.</p>
                </div>

                <div class="space-y-4 pt-2">
                    <div class="space-y-2 text-left">
                        <label class="text-xs font-bold text-slate-400 uppercase tracking-wider ml-1">Link URL</label>
                        <div class="flex gap-2">
                            <input type="text" id="resetUrl" readonly class="flex-1 bg-slate-800 border border-white/10 rounded-xl py-3 px-4 text-white text-xs font-mono">
                            <button id="copyResetBtn" onclick="copyResetUrl()" class="w-12 h-12 flex items-center justify-center rounded-xl bg-indigo-600 text-white hover:bg-indigo-500 transition-all">
                                <i class="far fa-copy"></i>
                            </button>
                        </div>
                    </div>

                    <div id="linkExpiryInfo" class="p-4 rounded-2xl bg-indigo-500/5 border border-indigo-500/10 flex items-center gap-3 text-left">
                        <i class="fas fa-clock text-indigo-400"></i>
                        <div>
                            <p class="text-[10px] text-slate-400 uppercase font-bold tracking-wider">Temporary Link</p>
                            <p class="text-white text-xs">Expires on: <span id="expiresAtText" class="font-bold"></span></p>
                        </div>
                    </div>
                </div>

                <button onclick="closeResetModal()" class="w-full py-4 rounded-2xl bg-indigo-600 text-white font-bold hover:bg-indigo-500 transition-all text-xs uppercase tracking-widest mt-4">
                    Got it
                </button>
            </div>
        </div>
    </div>

</body>
</html>