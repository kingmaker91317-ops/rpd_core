<?= $this->extend('Layout/Starter') ?>

<?= $this->section('content') ?>
<div class="row">
    <div class="col-lg-12">
        <div class="alert alert-primary" role="alert">
            <strong>INFO</strong> &middot; Here You Can Edit Status of Mod, Notices and all.
        </div>
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white h6 p-3">
                Manage <?= $title ?>
            </div>
            <div class="card-body">
              
                <div class="card-body  bg-dark text-white">
                    <?php if ($history) : ?>
                        <div class="table-responsive">
                            <table id="datatable" class="table table-bordered table-hover text-center text-white" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Game</th>
                                        <th>User Keys</th>
                                        <th>Devices</th>
                                        <th>Duration</th>
                                        <th>Expired</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                
                            </table>
                        </div>
                    <?php else : ?>
                        <p class="text-center">Nothing keys to show</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('css') ?>
<?= link_tag("https://cdn.datatables.net/1.10.25/css/dataTables.bootstrap5.min.css") ?>
<?= $this->endSection() ?>

<?= $this->section('js') ?>
<?= script_tag("https://cdn.datatables.net/1.10.25/js/jquery.dataTables.min.js") ?>

<?= script_tag("https://cdn.datatables.net/1.10.25/js/dataTables.bootstrap5.min.js") ?>
<?= $this->endSection() ?>