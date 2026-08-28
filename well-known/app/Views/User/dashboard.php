<?php
// Core logic now handled by User controller. Stats passed via $stats array.
// This prevents database connection errors and ensures resellers see their own data.
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | RapidCore</title>
    
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

        .stat-card {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: inset 0 0 20px rgba(255, 255, 255, 0.05), 0 20px 40px rgba(0, 0, 0, 0.4);
            border-color: rgba(99, 102, 241, 0.3);
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
                
                <a href="<?= site_url('dashboard') ?>" class="sidebar-link active flex items-center px-3 py-3 rounded-lg text-slate-300 group">
                    <i class="fas fa-chart-pie w-6 text-center mr-2 text-indigo-400"></i>
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
                
                <?php if ($user->level == 1 || $user->level == 2) : ?>
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
                        <span class="text-xs text-slate-400"><?= getLevel($user->level) ?></span>
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
                <!-- Hamburger Menu -->
                <button onclick="toggleSidebar()" class="md:hidden p-2 text-white hover:bg-white/10 rounded-lg transition-colors">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                
                <div class="hidden md:block">
                    <h2 class="text-2xl font-bold text-white">Overview</h2>
                    <p class="text-slate-400 text-xs mt-1">Welcome back, <span class="text-indigo-400 font-medium"><?= $user->username ?></span></p>
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
            
            <!-- Messages -->
            <!-- Messages -->
            <?php if (session()->getFlashdata('msgDanger')) : ?>
                <div class="bg-red-500/10 border border-red-500/20 text-red-400 px-4 py-3 rounded-xl mb-6 text-sm flex items-center gap-2">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= session()->getFlashdata('msgDanger') ?>
                </div>
            <?php elseif (session()->getFlashdata('msgSuccess')) : ?>
                <div class="bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 px-4 py-3 rounded-xl mb-6 text-sm flex items-center gap-2">
                    <i class="fas fa-check-circle"></i>
                    <?= session()->getFlashdata('msgSuccess') ?>
                </div>
             <?php elseif (session()->getFlashdata('msgWarning')) : ?>
                <div class="bg-amber-500/10 border border-amber-500/20 text-amber-400 px-4 py-3 rounded-xl mb-6 text-sm flex items-center gap-2">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?= session()->getFlashdata('msgWarning') ?>
                </div>
            <?php endif; ?>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Total Licenses -->
                <div class="glass-panel p-6 rounded-2xl stat-card relative overflow-hidden group">
                    <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                        <i class="fas fa-key text-6xl text-indigo-500"></i>
                    </div>
                    <p class="text-xs text-slate-400 uppercase font-bold tracking-wider mb-2">Total Licenses</p>
                    <h3 class="text-3xl font-bold text-white mb-4"><?= $stats['total_keys'] ?></h3>
                    <div class="flex items-center text-xs text-slate-500">
                        <i class="fas fa-database mr-2"></i> All time generated
                    </div>
                </div>

                <!-- Active Keys -->
                <div class="glass-panel p-6 rounded-2xl stat-card relative overflow-hidden group">
                    <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                        <i class="fas fa-bolt text-6xl text-emerald-500"></i>
                    </div>
                    <p class="text-xs text-slate-400 uppercase font-bold tracking-wider mb-2">Active Keys</p>
                    <h3 class="text-3xl font-bold text-white mb-4"><?= $stats['active_keys'] ?></h3>
                    <div class="flex items-center text-xs text-emerald-400">
                        <i class="fas fa-check-circle mr-2"></i> Currently running
                    </div>
                </div>

                <!-- Unused Stock -->
                <div class="glass-panel p-6 rounded-2xl stat-card relative overflow-hidden group">
                    <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                        <i class="fas fa-box text-6xl text-blue-500"></i>
                    </div>
                    <p class="text-xs text-slate-400 uppercase font-bold tracking-wider mb-2">Unused Stock</p>
                    <h3 class="text-3xl font-bold text-white mb-4"><?= $stats['unused_keys'] ?></h3>
                    <div class="flex items-center text-xs text-blue-400">
                        <i class="fas fa-archive mr-2"></i> Keys ready to sell
                    </div>
                </div>

                <!-- CTA -->
                <div class="p-6 rounded-2xl bg-gradient-to-br from-indigo-600 to-purple-600 relative overflow-hidden group cursor-pointer hover:shadow-lg hover:shadow-indigo-500/25 transition-all" onclick="window.location.href='<?= site_url('keys/generate') ?>'">
                    <div class="relative z-10 h-full flex flex-col justify-center">
                        <div class="w-10 h-10 rounded-xl bg-white/20 backdrop-blur-md flex items-center justify-center mb-4">
                            <i class="fas fa-bolt text-white"></i>
                        </div>
                        <h3 class="text-xl font-bold text-white mb-1">Generate Keys</h3>
                        <p class="text-indigo-100 text-xs">Create new licenses instantly</p>
                    </div>
                    <div class="absolute -bottom-4 -right-4 w-32 h-32 bg-white/10 rounded-full blur-2xl group-hover:bg-white/20 transition-colors"></div>
                </div>
            </div>

            <!-- Dashboard Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                <!-- Resellers / Top Users -->
                <div class="lg:col-span-2 space-y-6">
                    <div class="glass-panel rounded-2xl p-6">
                        <div class="flex items-center justify-between mb-6">
                            <div class="flex items-center gap-3">
                                <i class="fas fa-users text-indigo-400"></i>
                                <h3 class="font-bold text-white">Top Performance</h3>
                            </div>
                        </div>
                        
                        <div class="space-y-4">
                            <?php foreach ($resellers as $res) : ?>
                            <div class="flex items-center justify-between p-4 rounded-xl bg-slate-800/30 border border-white/5 hover:bg-slate-800/50 transition-all group">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 rounded-full bg-slate-700 flex items-center justify-center text-white font-bold border-2 border-slate-600 shadow-inner group-hover:border-indigo-500/50 transition-colors">
                                        <?= strtoupper(substr($res->username, 0, 1)) ?>
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <h4 class="text-white font-bold tracking-wide"><?= $res->username ?></h4>
                                            <?php if ($res->level == 1): ?>
                                            <span class="px-1.5 py-0.5 rounded bg-blue-500/20 text-[9px] text-blue-400 font-bold border border-blue-500/30 flex items-center gap-1">
                                                <i class="fas fa-check-circle"></i> VERIFIED
                                            </span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="flex items-center gap-2 mt-1">
                                            <span class="text-[10px] text-slate-500 uppercase font-black tracking-tighter bg-slate-700/50 px-2 py-0.5 rounded"><?= $accLevel[$res->level] ?? 'RESeller' ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-xl font-black text-white leading-none"><?= $res->managed_keys ?></p>
                                    <p class="text-[10px] text-slate-500 uppercase font-bold tracking-widest mt-1">Keys</p>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Recent Activity -->
                    <div class="glass-panel rounded-2xl p-6">
                        <div class="flex items-center justify-between mb-6">
                            <div class="flex items-center gap-3">
                                <i class="fas fa-history text-indigo-400"></i>
                                <h3 class="font-bold text-white">Recent Activity</h3>
                            </div>
                        </div>
                        
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="text-xs text-slate-400 border-b border-white/5">
                                        <th class="py-3 px-2 font-medium">Action</th>
                                        <th class="py-3 px-2 font-medium">Key</th>
                                        <th class="py-3 px-2 font-medium text-right">Time</th>
                                    </tr>
                                </thead>
                                <tbody class="text-sm">
                                <?php if (!empty($history)): ?>
                                    <?php foreach ($history as $h) : ?>
                                        <?php 
                                            $in = explode("|", $h->info); 
                                            $action = $in[0] ?? 'Unknown';
                                            $key = $in[1] ?? '---';
                                            $displayKey = $key;
                                            if ($key !== '---' && strlen($key) > 2) {
                                                $displayKey = 'xxxx' . substr($key, -2);
                                            }
                                        ?>
                                        <tr class="border-b border-white/5 hover:bg-white/5 transition-colors">
                                            <td class="py-3 px-2 text-white font-medium text-xs"><?= $action ?></td>
                                            <td class="py-3 px-2 font-mono text-indigo-400 text-[10px]"><?= $displayKey ?></td>
                                            <td class="py-3 px-2 text-right text-slate-500 text-[10px]"><?= $time::parse($h->created_at)->humanize() ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="py-8 text-center text-slate-500">
                                            <p class="text-xs">No recent activity</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Quick Access & Account Info -->
                <div class="space-y-6">
                    <!-- Quick Access -->
                    <div class="glass-panel rounded-2xl p-6">
                         <div class="flex items-center gap-3 mb-6">
                            <i class="fas fa-rocket text-indigo-400"></i>
                            <h3 class="font-bold text-white uppercase text-xs tracking-widest">Quick Access</h3>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <a href="<?= site_url('keys/generate') ?>" class="p-6 rounded-2xl bg-slate-800/30 border border-white/5 hover:bg-indigo-500/10 hover:border-indigo-500/30 transition-all text-center group">
                                <i class="fas fa-plus text-indigo-400 text-xl mb-3 group-hover:scale-110 transition-transform"></i>
                                <span class="block text-[10px] font-black text-slate-400 uppercase tracking-widest">Generate</span>
                            </a>
                            <a href="<?= site_url('keys') ?>" class="p-6 rounded-2xl bg-indigo-500/10 border border-indigo-500/20 hover:bg-indigo-500/20 transition-all text-center group">
                                <i class="fas fa-key text-indigo-400 text-xl mb-3 group-hover:scale-110 transition-transform"></i>
                                <span class="block text-[10px] font-black text-indigo-300 uppercase tracking-widest">Licenses</span>
                            </a>
                        </div>
                        <div class="mt-4">
                            <a href="<?= site_url('settings') ?>" class="p-6 rounded-2xl bg-slate-800/30 border border-white/5 hover:bg-slate-700/50 transition-all text-center group flex flex-col items-center">
                                <i class="fas fa-cog text-slate-400 text-xl mb-3 group-hover:rotate-45 transition-transform"></i>
                                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Settings</span>
                            </a>
                        </div>
                    </div>

                    <!-- Account Info -->
                    <div class="glass-panel rounded-2xl p-6">
                        <div class="flex items-center gap-3 mb-6">
                            <i class="fas fa-user-shield text-indigo-400"></i>
                            <h3 class="font-bold text-white uppercase text-xs tracking-widest">Account Info</h3>
                        </div>
                        
                        <div class="space-y-4">
                            <div class="flex justify-between items-center">
                                <span class="text-xs text-slate-500 font-medium">Username</span>
                                <span class="text-sm text-white font-bold"><?= $user->username ?></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-xs text-slate-500 font-medium">Role</span>
                                <span class="px-2 py-1 rounded bg-indigo-500/20 text-[10px] text-indigo-300 font-black border border-indigo-500/30 uppercase"><?= $role_label ?></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-xs text-slate-500 font-medium">Expiration</span>
                                <span class="text-xs text-slate-300 font-mono"><?= date("d M Y", strtotime($user->expiration_date)) ?></span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </main>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            
            if (sidebar.classList.contains('-translate-x-full')) {
                // Open sidebar
                sidebar.classList.remove('-translate-x-full');
                overlay.classList.remove('hidden');
            } else {
                // Close sidebar
                sidebar.classList.add('-translate-x-full');
                overlay.classList.add('hidden');
            }
        }
    </script>
</body>
</html>