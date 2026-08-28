<?= $this->extend('Layout/Starter') ?>

<?= $this->section('content') ?>

<div class="row mb-3">
    <div class="col-12">
        <?= $this->include('Layout/msgStatus') ?>
    </div>
</div>

<?php
$displayName = trim((string) getName($user));
$displayInitial = $displayName !== '' ? strtoupper(substr($displayName, 0, 1)) : 'U';

$allowedShortServices = ['xlink', 'linkx', 'yeumoney', 'sieuthiapi', 'funlink', 'layma', 'vuotlink', 'just2earn', 'nhapma'];
$normalizeShortService = static function ($service) use ($allowedShortServices) {
    $service = strtolower(trim((string) $service));
    if ($service === 'vuotlink.vip' || $service === 'vuotlinkvip') {
        $service = 'vuotlink';
    }
    if ($service === 'linkx.me' || $service === 'linkxme') {
        $service = 'linkx';
    }
    return in_array($service, $allowedShortServices, true) ? $service : 'xlink';
};

$shortlinkRows = [];
$postedServices = old('shortlink_services');
$postedTokens = old('api_tokens');
$fallbackService = $normalizeShortService(old('shortlink_service') ?: ($user->shortlink_service ?? 'xlink'));
$fallbackTokenRaw = old('api_token') ?: ($user->api_token ?? '');

if (is_array($postedServices) && is_array($postedTokens)) {
    foreach ($postedServices as $idx => $srv) {
        $token = trim((string) ($postedTokens[$idx] ?? ''));
        $service = $normalizeShortService($srv);
        if ($service || $token !== '') {
            $shortlinkRows[] = [
                'service' => $service,
                'token' => $token,
            ];
        }
    }
} else {
    $decoded = json_decode((string) $fallbackTokenRaw, true);
    if (is_array($decoded)) {
        foreach ($decoded as $row) {
            $service = $normalizeShortService($row['service'] ?? $fallbackService);
            $token = trim((string) ($row['token'] ?? ''));
            if ($token !== '') {
                $shortlinkRows[] = [
                    'service' => $service,
                    'token' => $token,
                ];
            }
        }
    }

    if (empty($shortlinkRows)) {
        $shortlinkRows[] = [
            'service' => $fallbackService,
            'token' => trim((string) $fallbackTokenRaw),
        ];
    }
}

