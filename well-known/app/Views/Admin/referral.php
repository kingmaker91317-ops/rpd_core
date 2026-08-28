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
    <title>Create User | RapidCore</title>
    
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

        /* Select option colors */
        select option {
            background-color: #111827 !important;
            color: #f8fafc !important;
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
                
                <a href="<?= site_url('admin/manage-users') ?>" class="sidebar-link flex items-center px-3 py-3 rounded-lg text-slate-300 group">
                    <i class="fas fa-users w-6 text-center mr-2 text-slate-400 group-hover:text-indigo-400 transition-colors"></i>
                    <span class="font-medium">Manage Users</span>
                </a>

                <a href="<?= site_url('admin/create-referral') ?>" class="sidebar-link active flex items-center px-3 py-3 rounded-lg text-slate-300 group">
                    <i class="fas fa-user-plus w-6 text-center mr-2 text-indigo-400 transition-colors"></i>
                    <span class="font-medium">Create User</span>
                </a>
                <?php if ($user->level == 1) : ?>
                <a href="<?= site_url('admin/server-management') ?>" class="sidebar-link flex items-center px-3 py-3 rounded-lg text-slate-300 group">
                    <i class="fas fa-server w-6 text-center mr-2 text-slate-400 group-hover:text-indigo-400 transition-colors"></i>
                    <span class="font-medium">Server Management</span>
                </a>
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
                    <h2 class="text-2xl font-bold text-white">Create New User</h2>
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
            <div class="max-w-xl mx-auto card-enter">
                
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
                            <i class="fas fa-user-plus text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-white">Registration</h3>
                            <p class="text-slate-400 text-xs">Provision a new account on the network</p>
                        </div>
                    </div>

                    <?= form_open() ?>
                    
                    <div class="space-y-6">
                        <!-- Username -->
                        <div class="space-y-2">
                            <label class="text-xs font-bold text-slate-400 uppercase tracking-wider ml-1">Account Username</label>
                            <div class="relative group">
                                <i class="fas fa-user-circle absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 group-focus-within:text-indigo-400 transition-colors"></i>
                                <input type="text" name="username" id="username" placeholder="Enter unique username" value="<?= old('username') ?>"
                                    class="w-full bg-slate-800/50 border border-white/10 rounded-2xl py-3 pl-12 pr-4 text-white focus:outline-none focus:border-indigo-500 transition-all placeholder-slate-600">
                            </div>
                            <?php if ($validation->hasError('username')) : ?>
                                <p class="text-[11px] text-red-400 ml-1"><?= $validation->getError('username') ?></p>
                            <?php endif; ?>
                        </div>

                        <!-- Password -->
                        <div class="space-y-2">
                            <label class="text-xs font-bold text-slate-400 uppercase tracking-wider ml-1">Initial Password</label>
                            <div class="relative group">
                                <i class="fas fa-key absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 group-focus-within:text-indigo-400 transition-colors"></i>
                                <input type="text" name="password" id="password" placeholder="Enter secure password"
                                    class="w-full bg-slate-800/50 border border-white/10 rounded-2xl py-3 pl-12 pr-4 text-white focus:outline-none focus:border-indigo-500 transition-all placeholder-slate-600 font-mono">
                            </div>
                            <?php if ($validation->hasError('password')) : ?>
                                <p class="text-[11px] text-red-400 ml-1"><?= $validation->getError('password') ?></p>
                            <?php endif; ?>
                        </div>

                        <!-- Saldo -->
                        <div class="space-y-2">
                            <label class="text-xs font-bold text-slate-400 uppercase tracking-wider ml-1">Initial Point Balance (₹)</label>
                            <div class="relative group">
                                <i class="fas fa-coins absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 group-focus-within:text-indigo-400 transition-colors"></i>
                                <input type="number" name="set_saldo" id="set_saldo" value="5" min="0"
                                    class="w-full bg-slate-800/50 border border-white/10 rounded-2xl py-3 pl-12 pr-4 text-white focus:outline-none focus:border-indigo-500 transition-all font-mono">
                            </div>
                            <p class="text-[10px] text-slate-500 ml-1">Default starting balance for new reseller</p>
                            <?php if ($validation->hasError('set_saldo')) : ?>
                                <p class="text-[11px] text-red-400 ml-1"><?= $validation->getError('set_saldo') ?></p>
                            <?php endif; ?>
                        </div>

                        <!-- Expiration -->
                        <div class="space-y-2">
                            <label class="text-xs font-bold text-slate-400 uppercase tracking-wider ml-1">Account Expiration</label>
                            <div class="relative group">
                                <i class="fas fa-clock absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 group-focus-within:text-indigo-400 transition-colors"></i>
                                <?= form_dropdown(['class' => 'w-full bg-slate-800/50 border border-white/10 rounded-2xl py-3 pl-12 pr-10 text-white focus:outline-none focus:border-indigo-500 transition-all appearance-none cursor-pointer', 'name' => 'accExpire', 'id' => 'accExpire'], $accExpire, old('accExpire') ?: '') ?>
                                <i class="fas fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-slate-500 pointer-events-none text-xs"></i>
                            </div>
                            <?php if ($validation->hasError('accExpire')) : ?>
                                <p class="text-[11px] text-red-400 ml-1"><?= $validation->getError('accExpire') ?></p>
                            <?php endif; ?>
                        </div>

                        <!-- Level -->
                        <div class="space-y-2">
                            <label class="text-xs font-bold text-slate-400 uppercase tracking-wider ml-1">Access Privilege Level</label>
                            <div class="relative group">
                                <i class="fas fa-user-shield absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 group-focus-within:text-indigo-400 transition-colors"></i>
                                <?= form_dropdown(['class' => 'w-full bg-slate-800/50 border border-white/10 rounded-2xl py-3 pl-12 pr-10 text-white focus:outline-none focus:border-indigo-500 transition-all appearance-none cursor-pointer', 'name' => 'accLevel', 'id' => 'accLevel'], $accLevel, old('accLevel') ?: '') ?>
                                <i class="fas fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-slate-500 pointer-events-none text-xs"></i>
                            </div>
                            <?php if ($validation->hasError('accLevel')) : ?>
                                <p class="text-[11px] text-red-400 ml-1"><?= $validation->getError('accLevel') ?></p>
                            <?php endif; ?>
                        </div>

                        <?php if ($user->level == 1) : ?>
                        <!-- Telegram Chat ID (Owner Only Control - Only for Owner/Admin creation) -->
                        <div id="twoFactorChatIdWrapper" class="space-y-2" style="display: none;">
                            <label class="text-xs font-bold text-amber-400 uppercase tracking-wider ml-1 flex items-center gap-1.5">
                                <i class="fab fa-telegram"></i> 2-Step OTP Telegram Chat ID
                                <span class="px-2 py-0.5 rounded text-[9px] font-black bg-amber-500/20 text-amber-300 border border-amber-500/30 uppercase tracking-wider">Owner Controlled</span>
                            </label>
                            <div class="relative group">
                                <i class="fab fa-telegram-plane absolute left-4 top-1/2 -translate-y-1/2 text-amber-500 group-focus-within:text-amber-400 transition-colors"></i>
                                <input type="text" name="two_factor_chat_id" id="two_factor_chat_id" placeholder="Optional: Enter 2-Step OTP Telegram Chat ID" value="<?= old('two_factor_chat_id') ?>"
                                    class="w-full bg-slate-800/50 border border-amber-500/30 rounded-2xl py-3 pl-12 pr-4 text-white focus:outline-none focus:border-amber-500 transition-all font-mono placeholder-slate-600">
                            </div>
                            <p class="text-[10px] text-slate-400 ml-1">Assign Telegram Chat ID to receive 2-Step OTP on login</p>
                        </div>
                        <?php endif; ?>

                        <button type="submit" class="w-full py-4 rounded-2xl bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-bold hover:shadow-lg hover:shadow-indigo-500/25 transition-all text-sm uppercase tracking-widest mt-4">
                            Provision New Account
                        </button>
                    </div>
                    <?= form_close() ?>
                </div>

                <div class="mt-12 text-center">
                    <p class="text-slate-500 text-[10px] uppercase font-bold tracking-[0.2em]">RapidCore User Management v3.1</p>
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

        $(document).ready(function() {
            // Auto-hide alert boxes
            setTimeout(function() {
                $('.alert-box').fadeOut('slow');
            }, 5000);

            function toggle2FAVisibility() {
                const level = $('#accLevel').val();
                if (level == '1' || level == '2') {
                    $('#twoFactorChatIdWrapper').slideDown();
                } else {
                    $('#twoFactorChatIdWrapper').slideUp();
                }
            }

            $('#accLevel').on('change', toggle2FAVisibility);
            toggle2FAVisibility();
        });
    </script>
</body>
</html>