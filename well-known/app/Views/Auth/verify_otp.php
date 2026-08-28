<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Owner Verification OTP | RapidCore</title>

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

        .otp-input {
            letter-spacing: 0.5em;
            font-size: 1.75rem;
            text-align: center;
        }
    </style>
</head>

<body class="min-h-screen flex items-center justify-center p-4">

    <div class="w-full max-w-md">
        <!-- Logo/Header -->
        <div class="text-center mb-8">
            <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-gradient-to-br from-amber-500 to-purple-600 flex items-center justify-center shadow-lg shadow-amber-500/20">
                <i class="fas fa-key text-3xl text-white"></i>
            </div>
            <h1 class="text-3xl font-bold text-white mb-2">Owner Security Check</h1>
            <p class="text-slate-400 text-sm">Enter the 6-digit OTP sent to your Telegram account</p>
            <?php if (session()->get('otp_pending_uname')) : ?>
                <span class="inline-block mt-2 px-3 py-1 bg-violet-500/20 border border-violet-500/30 rounded-full text-violet-300 text-xs font-semibold">
                    <i class="fas fa-user-shield mr-1"></i> Owner: <?= esc(session()->get('otp_pending_uname')) ?>
                </span>
            <?php endif; ?>
        </div>

        <!-- Verification Card -->
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

            <?= form_open('verify-otp') ?>

                <!-- OTP Code Input -->
                <div class="mb-6">
                    <label class="block text-slate-300 text-xs uppercase tracking-wider font-semibold mb-3 text-center">
                        6-Digit Security OTP
                    </label>
                    <div class="relative">
                        <input type="text" 
                               name="otp_code" 
                               id="otp_code"
                               maxlength="6" 
                               pattern="[0-9]*" 
                               inputmode="numeric" 
                               class="login-input otp-input w-full py-4 rounded-xl font-mono font-bold placeholder-slate-600" 
                               placeholder="••••••" 
                               required 
                               autofocus
                               autocomplete="one-time-code">
                    </div>
                    <?php if (isset($validation) && $validation->hasError('otp_code')) : ?>
                        <p class="text-red-400 text-xs mt-2 text-center"><?= $validation->getError('otp_code') ?></p>
                    <?php endif; ?>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="glow-btn w-full py-3.5 rounded-xl text-white font-bold text-lg tracking-wide mb-4">
                    Verify & Access Panel
                </button>

            <?= form_close() ?>

            <!-- Action Links -->
            <div class="flex items-center justify-between text-xs text-slate-400 pt-2 border-t border-white/5">
                <a href="<?= site_url('resend-otp') ?>" class="hover:text-purple-300 transition flex items-center gap-1">
                    <i class="fas fa-paper-plane text-violet-400"></i> Resend OTP via Telegram
                </a>
                <a href="<?= site_url('logout') ?>" class="hover:text-red-400 transition flex items-center gap-1">
                    <i class="fas fa-sign-out-alt"></i> Cancel
                </a>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-8">
            <p class="text-slate-500 text-xs">
                © <?= date('Y') ?> RapidCore • Owner Authentication Security
            </p>
        </div>
    </div>

    <script>
        // Auto format and focus
        const otpInput = document.getElementById('otp_code');
        otpInput.addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    </script>
</body>
</html>
