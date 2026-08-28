<?= $this->extend('Layout/Starter') ?>

<?= $this->section('content') ?>

<div class="row">
    <div class="col-lg-12 mb-4">
        <?= $this->include('Layout/msgStatus') ?>
    </div> 

    <!-- Dashboard Stats -->
    <div class="col-lg-4 mb-4">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-header bg-primary text-white py-3">
                <h5 class="card-title mb-0"><i class="bi bi-person-circle me-2"></i>Account Information</h5>
            </div>
            <div class="card-body">
                <div class="mb-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="p-3 bg-light rounded-circle me-3">
                            <i class="bi bi-person-badge fs-4 text-primary"></i>
                        </div>
                        <div>
                            <h6 class="mb-1">Account Type</h6>
                            <h5 class="mb-0 text-primary"><?= getLevel($user->level) ?></h5>
                        </div>
                    </div>
                    <div class="d-flex align-items-center">
                        <div class="p-3 bg-light rounded-circle me-3">
                            <i class="bi bi-wallet2 fs-4 text-primary"></i>
                        </div>
                        <div>
                            <h6 class="mb-1">Balance</h6>
                            <h5 class="mb-0 text-primary">$<?= number_format($user->saldo, 2) ?></h5>
                        </div>
                    </div>
                </div>

                <hr>

                <div class="mt-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <i class="bi bi-clock-history text-muted me-2"></i>
                            <span>Login Time</span>
                        </div>
                        <span class="badge bg-light text-dark">
                            <?= $time::parse(session()->time_since)->humanize() ?>
                        </span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <i class="bi bi-stopwatch text-muted me-2"></i>
                            <span>Session Duration</span>
                        </div>
                        <span class="badge bg-light text-dark">
                            <?= $time::now()->difference($time::parse(session()->time_login))->humanize() ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Registration History -->
    <div class="col-lg-8 mb-4">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-header bg-primary text-white py-3">
                <h5 class="card-title mb-0">
                    <i class="bi bi-clock-history me-2"></i>Registration History
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="border-0">ID</th>
                                <th class="border-0">Game</th>
                                <th class="border-0">License</th>
                                <th class="border-0">Duration</th>
                                <th class="border-0">Devices</th>
                                <th class="border-0">Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($history as $h) : ?>
                                <?php $in = explode("|", $h->info) ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-light text-primary">#3812<?= $h->id_history ?></span>
                                    </td>
                                    <td><?= $in[0] ?></td>
                                    <td>
                                        <span class="badge bg-light text-dark"><?= $in[1] ?>**</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary"><?= $in[2] ?> Days</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-info"><?= $in[3] ?> Device</span>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <i class="bi bi-clock me-1"></i>
                                            <?= $time::parse($h->created_at)->humanize() ?>
                                        </small>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Leaderboard -->
    <div class="col-lg-4 mb-4">
        <?= $this->include('User/leaderboard') ?>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('css') ?>
<style>
.card {
    transition: transform 0.2s ease-in-out;
}
.card:hover {
    transform: translateY(-5px);
}
.table td, .table th {
    padding: 0.5rem; /* Giảm padding để các phần tử trong bảng tự thu nhỏ */
    vertical-align: middle;
}
.badge {
    padding: 0.3rem 0.6rem; /* Giảm padding để các phần tử badge thu nhỏ */
    font-size: 0.875rem; /* Giảm kích thước font của badge */
}
.table-responsive {
    overflow-x: auto; /* Đảm bảo bảng có thể cuộn ngang nếu cần */
}
.card-body {
    padding: 1rem; /* Giảm padding để các phần tử trong card tự thu nhỏ */
}
</style>
<?= $this->endSection() ?>