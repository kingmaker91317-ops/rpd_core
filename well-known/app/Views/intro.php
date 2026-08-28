<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home | RapidCore</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" rel="stylesheet" crossorigin="anonymous">

    <!-- Google Fonts: Inter & Space Grotesk -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        mono: ['Space Grotesk', 'monospace'],
                    },
                    colors: {
                        glass: {
                            100: 'rgba(255, 255, 255, 0.1)',
                            200: 'rgba(255, 255, 255, 0.2)',
                            300: 'rgba(255, 255, 255, 0.3)',
                            border: 'rgba(255, 255, 255, 0.1)',
                        },
                        accent: {
                            DEFAULT: '#6366f1',
                            hover: '#4f46e5',
                            light: '#818cf8',
                        }
                    }
                }
            }
        }
    </script>

    <style>
        :root {
            --bg-body: #0f172a;
            --bg-card: rgba(30, 41, 59, 0.7);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --border-color: rgba(255, 255, 255, 0.08);
        }

        body {
            background-color: var(--bg-body);
            background-image:
                radial-gradient(at 0% 0%, rgba(99, 102, 241, 0.15) 0px, transparent 50%),
                radial-gradient(at 100% 0%, rgba(139, 92, 246, 0.15) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(236, 72, 153, 0.15) 0px, transparent 50%);
            background-attachment: fixed;
            color: var(--text-main);
            font-family: 'Inter', sans-serif;
            -webkit-font-smoothing: antialiased;
        }

        .hero-gradient {
            background: radial-gradient(ellipse at top, rgba(99, 102, 241, 0.15) 0%, transparent 50%),
                radial-gradient(ellipse at bottom right, rgba(139, 92, 246, 0.1) 0%, transparent 50%);
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        .glow-btn {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            box-shadow: 0 0 30px rgba(99, 102, 241, 0.4);
            transition: all 0.3s ease;
        }

        .glow-btn:hover {
            box-shadow: 0 0 50px rgba(99, 102, 241, 0.6);
            transform: translateY(-2px);
        }

        .secondary-btn {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }

        .feature-card {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.05);
            transition: all 0.3s ease;
        }

        .feature-card:hover {
            background: rgba(255, 255, 255, 0.05);
            border-color: rgba(99, 102, 241, 0.3);
            transform: translateY(-5px);
        }

        .stat-card {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.1) 0%, rgba(139, 92, 246, 0.05) 100%);
            border: 1px solid rgba(99, 102, 241, 0.2);
        }
    </style>
</head>

