<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HWID Reset Portal | RapidCore</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            background-color: #0f172a;
            background-image: 
                radial-gradient(at 0% 0%, rgba(99, 102, 241, 0.15) 0px, transparent 50%),
                radial-gradient(at 100% 0%, rgba(139, 92, 246, 0.15) 0px, transparent 50%);
            background-attachment: fixed;
            font-family: 'Inter', sans-serif;
        }
        .glass-panel {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
        }
        .animate-float {
            animation: float 6s ease-in-out infinite;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <!-- Logo/Header -->
        <div class="text-center mb-8 animate-float">
            <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center mx-auto shadow-2xl shadow-indigo-500/20 mb-4">
                <i class="fas fa-shield-alt text-white text-2xl"></i>
            </div>
            <h1 class="text-3xl font-bold text-white tracking-tight">RapidCore</h1>
            <p class="text-slate-400 text-sm mt-1 uppercase tracking-widest font-medium">HWID Reset Portal</p>
        </div>

        <!-- Main Card -->
        <div class="glass-panel rounded-[2rem] p-8 md:p-10 relative overflow-hidden">
            <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500"></div>
            
            <div class="mb-8">
                <h2 class="text-xl font-bold text-white mb-2">Reset your License</h2>
                <p class="text-slate-400 text-sm leading-relaxed">Enter your license key below to clear the registered hardware ID (HWID). This allows you to use the key on a different device.</p>
            </div>

            <?php if (session()->getFlashdata('msgDanger')) : ?>
                <div class="bg-red-500/10 border border-red-500/20 text-red-400 px-4 py-3 rounded-xl mb-6 text-xs flex items-center gap-3">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= session()->getFlashdata('msgDanger') ?>
                </div>
            <?php elseif (session()->getFlashdata('msgSuccess')) : ?>
                <div class="bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 px-4 py-3 rounded-xl mb-6 text-xs flex items-center gap-3">
                    <i class="fas fa-check-circle"></i>
                    <?= session()->getFlashdata('msgSuccess') ?>
                </div>
            <?php elseif (session()->getFlashdata('msgWarning')) : ?>
                <div class="bg-amber-500/10 border border-amber-500/20 text-amber-400 px-4 py-3 rounded-xl mb-6 text-xs flex items-center gap-3">
                    <i class="fas fa-info-circle"></i>
                    <?= session()->getFlashdata('msgWarning') ?>
                </div>
            <?php endif; ?>

            <form action="" method="POST" class="space-y-6">
                <?= csrf_field() ?>
                <div class="space-y-2">
                    <label class="text-xs font-bold text-slate-400 uppercase tracking-wider ml-1">License Key</label>
                    <div class="relative group">
                        <i class="fas fa-key absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 group-focus-within:text-indigo-400 transition-colors"></i>
                        <input type="text" name="user_key" required placeholder="XXXX-XXXX-XXXX-XXXX"
                            class="w-full bg-slate-800/50 border border-white/10 rounded-2xl py-4 pl-12 pr-4 text-white focus:outline-none focus:border-indigo-500 transition-all placeholder-slate-600 font-mono text-sm">
                    </div>
                </div>

                <button type="submit" class="w-full py-4 rounded-2xl bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-bold hover:shadow-lg hover:shadow-indigo-500/25 transition-all text-sm uppercase tracking-widest">
                    Reset HWID Now
                </button>
            </form>

            <div class="mt-8 pt-6 border-t border-white/5 text-center">
                <p class="text-[10px] text-slate-500 uppercase font-bold tracking-widest mb-1">Security Notice</p>
                <p class="text-[10px] text-slate-600 italic">This action is logged and monitored for security purposes.</p>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-8">
            <p class="text-slate-500 text-[10px] uppercase font-bold tracking-[0.2em]">&copy; <?= date('Y') ?> RapidCore Security</p>
        </div>
    </div>
</body>
</html>
