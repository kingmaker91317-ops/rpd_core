<?= $this->extend('Layout/Starter') ?>

<?= $this->section('content') ?>
<div class="row justify-content-center">
    <div class="col-lg-6">
        <?= $this->include('Layout/msgStatus') ?>
        <?php
            $generatedAccounts = session()->getFlashdata('generated_accounts');
            $generatedKeys = session()->getFlashdata('generated_keys');
            $flashGame = session()->getFlashdata('game');
            $flashDuration = session()->getFlashdata('duration');
            $flashKeyLevel = session()->getFlashdata('key_level');
            $flashMaxDevices = session()->getFlashdata('max_devices');
            $flashFees = session()->getFlashdata('fees');
            $flashUsername = session()->getFlashdata('username');
            $flashPassword = session()->getFlashdata('password');
            $gameName = $flashGame && isset($game[$flashGame]) ? $game[$flashGame] : $flashGame;
            $durationLabel = $flashDuration ? formatDuration((int) $flashDuration) : null;
            $deviceLabel = $flashMaxDevices ? '0/' . $flashMaxDevices : null;
        ?>
        <?php if (!empty($generatedAccounts)) : ?>
            <?php $copyId = uniqid('copy_'); ?>
            <?php
                $copyLines = [];
                foreach ($generatedAccounts as $account) {
                    $lineParts = ["User: " . $account['username']];
                    if (!empty($account['password'])) {
                        $lineParts[] = "Pass: " . $account['password'];
                    }
                    if ($durationLabel) {
                        $lineParts[] = "Hạn: " . $durationLabel;
                    }
                    if ($deviceLabel) {
                        $lineParts[] = "Thiết bị: " . $deviceLabel;
                    }
                    if ($gameName) {
                        $lineParts[] = "Game: " . $gameName;
                    }
                    $copyLines[] = implode(' | ', $lineParts);
                }
                $keysText = implode("\n", $copyLines);
            ?>
            <div class="alert alert-success" role="alert">
                <div id="<?= $copyId ?>" class="copy-content" style="display: none;">
                    <?= esc($keysText) ?>
                </div>
                <div>
                    <p>Accounts generated successfully:</p>
                    <?php if ($gameName || $durationLabel || $deviceLabel) : ?>
                        <div class="d-flex flex-wrap gap-2 mb-2">
                            <?php if ($gameName) : ?>
                                <span class="badge bg-light text-dark">Game: <?= esc($gameName) ?></span>
                            <?php endif; ?>
                            <?php if ($durationLabel) : ?>
                                <span class="badge bg-light text-dark">Hạn: <?= esc($durationLabel) ?></span>
                            <?php endif; ?>
                            <?php if ($deviceLabel) : ?>
                                <span class="badge bg-light text-dark">Thiết bị: <?= esc($deviceLabel) ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <ul>
                        <?php foreach ($generatedAccounts as $account) : ?>
                            <li>
                                <strong class="key-sensi22"><?= esc($account['username']) ?></strong>
                                <?php if (!empty($account['password'])) : ?>
                                    <br>Password: <strong><?= esc($account['password']) ?></strong>
                                <?php endif; ?>
                                <?php if ($gameName || $durationLabel || $deviceLabel) : ?>
                                    <div class="small text-muted mt-1">
                                        <?= $gameName ? 'Game: ' . esc($gameName) : '' ?>
                                        <?= $durationLabel ? ($gameName ? ' · ' : '') . 'Hạn: ' . esc($durationLabel) : '' ?>
                                        <?= $deviceLabel ? (($gameName || $durationLabel) ? ' · ' : '') . 'Thiết bị: ' . esc($deviceLabel) : '' ?>
                                    </div>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <small>
                    <i>The time will start when the key is logged in.</i><br>
                    <i class="bi bi-wallet"></i> Key creation fee:
                    <span class="text-danger">-<?= $flashFees ?></span>
                    (Total left <?= $user->saldo ?>$)
                </small>
                <div class="text-end mt-2">
                    <button type="button" class="btn btn-sm btn-outline-dark copy-btn" data-target="<?= $copyId ?>"><i class="bi bi-clipboard"></i> Copy</button>
                </div>
            </div>
        <?php elseif ($generatedKeys) : ?>
            <?php $copyId = uniqid('copy_'); ?>
            <?php
                $copyLines = [];
                foreach ($generatedKeys as $key) {
                    $lineParts = ["User: " . $key];
                    if ($durationLabel) {
                        $lineParts[] = "Hạn: " . $durationLabel;
                    }
                    if ($deviceLabel) {
                        $lineParts[] = "Thiết bị: " . $deviceLabel;
                    }
                    if ($gameName) {
                        $lineParts[] = "Game: " . $gameName;
                    }
                    $copyLines[] = implode(' | ', $lineParts);
                }
                $keysText = implode("\n", $copyLines);
            ?>
            <div class="alert alert-success" role="alert">
                <div id="<?= $copyId ?>" class="copy-content" style="display: none;">
                    <?= esc($keysText) ?>
                </div>
                <div>
                    <p>Keys generated successfully:</p>
                    <?php if ($gameName || $durationLabel || $deviceLabel) : ?>
                        <div class="d-flex flex-wrap gap-2 mb-2">
                            <?php if ($gameName) : ?>
                                <span class="badge bg-light text-dark">Game: <?= esc($gameName) ?></span>
                            <?php endif; ?>
                            <?php if ($durationLabel) : ?>
                                <span class="badge bg-light text-dark">Hạn: <?= esc($durationLabel) ?></span>
                            <?php endif; ?>
                            <?php if ($deviceLabel) : ?>
                                <span class="badge bg-light text-dark">Thiết bị: <?= esc($deviceLabel) ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <ul>
                        <?php foreach ($generatedKeys as $key) : ?>
                            <li>
                                <strong class="key-sensi22"><?= esc($key) ?></strong>
                                <?php if ($gameName || $durationLabel || $deviceLabel) : ?>
                                    <div class="small text-muted mt-1">
                                        <?= $gameName ? 'Game: ' . esc($gameName) : '' ?>
                                        <?= $durationLabel ? ($gameName ? ' · ' : '') . 'Hạn: ' . esc($durationLabel) : '' ?>
                                        <?= $deviceLabel ? (($gameName || $durationLabel) ? ' · ' : '') . 'Thiết bị: ' . esc($deviceLabel) : '' ?>
                                    </div>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <small>
                    <i>The time will start when the key is logged in.</i><br>
                    <i class="bi bi-wallet"></i> Key creation fee:
                    <span class="text-danger">-<?= $flashFees ?></span>
                    (Total left <?= $user->saldo ?>$)
                </small>
                <div class="text-end mt-2">
                    <button type="button" class="btn btn-sm btn-outline-dark copy-btn" data-target="<?= $copyId ?>"><i class="bi bi-clipboard"></i> Copy</button>
                </div>
            </div>
        <?php elseif ($flashUsername) : ?>
            <?php $copyId = uniqid('copy_'); ?>
            <?php
                $lineParts = ["User: " . $flashUsername];
                if ($flashPassword) {
                    $lineParts[] = "Pass: " . $flashPassword;
                }
                if ($durationLabel) {
                    $lineParts[] = "Hạn: " . $durationLabel;
                }
                if ($deviceLabel) {
                    $lineParts[] = "Thiết bị: " . $deviceLabel;
                }
                if ($gameName) {
                    $lineParts[] = "Game: " . $gameName;
                }
                $keysText = implode(' | ', $lineParts);
            ?>
            <div class="alert alert-success" role="alert">
                <div id="<?= $copyId ?>" class="copy-content" style="display: none;">
                    <?= esc($keysText) ?>
                </div>
                <div>
                    <strong class="key-sensi22"><?= esc($flashUsername) ?></strong>
                    <?php if ($flashPassword) : ?>
                        <div class="small text-muted mt-1">
                            Password: <strong><?= esc($flashPassword) ?></strong>
                        </div>
                    <?php endif; ?>
                    <?php if ($gameName || $durationLabel || $deviceLabel) : ?>
                        <div class="small text-muted mt-2">
                            <?= $gameName ? 'Game: ' . esc($gameName) : '' ?>
                            <?= $durationLabel ? ($gameName ? ' · ' : '') . 'Hạn: ' . esc($durationLabel) : '' ?>
                            <?= $deviceLabel ? (($gameName || $durationLabel) ? ' · ' : '') . 'Thiết bị: ' . esc($deviceLabel) : '' ?>
                        </div>
                    <?php endif; ?>
                </div>
                <small>
                    <i>The time will start when the key is logged in.</i><br>
                    <i class="bi bi-wallet"></i> Key creation fee:
                    <span class="text-danger">-<?= $flashFees ?></span>
                    (Total left <?= $user->saldo ?>$)
                </small>
                <div class="text-end mt-2">
                    <button type="button" class="btn btn-sm btn-outline-dark copy-btn" data-target="<?= $copyId ?>"><i class="bi bi-clipboard"></i> Copy</button>
                </div>
            </div>
        <?php endif; ?>
        <div class="card">
            <div class="card-header p3 bg-primary text-white">
                <div class="row">
                    <div class="col pt-1">
                        Create Key
                    </div>
                    <div class="col text-end">
                        <a class="btn btn-sm btn-outline-light" href="<?= site_url('keys') ?>"><i class="bi bi-people"></i></a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <?= form_open() ?>

                <div class="row">
                    <div class="form-group col-lg-12 mb-3">
                        <label for="game" class="form-label">Game</label>
                        <?= form_dropdown(['class' => 'form-select', 'name' => 'game', 'id' => 'game'], $game, old('game') ?: '') ?>
                        <?php if ($validation->hasError('game')) : ?>
                            <small id="help-game" class="text-danger"><?= $validation->getError('game') ?></small>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="row">
                    <div class="form-group col-lg-6 mb-3">
                        <label for="username" class="form-label">Username</label>
                        <div class="input-group">
                            <input type="text" name="username" id="username" class="form-control" placeholder="Enter username" value="<?= old('username') ?>">
                            <button type="button" class="btn btn-outline-secondary" onclick="generateRandomUsername()"><i class="bi bi-shuffle"></i></button>
                        </div>
                        <?php if ($validation->hasError('username')) : ?>
                            <small id="help-username" class="text-danger"><?= $validation->getError('username') ?></small>
                        <?php endif; ?>
                    </div>
                    <div class="form-group col-lg-6 mb-3" id="password-field-wrapper" style="display: none;">
                        <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="text" name="password" id="password" class="form-control" placeholder="Enter password" value="<?= old('password') ?>">
                            <button type="button" class="btn btn-outline-secondary" onclick="generateRandomPassword()"><i class="bi bi-shuffle"></i></button>
                        </div>
                        <?php if ($validation->hasError('password')) : ?>
                            <small id="help-password" class="text-danger"><?= $validation->getError('password') ?></small>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="row">
                    <div class="form-group col-lg-6 mb-3">
                        <label for="max_devices" class="form-label">Maximum Devices</label>
                        <input type="number" name="max_devices" id="max_devices" class="form-control" placeholder="1" value="<?= old('max_devices') ?: 1 ?>">
                        <?php if ($validation->hasError('max_devices')) : ?>
                            <small id="help-max_devices" class="text-danger"><?= $validation->getError('max_devices') ?></small>
                        <?php endif; ?>
                    </div>
                    <div class="form-group col-lg-6 mb-3">
                        <label for="duration" class="form-label">Số Ngày</label>
                        <?= form_dropdown(['class' => 'form-select', 'name' => 'duration', 'id' => 'duration'], $duration, old('duration') ?: '') ?>
                        <?php if ($validation->hasError('duration')) : ?>
                            <small id="help-duration" class="text-danger"><?= $validation->getError('duration') ?></small>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="row">
                    <div class="form-group col-lg-6 mb-3">
                        <label for="key_level" class="form-label">Key Level</label>
                        <?= form_dropdown(['class' => 'form-select', 'name' => 'key_level', 'id' => 'key_level'], $key_levels, old('key_level') ?: 1) ?>
                        <?php if ($validation->hasError('key_level')) : ?>
                            <small id="help-key_level" class="text-danger"><?= $validation->getError('key_level') ?></small>
                        <?php endif; ?>
                    </div>
                    <div class="form-group col-lg-6 mb-3">
                        <label for="quantity" class="form-label">Số Lượng Key</label>
                        <input type="number" name="quantity" id="quantity" class="form-control" placeholder="1" value="<?= old('quantity') ?: 1 ?>">
                        <?php if ($validation->hasError('quantity')) : ?>
                            <small id="help-quantity" class="text-danger"><?= $validation->getError('quantity') ?></small>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="form-group mb-3">
                    <label for="estimation" class="form-label">Estimation</label>
                    <input type="text" id="estimation" class="form-control" placeholder="Your order will total" readonly>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-outline-dark">Initialize</button>
                </div>
                <?= form_close() ?>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('js') ?>
