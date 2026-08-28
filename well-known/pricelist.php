<?php
// ====================================================================
// RAPID CORE PRICING PAGE CONFIGURATION
// Customize the contact URLs and settings below to fit your panel.
// ====================================================================

$telegram_username = "RapidCoreOwner";          // Telegram support username (without @)
$telegram_channel  = "https://t.me/RapidCorePanel"; // Telegram official channel URL
$whatsapp_number   = "918438087603";                          // WhatsApp number with country code (e.g. 91xxxxxxxxxx). Leave blank to disable
$support_email     = "support@rapidcore.com";     // Contact email

// Titles & Badges
$panel_name        = "RAPID CORE";
$panel_subtitle    = "ROOT & NON-ROOT VIRTUAL";
$status_text       = "ALL SERVER WORKING & SAFE";

// Construct the contact URL
$telegram_url = "https://t.me/" . ltrim($telegram_username, '@');
$whatsapp_url = !empty($whatsapp_number) ? "https://wa.me/" . ltrim($whatsapp_number, '+') : "";
$contact_url  = !empty($telegram_url) ? $telegram_url : $whatsapp_url;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Price List | <?= htmlspecialchars($panel_name) ?></title>
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" rel="stylesheet" crossorigin="anonymous">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">

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
                        'pulse-glow': 'pulse-glow 2s infinite',
                    },
                    keyframes: {
                        blob: {
                            '0%': { transform: 'translate(0px, 0px) scale(1)' },
                            '33%': { transform: 'translate(30px, -50px) scale(1.15)' },
                            '66%': { transform: 'translate(-20px, 20px) scale(0.9)' },
                            '100%': { transform: 'translate(0px, 0px) scale(1)' }
                        },
                        'pulse-glow': {
                            '0%, 100%': { opacity: '0.6', filter: 'drop-shadow(0 0 5px rgba(168, 85, 247, 0.4))' },
                            '50%': { opacity: '1', filter: 'drop-shadow(0 0 20px rgba(168, 85, 247, 0.8))' }
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
        .blob-1 { background: radial-gradient(circle, rgba(99,102,241,0.2) 0%, rgba(99,102,241,0) 70%); }
        .blob-2 { background: radial-gradient(circle, rgba(168,85,247,0.2) 0%, rgba(168,85,247,0) 70%); }
        .blob-3 { background: radial-gradient(circle, rgba(56,189,248,0.15) 0%, rgba(56,189,248,0) 70%); }
        
        .glass-panel {
            background: rgba(17, 24, 39, 0.6);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            box-shadow: inset 0 0 20px rgba(255, 255, 255, 0.02), 0 8px 32px rgba(0, 0, 0, 0.3);
        }

        .pricing-card {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
        }

        .pricing-card::after {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: inherit;
            padding: 1px;
            background: linear-gradient(to bottom, rgba(255,255,255,0.1), rgba(255,255,255,0.01));
            -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            -webkit-mask-composite: xor;
            mask-composite: exclude;
            pointer-events: none;
            transition: background 0.4s ease;
        }

        .pricing-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4), 0 0 30px rgba(168, 85, 247, 0.15);
            border-color: rgba(168, 85, 247, 0.3);
        }

        .pricing-card:hover::after {
            background: linear-gradient(to bottom, rgba(168, 85, 247, 0.5), rgba(99, 102, 241, 0.2));
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #030712; 
        }
        ::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.1); 
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: rgba(168, 85, 247, 0.4); 
        }

        .price-animation {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .price-changing {
            transform: scale(0.9);
            opacity: 0;
        }
    </style>
</head>
<body class="min-h-screen relative flex flex-col justify-between antialiased">

    <!-- Animated Dynamic Background Blobs -->
    <div class="fixed inset-0 w-full h-full overflow-hidden pointer-events-none z-0">
        <div class="absolute top-[-10%] left-[-10%] w-[500px] h-[500px] blob-1 rounded-full mix-blend-screen filter blur-[80px] opacity-70 animate-blob"></div>
        <div class="absolute top-[30%] right-[-10%] w-[500px] h-[500px] blob-2 rounded-full mix-blend-screen filter blur-[80px] opacity-70 animate-blob" style="animation-delay: 2s;"></div>
        <div class="absolute bottom-[-10%] left-[20%] w-[600px] h-[600px] blob-3 rounded-full mix-blend-screen filter blur-[100px] opacity-60 animate-blob" style="animation-delay: 4s;"></div>
    </div>

    <!-- Top Navigation Bar -->
    <nav class="relative z-20 w-full max-w-6xl mx-auto px-4 pt-6 pb-2">
        <div class="glass-panel px-5 py-3 rounded-2xl flex items-center justify-between border border-white/10 shadow-2xl">
            <!-- Brand Logo -->
            <a href="pricelist.php" class="flex items-center gap-3 group">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-600 to-purple-600 p-0.5 shadow-lg shadow-indigo-600/30 group-hover:scale-105 transition-transform overflow-hidden">
                    <img src="assets/img/rapid-core-icon.png" alt="Rapid Core Logo" class="w-full h-full object-cover rounded-[10px]">
                </div>
                <div>
                    <span class="text-xl font-extrabold text-white tracking-wider block font-sans leading-none"><?= htmlspecialchars($panel_name) ?></span>
                    <span class="text-[10px] font-mono text-purple-400 tracking-widest uppercase"><?= htmlspecialchars($panel_subtitle) ?></span>
                </div>
            </a>

            <!-- Navigation Action Buttons -->
            <div class="flex items-center gap-2 sm:gap-3">
                <a href="download.php" class="px-3.5 py-2 rounded-xl bg-gradient-to-r from-purple-600/30 to-indigo-600/30 hover:from-purple-600/50 hover:to-indigo-600/50 border border-purple-500/30 text-purple-200 hover:text-white text-xs font-bold transition-all flex items-center gap-2 shadow-lg shadow-purple-600/20">
                    <i class="fas fa-download text-purple-300"></i>
                    <span>Download Center</span>
                </a>

                <a href="<?= htmlspecialchars($telegram_channel) ?>" target="_blank" class="px-3.5 py-2 rounded-xl bg-sky-500/10 hover:bg-sky-500/20 border border-sky-500/20 text-sky-300 hover:text-white text-xs font-semibold transition-all flex items-center gap-2">
                    <i class="fab fa-telegram text-sky-400 text-sm"></i>
                    <span class="hidden sm:inline">Telegram</span>
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Container -->
    <div class="relative z-10 w-full max-w-6xl mx-auto px-4 py-8 flex-grow">
        
        <!-- Header -->
        <header class="text-center mb-12">
            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full glass-panel border border-purple-500/20 text-purple-300 text-xs font-semibold uppercase tracking-wider mb-4 animate-pulse-glow">
                <span class="w-2 h-2 rounded-full bg-purple-500 animate-ping"></span>
                <?= htmlspecialchars($status_text) ?>
            </div>
            
            <h1 class="text-4xl md:text-6xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-white via-slate-200 to-slate-400 tracking-tight font-sans">
                <?= htmlspecialchars($panel_name) ?>
            </h1>
            
            <p class="text-lg md:text-xl font-bold font-mono text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 to-purple-400 mt-2 uppercase tracking-wide">
                <?= htmlspecialchars($panel_subtitle) ?>
            </p>
            
            <p class="text-slate-400 max-w-lg mx-auto text-sm mt-4">
                Premium high-security game booster panel. Tested on root & non-root servers for unmatched safety and stability.
            </p>
        </header>

        <!-- Currency/Region Switcher Tabs -->
        <div class="flex justify-center mb-12">
            <div class="glass-panel p-1 rounded-xl flex gap-1 border border-white/5 shadow-2xl relative">
                <!-- Sliding Indicator (handled by JS toggling active states) -->
                <button onclick="setCurrency('in')" id="tab-in" class="currency-tab active flex items-center gap-2 px-6 py-2.5 rounded-lg text-sm font-semibold transition-all duration-300 text-white bg-gradient-to-r from-indigo-600 to-purple-600 shadow-lg shadow-indigo-600/20">
                    <span class="text-lg">🇮🇳</span> India (INR)
                </button>
                <button onclick="setCurrency('bd')" id="tab-bd" class="currency-tab flex items-center gap-2 px-6 py-2.5 rounded-lg text-sm font-semibold transition-all duration-300 text-slate-400 hover:text-white hover:bg-white/5">
                    <span class="text-lg">🇧🇩</span> Bangladesh (BDT)
                </button>
                <button onclick="setCurrency('us')" id="tab-us" class="currency-tab flex items-center gap-2 px-6 py-2.5 rounded-lg text-sm font-semibold transition-all duration-300 text-slate-400 hover:text-white hover:bg-white/5">
                    <span class="text-lg">🌐</span> Global (USD)
                </button>
            </div>
        </div>

        <!-- Section Title: Key Licenses -->
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-white tracking-tight flex items-center gap-2">
                    <i class="fas fa-key text-indigo-400"></i> License Key Plans
                </h2>
                <p class="text-slate-400 text-xs mt-1">Select the duration that matches your gaming schedule</p>
            </div>
            <div class="hidden sm:block text-slate-500 font-mono text-[10px] uppercase tracking-wider">
                Instant Automatic Activation
            </div>
        </div>

        <!-- License Keys Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-16">
            
            <!-- 1 Day Card -->
            <div class="pricing-card glass-panel rounded-2xl p-6 flex flex-col justify-between overflow-hidden group">
                <div>
                    <div class="flex justify-between items-center mb-4">
                        <span class="text-xs font-mono font-bold tracking-widest text-slate-400 uppercase">Trial Pass</span>
                        <span class="px-2 py-0.5 rounded bg-slate-800 text-[10px] text-slate-300 border border-slate-700">1 Day</span>
                    </div>
                    <h3 class="text-2xl font-bold text-white mb-2">Daily Key</h3>
                    <p class="text-slate-400 text-xs mb-6">Test all features with minimal investment.</p>
                    
                    <!-- Price Container -->
                    <div class="mb-6">
                        <span class="text-5xl font-extrabold font-mono text-white tracking-tight price-animation" id="price-1day">₹89</span>
                        <span class="text-xs text-slate-400 font-medium">/ 1 Day</span>
                    </div>

                    <!-- Features -->
                    <ul class="space-y-3 mb-8 text-xs text-slate-300">
                        <li class="flex items-center gap-2.5">
                            <i class="fas fa-check-circle text-indigo-400"></i>
                            <span>Root & Non-Root Servers</span>
                        </li>
                        <li class="flex items-center gap-2.5">
                            <i class="fas fa-check-circle text-indigo-400"></i>
                            <span>All Server Region Support</span>
                        </li>
                        <li class="flex items-center gap-2.5">
                            <i class="fas fa-check-circle text-indigo-400"></i>
                            <span>Anti-Ban Engine Safe</span>
                        </li>
                    </ul>
                </div>
                <a href="<?= htmlspecialchars($contact_url) ?>" target="_blank" class="w-full text-center py-3 rounded-xl bg-white/5 border border-white/10 hover:bg-white/10 text-white text-xs font-bold transition-all flex items-center justify-center gap-2">
                    Buy Key <i class="fas fa-arrow-right text-[10px]"></i>
                </a>
            </div>

            <!-- 7 Days Card -->
            <div class="pricing-card glass-panel rounded-2xl p-6 flex flex-col justify-between overflow-hidden group relative">
                <!-- Glow highlight -->
                <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-cyan-500 to-indigo-500"></div>
                <div>
                    <div class="flex justify-between items-center mb-4">
                        <span class="text-xs font-mono font-bold tracking-widest text-indigo-400 uppercase">Standard Pass</span>
                        <span class="px-2 py-0.5 rounded bg-indigo-500/10 text-[10px] text-indigo-300 border border-indigo-500/20">7 Days</span>
                    </div>
                    <h3 class="text-2xl font-bold text-white mb-2">Weekly Key</h3>
                    <p class="text-slate-400 text-xs mb-6">Perfect for mid-term rank pushing events.</p>
                    
                    <!-- Price Container -->
                    <div class="mb-6">
                        <span class="text-5xl font-extrabold font-mono text-white tracking-tight price-animation" id="price-7days">₹299</span>
                        <span class="text-xs text-slate-400 font-medium">/ 7 Days</span>
                    </div>

                    <!-- Features -->
                    <ul class="space-y-3 mb-8 text-xs text-slate-300">
                        <li class="flex items-center gap-2.5">
                            <i class="fas fa-check-circle text-indigo-400"></i>
                            <span>Root & Non-Root Servers</span>
                        </li>
                        <li class="flex items-center gap-2.5">
                            <i class="fas fa-check-circle text-indigo-400"></i>
                            <span>All Server Region Support</span>
                        </li>
                        <li class="flex items-center gap-2.5">
                            <i class="fas fa-check-circle text-indigo-400"></i>
                            <span>Anti-Ban Engine Safe</span>
                        </li>
                    </ul>
                </div>
                <a href="<?= htmlspecialchars($contact_url) ?>" target="_blank" class="w-full text-center py-3 rounded-xl bg-gradient-to-r from-indigo-600/30 to-purple-600/30 border border-indigo-500/20 hover:from-indigo-600/50 hover:to-purple-600/50 text-white text-xs font-bold transition-all flex items-center justify-center gap-2">
                    Buy Key <i class="fas fa-arrow-right text-[10px]"></i>
                </a>
            </div>

            <!-- 14 Days Card -->
            <div class="pricing-card glass-panel rounded-2xl p-6 flex flex-col justify-between overflow-hidden group">
                <div>
                    <div class="flex justify-between items-center mb-4">
                        <span class="text-xs font-mono font-bold tracking-widest text-slate-400 uppercase">Value Pass</span>
                        <span class="px-2 py-0.5 rounded bg-slate-800 text-[10px] text-slate-300 border border-slate-700">14 Days</span>
                    </div>
                    <h3 class="text-2xl font-bold text-white mb-2">Half-Month Key</h3>
                    <p class="text-slate-400 text-xs mb-6">Extended access with discounted price.</p>
                    
                    <!-- Price Container -->
                    <div class="mb-6">
                        <span class="text-5xl font-extrabold font-mono text-white tracking-tight price-animation" id="price-14days">₹549</span>
                        <span class="text-xs text-slate-400 font-medium">/ 14 Days</span>
                    </div>

                    <!-- Features -->
                    <ul class="space-y-3 mb-8 text-xs text-slate-300">
                        <li class="flex items-center gap-2.5">
                            <i class="fas fa-check-circle text-indigo-400"></i>
                            <span>Root & Non-Root Servers</span>
                        </li>
                        <li class="flex items-center gap-2.5">
                            <i class="fas fa-check-circle text-indigo-400"></i>
                            <span>All Server Region Support</span>
                        </li>
                        <li class="flex items-center gap-2.5">
                            <i class="fas fa-check-circle text-indigo-400"></i>
                            <span>Anti-Ban Engine Safe</span>
                        </li>
                    </ul>
                </div>
                <a href="<?= htmlspecialchars($contact_url) ?>" target="_blank" class="w-full text-center py-3 rounded-xl bg-white/5 border border-white/10 hover:bg-white/10 text-white text-xs font-bold transition-all flex items-center justify-center gap-2">
                    Buy Key <i class="fas fa-arrow-right text-[10px]"></i>
                </a>
            </div>

            <!-- 30 Days Card -->
            <div class="pricing-card glass-panel rounded-2xl p-6 flex flex-col justify-between overflow-hidden group relative">
                <!-- Popular Glow Tag -->
                <div class="absolute top-0 right-0 bg-gradient-to-l from-purple-600 to-indigo-600 text-white font-mono text-[9px] uppercase tracking-wider font-extrabold px-3 py-1 rounded-bl-lg">
                    Best Seller
                </div>
                <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-purple-500 via-pink-500 to-red-500"></div>
                <div>
                    <div class="flex justify-between items-center mb-4">
                        <span class="text-xs font-mono font-bold tracking-widest text-purple-400 uppercase">Pro Gamer Pass</span>
                        <span class="px-2 py-0.5 rounded bg-purple-500/10 text-[10px] text-purple-300 border border-purple-500/20">30 Days</span>
                    </div>
                    <h3 class="text-2xl font-bold text-white mb-2">Monthly Key</h3>
                    <p class="text-slate-400 text-xs mb-6">Ultimate value. Play uninterrupted all month long.</p>
                    
                    <!-- Price Container -->
                    <div class="mb-6">
                        <span class="text-5xl font-extrabold font-mono text-white tracking-tight price-animation" id="price-30days">₹1099</span>
                        <span class="text-xs text-slate-400 font-medium">/ 30 Days</span>
                    </div>

                    <!-- Features -->
                    <ul class="space-y-3 mb-8 text-xs text-slate-300">
                        <li class="flex items-center gap-2.5">
                            <i class="fas fa-check-circle text-purple-400"></i>
                            <span>Root & Non-Root Servers</span>
                        </li>
                        <li class="flex items-center gap-2.5">
                            <i class="fas fa-check-circle text-purple-400"></i>
                            <span>All Server Region Support</span>
                        </li>
                        <li class="flex items-center gap-2.5">
                            <i class="fas fa-check-circle text-purple-400"></i>
                            <span>Anti-Ban Engine Safe</span>
                        </li>
                        <li class="flex items-center gap-2.5">
                            <i class="fas fa-check-circle text-purple-400"></i>
                            <span class="font-semibold text-purple-300">Priority 24/7 Admin Support</span>
                        </li>
                    </ul>
                </div>
                <a href="<?= htmlspecialchars($contact_url) ?>" target="_blank" class="w-full text-center py-3 rounded-xl bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white text-xs font-bold transition-all shadow-lg shadow-purple-600/20 hover:shadow-purple-500/40 flex items-center justify-center gap-2">
                    Buy Key <i class="fas fa-arrow-right text-[10px]"></i>
                </a>
            </div>

        </div>

        <!-- Resellers Section -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-16 items-stretch">
            
            <!-- Reseller Text & Intro Info -->
            <div class="lg:col-span-1 flex flex-col justify-center">
                <span class="text-xs font-mono font-bold tracking-widest text-indigo-400 uppercase mb-2">Partner Program</span>
                <h2 class="text-3xl font-extrabold text-white tracking-tight mb-4">Reseller Credit Plans</h2>
                <p class="text-slate-400 text-sm mb-6 leading-relaxed">
                    Start your own license selling business. Purchase wholesale credits, manage your clients via panel, and set your own prices. The more credits you purchase, the larger your profit margin.
                </p>
                <div class="space-y-4">
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-lg bg-indigo-500/10 flex items-center justify-center text-indigo-400 mt-0.5">
                            <i class="fas fa-chart-line text-sm"></i>
                        </div>
                        <div>
                            <h4 class="text-white text-sm font-semibold">High Margin Return</h4>
                            <p class="text-slate-500 text-xs mt-0.5">Sell at retail prices and secure up to 200% profit margins.</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-lg bg-indigo-500/10 flex items-center justify-center text-indigo-400 mt-0.5">
                            <i class="fas fa-cogs text-sm"></i>
                        </div>
                        <div>
                            <h4 class="text-white text-sm font-semibold">Dedicated Sub-Panel</h4>
                            <p class="text-slate-500 text-xs mt-0.5">Get a reseller portal to generate, extend, and manage client keys.</p>
                        </div>
                    </div>
                </div>
                
                <!-- Credit Consumption Guide Card -->
                <div class="mt-8 p-4 rounded-xl border border-indigo-500/10 bg-indigo-950/20 backdrop-blur">
                    <h4 class="text-white text-xs font-bold font-mono tracking-wider uppercase mb-3 flex items-center gap-2">
                        <i class="fas fa-calculator text-indigo-400"></i> Credit Deduction Guide
                    </h4>
                    <p class="text-slate-400 text-[11px] mb-4 leading-normal">
                        When you generate license keys in your reseller panel, credits are deducted from your balance based on the key duration:
                    </p>
                    <div class="grid grid-cols-2 gap-2 text-xs font-mono">
                        <div class="flex items-center justify-between p-2 rounded bg-slate-900/50 border border-white/5">
                            <span class="text-slate-400">1 Day Key</span>
                            <span class="text-emerald-400 font-bold">0.5 Cr</span>
                        </div>
                        <div class="flex items-center justify-between p-2 rounded bg-slate-900/50 border border-white/5">
                            <span class="text-slate-400">7 Days Key</span>
                            <span class="text-emerald-400 font-bold">1.0 Cr</span>
                        </div>
                        <div class="flex items-center justify-between p-2 rounded bg-slate-900/50 border border-white/5">
                            <span class="text-slate-400">14 Days Key</span>
                            <span class="text-emerald-400 font-bold">2.0 Cr</span>
                        </div>
                        <div class="flex items-center justify-between p-2 rounded bg-slate-900/50 border border-white/5">
                            <span class="text-slate-400">30 Days Key</span>
                            <span class="text-emerald-400 font-bold">3.0 Cr</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Reseller Packages List -->
            <div class="lg:col-span-2 glass-panel rounded-2xl p-6 md:p-8 flex flex-col justify-between relative overflow-hidden border border-white/5">
                <div class="absolute -right-16 -top-16 w-48 h-48 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>
                
                <div>
                    <div class="flex items-center justify-between border-b border-white/5 pb-4 mb-6">
                        <h3 class="text-lg font-bold text-white">Wholesale Rates</h3>
                        <span class="text-xs text-slate-500 font-mono">Flexible Credits Deduction</span>
                    </div>

                    <!-- Reseller Pack 1 -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between p-4 rounded-xl border border-white/5 bg-slate-900/40 hover:bg-slate-900/60 transition-all gap-4 mb-4">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-xl bg-slate-800 flex flex-col items-center justify-center text-slate-300 font-bold border border-slate-700">
                                <span class="text-base leading-none">1</span>
                                <span class="text-[9px] uppercase font-semibold font-mono text-slate-400 mt-1">Cr</span>
                            </div>
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <h4 class="text-white font-bold text-sm">Single Credit Rate</h4>
                                    <span class="px-1.5 py-0.5 rounded bg-rose-500/10 text-rose-400 text-[8px] font-mono font-bold uppercase tracking-wider border border-rose-500/20">Min. Purchase 10 Credits</span>
                                </div>
                                <p class="text-slate-500 text-xs mt-0.5">Reference rate per credit. We do not sell single credits.</p>
                            </div>
                        </div>
                        <div class="text-left sm:text-right">
                            <div class="text-2xl font-extrabold font-mono text-slate-400 price-animation" id="price-reseller-1">₹90</div>
                            <span class="text-[10px] font-mono text-slate-500 font-medium">per credit</span>
                        </div>
                    </div>

                    <!-- Reseller Pack 10 -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between p-4 rounded-xl border border-purple-500/20 bg-purple-500/5 hover:bg-purple-500/10 transition-all gap-4 mb-4 relative">
                        <div class="absolute left-1/2 -translate-x-1/2 -top-2.5 bg-gradient-to-r from-purple-600 to-indigo-600 text-white font-mono text-[9px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wider shadow">
                            Starter Dealer
                        </div>
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-xl bg-purple-500/10 flex flex-col items-center justify-center text-purple-400 font-bold border border-purple-500/20">
                                <span class="text-lg leading-none">10</span>
                                <span class="text-[9px] uppercase font-semibold font-mono text-purple-400 mt-0.5">Cr</span>
                            </div>
                            <div>
                                <h4 class="text-white font-bold text-sm">10 Credits Pack</h4>
                                <p class="text-slate-400 text-xs">Perfect for local friend circles and small client bases.</p>
                            </div>
                        </div>
                        <div class="text-left sm:text-right">
                            <div class="text-2xl font-extrabold font-mono text-purple-300 price-animation" id="price-reseller-10">₹900</div>
                            <span class="text-[10px] font-mono text-purple-400 font-medium">Bulk package</span>
                        </div>
                    </div>

                    <!-- Reseller Pack 20 -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between p-4 rounded-xl border border-emerald-500/20 bg-emerald-500/5 hover:bg-emerald-500/10 transition-all gap-4">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-xl bg-emerald-500/10 flex flex-col items-center justify-center text-emerald-400 font-bold border border-emerald-500/20">
                                <span class="text-lg leading-none">20</span>
                                <span class="text-[9px] uppercase font-semibold font-mono text-emerald-400 mt-0.5">Cr</span>
                            </div>
                            <div>
                                <div class="flex items-center gap-1.5">
                                    <h4 class="text-white font-bold text-sm">20 Credits Pack</h4>
                                    <span class="px-1.5 py-0.5 rounded bg-emerald-500/20 text-emerald-300 text-[8px] font-mono font-bold uppercase">Best Value</span>
                                </div>
                                <p class="text-slate-400 text-xs">High discount margin. Secure maximum profit returns.</p>
                            </div>
                        </div>
                        <div class="text-left sm:text-right">
                            <div class="text-2xl font-extrabold font-mono text-emerald-300 price-animation" id="price-reseller-20">₹1750</div>
                            <span class="text-[10px] font-mono text-slate-400 font-medium">Mega wholesale rate</span>
                        </div>
                    </div>

                    <!-- Minimum Order Warning Note -->
                    <p class="text-[11px] text-amber-500/90 bg-amber-500/5 border border-amber-500/10 rounded-lg p-3 mt-4 flex items-start gap-2 select-none">
                        <i class="fas fa-exclamation-triangle mt-0.5"></i>
                        <span><strong>Important Note:</strong> The minimum purchase limit for reseller accounts is <strong>10 Credits</strong>. Single (1) credit activations are not processed.</span>
                    </p>
                </div>

                <div class="mt-8 pt-6 border-t border-white/5 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <span class="text-slate-500 text-xs flex items-center gap-1.5">
                        <i class="fas fa-shield-alt text-slate-400"></i> Secured reseller manager dashboard
                    </span>
                    <a href="<?= htmlspecialchars($contact_url) ?>" target="_blank" class="px-6 py-3 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs transition-all shadow-md shadow-indigo-600/10 hover:shadow-indigo-600/30 flex items-center gap-2">
                        Become Reseller <i class="fas fa-handshake"></i>
                    </a>
                </div>
            </div>

        </div>

        <!-- Admin Access Section -->
        <section class="glass-panel rounded-3xl p-8 md:p-12 relative overflow-hidden border border-purple-500/20 shadow-2xl">
            <!-- Background glow -->
            <div class="absolute right-0 bottom-0 w-80 h-80 bg-gradient-to-tr from-purple-600/10 to-indigo-600/10 rounded-full blur-3xl pointer-events-none"></div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 items-center">
                <div class="md:col-span-2">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-purple-500/10 border border-purple-500/20 text-purple-300 text-xs font-semibold uppercase tracking-wider mb-4">
                        <i class="fas fa-crown text-[10px]"></i> Ultimate Control
                    </span>
                    <h3 class="text-3xl font-extrabold text-white tracking-tight mb-3">🔥 RAPIDCORE ADMIN PANEL ACCESS 🔥</h3>
                    <p class="text-slate-400 text-sm leading-relaxed max-w-xl mb-4">
                        Get absolute panel administration access. Generate keys, manage resellers, configure servers, reset HWIDs, and control everything under your panel.
                    </p>

                    <!-- How It Works Box -->
                    <div class="mb-6 p-4 rounded-2xl border border-amber-500/20 bg-amber-500/5">
                        <h4 class="text-amber-300 font-bold text-sm mb-3 flex items-center gap-2">
                            <i class="fas fa-info-circle"></i> How Admin Access Works
                        </h4>
                        <div class="space-y-3 text-xs text-slate-300 leading-relaxed">
                            <div class="flex items-start gap-2.5">
                                <span class="mt-0.5 w-5 h-5 rounded-full bg-purple-600 text-white flex items-center justify-center font-bold text-[10px] shrink-0">1</span>
                                <p><span class="text-white font-semibold">One-Time Activation Fee</span> — Pay the activation amount once to get full Admin Panel access. This is your first-time setup fee.</p>
                            </div>
                            <div class="flex items-start gap-2.5">
                                <span class="mt-0.5 w-5 h-5 rounded-full bg-indigo-600 text-white flex items-center justify-center font-bold text-[10px] shrink-0">2</span>
                                <p><span class="text-white font-semibold">Lifetime Access</span> — Your admin account remains active for lifetime. There is no monthly fee.</p>
                            </div>
                            <div class="flex items-start gap-2.5">
                                <span class="mt-0.5 w-5 h-5 rounded-full bg-rose-600 text-white flex items-center justify-center font-bold text-[10px] shrink-0">3</span>
                                <p><span class="text-white font-semibold">OB Update Renewal</span> — Whenever a major game OB (patch) update is released and the cheat is updated, admins must pay a <span class="text-amber-300 font-bold">₹650 renewal fee</span> to continue access for that update cycle. This keeps your panel active on the latest version.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Admin Benefits List -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 text-slate-300">
                        <div class="flex gap-3">
                            <div class="text-xl mt-0.5 select-none">🔑</div>
                            <div>
                                <h4 class="text-white font-bold text-sm">License Key Control</h4>
                                <p class="text-slate-400 text-xs mt-1 leading-relaxed">Generate single/bulk keys, reset HWIDs, extend time (add days), and delete keys.</p>
                            </div>
                        </div>
                        <div class="flex gap-3">
                            <div class="text-xl mt-0.5 select-none">👑</div>
                            <div>
                                <h4 class="text-white font-bold text-sm">Reseller Management</h4>
                                <p class="text-slate-400 text-xs mt-1 leading-relaxed">Create sub-reseller accounts, set custom point balances, and delete/block resellers.</p>
                            </div>
                        </div>
                        <div class="flex gap-3">
                            <div class="text-xl mt-0.5 select-none">🎉</div>
                            <div>
                                <h4 class="text-white font-bold text-sm">Live Dashboard</h4>
                                <p class="text-slate-400 text-xs mt-1 leading-relaxed">Real-time stats and tracking for all keys and resellers under you.</p>
                            </div>
                        </div>
                        <div class="flex gap-3">
                            <div class="text-xl mt-0.5 select-none">🔒</div>
                            <div>
                                <h4 class="text-white font-bold text-sm">100% Privacy</h4>
                                <p class="text-slate-400 text-xs mt-1 leading-relaxed">Secure panel where you only see and manage the keys and resellers created by you.</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="md:col-span-1 glass-panel border border-white/10 bg-slate-950/40 p-6 rounded-2xl flex flex-col justify-between items-center text-center">
                    <span class="text-xs font-mono font-bold tracking-widest text-slate-400 uppercase mb-2">One-Time Activation Fee</span>
                    <div class="text-4xl md:text-5xl font-extrabold font-mono text-transparent bg-clip-text bg-gradient-to-r from-purple-400 to-indigo-400 tracking-tight mb-1 price-animation" id="price-admin">&#8377;3500</div>
                    <span class="text-slate-500 text-xs mb-2">First-Time Setup — Lifetime Access</span>
                    <!-- OB Renewal Badge -->
                    <div class="w-full mb-5 px-3 py-2 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-300 text-[11px] font-semibold flex items-center justify-center gap-2">
                        <i class="fas fa-sync-alt text-rose-400 text-[10px]"></i>
                        OB Update Renewal: <span class="text-white font-bold ml-1">&#8377;650</span>
                    </div>
                    <a href="<?= htmlspecialchars($contact_url) ?>" target="_blank" class="w-full text-center py-3.5 rounded-xl bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white text-xs font-bold transition-all shadow-lg shadow-purple-600/20 flex items-center justify-center gap-2">
                        Get Admin Access <i class="fas fa-crown text-[10px]"></i>
                    </a>
                </div>
            </div>
        </section>

        <!-- Contact & Support Section -->
        <section class="mt-16 text-center">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-indigo-300 text-xs font-semibold uppercase tracking-wider mb-4">
                <i class="fas fa-headset"></i> Instant Activation
            </div>
            <h2 class="text-2xl md:text-3xl font-extrabold text-white tracking-tight mb-2">Ready to Order? Contact Admin</h2>
            <p class="text-slate-400 text-xs md:text-sm max-w-md mx-auto mb-8">
                Send a direct message on Telegram or WhatsApp to buy keys instantly or join the partner program.
            </p>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 max-w-3xl mx-auto mb-8">
                <!-- Telegram Contact Card -->
                <a href="<?= htmlspecialchars($telegram_url) ?>" target="_blank" class="glass-panel p-6 rounded-2xl border border-sky-500/10 hover:border-sky-500/30 hover:bg-sky-500/5 transition-all duration-300 flex flex-col items-center group">
                    <div class="w-14 h-14 rounded-full bg-sky-500/10 flex items-center justify-center text-sky-400 text-2xl mb-4 group-hover:scale-110 transition-transform">
                        <i class="fab fa-telegram-plane"></i>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-1">Telegram Support</h3>
                    <p class="text-slate-400 text-xs mb-4">Chat with Admin on Telegram for instant response</p>
                    <span class="px-4 py-2 rounded-xl bg-sky-500/10 text-sky-300 font-bold text-xs uppercase tracking-wider group-hover:bg-sky-500 group-hover:text-white transition-all">
                        @<?= htmlspecialchars($telegram_username) ?>
                    </span>
                </a>

                <!-- WhatsApp Contact Card -->
                <?php if(!empty($whatsapp_number)): ?>
                <a href="<?= htmlspecialchars($whatsapp_url) ?>" target="_blank" class="glass-panel p-6 rounded-2xl border border-emerald-500/10 hover:border-emerald-500/30 hover:bg-emerald-500/5 transition-all duration-300 flex flex-col items-center group">
                    <div class="w-14 h-14 rounded-full bg-emerald-500/10 flex items-center justify-center text-emerald-400 text-2xl mb-4 group-hover:scale-110 transition-transform">
                        <i class="fab fa-whatsapp"></i>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-1">WhatsApp Support</h3>
                    <p class="text-slate-400 text-xs mb-4">Text Admin directly on WhatsApp for keys activation</p>
                    <span class="px-4 py-2 rounded-xl bg-emerald-500/10 text-emerald-300 font-bold text-xs uppercase tracking-wider group-hover:bg-emerald-500 group-hover:text-white transition-all">
                        +<?= htmlspecialchars($whatsapp_number) ?>
                    </span>
                </a>
                <?php endif; ?>
            </div>
        </section>

    </div>

    <!-- Footer -->
    <footer class="relative z-10 border-t border-white/5 bg-[#02050f]/80 backdrop-blur-md py-8">
        <div class="max-w-6xl mx-auto px-4 flex flex-col md:flex-row items-center justify-between gap-6">
            <div class="flex items-center">
                <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center mr-3 shadow-lg shadow-indigo-500/20">
                    <i class="fas fa-shield-alt text-white text-sm"></i>
                </div>
                <div>
                    <h4 class="font-bold text-white text-sm leading-tight"><?= htmlspecialchars($panel_name) ?></h4>
                    <p class="text-[10px] text-slate-500 font-mono tracking-wider uppercase">High-Safety License Manager</p>
                </div>
            </div>
            
            <!-- Contact Quick Links -->
            <div class="flex flex-wrap justify-center gap-6 text-xs text-slate-400">
                <a href="<?= htmlspecialchars($telegram_channel) ?>" target="_blank" class="hover:text-purple-400 transition-colors flex items-center gap-1">
                    <i class="fab fa-telegram-plane"></i> Official Channel
                </a>
                <a href="<?= htmlspecialchars($telegram_url) ?>" target="_blank" class="hover:text-indigo-400 transition-colors flex items-center gap-1">
                    <i class="fas fa-headset"></i> Support Telegram
                </a>
                <?php if(!empty($whatsapp_url)): ?>
                <a href="<?= htmlspecialchars($whatsapp_url) ?>" target="_blank" class="hover:text-emerald-400 transition-colors flex items-center gap-1">
                    <i class="fab fa-whatsapp"></i> Support WhatsApp
                </a>
                <?php endif; ?>
            </div>
            
            <p class="text-slate-600 text-xs font-mono">
                &copy; <?= date('Y') ?> <?= htmlspecialchars($panel_name) ?>. All rights reserved.
            </p>
        </div>
    </footer>

    <!-- Interactive Currency Switcher JavaScript Script -->
    <script>
        // Definition of the pricing data matrices matching India, Bangladesh & Global
        const pricingData = {
            in: {
                licenses: {
                    '1day': '₹89',
                    '7days': '₹299',
                    '14days': '₹549',
                    '30days': '₹1099'
                },
                reseller: {
                    '1': '₹90',
                    '10': '₹900',
                    '20': '₹1750'
                },
                admin: '&#8377;3500'
            },
            bd: {
                licenses: {
                    '1day': '110 ৳',
                    '7days': '380 ৳',
                    '14days': '700 ৳',
                    '30days': '1400 ৳'
                },
                reseller: {
                    '1': '110 ৳',
                    '10': '1100 ৳',
                    '20': '2100 ৳'
                },
                admin: '4000 ৳'
            },
            us: {
                licenses: {
                    '1day': '$0.90',
                    '7days': '$3.50',
                    '14days': '$6.00',
                    '30days': '$10.50'
                },
                reseller: {
                    '1': '$1.00',
                    '10': '$10.00',
                    '20': '$18.00'
                },
                admin: '$40.00'
            }
        };

        // State tracker
        let currentRegion = 'in';

        function setCurrency(region) {
            if (region === currentRegion) return;
            currentRegion = region;

            // 1. Update Tabs States
            document.querySelectorAll('.currency-tab').forEach(tab => {
                tab.classList.remove('text-white', 'bg-gradient-to-r', 'from-indigo-600', 'to-purple-600', 'shadow-lg', 'shadow-indigo-600/20');
                tab.classList.add('text-slate-400', 'hover:text-white', 'hover:bg-white/5');
            });

            const activeTab = document.getElementById('tab-' + region);
            activeTab.classList.remove('text-slate-400', 'hover:text-white', 'hover:bg-white/5');
            activeTab.classList.add('text-white', 'bg-gradient-to-r', 'from-indigo-600', 'to-purple-600', 'shadow-lg', 'shadow-indigo-600/20');

            // 2. Perform Price Element Updates with animations
            const data = pricingData[region];
            
            // Animation helper function
            function animateValueUpdate(elementId, newValue) {
                const el = document.getElementById(elementId);
                if (!el) return;
                
                el.classList.add('price-changing');
                setTimeout(() => {
                    el.innerText = newValue;
                    el.classList.remove('price-changing');
                }, 150); // half of CSS transition length
            }

            // Update license keys prices
            animateValueUpdate('price-1day', data.licenses['1day']);
            animateValueUpdate('price-7days', data.licenses['7days']);
            animateValueUpdate('price-14days', data.licenses['14days']);
            animateValueUpdate('price-30days', data.licenses['30days']);

            // Update reseller prices
            animateValueUpdate('price-reseller-1', data.reseller['1']);
            animateValueUpdate('price-reseller-10', data.reseller['10']);
            animateValueUpdate('price-reseller-20', data.reseller['20']);

            // Update admin price
            animateValueUpdate('price-admin', data.admin);
        }
    </script>
</body>
</html>
