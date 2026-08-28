<?= $this->extend('Layout/Starter') ?>

<?= $this->section('css') ?>
<style>
    input[readonly] {
        background-color: #f8f9fa !important;
        cursor: not-allowed;
        opacity: 0.7;
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="row pb-5 justify-content-center">
    <div class="col-lg-8">
        <?= $this->include('Layout/msgStatus') ?>
    </div>
    <div class="col-lg-8 mb-3">
        <div class="card">
            <div class="card-header bg-dark text-white">
                <div class="row">
                    <div class="col pt-1">
                        Key Information
                    </div>
                    <div class="col">
                        <div class="text-end">
                            <a class="btn btn-sm btn-outline-light" href="<?= site_url('keys/generate') ?>"><i class="bi bi-person-plus"></i></a>
                            <a class="btn btn-sm btn-outline-light" href="<?= site_url('keys') ?>"><i class="bi bi-people"></i></a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <?= form_open('keys/edit') ?>
                <div class="row">
                    <input type="hidden" name="<?= esc($key_column) ?>" value="<?= esc($key->{$key_column}) ?>">
                    <input type="hidden" name="original_username" value="<?= esc($key->username) ?>">
                    
                    <?php if ($can_edit['game'] && $game_list) : ?>
                    <div class="col-lg-6 mb-3">
                        <label for="game" class="form-label">Game</label>
                        <?= form_dropdown([
                            'class' => 'form-select', 
                            'name' => 'game', 
                            'id' => 'game'
                        ], $game_list, old('game') ?: $key->game) ?>
                        <?php if ($validation->hasError('game')) : ?>
                            <small id="help-game" class="text-danger"><?= $validation->getError('game') ?></small>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <?php if ($can_edit['username']) : ?>
                    <div class="col-lg-6 mb-3">
                        <label for="username" class="form-label">Key</label>
                        <input type="text" name="username" id="username" class="form-control" value="<?= old('username') ?: $key->username ?>">
                        <?php if ($validation->hasError('username')) : ?>
                            <small id="help-username" class="text-danger"><?= $validation->getError('username') ?></small>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <?php if ($can_edit['key_level']) : ?>
                    <div class="col-lg-6 mb-3">
                        <label for="key_level" class="form-label">Key Level</label>
                        <?= form_dropdown([
                            'class' => 'form-select', 
                            'name' => 'key_level', 
                            'id' => 'key_level'
                        ], $key_levels, old('key_level') ?: $key->key_level) ?>
                        <?php if ($validation->hasError('key_level')) : ?>
                            <small id="help-key_level" class="text-danger"><?= $validation->getError('key_level') ?></small>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <?php if ($can_edit['duration']) : ?>
                    <div class="col-lg-6 mb-3">
                        <label for="duration" class="form-label">Duration <small class="text-muted">(in days)</small></label>
                        <input type="number" name="duration" id="duration" class="form-control" 
                               value="<?= old('duration') ?: $key->duration ?>"
                               <?= !$can_modify['duration'] ? 'readonly' : '' ?>>
                        <?php if ($validation->hasError('duration')) : ?>
                            <small id="help-duration" class="text-danger"><?= $validation->getError('duration') ?></small>
                        <?php endif; ?>
                        <?php if (!$can_modify['duration']) : ?>
                            <small class="text-muted">Only admin can modify this field</small>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <?php if ($can_edit['max_devices']) : ?>
                    <div class="col-lg-6 mb-3">
                        <label for="max_devices" class="form-label">Max Devices</label>
                        <input type="number" name="max_devices" id="max_devices" class="form-control" 
                               value="<?= old('max_devices') ?: $key->max_devices ?>"
                               <?= !$can_modify['max_devices'] ? 'readonly' : '' ?>>
                        <?php if ($validation->hasError('max_devices')) : ?>
                            <small id="help-max_devices" class="text-danger"><?= $validation->getError('max_devices') ?></small>
                        <?php endif; ?>
                        <?php if (!$can_modify['max_devices']) : ?>
                            <small class="text-muted">Only admin can modify this field</small>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Status luôn hiển thị vì ai cũng sửa được -->
                    <div class="col-md-6 mb-2">
                        <label for="status" class="form-label">Status</label>
                        <?= form_dropdown([
                            'class' => 'form-select', 
                            'name' => 'status', 
                            'id' => 'status'
                        ], [
                            '' => '— Select Status —', 
                            '0' => 'Blocked/Locked', 
                            '1' => 'Active'
                        ], old('status') ?: $key->status) ?>
                        <?php if ($validation->hasError('status')) : ?>
                            <small id="help-status" class="text-danger"><?= $validation->getError('status') ?></small>
                        <?php endif; ?>
                    </div>

                    <?php if ($can_edit['registrator']) : ?>
                    <div class="col-md-6 mb-3">
                        <label for="registrator" class="form-label">Creator</label>
                        <input type="text" name="registrator" id="registrator" class="form-control" value="<?= old('registrator') ?: $key->registrator ?>">
                        <?php if ($validation->hasError('registrator')) : ?>
                            <small id="help-registrator" class="text-danger"><?= $validation->getError('registrator') ?></small>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <?php if ($can_edit['expired_date']) : ?>
                    <div class="col-md-12 mb-3">
                        <label for="expired_date" class="form-label">Expiration <?= !$key->expired_date ? '(Not started yet)' : '' ?></label>
                        <input type="text" name="expired_date" id="expired_date" class="form-control" 
                               placeholder="<?= date('Y-m-d H:i:s') ?>" 
                               value="<?= old('expired_date') ?: $key->expired_date ?>"
                               <?= !$can_modify['expired_date'] ? 'readonly' : '' ?>>
                        <?php if ($validation->hasError('expired_date')) : ?>
                            <small id="help-expired_date" class="text-danger"><?= $validation->getError('expired_date') ?></small>
                        <?php endif; ?>
                        <?php if (!$can_modify['expired_date']) : ?>
                            <small class="text-muted">Only admin can modify this field</small>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <?php if ($can_edit['devices']) : ?>
                    <div class="col-lg-12 mb-3">
                        <label for="devices" class="form-label">
                            Devices <span class="bg-dark text-white px-1 rounded maxDev"><?= $key_info->total ?>/<?= $key->max_devices ?></span>
                            <small class="text-muted">(Separately with enter)</small>
                        </label>
                        <textarea class="form-control" name="devices" id="devices" 
                                rows="<?= ($key_info->total > $key->max_devices) ? 3 : $key_info->total ?>"
                        ><?= old('devices') ?: ($key_info->total ? $key_info->devices : '') ?></textarea>
                        <?php if ($validation->hasError('devices')) : ?>
                            <small id="help-devices" class="text-danger"><?= $validation->getError('devices') ?></small>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <div class="col-lg-6">
                        <button type="submit" class="btn btn-outline-dark btnUpdate" disabled>Save</button>
                    </div>
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
    // Enable update button when any field changes (except readonly fields)
    $("input:not([readonly]), select, textarea").change(function() {
        $(".btnUpdate").attr('disabled', false);
    });

    // Update max devices display (only if not readonly)
    var total = "<?= $key_info->total ?>";
    <?php if ($can_modify['max_devices']) : ?>
    $("#max_devices").change(function() {
        $(".maxDev").html(total + '/' + $(this).val());
        $("#devices").attr('rows', $(this).val());
    });
    <?php endif; ?>
    
    // Prevent editing readonly fields
    $("input[readonly]").on('keydown paste', function(e) {
        e.preventDefault();
        return false;
    });
});
</script>
<?= $this->endSection() ?>
