<?= $this->extend('Layout/Starter') ?>
<?= $this->section('content') ?>

<div class="col-lg-12">
    <div class="alert alert-danger">
        <h4><i class="bi bi-exclamation-triangle"></i> <?= $title ?></h4>
        <p><?= $message ?></p>
        <a href="/" class="btn btn-danger">Back to Home</a>
    </div>
</div>

<?= $this->endSection() ?>