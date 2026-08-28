<?php
// ====================================================================
// RAPID CORE & GAME PRESET DOWNLOAD CENTER CONFIGURATION
// Customize download links, app metadata, and support info below.
// ====================================================================

$telegram_username = "RapidCoreOwner";              // Telegram support username (without @)
$telegram_channel  = "https://t.me/RapidCorePanel";  // Telegram official channel URL
$whatsapp_number   = "918438087603";                // WhatsApp contact number (with country code)
$support_email     = "support@rapidcore.com";       // Support Email Address

// General Titles
$panel_name        = "RAPID CORE";
$panel_subtitle    = "OFFICIAL DOWNLOAD CENTER";
$status_text       = "ALL SERVERS ONLINE & 100% SAFE";

// --------------------------------------------------------------------
// APP 1: RAPID CORE VIP APK (Main VIP App)
// --------------------------------------------------------------------
$rapidcore_name          = "RAPID CORE VIP APK";
$rapidcore_subtitle      = "Root & Non-Root Virtual Panel • ESP & Anti-Ban Engine";
$rapidcore_version       = "v3.5.2 Pro";
$rapidcore_size          = "24.8 MB";
$rapidcore_updated       = date('M d, Y');
$rapidcore_min_android   = "Android 7.0+";
$rapidcore_downloads     = "148,290+";
$rapidcore_rating        = "4.9 / 5.0";
// Direct Download Link (Local Path or External URL)
$rapidcore_download_url  = "https://rapidcore.fun/RAPID CORE BRUTAL SAFE .apk";
$rapidcore_mirror_url    = "https://t.me/RapidCorePanel"; // Backup / Telegram mirror link

// --------------------------------------------------------------------
// APP 2: GAME FREE PRESET APP (Free Preset Tool)
// --------------------------------------------------------------------
$gamepreset_name         = "FREE FIRE MAX RAPID GLOBAL";
$gamepreset_subtitle     = "One-Click Headshot Preset • GFX & Sensitivity Booster";
$gamepreset_version      = "v2.1.0 Pro";
$gamepreset_size         = "14.2 MB";
$gamepreset_updated      = date('M d, Y');
$gamepreset_min_android  = "Android 6.0+";
$gamepreset_downloads    = "92,450+";
$gamepreset_rating       = "4.8 / 5.0";
// Direct Download Link
$gamepreset_download_url = "https://rapidcore.fun/apks/FREE%20FIRE%20MAX%20RAPID%20GLOBAL.apks";
$gamepreset_mirror_url   = "https://t.me/RapidCorePanel"; // Backup / Telegram mirror link

// --------------------------------------------------------------------
// APP 3: AUXILIARY UTILITIES (Virtual Space & ZArchiver)
// --------------------------------------------------------------------
$virtual_app_name        = "RAPID VIRTUAL CONTAINER";
$virtual_app_version     = "v1.4.0";
$virtual_app_size        = "18.5 MB";
$virtual_app_url         = "#";

$zarchiver_name          = "ZARCHIVER PRO (CONFIG EXTRACTOR)";
$zarchiver_version       = "v1.0.8";
$zarchiver_size          = "4.8 MB";
$zarchiver_url           = "#";

// App Icons (Custom uploaded icons)
$rapidcore_icon          = "assets/img/rapid-core-icon.png";
$gamepreset_icon         = "assets/img/game-preset-icon.png";

// Construct Contact URLs
$telegram_url = "https://t.me/" . ltrim($telegram_username, '@');
$whatsapp_url = !empty($whatsapp_number) ? "https://wa.me/" . ltrim($whatsapp_number, '+') : "";
$contact_url  = !empty($telegram_url) ? $telegram_url : $whatsapp_url;
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Download Center | <?= htmlspecialchars($panel_name) ?> & Game Presets</title>
    
    <!-- Meta SEO -->
    <meta name="description" content="Download official Rapid Core VIP APK and Game Free Preset App for Free Fire and mobile games. 100% safe, anti-ban engine, root & non-root supported.">
    <meta name="keywords" content="Rapid Core APK, Free Fire Preset App, Game Free Preset, Rapid Core Download, VIP Booster APK, Virtual Panel Download">

    <!-- Page Favicon -->
    <link rel="icon" type="image/png" href="<?= htmlspecialchars($rapidcore_icon) ?>">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" rel="stylesheet" crossorigin="anonymous">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- QRCode JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Outfit', 'sans-serif'],
                        mono: ['Space Grotesk', 'monospace'],
                    },
                    animation: {
                        'blob': 'blob 10s infinite',
                        'pulse-glow': 'pulse-glow 2s infinite',
                        'float': 'float 4s ease-in-out infinite',
                        'shimmer': 'shimmer 2.5s infinite linear',
                    },
                    keyframes: {
                        blob: {
                            '0%': { transform: 'translate(0px, 0px) scale(1)' },
                            '33%': { transform: 'translate(30px, -50px) scale(1.15)' },
                            '66%': { transform: 'translate(-20px, 20px) scale(0.9)' },
                            '100%': { transform: 'translate(0px, 0px) scale(1)' }
                        },
                        'pulse-glow': {
                            '0%, 100%': { opacity: '0.6', filter: 'drop-shadow(0 0 8px rgba(168, 85, 247, 0.5))' },
                            '50%': { opacity: '1', filter: 'drop-shadow(0 0 25px rgba(168, 85, 247, 0.9))' }
                        },
                        float: {
                            '0%, 100%': { transform: 'translateY(0px)' },
                            '50%': { transform: 'translateY(-10px)' },
                        },
                        shimmer: {
                            '0%': { backgroundPosition: '-200% 0' },
                            '100%': { backgroundPosition: '200% 0' }
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
            overflow-x: hidden;
        }
        
        /* Animated Background Blobs */
        .blob-1 { background: radial-gradient(circle, rgba(99,102,241,0.22) 0%, rgba(99,102,241,0) 70%); }
        .blob-2 { background: radial-gradient(circle, rgba(168,85,247,0.22) 0%, rgba(168,85,247,0) 70%); }
        .blob-3 { background: radial-gradient(circle, rgba(56,189,248,0.18) 0%, rgba(56,189,248,0) 70%); }
        .blob-4 { background: radial-gradient(circle, rgba(236,72,153,0.18) 0%, rgba(236,72,153,0) 70%); }
        
        .glass-panel {
            background: rgba(17, 24, 39, 0.65);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(255, 255, 255, 0.07);
            box-shadow: inset 0 0 20px rgba(255, 255, 255, 0.02), 0 8px 32px rgba(0, 0, 0, 0.4);
        }

        .download-card {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
        }

        .download-card::after {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: inherit;
            padding: 1px;
            background: linear-gradient(to bottom, rgba(255,255,255,0.12), rgba(255,255,255,0.02));
            -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            -webkit-mask-composite: xor;
            mask-composite: exclude;
            pointer-events: none;
            transition: background 0.4s ease;
        }

        .download-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 45px rgba(0, 0, 0, 0.5), 0 0 35px rgba(168, 85, 247, 0.2);
            border-color: rgba(168, 85, 247, 0.4);
        }

        .download-card:hover::after {
            background: linear-gradient(to bottom, rgba(168, 85, 247, 0.6), rgba(56, 189, 248, 0.3));
        }

        /* Shimmer Button */
        .btn-shimmer {
            background-size: 200% auto;
            background-image: linear-gradient(to right, #6366f1 0%, #a855f7 51%, #6366f1 100%);
            transition: 0.5s;
        }
        .btn-shimmer:hover {
            background-position: right center;
        }

        .btn-emerald {
            background-size: 200% auto;
            background-image: linear-gradient(to right, #059669 0%, #10b981 51%, #059669 100%);
            transition: 0.5s;
        }
        .btn-emerald:hover {
            background-position: right center;
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #030712; 
        }
        ::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.12); 
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: rgba(168, 85, 247, 0.5); 
        }

        /* Accordion transition */
        .faq-answer {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s cubic-bezier(0, 1, 0, 1);
        }
        .faq-answer.open {
            max-height: 500px;
            transition: max-height 0.4s ease-in-out;
        }
    </style>
