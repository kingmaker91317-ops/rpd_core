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
    <title>Server Management | RapidCore</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" rel="stylesheet">
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
    <div id="sidebarOverlay" class="fixed inset-0 bg-black/60 z-40 hidden md:hidden glass-overlay backdrop-blur-sm" onclick="toggleSidebar()"></div>

    <!-- Sidebar -->
    <aside id="sidebar" class="fixed inset-y-0 left-0 z-50 w-64 glass-panel border-r border-white/5 transform -translate-x-full transition-transform duration-300 md:translate-x-0 md:static md:flex flex-col justify-between h-full">
        <div>
            <div class="h-20 flex items-center px-6 border-b border-white/5">
                <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center mr-3 shadow-lg shadow-indigo-500/20">
                    <i class="fas fa-shield-alt text-white text-sm"></i>
                </div>
                <div>
                    <h1 class="font-bold text-white text-base leading-tight"> RapidCore</h1>
                    <span class="text-[10px] text-slate-400 font-mono tracking-wider">LICENSE MANAGER</span>
                </div>
            </div>

            <nav class="mt-6 px-3 space-y-1">
                <div class="px-3 mb-2 text-[10px] font-bold text-slate-500 uppercase tracking-wider">Main</div>
                <a href="<?= site_url('dashboard') ?>" class="sidebar-link flex items-center px-3 py-3 rounded-lg text-slate-300 group"><i class="fas fa-chart-pie w-6 text-center mr-2 text-slate-400"></i><span class="font-medium">Overview</span></a>
                <a href="<?= site_url('keys/generate') ?>" class="sidebar-link flex items-center px-3 py-3 rounded-lg text-slate-300 group"><i class="fas fa-bolt w-6 text-center mr-2 text-slate-400"></i><span class="font-medium">Generate Keys</span></a>
                <a href="<?= site_url('keys') ?>" class="sidebar-link flex items-center px-3 py-3 rounded-lg text-slate-300 group"><i class="fas fa-key w-6 text-center mr-2 text-slate-400"></i><span class="font-medium">License Manager</span></a>

                <div class="px-3 mt-6 mb-2 text-[10px] font-bold text-slate-500 uppercase tracking-wider">Configuration</div>
                <a href="<?= site_url('settings') ?>" class="sidebar-link flex items-center px-3 py-3 rounded-lg text-slate-300 group"><i class="fas fa-cog w-6 text-center mr-2 text-slate-400"></i><span class="font-medium">Settings</span></a>
                
                <?php if ($user->level == 1 || $user->level == 2) : ?>
                <a href="<?= site_url('admin/manage-users') ?>" class="sidebar-link flex items-center px-3 py-3 rounded-lg text-slate-300 group"><i class="fas fa-users w-6 text-center mr-2 text-slate-400"></i><span class="font-medium">Manage Users</span></a>
                <a href="<?= site_url('admin/create-referral') ?>" class="sidebar-link flex items-center px-3 py-3 rounded-lg text-slate-300 group"><i class="fas fa-user-plus w-6 text-center mr-2 text-slate-400"></i><span class="font-medium">CREATE NEW USER</span></a>
                <a href="<?= site_url('admin/server-management') ?>" class="sidebar-link active flex items-center px-3 py-3 rounded-lg text-slate-300 group"><i class="fas fa-server w-6 text-center mr-2 text-indigo-400"></i><span class="font-medium">Server Management</span></a>
                <?php endif; ?>
            </nav>
        </div>
        <div class="p-4 border-t border-white/5">
            <a href="<?= site_url('logout') ?>" class="flex items-center justify-center w-full py-2.5 px-4 rounded-xl border border-red-500/20 bg-red-500/10 text-red-400 hover:bg-red-500/20 transition-all font-medium text-xs"><i class="fas fa-power-off mr-2"></i> LOGOUT SESSION</a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col overflow-hidden relative">
        <header class="h-20 flex items-center justify-between px-4 md:px-8 border-b border-white/5 bg-slate-900/50 backdrop-blur-sm z-10">
            <div class="flex items-center gap-3">
                <button onclick="toggleSidebar()" class="md:hidden p-2 text-white hover:bg-white/10 rounded-lg transition-colors"><i class="fas fa-bars text-xl"></i></button>
                <div class="hidden md:block">
                    <h2 class="text-2xl font-bold text-white">Server Management</h2>
                    <p class="text-slate-400 text-xs mt-1">Global administrative controls</p>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <div class="glass-panel px-4 py-2 rounded-xl flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-amber-500/10 flex items-center justify-center text-amber-500"><i class="fas fa-coins text-sm"></i></div>
                    <div class="text-right">
                        <p class="text-[10px] text-slate-400 uppercase font-bold tracking-wider">Balance</p>
                        <p class="text-white font-mono font-medium">₹<?= number_format($user->saldo, 2) ?></p>
                    </div>
                </div>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-4 md:p-8">
            <div class="max-w-5xl mx-auto space-y-8">
                
                <!-- Maintenance Mode -->
                <div class="glass-panel rounded-3xl p-6 md:p-8 border border-cyan-500/20">
                    <div class="flex flex-col md:flex-row md:items-center gap-4 mb-6">
                        <div class="flex items-center gap-4 flex-1">
                            <div class="w-12 h-12 rounded-2xl bg-cyan-500/10 flex items-center justify-center text-cyan-400 border border-cyan-500/20"><i class="fas fa-tools text-xl"></i></div>
                            <div>
                                <h3 class="text-xl font-bold text-white">Maintenance Mode</h3>
                                <p class="text-slate-400 text-xs">Block all user connections with a custom message</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="text-sm font-bold text-slate-400">STATUS:</span>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" id="maintenanceToggle" class="sr-only peer" <?= (isset($onoff) && $onoff->status === 'on') ? 'checked' : '' ?>>
                                <div class="w-14 h-7 bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-cyan-500"></div>
                            </label>
                        </div>
                    </div>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-400 mb-2">Maintenance Reason (Shown to users)</label>
                            <textarea id="maintenanceReason" rows="3" class="w-full bg-slate-800/50 border border-white/10 rounded-xl p-4 text-white placeholder-slate-500 focus:outline-none focus:border-cyan-500 transition-colors" placeholder="E.g., Server is currently under maintenance. Please try again in 30 minutes."><?= isset($onoff) ? htmlspecialchars($onoff->myinput) : '' ?></textarea>
                        </div>
                        <button id="saveMaintenanceBtn" class="w-full md:w-auto px-8 py-3 rounded-xl bg-cyan-500/10 border border-cyan-500/50 text-cyan-400 font-bold hover:bg-cyan-500 hover:text-white transition-all flex items-center justify-center gap-2">
                            <i class="fas fa-save"></i> Save Maintenance Settings
                        </button>
                    </div>
                </div>

                <div class="glass-panel rounded-3xl p-6 md:p-8">
                    <div class="flex items-center gap-4 mb-8">
                        <div class="w-12 h-12 rounded-2xl bg-indigo-500/10 flex items-center justify-center text-indigo-400 border border-indigo-500/20"><i class="fas fa-calendar-plus text-xl"></i></div>
                        <div>
                            <h3 class="text-xl font-bold text-white">Global Time Management</h3>
                            <p class="text-slate-400 text-xs">Add days to all existing keys at once</p>
                        </div>
                    </div>
                    <button id="addDaysBtn" class="w-full md:w-auto px-8 py-4 rounded-xl bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-medium hover:shadow-lg hover:shadow-indigo-500/25 transition-all flex items-center justify-center gap-2">
                        <i class="fas fa-calendar-plus"></i> Add Days to Keys
                    </button>
                </div>

                <div class="glass-panel rounded-3xl p-6 md:p-8">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-2xl bg-amber-500/10 flex items-center justify-center text-amber-400 border border-amber-500/20"><i class="fas fa-pause-circle text-xl"></i></div>
                            <div>
                                <h3 class="text-xl font-bold text-white">Pause / Resume Keys</h3>
                                <p class="text-slate-400 text-xs">Temporarily block or unblock all licenses</p>
                            </div>
                        </div>
                        <div class="bg-slate-800/50 p-3 rounded-xl border border-white/5">
                            <p class="text-[10px] font-bold text-slate-400 mb-1.5 uppercase tracking-wider text-center">Current Key Status</p>
                            <div class="flex gap-2">
                                <span class="px-3 py-1.5 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-[10px] rounded-lg uppercase font-bold tracking-wider flex items-center gap-1.5"><i class="fas fa-check-circle"></i> <?= $active_keys ?> Active</span>
                                <span class="px-3 py-1.5 bg-amber-500/10 border border-amber-500/20 text-amber-400 text-[10px] rounded-lg uppercase font-bold tracking-wider flex items-center gap-1.5"><i class="fas fa-pause-circle"></i> <?= $paused_keys ?> Paused</span>
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-col md:flex-row gap-4">
                        <button id="pauseKeysBtn" class="flex-1 px-8 py-4 rounded-xl bg-gradient-to-r from-amber-500 to-orange-600 text-white font-medium hover:shadow-lg hover:shadow-amber-500/25 transition-all flex items-center justify-center gap-2">
                            <i class="fas fa-pause"></i> Pause All Keys
                        </button>
                        <button id="unpauseKeysBtn" class="flex-1 px-8 py-4 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-600 text-white font-medium hover:shadow-lg hover:shadow-emerald-500/25 transition-all flex items-center justify-center gap-2">
                            <i class="fas fa-play"></i> Resume All Keys
                        </button>
                    </div>
                </div>

                <div class="glass-panel rounded-3xl p-6 md:p-8">
                    <div class="flex items-center gap-4 mb-8">
                        <div class="w-12 h-12 rounded-2xl bg-pink-500/10 flex items-center justify-center text-pink-400 border border-pink-500/20"><i class="fas fa-sync text-xl"></i></div>
                        <div>
                            <h3 class="text-xl font-bold text-white">Reset Devices</h3>
                            <p class="text-slate-400 text-xs">Clear all hardware binds across all keys</p>
                        </div>
                    </div>
                    <button id="resetAllDevicesBtn" class="w-full md:w-auto px-8 py-4 rounded-xl bg-gradient-to-r from-pink-500 to-rose-600 text-white font-medium hover:shadow-lg hover:shadow-pink-500/25 transition-all flex items-center justify-center gap-2">
                        <i class="fas fa-sync"></i> Reset All Devices
                    </button>
                </div>

                <div class="glass-panel rounded-3xl p-6 md:p-8 border border-red-500/20">
                    <div class="flex items-center gap-4 mb-8">
                        <div class="w-12 h-12 rounded-2xl bg-red-500/10 flex items-center justify-center text-red-400 border border-red-500/20"><i class="fas fa-trash-alt text-xl"></i></div>
                        <div>
                            <h3 class="text-xl font-bold text-red-400">Danger Zone</h3>
                            <p class="text-slate-400 text-xs">Permanently delete all keys from the database</p>
                        </div>
                    </div>
                    <button id="deleteAllKeysBtn" class="w-full md:w-auto px-8 py-4 rounded-xl bg-red-500/10 border border-red-500/50 text-red-400 font-bold hover:bg-red-500 hover:text-white transition-all flex items-center justify-center gap-2">
                        <i class="fas fa-trash"></i> Delete All Keys
                    </button>
                </div>

            </div>
        </div>
    </main>

    <script src="https://code.jquery.com/jquery-3.6.0.js"></script>
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
            $('#addDaysBtn').click(function() {
                Swal.fire({
                    title: 'Add Days',
                    html: `
                        <div class="mb-3 text-left">
                            <label class="block text-sm font-medium text-slate-400 mb-1">Days to add</label>
                            <input type="number" id="swal-days" class="w-full bg-slate-700 border border-slate-600 rounded-lg px-3 py-2 text-white" min="1" value="1">
                        </div>
                        <div class="mb-3 text-left">
                            <label class="block text-sm font-medium text-slate-400 mb-1">Game</label>
                            <select id="swal-game" class="w-full bg-slate-700 border border-slate-600 rounded-lg px-3 py-2 text-white">
                                <option value="ALL">All Games</option>
                                <?php 
                                $db = \Config\Database::connect();
                                $games = $db->query("SELECT DISTINCT game FROM keys_code ORDER BY game ASC")->getResult();
                                foreach ($games as $game) {
                                    echo "<option value=\"{$game->game}\">{$game->game}</option>";
                                }
                                ?>
                            </select>
                        </div>
                    `,
                    showCancelButton: true,
                    confirmButtonColor: '#6366f1',
                    background: '#1e293b',
                    color: '#fff',
                    preConfirm: () => {
                        return { 
                            days: document.getElementById('swal-days').value, 
                            game: document.getElementById('swal-game').value 
                        };
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                         $.getJSON("<?= site_url('keys/add_days') ?>", result.value, function(data) {
                            if (data.success) {
                                Swal.fire({ title: 'Success', text: `${data.affected} keys updated.`, icon: 'success', background: '#1e293b', color: '#fff' });
                            }
                        });
                    }
                });
            });

            $('#resetAllDevicesBtn').click(function() {
                Swal.fire({
                    title: 'Reset Devices?',
                    text: "This will reset all devices for your keys.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ec4899',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Yes, reset all',
                    background: '#1e293b',
                    color: '#fff'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.getJSON("<?= site_url('keys/reset_all_devices') ?>", {}, function(data) {
                            if (data.success) {
                                Swal.fire({ title: 'Success', text: 'All devices have been reset.', icon: 'success', background: '#1e293b', color: '#fff' });
                            }
                        });
                    }
                });
            });

            $('#pauseKeysBtn').click(function() {
                Swal.fire({
                    title: 'Pause All Keys?',
                    text: "This will block all keys from authenticating.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#f59e0b',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Yes, pause them',
                    background: '#1e293b',
                    color: '#fff'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.getJSON("<?= site_url('keys/pause_all_keys') ?>", {}, function(data) {
                            if (data.success) {
                                Swal.fire({ title: 'Success', text: 'All keys have been paused.', icon: 'success', background: '#1e293b', color: '#fff' }).then(() => location.reload());
                            }
                        });
                    }
                });
            });

            $('#unpauseKeysBtn').click(function() {
                Swal.fire({
                    title: 'Resume All Keys?',
                    text: "This will unblock all keys, allowing authentication again.",
                    icon: 'info',
                    showCancelButton: true,
                    confirmButtonColor: '#10b981',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Yes, resume them',
                    background: '#1e293b',
                    color: '#fff'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.getJSON("<?= site_url('keys/unpause_all_keys') ?>", {}, function(data) {
                            if (data.success) {
                                Swal.fire({ title: 'Success', text: 'All keys have been resumed.', icon: 'success', background: '#1e293b', color: '#fff' }).then(() => location.reload());
                            }
                        });
                    }
                });
            });

            $('#deleteAllKeysBtn').click(function() {
                Swal.fire({
                    title: 'Delete All Keys?',
                    text: "WARNING: This cannot be undone. All keys will be permanently deleted!",
                    icon: 'error',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'I understand, DELETE ALL',
                    background: '#1e293b',
                    color: '#fff'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.getJSON("<?= site_url('keys/delete_all_keys') ?>", {}, function(data) {
                            if (data.success) {
                                Swal.fire({ title: 'Deleted', text: 'All keys have been deleted.', icon: 'success', background: '#1e293b', color: '#fff' });
                            }
                        });
                    }
                });
            });

            $('#saveMaintenanceBtn').click(function() {
                const status = $('#maintenanceToggle').is(':checked') ? 'on' : 'off';
                const reason = $('#maintenanceReason').val();

                $.post("<?= site_url('admin/save-maintenance') ?>", { status: status, reason: reason }, function(data) {
                    if (data.success) {
                        Swal.fire({ title: 'Saved', text: 'Maintenance settings updated.', icon: 'success', background: '#1e293b', color: '#fff' });
                    } else {
                        Swal.fire({ title: 'Error', text: data.message || 'Could not save settings.', icon: 'error', background: '#1e293b', color: '#fff' });
                    }
                }, 'json');
            });
        });
    </script>
</body>
</html>