if (empty($shortlinkRows)) {
    $shortlinkRows[] = [
        'service' => 'xlink',
        'token' => '',
    ];
}
?>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width:56px;height:56px;">
                        <?= esc($displayInitial) ?>
                    </div>
                    <div>
                        <div class="text-uppercase small text-muted">Profile</div>
                        <div class="fw-semibold"><?= esc($displayName ?: $user->username) ?></div>
                        <div class="small text-muted"><?= esc($user->username) ?></div>
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2 mt-3">
                    <span class="badge bg-light text-dark">Role: <?= esc(getLevel($user->level)) ?></span>
                    <span class="badge bg-light text-dark">Balance: $<?= number_format($user->saldo, 2) ?></span>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header p-3 bg-dark text-white">
                <h6 class="mb-0">Change Password</h6>
            </div>
            <div class="card-body">
                <?= form_open('', ['enctype' => 'multipart/form-data']) ?>
                <input type="hidden" name="password_form" value="1">
                <div class="mb-3">
                    <label for="current" class="form-label">Current Password</label>
                    <input type="password" name="current" id="current" class="form-control" placeholder="••••••••">
                    <?php if ($validation->hasError('current')) : ?>
                        <small class="text-danger"><?= $validation->getError('current') ?></small>
                    <?php endif; ?>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">New Password</label>
                    <input type="password" name="password" id="password" class="form-control" placeholder="••••••••">
                    <?php if ($validation->hasError('password')) : ?>
                        <small class="text-danger"><?= $validation->getError('password') ?></small>
                    <?php endif; ?>
                </div>
                <div class="mb-3">
                    <label for="password2" class="form-label">Confirm New Password</label>
                    <input type="password" name="password2" id="password2" class="form-control" placeholder="••••••••">
                    <?php if ($validation->hasError('password2')) : ?>
                        <small class="text-danger"><?= $validation->getError('password2') ?></small>
                    <?php endif; ?>
                </div>
                <button type="submit" class="btn btn-primary w-100">Change Password</button>
                <?= form_close() ?>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-header p-3 bg-primary text-white">
                <h6 class="mb-0">Profile Settings</h6>
            </div>
            <div class="card-body">
                <?= form_open('', ['enctype' => 'multipart/form-data']) ?>
                <input type="hidden" name="fullname_form" value="1">
                
                <!-- Basic Info -->
                <div class="mb-4">
                    <h6 class="text-muted text-uppercase mb-3" style="font-size: 0.75rem; letter-spacing: 0.5px;">Basic Information</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="fullname" class="form-label">Full Name</label>
                            <input type="text" name="fullname" id="fullname" class="form-control" placeholder="Maru-kun" value="<?= old('fullname') ?: ($user->fullname ?: '') ?>">
                            <?php if ($validation->hasError('fullname')) : ?>
                                <small class="text-danger"><?= $validation->getError('fullname') ?></small>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label for="menu_name" class="form-label">Menu Name</label>
                            <input type="text" name="menu_name" id="menu_name" class="form-control" placeholder="My Menu" value="<?= old('menu_name') ?: ($user->menu_name ?: '') ?>">
                            <?php if ($validation->hasError('menu_name')) : ?>
                                <small class="text-danger"><?= $validation->getError('menu_name') ?></small>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-12">
                            <label for="getkey_link" class="form-label">GetKey Link</label>
                            <div class="input-group">
                                <input type="text" id="getkey_link" class="form-control" value="<?= esc($getkey_link ?? '') ?>" readonly>
                                <button type="button" class="btn btn-outline-secondary" id="btnCopyGetkey"><i class="bi bi-clipboard"></i></button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Shortlink Services -->
                <div class="mb-4">
                    <h6 class="text-muted text-uppercase mb-3" style="font-size: 0.75rem; letter-spacing: 0.5px;">Shortlink Services</h6>
                    <div class="d-flex flex-column gap-2" id="shortlinkRows">
                        <?php foreach ($shortlinkRows as $idx => $row): ?>
                            <div class="row g-2 align-items-center shortlink-row" data-row-index="<?= $idx ?>">
                                <div class="col-md-5">
                                    <select name="shortlink_services[]" class="form-select form-select-sm">
                                        <option value="xlink" <?= ($row['service'] ?? '') === 'xlink' ? 'selected' : '' ?>>XLink</option>
                                        <option value="linkx" <?= ($row['service'] ?? '') === 'linkx' ? 'selected' : '' ?>>LinkX.me</option>
                                        <option value="yeumoney" <?= ($row['service'] ?? '') === 'yeumoney' ? 'selected' : '' ?>>YeuMoney</option>
                                        <option value="sieuthiapi" <?= ($row['service'] ?? '') === 'sieuthiapi' ? 'selected' : '' ?>>SieuThi API</option>
                                        <option value="funlink" <?= ($row['service'] ?? '') === 'funlink' ? 'selected' : '' ?>>Funlink</option>
                                        <option value="layma" <?= ($row['service'] ?? '') === 'layma' ? 'selected' : '' ?>>LAYMA.NET</option>
                                        <option value="vuotlink" <?= ($row['service'] ?? '') === 'vuotlink' ? 'selected' : '' ?>>VuotLink.vip</option>
                                        <option value="just2earn" <?= ($row['service'] ?? '') === 'just2earn' ? 'selected' : '' ?>>Just2Earn</option>
                                        <option value="nhapma" <?= ($row['service'] ?? '') === 'nhapma' ? 'selected' : '' ?>>NhapMa</option>
                                    </select>
                                </div>
                                <div class="col">
                                    <input type="text" name="api_tokens[]" class="form-control form-control-sm" placeholder="API Token" value="<?= esc($row['token'] ?? '') ?>">
                                </div>
                                <div class="col-auto">
                                    <button type="button" class="btn btn-outline-danger btn-sm remove-shortlink" title="Remove" <?= count($shortlinkRows) <= 1 ? 'disabled' : '' ?>><i class="bi bi-trash"></i></button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-2">
                        <small class="text-muted">Max 4 services, executed sequentially</small>
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="addShortlinkRow"><i class="bi bi-plus-lg"></i> Add Service</button>
                    </div>
                    <?php if ($validation->hasError('shortlink_services') || $validation->hasError('api_tokens')) : ?>
                        <small class="text-danger d-block mt-1">
                            <?= $validation->getError('shortlink_services') ?: $validation->getError('api_tokens') ?>
                        </small>
                    <?php endif; ?>
                </div>

                <!-- GetKey Settings -->
                <div class="mb-4">
                    <h6 class="text-muted text-uppercase mb-3" style="font-size: 0.75rem; letter-spacing: 0.5px;">GetKey Settings</h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="getkey_steps" class="form-label">Bypass Steps</label>
                            <input type="number" min="1" max="10" name="getkey_steps" id="getkey_steps" class="form-control" value="<?= old('getkey_steps') ?: ($user->getkey_steps ?? 1) ?>">
                            <?php if ($validation->hasError('getkey_steps')) : ?>
                                <small class="text-danger"><?= $validation->getError('getkey_steps') ?></small>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-4">
                            <label for="getkey_buy_ib" class="form-label">Liên kết mua key IB</label>
                            <input type="text" name="getkey_buy_ib" id="getkey_buy_ib" class="form-control" placeholder="https://..." value="<?= old('getkey_buy_ib') ?: ($user->getkey_buy_ib ?? '') ?>">
                            <?php if ($validation->hasError('getkey_buy_ib')) : ?>
                                <small class="text-danger"><?= $validation->getError('getkey_buy_ib') ?></small>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-4">
                            <label for="getkey_support_tele" class="form-label">Hỗ trợ nhanh Telegram</label>
                            <input type="text" name="getkey_support_tele" id="getkey_support_tele" class="form-control" placeholder="https://t.me/..." value="<?= old('getkey_support_tele') ?: ($user->getkey_support_tele ?? '') ?>">
                            <?php if ($validation->hasError('getkey_support_tele')) : ?>
                                <small class="text-danger"><?= $validation->getError('getkey_support_tele') ?></small>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-4">
                            <label for="getkey_auto_buy" class="form-label">Mua tự động</label>
                            <input type="text" name="getkey_auto_buy" id="getkey_auto_buy" class="form-control" placeholder="https://..." value="<?= old('getkey_auto_buy') ?: ($user->getkey_auto_buy ?? '') ?>">
                            <?php if ($validation->hasError('getkey_auto_buy')) : ?>
                                <small class="text-danger"><?= $validation->getError('getkey_auto_buy') ?></small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- GetKey Games -->
                <?php if (!empty($getkey_games_enabled)) : ?>
                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="text-muted text-uppercase mb-0" style="font-size: 0.75rem; letter-spacing: 0.5px;">Available Games</h6>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="btnGetkeyGamesAll">Select All</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="btnGetkeyGamesClear">Clear</button>
                        </div>
                    </div>
                    <div class="input-group input-group-sm mb-2">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control" id="getkey_games_search" placeholder="Search games...">
                    </div>
                    <div class="getkey-games-grid d-flex flex-wrap gap-2" id="getkey_games">
                        <?php if (!empty($getkey_games_available)) : ?>
                            <?php foreach (($getkey_games_available ?? []) as $g) : ?>
                                <?php $gid = (int) ($g['id'] ?? 0); ?>
                                <?php $inputId = 'getkey_game_' . $gid; ?>
                                <div class="getkey-game-item">
                                    <input type="checkbox"
                                           class="btn-check getkey-game-input"
                                           id="<?= esc($inputId) ?>"
                                           name="getkey_games[]"
                                           value="<?= esc($gid) ?>"
                                           <?= in_array($gid, ($getkey_games_selected ?? []), true) ? 'checked' : '' ?>>
                                    <label class="btn btn-outline-secondary btn-sm getkey-game-pill" for="<?= esc($inputId) ?>">
                                        <?= esc($g['name'] ?? '') ?>
                                        <span class="text-muted small">— <?= esc($g['package'] ?? '') ?></span>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <div class="text-muted small">(No games assigned)</div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary px-4">Save Changes</button>
                </div>
                <?= form_close() ?>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('js') ?>
