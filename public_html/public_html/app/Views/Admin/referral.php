<?= $this->extend('Layout/Starter') ?>

<?= $this->section('content') ?>
<div class="row">
    <div class="col-lg-12">
        <?= $this->include('Layout/msgStatus') ?>
    </div>

    <!-- Form Generate Code -->
    <div class="col-lg-4 mb-3">
        <div class="card">
            <div class="card-header bg-primary text-white p-3">
                Generate <?= $title ?>
            </div>
            <div class="card-body">
                <?= form_open() ?>
                <div class="form-group mb-3">
                    <label for="set_saldo">Set Balance Amount</label>
                    <div class="input-group mt-2">
                        <span class="input-group-text"><i class="bi bi-currency-dollar"></i></span>
                        <input type="number" class="form-control" name="set_saldo" id="set_saldo" minlength="1" maxlength="11" value="5">
                    </div>
                    <?php if ($validation->hasError('set_saldo')) : ?>
                        <small id="help-saldo" class="text-danger"><?= $validation->getError('set_saldo') ?></small>
                    <?php endif; ?>
                </div>

                <div class="form-group mb-3">
    <label for="level">Account Level</label>
    <select class="form-select" name="level" id="level">
        <?php if ($user->level == 1): ?>
            <option value="2">Reseller</option>
            <option value="3">Tenant</option>
            <option value="1">Admin</option>
        <?php else: ?>
            <option value="2">Reseller</option>
        <?php endif; ?>
    </select>
    <?php if ($validation->hasError('level')) : ?>
        <small class="text-danger"><?= $validation->getError('level') ?></small>
    <?php endif; ?>
</div>

                <div class="form-group mb-3">
                    <label for="contract_expired_at">Contract Expiry Date (Optional)</label>
                    <input type="date" class="form-control" name="contract_expired_at" id="contract_expired_at">
                    <small class="text-muted">Để trống = không giới hạn thời gian</small>
                    <?php if ($validation->hasError('contract_expired_at')) : ?>
                        <small class="text-danger"><?= $validation->getError('contract_expired_at') ?></small>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-outline-dark">Create Code</button>
                </div>
                <?= form_close() ?>
            </div>
        </div>
    </div>

    <!-- Referral Codes List -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header bg-dark text-white p-3">
                <?php if ($user->level == 1): ?>
                    All Referral Codes - Total: <?= $total_code ?>
                <?php else: ?>
                    Your Referral Codes - Total: <?= $total_code ?>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover text-center" id="referralTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Referral Code</th>
                                <th>Balance</th>
                                <th>Level</th>
                                <th>Status</th>
                                <th>Used by</th>
                                <th>Created by</th>
                                <th>Created at</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($codes as $c) : ?>
                                <tr>
                                    <td><?= $c->id_reff ?></td>
                                    <td>
                                        <code class="bg-light px-2"><?= $c->orig_code ?></code>
                                        <?php if ($c->used_by === null): ?>
                                            <button class="btn btn-sm btn-outline-primary copy-btn" data-code="<?= $c->orig_code ?>">
                                                <i class="bi bi-clipboard"></i>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                    <td>$<?= $c->set_saldo ?></td>
                                    <td>
    <?php
    switch($c->level) {
        case 1: echo '<span class="badge bg-danger">Admin</span>'; break;
        case 2: echo '<span class="badge bg-primary">Reseller</span>'; break;
        case 3: echo '<span class="badge bg-success">Tenant</span>'; break;
    }
    ?>
</td>
                                    <td>
                                        <?php if ($c->used_by === null): ?>
                                            <span class="badge bg-success">Available</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Used</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $c->used_by ?: '&mdash;' ?></td>
                                    <td><?= $c->created_by ?></td>
                                    <td><small><?= date('Y-m-d H:i', strtotime($c->created_at)) ?></small></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#referralTable').DataTable({
        order: [[0, 'desc']]
    });

    $('.copy-btn').click(function() {
        var code = $(this).data('code');
        navigator.clipboard.writeText(code).then(function() {
            alert('Code copied: ' + code);
        });
    });
});
</script>
<?= $this->endSection() ?>