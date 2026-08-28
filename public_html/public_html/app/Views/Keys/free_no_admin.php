<?= $this->extend('Layout/Starter') ?>
<?= $this->section('content') ?>

<div class="justify-content-center">
    <div class="alert alert-warning text-center">
        <h4 class="alert-heading mb-3"><i class="bi bi-exclamation-triangle"></i> Invalid Access</h4>
        <p>Please use a valid admin link to access the key free system.</p>
        <p>Example: <code>keys/free?admin=Khanhmods</code></p>
    </div>
</div>

<?= $this->endSection() ?>