<script>
    $(document).ready(function() {
        var price = JSON.parse('<?= $price ?>');
        var gamesInfo = <?= json_encode($games_info ?? []) ?>;
        
        // Function to show/hide password field based on selected game
        function togglePasswordField() {
            var selectedGame = $("#game").val();
            var passwordWrapper = $("#password-field-wrapper");
            var passwordInput = $("#password");
            
            if (selectedGame && gamesInfo[selectedGame] && gamesInfo[selectedGame].require_password) {
                passwordWrapper.show();
                passwordInput.prop('required', true);
            } else {
                passwordWrapper.hide();
                passwordInput.prop('required', false);
                passwordInput.val(''); // Clear password when hidden
            }
        }
        
        // Check on page load
        togglePasswordField();
        
        // Check when game changes
        $("#game").change(function() {
            togglePasswordField();
            getPrice(price);
        });
        
        getPrice(price);
        // When selected
        $("#max_devices, #duration, #key_level, #quantity").change(function() {
            getPrice(price);
        });
        // try to get price
        function getPrice(price) {
            var price = price;
            var device = $("#max_devices").val();
            var durate = $("#duration").val();
            var keyLevel = $("#key_level").val();
            var quantity = $("#quantity").val();
            var gprice = price[durate];
            if (!isNaN(gprice)) {
                var result = (device * gprice) * quantity;
                // Nếu là VIP key, tăng giá lên (ví dụ: gấp đôi)
                if(keyLevel == 2) {
                    result = result * 2;
                }
                $("#estimation").val(result);
            } else {
                $("#estimation").val('Estimation error');
            }
        }
    });

    $(document).on('click', '.copy-btn', function() {
        var $btn = $(this);
        var targetId = $btn.data('target');
        var target = document.getElementById(targetId);
        if (!target) return;
        var text = target.innerText.trim();
        var originalHtml = $btn.html();
        $btn.blur();
        navigator.clipboard.writeText(text).then(function() {
            $btn.removeClass('btn-outline-dark').addClass('btn-success').html('<i class="bi bi-check-lg"></i> Copied');
        }).catch(function() {
            alert('Failed to copy. Please copy manually.');
        }).finally(function() {
            setTimeout(function() {
                $btn.removeClass('btn-success').addClass('btn-outline-dark').html(originalHtml);
            }, 1500);
        });
    });

    function generateRandomString(length) {
        var characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        var result = '';
        var charactersLength = characters.length;
        for (var i = 0; i < length; i++) {
            result += characters.charAt(Math.floor(Math.random() * charactersLength));
        }
        return result;
    }

    function generateRandomPassword() {
        document.getElementById('password').value = generateRandomString(10);
    }

    function generateRandomUsername() {
        document.getElementById('username').value = generateRandomString(10);
    }
</script>
<?= $this->endSection() ?>