<style>
.getkey-games-grid {
    max-height: 400px;
    overflow-y: auto;
    border: 1px solid #dee2e6;
    border-radius: 0.5rem;
    padding: 1rem;
    background: #f8f9fa;
}

.getkey-game-item {
    flex: 0 0 auto;
}

.getkey-game-pill {
    font-size: 0.8125rem;
    padding: 0.375rem 0.75rem;
    border-radius: 1.5rem;
    white-space: nowrap;
    transition: all 0.2s ease;
    border-color: #dee2e6;
}

.getkey-game-pill:hover {
    background-color: #e9ecef;
    border-color: #adb5bd;
}

.getkey-game-input:checked + .getkey-game-pill {
    background-color: #0d6efd;
    border-color: #0d6efd;
    color: white;
}

.getkey-game-pill .text-muted {
    opacity: 0.7;
    font-size: 0.75rem;
}

.getkey-game-input:checked + .getkey-game-pill .text-muted {
    color: rgba(255, 255, 255, 0.8) !important;
}

.getkey-game-item.d-none {
    display: none !important;
}

.getkey-games-grid::-webkit-scrollbar {
    width: 8px;
}

.getkey-games-grid::-webkit-scrollbar-track {
    background: #e9ecef;
    border-radius: 4px;
}

.getkey-games-grid::-webkit-scrollbar-thumb {
    background: #adb5bd;
    border-radius: 4px;
}

