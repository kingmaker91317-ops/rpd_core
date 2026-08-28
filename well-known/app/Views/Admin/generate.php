<?= $this->extend('Layout/Starter') ?>

<?= $this->section('content') ?>
<div class="row justify-content-center">
    <div class="col-lg-6">
        <?= $this->include('Layout/msgStatus') ?>
        <?php if (session()->getFlashdata('user_key')) : ?>
            <div class="alert alert-success" role="alert">
                Game : <?= session()->getFlashdata('game') ?> / <?= session()->getFlashdata('duration') ?> Days<br>
                License : <strong class="key-sensi"><?= session()->getFlashdata('user_key') ?></strong><br>
                Available for <?= session()->getFlashdata('max_devices') ?> Devices<br>
                <small>
                    <i>Duration will start when license login.</i><br>
                    <i class="bi bi-wallet"></i> Credits Reduce :
                    <span class="text-danger">-<?= session()->getFlashdata('fees') ?></span>
                    (Total left <?= $user->saldo ?> Credits)
                </small>
            </div>
        <?php endif; ?>
        <div class="card">
            <div class="card-header p3 bg-dark text-white">
                <div class="row">
                    <div class="col pt-1">
                        Create License
                    </div>
                    <div class="col text-end">
                        <a class="btn btn-sm btn-outline-light" href="<?= site_url('keys') ?>"><i class="bi bi-people"></i></a>
                    </div>
                </div>
            </div>
            <div class="card-body  bg-dark  text-white">
                <?= form_open() ?>

                <div class="row">
                    <div class="form-group col-lg-6 mb-3">
                        <label for="game" class="form-label">Games</label>
                        <?= form_dropdown(['class' => 'form-select', 'name' => 'game', 'id' => 'game'], $game, old('game') ?: '') ?>
                        <?php if ($validation->hasError('game')) : ?>
                            <small id="help-game" class="text-danger"><?= $validation->getError('game') ?></small>
                        <?php endif; ?>
                    </div>
                    <div class="form-group col-lg-6 mb-3">
                        <label for="max_devices" class="form-label">Max Devices</label>
                        <input type="number" name="max_devices" id="max_devices" class="form-control" placeholder="1" readonly value="<?= old('max_devices') ?: 1 ?>">
                        <?php if ($validation->hasError('game')) : ?>
                            <small id="help-max_devices" class="text-danger"><?= $validation->getError('max_devices') ?></small>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="form-group mb-3">
                    <label for="duration" class="form-label">Duration</label>
                    <?= form_dropdown(['class' => 'form-select', 'name' => 'duration', 'id' => 'duration'], $duration, old('duration') ?: '') ?>
                    <?php if ($validation->hasError('duration')) : ?>
                        <small id="help-duration" class="text-danger"><?= $validation->getError('duration') ?></small>
                    <?php endif; ?>
                </div>
                

                    <br>
                    <label class="form-check-label" for="check">Custom Key</label>
                    <input class="form-check-input" type="checkbox" name="check" id="check">

                    <br><br>
                    <label for="custom" id="cuslabel" class="form-label">Custom Key</label>
                    <div class="input-group" id="customKeyInputGroup" style="display:none;">
                        <span class="input-group-text"><i class="bi bi-key"></i></span>
                        <input type="text" minlength="4" maxlength="16" name="cuslicense" class="form-control" id="custom">
                    </div>

                    <br>
                    <label for="hulala" id="labula" class="form-label">Bulk Keys</label>
                    <select class="form-select" id="hulala" name="loopcount">
                        <option value="5">1 Key</option>
                        <option value="1">5 Keys</option>
                        <option value="2">10 Keys</option>
                        <option value="3">25 Keys</option>
                        <option value="3">50 Keys</option>
                        <option value="4">100 Keys</option>
                    </select>

                    <input type="text" id="textinput" name="custominput" hidden>

                <div class="form-group mb-3">
                    <label for="estimation" class="form-label">Credits (-)</label>
                    <input type="text" id="estimation" class="form-control" placeholder="Your order will total" readonly>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-outline-dark btn-danger">Generate</button>
                </div>
                


                    <?= form_close() ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap Icons CDN -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

<?= $this->endSection() ?>

<?= $this->section('js') ?>
<script>
    $(document).ready(function () {
        const price = JSON.parse('<?= $price ?>');
        updatePrice();

        $("#max_devices, #duration, #game").change(updatePrice);

        function updatePrice() {
            const device = $("#max_devices").val();
            const durate = $("#duration").val();
            const gprice = price[durate];
            const result = device * gprice;
            $("#estimation").val(isNaN(result) ? 'Estimation error' : result);
        }

        // Always hide Bulk Keys dropdown and label
        $("#hulala, #labula").hide();

        // Hide custom key input group and label by default
        $("#customKeyInputGroup, #cuslabel").hide();

        // Show/hide custom key input on checkbox change
        $("#check").change(function () {
            if (this.checked) {
                $("#customKeyInputGroup, #cuslabel").show();
                $('#textinput').val("custom");
            } else {
                $("#customKeyInputGroup, #cuslabel").hide();
                $('#textinput').val("auto");
            }
        });
    });
</script>
<?= $this->endSection() ?>
