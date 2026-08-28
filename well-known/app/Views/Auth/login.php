<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | RapidCore</title>

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
                            card: 'rgba(30, 41, 59, 0.7)',
                            border: 'rgba(255, 255, 255, 0.08)',
                            input: 'rgba(255, 255, 255, 0.05)',
                        },
                        accent: {
                            DEFAULT: '#8b5cf6', // Violet 500
                            hover: '#7c3aed',   // Violet 600
                        }
                    }
                }
            }
        }
    </script>

    <style>
        body {
            background-color: #0f172a;
            background-image:
                radial-gradient(at 0% 0%, rgba(99, 102, 241, 0.15) 0px, transparent 50%),
                radial-gradient(at 100% 0%, rgba(139, 92, 246, 0.15) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(236, 72, 153, 0.15) 0px, transparent 50%);
            background-attachment: fixed;
            color: #f8fafc;
        }

        .glass-card {
            background: var(--bg-card);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
        }

        .login-input {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: white;
            transition: all 0.3s ease;
        }

        .login-input:focus {
            background: rgba(255, 255, 255, 0.1);
            border-color: #8b5cf6;
            outline: none;
            box-shadow: 0 0 0 2px rgba(139, 92, 246, 0.2);
        }

        .glow-btn {
            background: linear-gradient(135deg, #8b5cf6 0%, #a855f7 100%);
            box-shadow: 0 0 20px rgba(139, 92, 246, 0.3);
            transition: all 0.3s ease;
        }

        .glow-btn:hover {
            box-shadow: 0 0 30px rgba(139, 92, 246, 0.5);
            transform: translateY(-1px);
        }
    </style>
</head>

<body class="min-h-screen flex items-center justify-center p-4">

    <div class="w-full max-w-md">
        <!-- Logo/Header -->
        <div class="text-center mb-8">
            <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-lg shadow-indigo-500/20">
                <i class="fas fa-shield-alt text-3xl text-white"></i>
            </div>
            <h1 class="text-3xl font-bold text-white mb-2">Welcome Back</h1>
            <p class="text-slate-400">Sign in to RapidCore</p>
        </div>

        <!-- Login Card -->
        <div class="bg-slate-800/50 backdrop-blur-xl border border-white/10 rounded-3xl p-8 shadow-2xl">
            
            <!-- Messages -->
            <?php if (session()->getFlashdata('msgDanger')) : ?>
                <div class="bg-red-500/10 border border-red-500/20 text-red-400 px-4 py-3 rounded-xl mb-6 text-sm flex items-center gap-2">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= session()->getFlashdata('msgDanger') ?>
                </div>
            <?php endif; ?>

            <?php if (session()->getFlashdata('msgSuccess')) : ?>
                <div class="bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 px-4 py-3 rounded-xl mb-6 text-sm flex items-center gap-2">
                    <i class="fas fa-check-circle"></i>
                    <?= session()->getFlashdata('msgSuccess') ?>
                </div>
            <?php endif; ?>

            <?= form_open() ?>
                
                <!-- Use hidden inputs for extra data -->
                <input type="hidden" name="ip" value="<?= $_SERVER['HTTP_USER_AGENT'] ?>">
                <!-- Default stay_log to yes/checked since the design doesn't show it explicitly, or we can add it narrowly -->
                <input type="hidden" name="stay_log" value="yes">

                <!-- Username -->
                <div class="mb-5">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <i class="fas fa-user text-slate-400"></i>
                        </div>
                        <input type="text" name="username" class="login-input w-full pl-11 pr-4 py-3.5 rounded-xl font-medium placeholder-slate-500" placeholder="Username" required autocomplete="off">
                    </div>
                    <?php if ($validation->hasError('username')) : ?>
                        <p class="text-red-400 text-xs mt-2 ml-1"><?= $validation->getError('username') ?></p>
                    <?php endif; ?>
                </div>

                <!-- Password -->
                <div class="mb-8">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <i class="fas fa-lock text-slate-400"></i>
                        </div>
                        <input type="password" name="password" id="password" class="login-input w-full pl-11 pr-11 py-3.5 rounded-xl font-medium placeholder-slate-500" placeholder="••••••••" required>
                        <button type="button" onclick="togglePassword()" class="absolute inset-y-0 right-0 pr-4 flex items-center text-slate-500 hover:text-slate-300 transition cursor-pointer">
                            <i class="fas fa-eye" id="eyeIcon"></i>
                        </button>
                    </div>
                    <?php if ($validation->hasError('password')) : ?>
                        <p class="text-red-400 text-xs mt-2 ml-1"><?= $validation->getError('password') ?></p>
                    <?php endif; ?>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="glow-btn w-full py-3.5 rounded-xl text-white font-bold text-lg tracking-wide">
                    Sign In
                </button>

            <?= form_close() ?>
        </div>

        <!-- Footer Links -->
        <div class="text-center mt-8">
            <p class="text-slate-500 text-sm mb-2">
                <span class="w-2 h-2 rounded-full bg-emerald-500 inline-block mr-1"></span>
                Need an account? <a href="https://t.me/RapidCoreOwner" target="_blank" class="text-slate-400 hover:text-white transition underline decoration-slate-600 underline-offset-4">Contact Support</a>
            </p>
            <p class="text-slate-600 text-xs">
                © <?= date('Y') ?> RapidCore
            </p>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eyeIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.classList.remove('fa-eye');
                eyeIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                eyeIcon.classList.remove('fa-eye-slash');
                eyeIcon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>