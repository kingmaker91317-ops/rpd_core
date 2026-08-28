<?= $this->extend('Layout/Starter') ?>

<?= $this->section('content') ?>
<div class="row justify-content-center">
    <div class="row">
        <div class="col-lg-12">
            <?= $this->include('Layout/msgStatus') ?>
        </div>
        <div class="col-lg-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <div class="row">
                        <div class="col pt-1">
                            Created Key
                        </div>
                        <div class="col text-end">
                            <a class="btn btn-outline-light btn-sm" href="<?= site_url('keys/generate') ?>"><i class="bi bi-person-plus"></i> KEY</a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <?php if ($keylist) : ?>
                        <div class="table-responsive">
                            <table id="datatable" class="table table-bordered table-hover text-center" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Game</th>
                                        <th>Key</th>
                                        <th>Level</th>
                                        <th>Device</th>
                                        <th>Duration</th>
                                        <th>Expired</th>
                                        <th>Advanced</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    <?php else : ?>
                        <p class="text-center">No Key to display</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('css') ?>
<?= link_tag("https://cdn.datatables.net/1.10.25/css/dataTables.bootstrap5.min.css") ?>
<style>
    .badge-vip {
        background-color: #FFD700;
        color: #000;
    }
    .badge-free {
        background-color: #6c757d;
        color: #fff;
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('js') ?>
<?= script_tag("https://cdn.datatables.net/1.10.25/js/jquery.dataTables.min.js") ?>
<?= script_tag("https://cdn.datatables.net/1.10.25/js/dataTables.bootstrap5.min.js") ?>
<script>
    const keyResetUrl = "<?= site_url('keys/reset') ?>";
    const keyDeleteUrl = "<?= site_url('keys/delete') ?>";
    const keyEditBaseUrl = "<?= rtrim(site_url('keys/edit'), '/') ?>";

    $(document).ready(function() {
        var table = $('#datatable').DataTable({
            processing: true,
            serverSide: true,
            order: [
                [0, "asc"]
            ],
            ajax: "<?= site_url('keys/api') ?>",
            columns: [{
                    data: null,
                    orderable: false,
                    searchable: false,
                    render: function(data, type, row, meta) {
                        // meta.row is the row index (0-based)
                        // meta.settings._iDisplayStart is the start index for current page
                        return meta.settings._iDisplayStart + meta.row + 1;
                    }
                },
                {
                    data: 'game',
                },
                {
                    data: 'username',
                    render: function(data, type, row, meta) {
                        var is_valid = (row.status == 'Active') ? "text-success" : "text-danger";
                        return `<span class="${is_valid} key-sensi22">${(row.username ? row.username : '&mdash;')}</span> `;
                    }
                },
                {
                    data: 'key_level',
                    render: function(data, type, row, meta) {
                        var levelClass = (row.key_level == 2) ? "badge-vip" : "badge-free";
                        var levelText = (row.key_level == 2) ? "VIP" : "FREE";
                        return `<span class="badge ${levelClass}">${levelText}</span>`;
                    }
                },
                {
                    data: 'devices',
                    render: function(data, type, row, meta) {
                        var totalDevice = (row.devices ? row.devices : 0);
                        var rowNum = meta.settings._iDisplayStart + meta.row + 1;
                        return `<span id="devMax-${rowNum}">${totalDevice}/${row.max_devices}</span>`;
                    }
                },
                {
                    data: 'duration',
                    render: function(data, type, row, meta) {
                        return row.duration;
                    }
                },
                {
                    data: 'expired',
                    name: 'expired_date',
                    render: function(data, type, row, meta) {
                        return row.expired ? `<span class="badge text-dark">${row.expired}</span>` : '(not started yet)';
                    }
                },
                {
                    data: null,
                    render: function(data, type, row, meta) {
                        // Use username as identifier since all keys have id=0
                        var keyIdentifier = row.username;
                        var rowNum = meta.settings._iDisplayStart + meta.row + 1;
                        var btnReset = `<button class="btn btn-outline-danger btn-sm" onclick="resetUserKey('${row.username}', '${rowNum}')"
                        data-bs-toggle="tooltip" data-bs-placement="left" title="Reset key?"><i class="bi bi-bootstrap-reboot"></i></button>`;
                        var btnEdits = `<a href="${keyEditBaseUrl}/${keyIdentifier}" class="btn btn-outline-dark btn-sm"
                        data-bs-toggle="tooltip" data-bs-placement="left" title="Edit key information?"><i class="bi bi-person"></i></a>`;
                        var btnDelete = `<button class="btn btn-outline-danger btn-sm" onclick="deleteUserKey('${row.username}', '${rowNum}')"
                        data-bs-toggle="tooltip" data-bs-placement="left" title="Delete key?"><i class="bi bi-trash"></i></button>`;
                        return `<div class="d-grid gap-2 d-md-block">${btnReset} ${btnEdits} ${btnDelete}</div>`;
                    }
                }
            ]
        });
    });

    function resetUserKey(keyValue, rowId) {
        Swal.fire({
            title: 'Do you want to reset?',
            text: "You will not be able to undo this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'OK'
        }).then((result) => {
            if (result.isConfirmed) {
                Toast.fire({
                    icon: 'info',
                    title: 'Please wait...'
                })

                $.getJSON(keyResetUrl, {
                        userkey: keyValue,
                        reset: 1
                    },
                    function(data, textStatus, jqXHR) {
                        if (textStatus == 'success') {
                            if (data.registered) {
                                if (data.reset) {
                                    $(`#devMax-${rowId}`).html(`0/${data.devices_max}`);
                                    Swal.fire(
                                        'Reset!',
                                        'The key has been reset.',
                                        'success'
                                    )
                                } else {
                                    Swal.fire(
                                        'Failed!',
                                        data.devices_total ? "You do not have access to this user" : "The key has already been reset.",
                                        data.devices_total ? 'error' : 'warning'
                                    )
                                }
                            } else {
                                Swal.fire(
                                    'Failed!',
                                    "The key does not exist.",
                                    'error'
                                )
                            }
                        }
                    }
                );
            }
        });
    }

    function deleteUserKey(keyValue, rowId) {
        Swal.fire({
            title: 'Do you want to delete this key?',
            text: "You will not be able to undo this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'OK'
        }).then((result) => {
            if (result.isConfirmed) {
                Toast.fire({
                    icon: 'info',
                    title: 'Please wait...'
                })

                $.getJSON(keyDeleteUrl, {
                        userkey: keyValue,
                        delete: 1
                    },
                    function(data, textStatus, jqXHR) {
                        if (textStatus == 'success') {
                            if (data.registered) {
                                if (data.delete) {
                                    $(`#devMax-${rowId}`).parents('tr').remove();
                                    Swal.fire(
                                        'Deleted!',
                                        'The key has been deleted.',
                                        'success'
                                    )
                                } else {
                                    Swal.fire(
                                        'Failed!',
                                        "You do not have access to this user",
                                        'error'
                                    )
                                }
                            } else {
                                Swal.fire(
                                    'Failed!',
                                    "The key does not exist.",
                                    'error'
                                )
                            }
                        }
                    }
                );
            }
        });
    }
</script>
<?= $this->endSection() ?>
