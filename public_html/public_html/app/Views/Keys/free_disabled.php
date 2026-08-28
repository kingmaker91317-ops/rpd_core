<?= $this->extend('Layout/Starter') ?>
<?= $this->section('content') ?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6 mt-5">
            <div class="card shadow">
                <div class="card-body text-center">
                    <i class="bi bi-exclamation-circle text-warning" style="font-size: 3rem;"></i>
                    <h4 class="mt-3">System Disabled</h4>
                    <p class="text-muted"><?= esc($message) ?></p>
                    <a href="<?= site_url() ?>" class="btn btn-primary mt-3">
                        <i class="bi bi-house"></i> Back to Home
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>