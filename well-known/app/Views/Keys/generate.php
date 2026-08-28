<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Keys | Rapid Core</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    
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

        /* Form Inputs & Select options styling */
        .form-input {
            background: rgba(15, 23, 42, 0.6) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            color: #f8fafc !important;
            transition: all 0.2s;
        }
        .form-input:focus {
            border-color: #a855f7 !important;
            outline: none;
            box-shadow: 0 0 0 2px rgba(168, 85, 247, 0.2) !important;
        }
        .form-input option, select option {
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

    <div id="sidebarOverlay" class="fixed inset-0 bg-black/60 z-40 hidden md:hidden glass-overlay backdrop-blur-sm" onclick="toggleSidebar()"></div>

    <!-- Sidebar -->
    <aside id="sidebar" class="fixed inset-y-0 left-0 z-50 w-64 glass-panel border-r border-white/5 transform -translate-x-full transition-transform duration-300 md:translate-x-0 md:static md:flex flex-col justify-between h-full">
        <div>
            <div class="h-20 flex items-center px-6 border-b border-white/5">
                <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center mr-3 shadow-lg shadow-indigo-500/20">
                    <i class="fas fa-shield-alt text-white text-sm"></i>
                </div>
                <div>
                    <h1 class="font-bold text-white text-base leading-tight"> Rapid Core</h1>
                    <span class="text-[10px] text-slate-400 font-mono tracking-wider">LICENSE MANAGER</span>
                </div>
            </div>

            <nav class="mt-6 px-3 space-y-1">
                <div class="px-3 mb-2 text-[10px] font-bold text-slate-500 uppercase tracking-wider">Main</div>
                
                <a href="<?= site_url('dashboard') ?>" class="sidebar-link flex items-center px-3 py-3 rounded-lg text-slate-300 group">
                    <i class="fas fa-chart-pie w-6 text-center mr-2 text-slate-400 group-hover:text-indigo-400 transition-colors"></i>
                    <span class="font-medium">Overview</span>
                </a>
                
                <a href="<?= site_url('keys/generate') ?>" class="sidebar-link active flex items-center px-3 py-3 rounded-lg text-slate-300 group">
                    <i class="fas fa-bolt w-6 text-center mr-2 text-indigo-400"></i>
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

    <main class="flex-1 flex flex-col overflow-hidden relative">
        <header class="h-20 flex items-center justify-between px-4 md:px-8 border-b border-white/5 bg-slate-900/50 backdrop-blur-sm z-10">
            <div class="flex items-center gap-3">
                <button onclick="toggleSidebar()" class="md:hidden p-2 text-white hover:bg-white/10 rounded-lg transition-colors">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                
                <div class="hidden md:block">
                    <h2 class="text-2xl font-bold text-white">Generate Keys</h2>
                    <p class="text-slate-400 text-xs mt-1">Create new licenses for your users</p>
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

        <div class="flex-1 overflow-y-auto p-4 md:p-8">
            
            <?php if (session()->getFlashdata('msgDanger')) : ?>
                <div class="bg-red-500/10 border border-red-500/20 text-red-400 px-4 py-3 rounded-xl mb-6 text-sm flex items-center gap-2 animate-in fade-in slide-in-from-top-4 duration-300">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= session()->getFlashdata('msgDanger') ?>
                </div>
            <?php elseif (session()->getFlashdata('msgWarning')) : ?>
                <div class="bg-amber-500/10 border border-amber-500/20 text-amber-400 px-4 py-3 rounded-xl mb-6 text-sm flex items-center gap-2 animate-in fade-in slide-in-from-top-4 duration-300">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?= session()->getFlashdata('msgWarning') ?>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div class="lg:col-span-2 space-y-6">
                    <?= form_open() ?>
                    
                    <div class="glass-panel p-6 rounded-2xl">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="w-8 h-8 rounded-full bg-indigo-500 flex items-center justify-center text-white font-bold text-sm">1</div>
                            <h3 class="font-bold text-white text-lg">SELECT GAME</h3>
                        </div>
                        
                        <div class="form-group">
                            <label class="block text-xs font-bold text-slate-400 mb-2 uppercase tracking-wide">Game Type</label>
                            <?= form_dropdown(['class' => 'w-full form-input rounded-xl px-4 py-3 text-sm focus:border-indigo-500', 'name' => 'game', 'id' => 'game'], $game, old('game') ?: '') ?>
                             <?php if ($validation->hasError('game')) : ?>
                                <p class="text-red-400 text-xs mt-1"><?= $validation->getError('game') ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="glass-panel p-6 rounded-2xl">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="w-8 h-8 rounded-full bg-indigo-500 flex items-center justify-center text-white font-bold text-sm">2</div>
                            <h3 class="font-bold text-white text-lg">CONFIGURE KEY</h3>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div>
                                <label class="block text-xs font-bold text-slate-400 mb-2 uppercase tracking-wide">Duration</label>
                                <?= form_dropdown(['class' => 'w-full form-input rounded-xl px-4 py-3 text-sm focus:border-indigo-500', 'name' => 'duration', 'id' => 'duration'], $duration, old('duration') ?: '') ?>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-indigo-400 mb-2 uppercase tracking-wide">Key Method</label>
                                <select id="key_method" name="key_method" onchange="toggleKeyType()" class="w-full form-input rounded-xl px-4 py-3 text-sm focus:border-indigo-500">
                                    <option value="random">Random Key Generation</option>
                                    <option value="custom">Custom Key Entry</option>
                                </select>
                            </div>
                            
                            <div id="prefix_container">
                                <label class="block text-xs font-bold text-slate-400 mb-2 uppercase tracking-wide">Prefix</label>
                                <input type="text" name="prefix" id="prefix_input" class="w-full form-input rounded-xl px-4 py-3 text-sm focus:border-indigo-500" placeholder="KEY" value="RAPID">
                            </div>

                            <div id="bulk_container">
                                <label class="block text-xs font-bold text-slate-400 mb-2 uppercase tracking-wide">Bulk Count</label>
                                <input type="number" name="bulk_count" id="bulk_count" class="w-full form-input rounded-xl px-4 py-3 text-sm focus:border-indigo-500" placeholder="1" value="1" min="1">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-400 mb-2 uppercase tracking-wide">
                                    Max Devices
                                    <?php if ($user->level == 2): ?>
                                        <span class="ml-2 px-1.5 py-0.5 rounded text-[9px] font-black bg-amber-500/20 text-amber-400 border border-amber-500/30 uppercase tracking-wider">Max 10</span>
                                    <?php else: ?>
                                        <span class="ml-2 px-1.5 py-0.5 rounded text-[9px] font-black bg-indigo-500/20 text-indigo-400 border border-indigo-500/30 uppercase tracking-wider">Unlimited</span>
                                    <?php endif; ?>
                                </label>
                                <input type="number" name="max_devices" id="max_devices"
                                    class="w-full form-input rounded-xl px-4 py-3 text-sm focus:border-indigo-500"
                                    placeholder="1"
                                    value="<?= old('max_devices', 1) ?>"
                                    min="1"
                                    <?= ($user->level == 2) ? 'max="10"' : '' ?>>
                                <?php if ($user->level == 2): ?>
                                    <p class="text-amber-400/70 text-[10px] mt-1.5 flex items-center gap-1">
                                        <i class="fas fa-info-circle"></i> Admin accounts are limited to a maximum of 10 devices per key.
                                    </p>
                                <?php endif; ?>
                            </div>

                        </div>

                        <div id="custom_key_container" class="mb-2 hidden">
                             <label class="block text-xs font-bold text-slate-400 mb-2 uppercase tracking-wide">License Key</label>
                             <input type="text" id="cuslicense_input" class="w-full form-input rounded-xl px-4 py-3 text-sm focus:border-indigo-500 font-mono text-indigo-300" placeholder="Enter License Key" value="<?= old('cuslicense') ?>">
                             <?php if ($validation->hasError('cuslicense')) : ?>
                                <p class="text-red-400 text-xs mt-1"><?= $validation->getError('cuslicense') ?></p>
                            <?php endif; ?>
                        </div>

                    </div>

                    <div class="glass-panel p-8 rounded-2xl text-center relative overflow-hidden">
                        <div class="relative z-10">
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-wide mb-1">Total Cost</p>
                            <h2 class="text-4xl font-bold text-white mb-6">₹<span id="totalCost">0.00</span></h2>
                            
                            <button type="submit" class="w-full py-4 bg-gradient-to-r from-indigo-600 to-purple-600 rounded-xl text-white font-bold shadow-lg shadow-indigo-500/25 hover:shadow-indigo-500/40 hover:scale-[1.02] transition-all">
                                <i class="fas fa-bolt mr-2"></i> Generate Key
                            </button>
                        </div>
                        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-32 h-32 bg-indigo-500/20 rounded-full blur-3xl z-0"></div>
                    </div>

                    <?= form_close() ?>
                </div>

                <div class="space-y-6">
                    <?php if (session()->getFlashdata('generated_keys')) : ?>
                        <div class="glass-panel rounded-2xl overflow-hidden mb-6 border-emerald-500/20 bg-emerald-500/5 animate-in zoom-in duration-300">
                            <div class="p-4 bg-emerald-500/10 border-b border-emerald-500/20 flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <div class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></div>
                                    <span class="text-[10px] font-black text-emerald-400 uppercase tracking-widest bg-emerald-500/20 px-2 py-0.5 rounded">Generated!</span>
                                </div>
                                <button onclick="copyBulk(this)" class="px-3 py-1 rounded-lg bg-emerald-500 text-white text-[10px] font-bold hover:bg-emerald-600 transition-all flex items-center gap-1.5 shadow-lg shadow-emerald-500/20">
                                    <i class="fas fa-copy"></i> Copy All Details
                                </button>
                            </div>
                            <div class="p-4 space-y-3 max-h-[350px] overflow-y-auto custom-scrollbar">
                                <?php 
                                    $bulkText = "";
                                    foreach(session()->getFlashdata('generated_keys') as $keyData): 
                                        $key = $keyData['key'];
                                        $gameName = $keyData['game'];
                                        $panelName = isset($keyData['game_name']) ? $keyData['game_name'] : $gameName;
                                        $drtn = $keyData['duration'];
                                        $days = $drtn / 24;
                                        $daysText = $days >= 1 ? ($days . " Day" . ($days > 1 ? "s" : "")) : ($drtn . " Hour" . ($drtn > 1 ? "s" : ""));
                                        
                                        $copyDetails = "🔑 Key: {$key}\n🎮 Game: {$gameName}\n🏷️ Panel Name: {$panelName}\n⏳ Duration: {$daysText}\n📅 Expiry: Not Activated (Starts on first login)";
                                        $bulkText .= $copyDetails . "\n\n";
                                ?>
                                    <div class="flex flex-col gap-2 p-3 rounded-xl bg-slate-900/50 border border-white/5 group hover:border-indigo-500/30 transition-all">
                                        <div class="flex items-center justify-between">
                                            <code class="text-indigo-400 font-mono text-xs tracking-wider font-bold"><?= $key ?></code>
                                            <button onclick="copySingle(<?= htmlspecialchars(json_encode($copyDetails)) ?>, this)" class="text-slate-400 hover:text-indigo-400 p-1 hover:bg-white/5 rounded transition-colors flex items-center gap-1 text-[11px]">
                                                <i class="fas fa-copy text-xs"></i> <span class="copy-text">Copy Details</span>
                                            </button>
                                        </div>
                                        <div class="grid grid-cols-4 gap-2 mt-1 pt-1.5 border-t border-white/5 text-[10px] text-slate-400 font-medium">
                                            <div>
                                                <span class="block text-[8px] uppercase tracking-wider text-slate-500 font-bold mb-0.5 font-sans">Panel Name</span>
                                                <span class="text-slate-300 font-sans"><?= $panelName ?></span>
                                            </div>
                                            <div>
                                                <span class="block text-[8px] uppercase tracking-wider text-slate-500 font-bold mb-0.5 font-sans">Game</span>
                                                <span class="text-slate-300 font-sans"><?= $gameName ?></span>
                                            </div>
                                            <div>
                                                <span class="block text-[8px] uppercase tracking-wider text-slate-500 font-bold mb-0.5 font-sans">Duration</span>
                                                <span class="text-slate-300 font-sans"><?= $daysText ?></span>
                                            </div>
                                            <div>
                                                <span class="block text-[8px] uppercase tracking-wider text-slate-500 font-bold mb-0.5 font-sans">Expiry</span>
                                                <span class="text-emerald-400 flex items-center gap-1 font-semibold font-sans">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span> Not Started
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <textarea id="bulkKeys" class="hidden"><?= htmlspecialchars(trim($bulkText)) ?></textarea>
                        </div>
                    <?php endif; ?>

                    <div class="glass-panel p-6 rounded-2xl">
                        <h3 class="font-bold text-white mb-4">Pricing</h3>
                        <div class="divide-y divide-white/5">
                            <?php 
                                $priceData = is_string($price) ? json_decode($price, true) : $price;
                                if($priceData):
                                    foreach($priceData as $dur => $cost): 
                            ?>
                                <div class="flex items-center justify-between py-3">
                                    <span class="text-slate-400 text-sm"><?= $dur ?> Hours</span>
                                    <span class="text-white font-mono font-medium">₹<?= $cost ?>/device</span>
                                </div>
                            <?php 
                                    endforeach; 
                                endif; 
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        function toggleKeyType() {
            const method = document.getElementById('key_method').value;
            const prefixBox = document.getElementById('prefix_container');
            const bulkBox = document.getElementById('bulk_container');
            const customBox = document.getElementById('custom_key_container');
            
            const prefixInput = document.getElementById('prefix_input');
            const bulkInput = document.getElementById('bulk_count');
            const customInput = document.getElementById('cuslicense_input');

            if (method === 'custom') {
                prefixBox.classList.add('hidden');
                bulkBox.classList.add('hidden');
                customBox.classList.remove('hidden');
                
                // Set name and required for custom key
                customInput.setAttribute('name', 'cuslicense');
                customInput.setAttribute('required', 'required');
                
                // Remove name from prefix/bulk to avoid validation confusion
                prefixInput.removeAttribute('name');
                bulkInput.value = 1; 
            } else {
                prefixBox.classList.remove('hidden');
                bulkBox.classList.remove('hidden');
                customBox.classList.add('hidden');
                
                // Reset names for random bulk generation
                customInput.removeAttribute('name');
                customInput.removeAttribute('required');
                prefixInput.setAttribute('name', 'prefix');
            }
            updateCost();
        }

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

        function copyBulk(btn) {
            const text = document.getElementById('bulkKeys').value;
            navigator.clipboard.writeText(text).then(() => {
                const original = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-check"></i> Done!';
                btn.classList.replace('bg-emerald-500', 'bg-indigo-500');
                setTimeout(() => {
                    btn.innerHTML = original;
                    btn.classList.replace('bg-indigo-500', 'bg-emerald-500');
                }, 2000);
            });
        }

        function copySingle(text, btn) {
            navigator.clipboard.writeText(text).then(() => {
                const icon = btn.querySelector('i');
                const label = btn.querySelector('.copy-text');
                
                if (icon) icon.classList.replace('fa-copy', 'fa-check');
                if (label) label.innerText = 'Copied!';
                btn.classList.add('text-indigo-400');
                
                setTimeout(() => {
                    if (icon) icon.classList.replace('fa-check', 'fa-copy');
                    if (label) label.innerText = 'Copy Details';
                    btn.classList.remove('text-indigo-400');
                }, 2000);
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            const priceMap = <?= is_string($price) ? $price : json_encode($price) ?>;
            const maxDevicesInput = document.getElementById('max_devices');
            const bulkCountInput = document.getElementById('bulk_count');
            const durationInput = document.getElementById('duration');
            const costDisplay = document.getElementById('totalCost');

            window.updateCost = function() {
                const maxDevices = parseInt(maxDevicesInput.value) || 0;
                const bulkCount = parseInt(bulkCountInput.value) || 0;
                const duration = durationInput.value;
                const unitPrice = priceMap[duration] || 0;

                const total = (unitPrice * maxDevices) * bulkCount;
                costDisplay.innerText = total.toFixed(2);
            }

            maxDevicesInput.addEventListener('input', updateCost);
            bulkCountInput.addEventListener('input', updateCost);
            durationInput.addEventListener('change', updateCost);

            // Initial calc
            updateCost();
            // Ensure correct view on load
            toggleKeyType();
        });
    </script>
</body>
</html>