</head>
<body class="min-h-screen relative flex flex-col justify-between antialiased font-sans">

    <!-- Toast Notification Container -->
    <div id="toast" class="fixed bottom-6 right-6 z-50 transform translate-y-20 opacity-0 transition-all duration-300 pointer-events-none glass-panel px-5 py-3.5 rounded-xl border border-purple-500/30 flex items-center gap-3 shadow-2xl">
        <i id="toast-icon" class="fas fa-check-circle text-emerald-400 text-lg"></i>
        <span id="toast-msg" class="text-sm font-medium text-white">Link copied to clipboard!</span>
    </div>

    <!-- Animated Dynamic Background Blobs -->
    <div class="fixed inset-0 w-full h-full overflow-hidden pointer-events-none z-0">
        <div class="absolute top-[-10%] left-[-10%] w-[550px] h-[550px] blob-1 rounded-full mix-blend-screen filter blur-[90px] opacity-70 animate-blob"></div>
        <div class="absolute top-[25%] right-[-10%] w-[550px] h-[550px] blob-2 rounded-full mix-blend-screen filter blur-[90px] opacity-70 animate-blob" style="animation-delay: 2s;"></div>
        <div class="absolute bottom-[-10%] left-[15%] w-[650px] h-[650px] blob-3 rounded-full mix-blend-screen filter blur-[110px] opacity-60 animate-blob" style="animation-delay: 4s;"></div>
        <div class="absolute bottom-[30%] right-[20%] w-[450px] h-[450px] blob-4 rounded-full mix-blend-screen filter blur-[80px] opacity-50 animate-blob" style="animation-delay: 6s;"></div>
    </div>

    <!-- Top Navigation Bar -->
    <nav class="relative z-20 w-full max-w-6xl mx-auto px-4 pt-6 pb-2">
        <div class="glass-panel px-5 py-3 rounded-2xl flex items-center justify-between border border-white/10 shadow-2xl">
            <!-- Brand Logo -->
            <a href="pricelist.php" class="flex items-center gap-3 group">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-600 to-purple-600 p-0.5 shadow-lg shadow-indigo-600/30 group-hover:scale-105 transition-transform overflow-hidden">
                    <img src="<?= htmlspecialchars($rapidcore_icon) ?>" alt="Rapid Core Logo" class="w-full h-full object-cover rounded-[10px]">
                </div>
                <div>
                    <span class="text-xl font-extrabold text-white tracking-wider block font-sans leading-none">RAPID CORE</span>
                    <span class="text-[10px] font-mono text-purple-400 tracking-widest uppercase">Official Downloads</span>
                </div>
            </a>

            <!-- Navigation Action Buttons -->
            <div class="flex items-center gap-2 sm:gap-3">
                <a href="pricelist.php" class="px-3.5 py-2 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 text-slate-300 hover:text-white text-xs font-semibold transition-all flex items-center gap-2">
                    <i class="fas fa-tags text-indigo-400"></i>
                    <span class="hidden sm:inline">Pricing & Keys</span>
                </a>

                <a href="<?= htmlspecialchars($telegram_channel) ?>" target="_blank" class="px-3.5 py-2 rounded-xl bg-sky-500/10 hover:bg-sky-500/20 border border-sky-500/20 text-sky-300 hover:text-white text-xs font-semibold transition-all flex items-center gap-2">
                    <i class="fab fa-telegram text-sky-400 text-sm"></i>
                    <span class="hidden sm:inline">Telegram</span>
                </a>

                <?php if (!empty($whatsapp_url)): ?>
                <a href="<?= htmlspecialchars($whatsapp_url) ?>" target="_blank" class="px-3 py-2 rounded-xl bg-emerald-500/10 hover:bg-emerald-500/20 border border-emerald-500/20 text-emerald-300 hover:text-white text-xs font-semibold transition-all flex items-center gap-2">
                    <i class="fab fa-whatsapp text-emerald-400 text-sm"></i>
                    <span class="hidden md:inline">Support</span>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Main Container -->
    <main class="relative z-10 w-full max-w-6xl mx-auto px-4 py-8 flex-grow">
        
        <!-- Header Hero Section -->
        <header class="text-center mb-12">
            <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full glass-panel border border-emerald-500/30 text-emerald-300 text-xs font-semibold uppercase tracking-wider mb-5 animate-pulse-glow">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-ping"></span>
                <i class="fas fa-shield-alt text-emerald-400"></i>
                <?= htmlspecialchars($status_text) ?>
            </div>
            
            <h1 class="text-4xl sm:text-5xl md:text-6xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-white via-slate-100 to-slate-400 tracking-tight font-sans mb-3">
                Download Center
            </h1>
            
            <p class="text-lg md:text-xl font-bold font-mono text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 via-purple-400 to-pink-400 uppercase tracking-wide">
                RAPID CORE VIP APK & GAME FREE PRESET
            </p>
            
            <p class="text-slate-400 max-w-2xl mx-auto text-sm sm:text-base mt-4 leading-relaxed">
                Get the latest official APK builds, Sensitivity Presets, and Virtual panel tools. Optimized for smooth 90FPS gaming, low latency, and 100% Anti-Ban protection on Root & Non-Root Android.
            </p>

            <!-- Quick Live Stats Bar -->
            <div class="mt-8 flex flex-wrap justify-center items-center gap-4 sm:gap-8 text-slate-300 text-xs font-mono">
                <div class="glass-panel px-4 py-2 rounded-xl border border-white/5 flex items-center gap-2">
                    <i class="fas fa-download text-indigo-400"></i>
                    <span>Total Downloads: <strong class="text-white font-bold">240,000+</strong></span>
                </div>
                <div class="glass-panel px-4 py-2 rounded-xl border border-white/5 flex items-center gap-2">
                    <i class="fab fa-android text-emerald-400"></i>
                    <span>Android 7 - 15 Supported</span>
                </div>
                <div class="glass-panel px-4 py-2 rounded-xl border border-white/5 flex items-center gap-2">
                    <i class="fas fa-bolt text-yellow-400"></i>
                    <span>Fast CDN Mirrors</span>
                </div>
            </div>
        </header>

        <!-- Category Tabs / App Filter -->
        <div class="flex justify-center mb-10">
            <div class="glass-panel p-1 rounded-xl flex flex-wrap justify-center gap-1 border border-white/5 shadow-xl">
                <button onclick="filterApps('all')" id="filter-all" class="app-filter-tab active px-5 py-2 rounded-lg text-xs sm:text-sm font-semibold transition-all duration-300 text-white bg-gradient-to-r from-indigo-600 to-purple-600 shadow-md">
                    <i class="fas fa-th-large mr-1.5"></i> All Downloads
                </button>
                <button onclick="filterApps('rapidcore')" id="filter-rapidcore" class="app-filter-tab px-5 py-2 rounded-lg text-xs sm:text-sm font-semibold transition-all duration-300 text-slate-400 hover:text-white hover:bg-white/5 flex items-center gap-1.5">
                    <img src="<?= htmlspecialchars($rapidcore_icon) ?>" alt="Icon" class="w-4 h-4 rounded-full object-cover">
                    <span>Rapid Core APK</span>
                </button>
                <button onclick="filterApps('preset')" id="filter-preset" class="app-filter-tab px-5 py-2 rounded-lg text-xs sm:text-sm font-semibold transition-all duration-300 text-slate-400 hover:text-white hover:bg-white/5 flex items-center gap-1.5">
                    <img src="<?= htmlspecialchars($gamepreset_icon) ?>" alt="Icon" class="w-4 h-4 rounded-full object-cover">
                    <span>Game Free Preset</span>
                </button>
            </div>
        </div>

        <!-- MAIN DOWNLOAD CARDS GRID -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-16 items-stretch" id="download-cards-container">
            
            <!-- ========================================================= -->
            <!-- CARD 1: RAPID CORE VIP APK (FLAGSHIP VIP APP) -->
            <!-- ========================================================= -->
            <div class="app-card download-card glass-panel rounded-3xl p-6 sm:p-8 flex flex-col justify-between overflow-hidden relative group" data-category="rapidcore">
                <!-- Glowing Top Accent Line -->
                <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500"></div>
                
                <!-- Background Glowing Icon -->
                <div class="absolute -right-10 -bottom-10 opacity-5 group-hover:opacity-10 transition-opacity pointer-events-none">
                    <i class="fas fa-shield-halved text-[240px] text-purple-400"></i>
                </div>

                <div>
                    <!-- Badges Header -->
                    <div class="flex flex-wrap items-center justify-between gap-2 mb-6">
                        <span class="px-3 py-1 rounded-full bg-gradient-to-r from-indigo-500/20 to-purple-500/20 border border-indigo-500/30 text-indigo-300 text-xs font-mono font-extrabold uppercase tracking-wider flex items-center gap-1.5">
                            <i class="fas fa-crown text-amber-400"></i> OFFICIAL VIP PANEL
                        </span>
                        <span class="px-2.5 py-1 rounded-lg bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 text-xs font-mono font-bold flex items-center gap-1">
                            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span> Anti-Ban Safe
                        </span>
                    </div>

                    <!-- App Title & Icon Header -->
                    <div class="flex items-start gap-4 mb-6">
                        <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl bg-gradient-to-tr from-indigo-600 via-purple-600 to-pink-600 p-0.5 shadow-xl shadow-indigo-600/30 flex-shrink-0">
                            <div class="w-full h-full bg-slate-950 rounded-[14px] overflow-hidden">
                                <img src="<?= htmlspecialchars($rapidcore_icon) ?>" alt="Rapid Core VIP Icon" class="w-full h-full object-cover rounded-[14px]">
                            </div>
                        </div>
                        <div>
                            <h2 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight leading-tight">
                                <?= htmlspecialchars($rapidcore_name) ?>
                            </h2>
                            <p class="text-xs sm:text-sm text-slate-400 mt-1 leading-snug">
                                <?= htmlspecialchars($rapidcore_subtitle) ?>
                            </p>
                            
                            <div class="flex flex-wrap items-center gap-3 mt-3 text-xs font-mono text-slate-300">
                                <span class="bg-white/5 px-2.5 py-1 rounded-md border border-white/5">
                                    <i class="fas fa-code-branch text-purple-400 mr-1"></i> <?= htmlspecialchars($rapidcore_version) ?>
                                </span>
                                <span class="bg-white/5 px-2.5 py-1 rounded-md border border-white/5">
                                    <i class="fas fa-star text-amber-400 mr-1"></i> <?= htmlspecialchars($rapidcore_rating) ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Spec Grid -->
                    <div class="grid grid-cols-2 gap-3 mb-6 p-3.5 rounded-2xl bg-white/[0.02] border border-white/5 text-xs font-mono">
                        <div>
                            <span class="text-slate-500 block text-[10px] uppercase">Compatibility</span>
                            <span class="text-slate-200 font-semibold"><?= htmlspecialchars($rapidcore_min_android) ?> (Root & Non-Root)</span>
                        </div>
                        <div>
                            <span class="text-slate-500 block text-[10px] uppercase">Total Downloads</span>
                            <span class="text-indigo-300 font-bold"><?= htmlspecialchars($rapidcore_downloads) ?></span>
                        </div>
                        <div>
                            <span class="text-slate-500 block text-[10px] uppercase">Last Updated</span>
                            <span class="text-slate-200 font-semibold"><?= htmlspecialchars($rapidcore_updated) ?></span>
                        </div>
                        <div>
                            <span class="text-slate-500 block text-[10px] uppercase">Virus Scan</span>
                            <span class="text-emerald-400 font-bold"><i class="fas fa-check-double mr-1"></i> Passed Clean</span>
                        </div>
                    </div>

                    <!-- Key Features Bullet List -->
                    <h4 class="text-xs font-mono font-bold tracking-widest text-slate-400 uppercase mb-3">Key Features:</h4>
                    <ul class="space-y-2.5 mb-8 text-xs sm:text-sm text-slate-300">
                        <li class="flex items-center gap-2.5">
                            <div class="w-5 h-5 rounded-full bg-indigo-500/20 flex items-center justify-center text-indigo-400 text-xs flex-shrink-0">
                                <i class="fas fa-check"></i>
                            </div>
                            <span><strong>ESP & Aimbot Injection Engine:</strong> Smooth tracking with 0% lag</span>
                        </li>
                        <li class="flex items-center gap-2.5">
                            <div class="w-5 h-5 rounded-full bg-indigo-500/20 flex items-center justify-center text-indigo-400 text-xs flex-shrink-0">
                                <i class="fas fa-check"></i>
                            </div>
                            <span><strong>Server Bypass Guard:</strong> Full Anti-Cheat bypass for rank pushing</span>
                        </li>
                        <li class="flex items-center gap-2.5">
                            <div class="w-5 h-5 rounded-full bg-indigo-500/20 flex items-center justify-center text-indigo-400 text-xs flex-shrink-0">
                                <i class="fas fa-check"></i>
                            </div>
                            <span><strong>Root & Non-Root Support:</strong> Virtual container for unrooted phones</span>
                        </li>
                        <li class="flex items-center gap-2.5">
                            <div class="w-5 h-5 rounded-full bg-indigo-500/20 flex items-center justify-center text-indigo-400 text-xs flex-shrink-0">
                                <i class="fas fa-check"></i>
                            </div>
                            <span><strong>Key System Integrated:</strong> Compatible with 1D/7D/30D keys from Pricelist</span>
                        </li>
                    </ul>
                </div>

                <!-- Download Actions Container -->
                <div class="space-y-3 pt-4 border-t border-white/10">
                    <!-- Primary Direct Download Button -->
                    <a href="<?= htmlspecialchars($rapidcore_download_url) ?>" download class="w-full py-4 rounded-xl btn-shimmer text-white font-bold text-sm sm:text-base shadow-xl shadow-indigo-600/30 flex items-center justify-center gap-3 transition-all hover:scale-[1.02] active:scale-[0.98]">
                        <i class="fas fa-download text-lg animate-bounce"></i>
                        <span>DIRECT DOWNLOAD APK</span>
                    </a>

                    <!-- Secondary Options: Mirror & QR Code Modal -->
                    <div class="grid grid-cols-2 gap-2">
                        <a href="<?= htmlspecialchars($rapidcore_mirror_url) ?>" target="_blank" class="py-2.5 px-3 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 text-slate-300 hover:text-white text-xs font-semibold transition-all text-center flex items-center justify-center gap-2">
                            <i class="fab fa-telegram text-sky-400"></i> Telegram Mirror
                        </a>

                        <button onclick="showQRCode('<?= htmlspecialchars($rapidcore_name) ?>', '<?= htmlspecialchars($rapidcore_download_url) ?>')" class="py-2.5 px-3 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 text-slate-300 hover:text-white text-xs font-semibold transition-all flex items-center justify-center gap-2">
                            <i class="fas fa-qrcode text-purple-400"></i> Scan QR Code
                        </button>
                    </div>

                    <!-- Copy Direct Link Button -->
                    <button onclick="copyToClipboard('<?= htmlspecialchars($rapidcore_download_url) ?>', 'Rapid Core APK Link Copied!')" class="w-full text-center py-2 text-xs font-mono text-slate-400 hover:text-slate-200 transition-colors flex items-center justify-center gap-1.5">
                        <i class="fas fa-link text-[10px]"></i> Copy Direct Link
                    </button>
                </div>
            </div>

            <!-- ========================================================= -->
            <!-- CARD 2: GAME FREE PRESET APP (FREE PRESET & SENSITIVITY) -->
            <!-- ========================================================= -->
            <div class="app-card download-card glass-panel rounded-3xl p-6 sm:p-8 flex flex-col justify-between overflow-hidden relative group" data-category="preset">
                <!-- Glowing Top Accent Line -->
                <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-emerald-400 via-teal-500 to-cyan-500"></div>
                
                <!-- Background Glowing Icon -->
                <div class="absolute -right-10 -bottom-10 opacity-5 group-hover:opacity-10 transition-opacity pointer-events-none">
                    <i class="fas fa-crosshair text-[240px] text-emerald-400"></i>
                </div>

                <div>
                    <!-- Badges Header -->
                    <div class="flex flex-wrap items-center justify-between gap-2 mb-6">
                        <span class="px-3 py-1 rounded-full bg-gradient-to-r from-emerald-500/20 to-teal-500/20 border border-emerald-500/30 text-emerald-300 text-xs font-mono font-extrabold uppercase tracking-wider flex items-center gap-1.5">
                            <i class="fas fa-gift text-emerald-400"></i> 100% FREE PRESET APP
                        </span>
                        <span class="px-2.5 py-1 rounded-lg bg-teal-500/10 text-teal-300 border border-teal-500/20 text-xs font-mono font-bold flex items-center gap-1">
                            <i class="fas fa-star text-amber-400"></i> No Key Needed
                        </span>
                    </div>

                    <!-- App Title & Icon Header -->
                    <div class="flex items-start gap-4 mb-6">
                        <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl bg-gradient-to-tr from-emerald-600 via-teal-600 to-cyan-600 p-0.5 shadow-xl shadow-emerald-600/30 flex-shrink-0">
                            <div class="w-full h-full bg-slate-950 rounded-[14px] overflow-hidden">
                                <img src="<?= htmlspecialchars($gamepreset_icon) ?>" alt="Game Free Preset Icon" class="w-full h-full object-cover rounded-[14px]">
                            </div>
                        </div>
                        <div>
                            <h2 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight leading-tight">
                                <?= htmlspecialchars($gamepreset_name) ?>
                            </h2>
                            <p class="text-xs sm:text-sm text-slate-400 mt-1 leading-snug">
                                <?= htmlspecialchars($gamepreset_subtitle) ?>
                            </p>
                            
                            <div class="flex flex-wrap items-center gap-3 mt-3 text-xs font-mono text-slate-300">
                                <span class="bg-white/5 px-2.5 py-1 rounded-md border border-white/5">
                                    <i class="fas fa-code-branch text-emerald-400 mr-1"></i> <?= htmlspecialchars($gamepreset_version) ?>
                                </span>
                                <span class="bg-white/5 px-2.5 py-1 rounded-md border border-white/5">
                                    <i class="fas fa-star text-amber-400 mr-1"></i> <?= htmlspecialchars($gamepreset_rating) ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Spec Grid -->
                    <div class="grid grid-cols-2 gap-3 mb-6 p-3.5 rounded-2xl bg-white/[0.02] border border-white/5 text-xs font-mono">
                        <div>
                            <span class="text-slate-500 block text-[10px] uppercase">Compatibility</span>
                            <span class="text-slate-200 font-semibold"><?= htmlspecialchars($gamepreset_min_android) ?> (All Android)</span>
                        </div>
                        <div>
                            <span class="text-slate-500 block text-[10px] uppercase">Total Downloads</span>
                            <span class="text-emerald-300 font-bold"><?= htmlspecialchars($gamepreset_downloads) ?></span>
                        </div>
                        <div>
                            <span class="text-slate-500 block text-[10px] uppercase">Last Updated</span>
                            <span class="text-slate-200 font-semibold"><?= htmlspecialchars($gamepreset_updated) ?></span>
                        </div>
                        <div>
                            <span class="text-slate-500 block text-[10px] uppercase">Access Level</span>
                            <span class="text-emerald-400 font-bold"><i class="fas fa-unlock mr-1"></i> Fully Unlocked</span>
                        </div>
                    </div>

                    <!-- Key Features Bullet List -->
                    <h4 class="text-xs font-mono font-bold tracking-widest text-slate-400 uppercase mb-3">Key Features:</h4>
                    <ul class="space-y-2.5 mb-8 text-xs sm:text-sm text-slate-300">
                        <li class="flex items-center gap-2.5">
                            <div class="w-5 h-5 rounded-full bg-emerald-500/20 flex items-center justify-center text-emerald-400 text-xs flex-shrink-0">
                                <i class="fas fa-check"></i>
                            </div>
                            <span><strong>1-Click Pro Presets:</strong> iPhone, ROG, & RedMagic Drag Headshot Sensitivity</span>
                        </li>
                        <li class="flex items-center gap-2.5">
                            <div class="w-5 h-5 rounded-full bg-emerald-500/20 flex items-center justify-center text-emerald-400 text-xs flex-shrink-0">
                                <i class="fas fa-check"></i>
                            </div>
                            <span><strong>GFX Frame Rate Optimizer:</strong> Unlock 60FPS / 90FPS / 120FPS smooth graphics</span>
                        </li>
                        <li class="flex items-center gap-2.5">
                            <div class="w-5 h-5 rounded-full bg-emerald-500/20 flex items-center justify-center text-emerald-400 text-xs flex-shrink-0">
                                <i class="fas fa-check"></i>
                            </div>
                            <span><strong>RAM Booster & Lag Killer:</strong> Auto memory clean up before launching games</span>
                        </li>
                        <li class="flex items-center gap-2.5">
                            <div class="w-5 h-5 rounded-full bg-emerald-500/20 flex items-center justify-center text-emerald-400 text-xs flex-shrink-0">
                                <i class="fas fa-check"></i>
                            </div>
                            <span><strong>No Registration Needed:</strong> Download and apply presets instantly</span>
                        </li>
                    </ul>
                </div>

                <!-- Download Actions Container -->
                <div class="space-y-3 pt-4 border-t border-white/10">
                    <!-- Primary Direct Download Button -->
                    <a href="<?= htmlspecialchars($gamepreset_download_url) ?>" download class="w-full py-4 rounded-xl btn-emerald text-white font-bold text-sm sm:text-base shadow-xl shadow-emerald-600/30 flex items-center justify-center gap-3 transition-all hover:scale-[1.02] active:scale-[0.98]">
                        <i class="fas fa-download text-lg animate-bounce"></i>
                        <span>DOWNLOAD GAME PRESET APK</span>
                    </a>

                    <!-- Secondary Options: Mirror & QR Code Modal -->
                    <div class="grid grid-cols-2 gap-2">
                        <a href="<?= htmlspecialchars($gamepreset_mirror_url) ?>" target="_blank" class="py-2.5 px-3 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 text-slate-300 hover:text-white text-xs font-semibold transition-all text-center flex items-center justify-center gap-2">
                            <i class="fab fa-telegram text-sky-400"></i> Telegram Mirror
                        </a>

                        <button onclick="showQRCode('<?= htmlspecialchars($gamepreset_name) ?>', '<?= htmlspecialchars($gamepreset_download_url) ?>')" class="py-2.5 px-3 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 text-slate-300 hover:text-white text-xs font-semibold transition-all flex items-center justify-center gap-2">
                            <i class="fas fa-qrcode text-emerald-400"></i> Scan QR Code
                        </button>
                    </div>

                    <!-- Copy Direct Link Button -->
                    <button onclick="copyToClipboard('<?= htmlspecialchars($gamepreset_download_url) ?>', 'Game Preset APK Link Copied!')" class="w-full text-center py-2 text-xs font-mono text-slate-400 hover:text-slate-200 transition-colors flex items-center justify-center gap-1.5">
                        <i class="fas fa-link text-[10px]"></i> Copy Direct Link
                    </button>
                </div>
            </div>

        </div>



        <!-- ========================================================= -->
        <!-- STEP-BY-STEP INSTALLATION GUIDE -->
        <!-- ========================================================= -->
        <section class="mb-16">
            <div class="text-center mb-10">
                <span class="text-xs font-mono font-bold tracking-widest text-indigo-400 uppercase mb-2 block">Quick Start Guide</span>
                <h2 class="text-3xl font-extrabold text-white tracking-tight">How To Download & Install</h2>
                <p class="text-slate-400 text-xs sm:text-sm mt-1">Follow these 4 simple steps to set up Rapid Core & Game Presets on your device</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Step 1 -->
                <div class="glass-panel rounded-2xl p-6 relative overflow-hidden group">
                    <div class="w-10 h-10 rounded-xl bg-indigo-600/20 text-indigo-400 flex items-center justify-center font-mono font-extrabold text-lg mb-4 border border-indigo-500/30">
                        01
                    </div>
                    <h3 class="text-white text-base font-bold mb-2 flex items-center gap-2">
                        <i class="fas fa-file-download text-indigo-400"></i> Download APK
                    </h3>
                    <p class="text-slate-400 text-xs leading-relaxed">
                        Click the Direct Download button above for Rapid Core VIP APK or Game Free Preset App.
                    </p>
                </div>

                <!-- Step 2 -->
                <div class="glass-panel rounded-2xl p-6 relative overflow-hidden group">
                    <div class="w-10 h-10 rounded-xl bg-purple-600/20 text-purple-400 flex items-center justify-center font-mono font-extrabold text-lg mb-4 border border-purple-500/30">
                        02
                    </div>
                    <h3 class="text-white text-base font-bold mb-2 flex items-center gap-2">
                        <i class="fas fa-cog text-purple-400"></i> Allow Installation
                    </h3>
                    <p class="text-slate-400 text-xs leading-relaxed">
                        Go to Android Settings &gt; Security &gt; Enable "Install Unknown Apps / Sources" for your browser.
                    </p>
                </div>

                <!-- Step 3 -->
                <div class="glass-panel rounded-2xl p-6 relative overflow-hidden group">
                    <div class="w-10 h-10 rounded-xl bg-pink-600/20 text-pink-400 flex items-center justify-center font-mono font-extrabold text-lg mb-4 border border-pink-500/30">
                        03
                    </div>
                    <h3 class="text-white text-base font-bold mb-2 flex items-center gap-2">
                        <i class="fas fa-play text-pink-400"></i> Install & Launch
                    </h3>
                    <p class="text-slate-400 text-xs leading-relaxed">
                        Open the downloaded file, click Install, and grant required permissions (Storage / Display over apps).
                    </p>
                </div>

                <!-- Step 4 -->
                <div class="glass-panel rounded-2xl p-6 relative overflow-hidden group">
                    <div class="w-10 h-10 rounded-xl bg-emerald-600/20 text-emerald-400 flex items-center justify-center font-mono font-extrabold text-lg mb-4 border border-emerald-500/30">
                        04
                    </div>
                    <h3 class="text-white text-base font-bold mb-2 flex items-center gap-2">
                        <i class="fas fa-key text-emerald-400"></i> Enter Key & Play
                    </h3>
                    <p class="text-slate-400 text-xs leading-relaxed">
                        For Rapid Core VIP, enter your License Key (buy from Price List). Game Preset app works 100% free!
                    </p>
                </div>
            </div>
        </section>

        <!-- ========================================================= -->
        <!-- PLAY PROTECT & SECURITY DISCLAIMER CARD -->
        <!-- ========================================================= -->
        <section class="glass-panel rounded-3xl p-6 sm:p-8 mb-16 border border-amber-500/20 relative overflow-hidden">
            <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
                <div class="flex items-start gap-4">
                    <div class="w-14 h-14 rounded-2xl bg-amber-500/10 border border-amber-500/30 text-amber-400 flex items-center justify-center text-2xl flex-shrink-0">
                        <i class="fas fa-shield-cat"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-white tracking-tight flex items-center gap-2">
                            Google Play Protect Warning Notice
                        </h3>
                        <p class="text-slate-300 text-xs sm:text-sm mt-1 leading-relaxed max-w-2xl">
                            Because Rapid Core uses memory injection hooks to bypass game server checks, Play Protect may trigger a false-positive flag. Rest assured, our builds are 100% free from malware or spy scripts. Select <strong>"Install Anyway (Unsafe)"</strong> during installation.
                        </p>
                    </div>
                </div>

                <a href="<?= htmlspecialchars($contact_url) ?>" target="_blank" class="px-5 py-3 rounded-xl bg-amber-500/20 hover:bg-amber-500/30 border border-amber-500/30 text-amber-200 text-xs font-bold transition-all flex-shrink-0 flex items-center gap-2">
                    <i class="fab fa-telegram"></i> Ask Admin Support
                </a>
            </div>
        </section>

        <!-- ========================================================= -->
        <!-- FREQUENTLY ASKED QUESTIONS (FAQ) ACCORDION -->
        <!-- ========================================================= -->
        <section class="mb-16 max-w-4xl mx-auto">
            <div class="text-center mb-10">
                <span class="text-xs font-mono font-bold tracking-widest text-purple-400 uppercase mb-2 block">Got Questions?</span>
                <h2 class="text-3xl font-extrabold text-white tracking-tight">Frequently Asked Questions</h2>
            </div>

            <div class="space-y-4">
                <!-- FAQ Item 1 -->
                <div class="glass-panel rounded-2xl border border-white/10 overflow-hidden">
                    <button onclick="toggleFaq(1)" class="w-full p-5 text-left flex items-center justify-between font-bold text-white text-sm sm:text-base hover:bg-white/5 transition-colors">
                        <span class="flex items-center gap-3">
                            <i class="fas fa-question-circle text-indigo-400"></i>
                            Do I need a rooted phone to use Rapid Core APK?
                        </span>
                        <i id="faq-icon-1" class="fas fa-chevron-down text-slate-400 transition-transform duration-300"></i>
                    </button>
                    <div id="faq-answer-1" class="faq-answer px-5 pb-5 text-slate-300 text-xs sm:text-sm leading-relaxed border-t border-white/5 pt-3">
                        No! Rapid Core supports both <strong>Rooted</strong> and <strong>Non-Rooted</strong> Android devices. For non-rooted devices, simply use our Rapid Virtual space container included in the Utilities section.
                    </div>
                </div>

                <!-- FAQ Item 2 -->
                <div class="glass-panel rounded-2xl border border-white/10 overflow-hidden">
                    <button onclick="toggleFaq(2)" class="w-full p-5 text-left flex items-center justify-between font-bold text-white text-sm sm:text-base hover:bg-white/5 transition-colors">
                        <span class="flex items-center gap-3">
                            <i class="fas fa-question-circle text-emerald-400"></i>
                            Is Game Free Preset App really 100% free?
                        </span>
                        <i id="faq-icon-2" class="fas fa-chevron-down text-slate-400 transition-transform duration-300"></i>
                    </button>
                    <div id="faq-answer-2" class="faq-answer px-5 pb-5 text-slate-300 text-xs sm:text-sm leading-relaxed border-t border-white/5 pt-3">
                        Yes! The Game Free Preset App is completely free without any mandatory key purchase. You can download drag sensitivity presets, GFX resolution tools, and lag fixes with a single tap.
                    </div>
                </div>

                <!-- FAQ Item 3 -->
                <div class="glass-panel rounded-2xl border border-white/10 overflow-hidden">
                    <button onclick="toggleFaq(3)" class="w-full p-5 text-left flex items-center justify-between font-bold text-white text-sm sm:text-base hover:bg-white/5 transition-colors">
                        <span class="flex items-center gap-3">
                            <i class="fas fa-question-circle text-purple-400"></i>
                            Where can I get a License Key for Rapid Core VIP?
                        </span>
                        <i id="faq-icon-3" class="fas fa-chevron-down text-slate-400 transition-transform duration-300"></i>
                    </button>
                    <div id="faq-answer-3" class="faq-answer px-5 pb-5 text-slate-300 text-xs sm:text-sm leading-relaxed border-t border-white/5 pt-3">
                        You can purchase instant key passes (1 Day, 7 Days, 14 Days, or 30 Days) on our official <a href="pricelist.php" class="text-indigo-400 underline font-semibold">Price List Page</a> or by contacting our Telegram support directly.
                    </div>
                </div>

                <!-- FAQ Item 4 -->
                <div class="glass-panel rounded-2xl border border-white/10 overflow-hidden">
                    <button onclick="toggleFaq(4)" class="w-full p-5 text-left flex items-center justify-between font-bold text-white text-sm sm:text-base hover:bg-white/5 transition-colors">
                        <span class="flex items-center gap-3">
                            <i class="fas fa-question-circle text-sky-400"></i>
                            How do I update the APK when a new game update arrives?
                        </span>
                        <i id="faq-icon-4" class="fas fa-chevron-down text-slate-400 transition-transform duration-300"></i>
                    </button>
                    <div id="faq-answer-4" class="faq-answer px-5 pb-5 text-slate-300 text-xs sm:text-sm leading-relaxed border-t border-white/5 pt-3">
                        We publish instant bypass updates on our Telegram channel whenever the game updates. You can re-download the updated APK directly from this page without losing your active key duration.
                    </div>
                </div>
            </div>
        </section>

    </main>

    <!-- Modal for QR Code Display -->
    <div id="qr-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-md opacity-0 pointer-events-none transition-opacity duration-300">
        <div class="glass-panel p-6 sm:p-8 rounded-3xl max-w-sm w-full mx-4 border border-purple-500/30 text-center relative transform scale-95 transition-transform duration-300" id="qr-modal-card">
            <button onclick="closeQRCode()" class="absolute top-4 right-4 text-slate-400 hover:text-white text-lg">
                <i class="fas fa-times"></i>
            </button>
            <h3 class="text-xl font-bold text-white mb-1" id="qr-title">Scan QR Code</h3>
            <p class="text-xs text-slate-400 mb-6">Scan with your Android phone to start instant download</p>
            
            <div class="bg-white p-4 rounded-2xl inline-block mb-4 shadow-xl">
                <div id="qrcode-container" class="flex justify-center"></div>
            </div>
            
            <p class="text-[11px] font-mono text-purple-300 break-all px-2 py-1 bg-white/5 rounded" id="qr-url-text"></p>
            
            <button onclick="closeQRCode()" class="mt-6 w-full py-2.5 rounded-xl bg-white/10 hover:bg-white/20 text-white text-xs font-bold transition-all">
                Close Modal
            </button>
        </div>
    </div>

    <!-- Footer -->
    <footer class="relative z-10 border-t border-white/5 bg-slate-950/80 py-10 mt-12">
        <div class="max-w-6xl mx-auto px-4 flex flex-col md:flex-row items-center justify-between gap-6">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-gradient-to-tr from-indigo-600 to-purple-600 flex items-center justify-center text-white text-sm font-bold">
                    <i class="fas fa-bolt text-yellow-300"></i>
                </div>
                <span class="text-slate-400 text-xs font-mono">
                    &copy; <?= date('Y') ?> <strong class="text-white"><?= htmlspecialchars($panel_name) ?></strong>. All rights reserved.
                </span>
            </div>

            <!-- Navigation Links -->
            <div class="flex items-center gap-6 text-xs text-slate-400 font-medium">
                <a href="pricelist.php" class="hover:text-white transition-colors">Pricing List</a>
                <a href="download.php" class="text-purple-400 font-bold hover:text-white transition-colors">Download Center</a>
                <a href="<?= htmlspecialchars($telegram_channel) ?>" target="_blank" class="hover:text-sky-400 transition-colors">Telegram Official</a>
                <?php if (!empty($whatsapp_url)): ?>
                <a href="<?= htmlspecialchars($whatsapp_url) ?>" target="_blank" class="hover:text-emerald-400 transition-colors">WhatsApp Support</a>
                <?php endif; ?>
            </div>
        </div>
    </footer>

    <!-- Interactive Scripts -->
    <script>
        // Copy to clipboard helper with custom toast
        function copyToClipboard(text, message) {
            if (!text || text === '#') {
                showToast('Download link will be updated soon!', true);
                return;
            }
            // Resolve relative link to absolute URL if needed
            const fullUrl = text.startsWith('http') ? text : window.location.origin + '/' + text.replace(/^\//, '');
            navigator.clipboard.writeText(fullUrl).then(() => {
                showToast(message || 'Link copied to clipboard!');
            }).catch(() => {
                showToast('Failed to copy link', true);
            });
        }

        // Custom Toast function
        function showToast(msg, isError = false) {
            const toast = document.getElementById('toast');
            const toastMsg = document.getElementById('toast-msg');
            const toastIcon = document.getElementById('toast-icon');

            toastMsg.innerText = msg;
            if (isError) {
                toastIcon.className = 'fas fa-exclamation-circle text-rose-400 text-lg';
            } else {
                toastIcon.className = 'fas fa-check-circle text-emerald-400 text-lg';
            }

            toast.classList.remove('translate-y-20', 'opacity-0', 'pointer-events-none');
            setTimeout(() => {
                toast.classList.add('translate-y-20', 'opacity-0', 'pointer-events-none');
            }, 3000);
        }

        // App Filtering logic
        function filterApps(category) {
            // Update active tab styles
            document.querySelectorAll('.app-filter-tab').forEach(tab => {
                tab.classList.remove('text-white', 'bg-gradient-to-r', 'from-indigo-600', 'to-purple-600', 'shadow-md');
                tab.classList.add('text-slate-400', 'hover:text-white', 'hover:bg-white/5');
            });

            const activeBtn = document.getElementById('filter-' + category);
            if (activeBtn) {
                activeBtn.classList.remove('text-slate-400', 'hover:text-white', 'hover:bg-white/5');
                activeBtn.classList.add('text-white', 'bg-gradient-to-r', 'from-indigo-600', 'to-purple-600', 'shadow-md');
            }

            // Filter app cards
            document.querySelectorAll('.app-card').forEach(card => {
                const cardCat = card.getAttribute('data-category');
                if (category === 'all' || cardCat === category) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        // FAQ Toggle Logic
        function toggleFaq(index) {
            const answer = document.getElementById('faq-answer-' + index);
            const icon = document.getElementById('faq-icon-' + index);

            if (answer.classList.contains('open')) {
                answer.classList.remove('open');
                icon.style.transform = 'rotate(0deg)';
            } else {
                // Close all other FAQs
                document.querySelectorAll('.faq-answer').forEach(el => el.classList.remove('open'));
                document.querySelectorAll('[id^="faq-icon-"]').forEach(el => el.style.transform = 'rotate(0deg)');

                answer.classList.add('open');
                icon.style.transform = 'rotate(180deg)';
            }
        }

        // QR Code Modal logic
        let qrcodeObj = null;

        function showQRCode(appName, relativeUrl) {
            const fullUrl = relativeUrl.startsWith('http') ? relativeUrl : window.location.origin + '/' + relativeUrl.replace(/^\//, '');
            document.getElementById('qr-title').innerText = appName;
            document.getElementById('qr-url-text').innerText = fullUrl;

            const qrContainer = document.getElementById('qrcode-container');
            qrContainer.innerHTML = '';

            // Generate QR Code using QRCode library
            if (typeof QRCode !== 'undefined') {
                qrcodeObj = new QRCode(qrContainer, {
                    text: fullUrl,
                    width: 180,
                    height: 180,
                    colorDark: "#0f172a",
                    colorLight: "#ffffff",
                    correctLevel: QRCode.CorrectLevel.H
                });
            } else {
                qrContainer.innerHTML = '<p class="text-xs text-rose-500">QR Code library loading...</p>';
            }

            const modal = document.getElementById('qr-modal');
            const card = document.getElementById('qr-modal-card');
            modal.classList.remove('opacity-0', 'pointer-events-none');
            card.classList.remove('scale-95');
            card.classList.add('scale-100');
        }

        function closeQRCode() {
            const modal = document.getElementById('qr-modal');
            const card = document.getElementById('qr-modal-card');
            card.classList.remove('scale-100');
            card.classList.add('scale-95');
            modal.classList.add('opacity-0', 'pointer-events-none');
        }
    </script>
</body>
</html>