<body class="min-h-screen overflow-x-hidden flex flex-col">

    <!-- Navigation -->
    <nav class="fixed top-0 left-0 right-0 z-50 glass-card">
        <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
            <a href="#" class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center">
                    <i class="fas fa-bolt text-white"></i>
                </div>
                <span class="text-xl font-bold text-white">RapidCore</span>
            </a>
            <div class="flex items-center gap-4">
                <a href="<?= base_url('login') ?>" class="glow-btn px-5 py-2 rounded-xl text-sm font-bold text-white">Login</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-gradient min-h-screen flex items-center justify-center pt-20 pb-16 px-6 grow">
        <div class="max-w-6xl mx-auto text-center">
            <!-- Badge -->
            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full glass-card mb-8">
                <span class="relative flex h-2 w-2">
                    <span class="animate-ping absolute h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="rounded-full h-2 w-2 bg-emerald-500"></span>
                </span>
                <span class="text-xs font-medium text-slate-300">System Online</span>
            </div>

            <!-- Main Heading -->
            <h1 class="text-5xl md:text-7xl font-black mb-6 leading-tight">
                <span class="bg-gradient-to-r from-white via-slate-200 to-slate-400 bg-clip-text text-transparent">Premium License</span>
                <br>
                <span class="bg-gradient-to-r from-indigo-400 via-purple-400 to-pink-400 bg-clip-text text-transparent">Management System</span>
            </h1>

            <p class="text-lg md:text-xl text-slate-400 max-w-2xl mx-auto mb-10">
                Secure, fast, and reliable license key management.
                <br>

            </p>

            <!-- CTA Buttons -->
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4 mb-16">
                <a href="<?= base_url('login') ?>" class="glow-btn px-8 py-4 rounded-2xl text-lg font-bold flex items-center gap-3 w-full sm:w-auto justify-center text-white">
                    <i class="fas fa-rocket"></i>
                    Access Panel Now
                </a>
                <a href="<?= base_url('keys/api') ?>" class="secondary-btn px-8 py-4 rounded-2xl text-lg font-medium flex items-center gap-3 w-full sm:w-auto justify-center text-white">
                    <i class="fas fa-key"></i>
                    Get Free Key
                </a>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 max-w-4xl mx-auto">
                <div class="stat-card p-4 rounded-2xl">
                    <div class="text-3xl font-black text-indigo-400">43+</div>
                    <div class="text-xs text-slate-400 font-medium uppercase tracking-wider mt-1">Total Licenses</div>
                </div>
                <div class="stat-card p-4 rounded-2xl">
                    <div class="text-3xl font-black text-purple-400">13+</div>
                    <div class="text-xs text-slate-400 font-medium uppercase tracking-wider mt-1">Active Users</div>
                </div>
                <div class="stat-card p-4 rounded-2xl">
                    <div class="text-3xl font-black text-pink-400">5</div>
                    <div class="text-xs text-slate-400 font-medium uppercase tracking-wider mt-1">Games Supported</div>
                </div>
                <div class="stat-card p-4 rounded-2xl">
                    <div class="text-3xl font-black text-emerald-400">99.9%</div>
                    <div class="text-xs text-slate-400 font-medium uppercase tracking-wider mt-1">Uptime</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-20 px-6">
        <div class="max-w-6xl mx-auto">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-bold mb-4 text-white">Why Choose RapidCore?</h2>
                <p class="text-slate-400 max-w-xl mx-auto">Everything you need to manage your license keys in one powerful platform.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div class="feature-card p-6 rounded-2xl">
                    <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-indigo-500/20 to-purple-500/20 flex items-center justify-center mb-4">
                        <i class="fas fa-bolt text-2xl text-indigo-400"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-2 text-white">Instant Generation</h3>
                    <p class="text-slate-400 text-sm">Generate hundreds of unique license keys in seconds with custom prefixes and durations.</p>
                </div>

                <div class="feature-card p-6 rounded-2xl">
                    <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-emerald-500/20 to-teal-500/20 flex items-center justify-center mb-4">
                        <i class="fas fa-shield-alt text-2xl text-emerald-400"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-2 text-white">HWID Protection</h3>
                    <p class="text-slate-400 text-sm">Advanced hardware ID binding ensures each license is tied to specific devices.</p>
                </div>

                <div class="feature-card p-6 rounded-2xl">
                    <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-amber-500/20 to-orange-500/20 flex items-center justify-center mb-4">
                        <i class="fas fa-chart-line text-2xl text-amber-400"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-2 text-white">Real-time Analytics</h3>
                    <p class="text-slate-400 text-sm">Monitor API traffic, login activity, and key usage with live dashboards.</p>
                </div>

                <div class="feature-card p-6 rounded-2xl">
                    <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-pink-500/20 to-rose-500/20 flex items-center justify-center mb-4">
                        <i class="fas fa-users text-2xl text-pink-400"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-2 text-white">Multi-Role System</h3>
                    <p class="text-slate-400 text-sm">Owner, Admin, and Reseller roles with granular permission controls.</p>
                </div>

                <div class="feature-card p-6 rounded-2xl">
                    <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-blue-500/20 to-cyan-500/20 flex items-center justify-center mb-4">
                        <i class="fas fa-gamepad text-2xl text-blue-400"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-2 text-white">Multi-Game Support</h3>
                    <p class="text-slate-400 text-sm">Manage licenses for multiple games with individual pricing and settings.</p>
                </div>

                <div class="feature-card p-6 rounded-2xl">
                    <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-violet-500/20 to-purple-500/20 flex items-center justify-center mb-4">
                        <i class="fas fa-code text-2xl text-violet-400"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-2 text-white">API Integration</h3>
                    <p class="text-slate-400 text-sm">RESTful API for seamless integration with your applications and tools.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="py-12 px-6 border-t border-white/5 mt-auto">
        <div class="max-w-6xl mx-auto">
            <div class="flex flex-col md:flex-row items-center justify-between gap-6">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center">
                        <i class="fas fa-bolt text-white text-sm"></i>
                    </div>
                    <span class="font-bold text-white">RapidCore</span>
                </div>

                <div class="flex items-center gap-6 text-sm text-slate-400">
                    <a href="<?= base_url('login') ?>" class="hover:text-white transition">Login</a>
                    <a href="<?= base_url('keys/api') ?>" class="hover:text-white transition">Free Keys</a>
                    <a href="https://t.me/RapidCoreOwner" target="_blank" class="hover:text-white transition">Contact</a>
                </div>

                <div class="text-xs text-slate-500">
                    © <?= date('Y') ?> RapidCore. All rights reserved.
                </div>
            </div>
        </div>
    </footer>


</body>

<script>
    // Auto show popup after page load
    window.onload = function () {
        setTimeout(() => {
            document.getElementById("popupModal").classList.remove("hidden");
        }, 10); // 1 sec delay
    };

    function closePopup() {
        document.getElementById("popupModal").classList.add("hidden");
    }
</script>
</html>
