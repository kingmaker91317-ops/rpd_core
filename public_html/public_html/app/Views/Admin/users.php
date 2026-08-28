<?= $this->extend('Layout/Starter') ?>

<?= $this->section('content') ?>
<div class="row">
    <div class="col-lg-12">
        <?= $this->include('Layout/msgStatus') ?>
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white p-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0">
                    <?php if ($user->level == 1): ?>
                        Manage All Users
                    <?php elseif ($user->level == 3): ?>
                        Manage Account & Resellers
                    <?php else: ?>
                        Manage Account
                    <?php endif; ?>
                </h6>
                <div>
                    <span class="badge bg-light text-dark">
                        Create by Khánh Mods
                    </span>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="datatable" class="table table-bordered table-hover text-center" style="width:100%">
                        <thead>
                            <tr>
                                <th scope="row">#</th>
                                <th>Username</th>
                                <th>Fullname</th>
                                <th>Level</th>
                                <th>Balance</th>
                                <th>Status</th>
                                <th>Uplink</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('css') ?>
<?= link_tag("https://cdn.datatables.net/1.10.25/css/dataTables.bootstrap5.min.css") ?>
<style>
.action-btn {
    margin: 0 2px;
}
.status-badge {
    padding: 5px 10px;
    border-radius: 15px;
}
</style>
<?= $this->endSection() ?>

<?= $this->section('js') ?>
<?= script_tag("https://cdn.datatables.net/1.10.25/js/jquery.dataTables.min.js") ?>
<?= script_tag("https://cdn.datatables.net/1.10.25/js/dataTables.bootstrap5.min.js") ?>
<script>
$(document).ready(function() {
    var table = $('#datatable').DataTable({
        processing: true,
        serverSide: true,
        order: [[0, "desc"]],
        ajax: "<?= site_url('admin/api/users') ?>",
        columns: [
            { 
                data: 'id'
            },
            {
                data: 'username',
                render: function(data, type, row) {
                    return `<strong>${data}</strong>`;
                }
            },
            {
                data: 'fullname',
                render: function(data, type, row) {
                    return data || '<em>Not set</em>';
                }
            },
            {
                data: 'level',
                render: function(data, type, row) {
                    let badge = '';
                    switch(data) {
                        case 'Admin': badge = 'danger'; break;
                        case 'Reseller': badge = 'primary'; break;
                        case 'Tenant': badge = 'success'; break;
                    }
                    return `<span class="badge bg-${badge}">${data}</span>`;
                }
            },
            {
                data: 'saldo',
                render: function(data, type, row) {
                    return `<span class="badge bg-info">$${data}</span>`;
                }
            },
            {
                data: 'status',
                render: function(data, type, row) {
                    const status = parseInt(data);
                    return status === 1 
                        ? '<span class="status-badge bg-success text-white">Active</span>'
                        : '<span class="status-badge bg-danger text-white">Banned</span>';
                }
            },
            {
                data: 'uplink'
            },
            {
                data: 'created_at',
                render: function(data) {
                    return data ? moment.utc(data).local().format('YYYY-MM-DD HH:mm:ss') : '';
                }
            },
            {
                data: null,
                render: function(data, type, row) {
                    let buttons = '';
                    const currentUser = <?= json_encode([
                        'id' => $user->id_users,
                        'level' => $user->level,
                        'username' => $user->username
                    ]) ?>;

                    // Edit button
                    if (currentUser.level == 1 || 
                        (currentUser.level == 3 && (row.level == 'Reseller' || row.id == currentUser.id))) {
                        buttons += `<a href="${window.location.origin}/admin/user/${row.id}" 
                                   class="btn btn-primary btn-sm action-btn" title="Edit">
                                   <i class="bi bi-pencil"></i></a>`;
                    }

                    // Delete button - không cho xóa chính mình
                    if ((currentUser.level == 1 || 
                        (currentUser.level == 3 && row.level == 'Reseller')) && 
                        row.id != currentUser.id) {
                        buttons += `<a href="${window.location.origin}/admin/user_delete/${row.id}" 
                                   class="btn btn-danger btn-sm action-btn" 
                                   onclick="return confirm('Are you sure you want to delete this user?')" 
                                   title="Delete">
                                   <i class="bi bi-trash"></i></a>`;
                    }

                    return buttons;
                }
            }
        ],
        createdRow: function(row, data) {
            if (data.status == 0) {
                $(row).addClass('table-danger');
            }
        }
    });

    // Auto refresh every 30 seconds
    setInterval(function() {
        table.ajax.reload(null, false);
    }, 30000);
});
</script>
<?= $this->endSection() ?>