.getkey-games-grid::-webkit-scrollbar-thumb:hover {
    background: #6c757d;
}
</style>
<script>
(function(){
    const btn = document.getElementById('btnCopyGetkey');
    const input = document.getElementById('getkey_link');
    if (btn && input) {
        btn.addEventListener('click', function() {
            input.select();
            input.setSelectionRange(0, 99999);
            navigator.clipboard.writeText(input.value).then(() => {
                btn.classList.remove('btn-outline-primary');
                btn.classList.add('btn-success');
                btn.innerHTML = '<i class="bi bi-check-lg"></i> Copied';
                setTimeout(() => {
                    btn.classList.remove('btn-success');
                    btn.classList.add('btn-outline-primary');
                    btn.innerHTML = '<i class="bi bi-clipboard"></i> Copy';
                }, 1200);
            }).catch(() => {
                alert('Copy failed, hãy copy thủ công.');
            });
        });
    }

    const shortlinkRows = document.getElementById('shortlinkRows');
    const addShortlinkRow = document.getElementById('addShortlinkRow');
    const maxShortlinkRows = 4;

    const updateRemoveState = () => {
        if (!shortlinkRows) return;
        const rows = shortlinkRows.querySelectorAll('.shortlink-row');
        const disable = rows.length <= 1;
        rows.forEach(row => {
            const btnRemove = row.querySelector('.remove-shortlink');
            if (btnRemove) {
                btnRemove.disabled = disable;
            }
        });
    };

    const createRow = () => {
        const row = document.createElement('div');
        row.className = 'row g-2 align-items-center shortlink-row';
        row.innerHTML = `
            <div class="col-md-5">
                <select name="shortlink_services[]" class="form-select form-select-sm">
                    <option value="xlink">XLink</option>
                    <option value="linkx">LinkX.me</option>
                    <option value="yeumoney">YeuMoney</option>
                    <option value="sieuthiapi">SieuThi API</option>
                    <option value="funlink">Funlink</option>
                    <option value="layma">LAYMA.NET</option>
                    <option value="vuotlink">VuotLink.vip</option>
                    <option value="just2earn">Just2Earn</option>
                    <option value="nhapma">NhapMa</option>
                </select>
            </div>
            <div class="col">
                <input type="text" name="api_tokens[]" class="form-control form-control-sm" placeholder="API Token">
            </div>
            <div class="col-auto">
                <button type="button" class="btn btn-outline-danger btn-sm remove-shortlink" title="Remove"><i class="bi bi-trash"></i></button>
            </div>
        `;
        return row;
    };

    if (addShortlinkRow && shortlinkRows) {
        addShortlinkRow.addEventListener('click', () => {
            const current = shortlinkRows.querySelectorAll('.shortlink-row').length;
            if (current >= maxShortlinkRows) {
                alert('Maximum 4 services allowed.');
                return;
            }
            const row = createRow();
            shortlinkRows.appendChild(row);
            updateRemoveState();
        });

        shortlinkRows.addEventListener('click', (e) => {
            const target = e.target;
            if (target && target.classList.contains('remove-shortlink')) {
                const row = target.closest('.shortlink-row');
                if (row && shortlinkRows.querySelectorAll('.shortlink-row').length > 1) {
                    row.remove();
                    updateRemoveState();
                }
            }
        });

        updateRemoveState();
    }

    const gameContainer = document.getElementById('getkey_games');
    const gameInputs = gameContainer ? gameContainer.querySelectorAll('.getkey-game-input') : [];
    const btnAll = document.getElementById('btnGetkeyGamesAll');
    const btnClear = document.getElementById('btnGetkeyGamesClear');
    const searchInput = document.getElementById('getkey_games_search');

    if (btnAll) {
        btnAll.addEventListener('click', function() {
            gameInputs.forEach(function(inputEl) {
                inputEl.checked = true;
            });
        });
    }

    if (btnClear) {
        btnClear.addEventListener('click', function() {
            gameInputs.forEach(function(inputEl) {
                inputEl.checked = false;
            });
        });
    }

    if (searchInput && gameContainer) {
        const items = gameContainer.querySelectorAll('.getkey-game-item');
        items.forEach(function(item) {
            const label = item.querySelector('label');
            item.dataset.search = label ? label.textContent.toLowerCase() : '';
        });

        searchInput.addEventListener('input', function() {
            const term = searchInput.value.trim().toLowerCase();
            items.forEach(function(item) {
                const haystack = item.dataset.search || '';
                const match = term === '' || haystack.includes(term);
                item.classList.toggle('d-none', !match);
            });
        });
    }
})();
</script>
<?= $this->endSection() ?>
