<?php
// Helper functions
function formatFileSize($bytes): string
{
    $s = ['bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB'];
    for ($pos = 0; $bytes >= 1000; $pos++, $bytes /= 1024);
    $d = round($bytes * 10);
    return $pos ? (int)($d / 10) . '.' . $d % 10 . ' ' . $s[$pos] : $bytes . ' bytes';
}

function formatTimestamp($unix_timestamp): string
{
    $m = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    $d = new DateTime("@$unix_timestamp");
    return $m[$d->format('n') - 1] . ' ' . $d->format('j, Y h:i A');
}
?>

<?= $this->extend('Layout/Starter') ?>
<?= $this->section('content') ?>
<main>
    <div class="col-lg-12">
        <?= $this->include('Layout/msgStatus') ?>
        <!-- Alert for Ajax messages -->
        <div id="alertMessage" class="alert" style="display: none;"></div>
    </div>
    <div class="card mb-3">
        <div class="card-header">
            <div class="row align-items-center">
                <div class="col card-title m-0"><span>Lib Online</span></div>
                <div class="col text-end">
                    <button type="button" class="btn btn-default btn-sm ms-auto" data-bs-toggle="modal" data-bs-target="#uploadModal">
                        Open Upload
                    </button>
                </div>
            </div>
        </div>
        <div class="card-body">
            <!-- Upload Modal -->
            <div class="modal fade" id="uploadModal" tabindex="-1" aria-labelledby="uploadModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="uploadModalLabel">Upload Lib File</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <?= form_open_multipart('libOnline/upload', ['id' => 'uploadForm']); ?>
                            <div class="mb-3">
                                <label class="form-label">Select Game</label>
                                <select name="game" class="form-select" required>
                                    <option value="">Choose Game...</option>
                                    <?php foreach($games as $game): ?>
                                        <option value="<?= $game['package'] ?>">
                                            <?= $game['name'] ?> (<?= $game['package'] ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Select Type</label>
                                <select name="type" class="form-select" required>
                                    <option value="">Choose Type...</option>
                                    <option value="vip">VIP</option>
                                    <option value="free">Free</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Select Architecture</label>
                                <select name="architecture" class="form-select" required>
                                    <option value="">Choose Architecture...</option>
                                    <option value="armeabi-v7a">armeabi-v7a</option>
                                    <option value="arm64-v8a">arm64-v8a</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Version</label>
                                <input type="text" name="version" class="form-control" placeholder="e.g., v1.0.0" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Select Library File (.so)</label>
                                <input type="file" name="file" class="form-control" accept=".so" required>
                            </div>

                            <!-- Progress Bar -->
                            <div class="progress mb-3" style="display: none;">
                                <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" 
                                     role="progressbar" 
                                     style="width: 0%" 
                                     id="uploadProgress">0%</div>
                            </div>

                            <div id="uploadStatus" class="alert" style="display: none;"></div>

                            <button type="submit" class="btn btn-primary" id="uploadBtn">Upload</button>
                            <?= form_close(); ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Files Table -->
            <div class="table-responsive">
                <table id="table" class="table table-sm table-borderless table-striped">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Game</th>
                            <th>Type</th>
                            <th>Architecture</th>
                            <th>Version</th>
                            <th>Size</th>
                            <th>Modified</th>
                            <th>Permissions</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($files as $file) : ?>
                            <tr>
                                <td><span class="align-middle badge text-body"><?= $file['name'] ?></span></td>
                                <td><span class="align-middle badge text-body"><?= $file['game_name'] ?></span></td>
                                <td><span class="align-middle badge text-body"><?= $file['type'] ?></span></td>
                                <td><span class="align-middle badge text-body"><?= $file['architecture'] ?></span></td>
                                <td><span class="align-middle badge text-body"><?= $file['version'] ?></span></td>
                                <td><span class="align-middle badge text-body"><?= formatFileSize($file['size']) ?></span></td>
                                <td><span class="align-middle badge text-body"><?= formatTimestamp($file['mtime']) ?></span></td>
                                <td><span class="align-middle badge text-body"><?= $file['permissions'] ?></span></td>
                                <td>
                                    <div class="btn-group">
    <?php if (!$file['is_dir']) : ?>
        <a href="<?= base_url('libOnline/download/' . base64_encode($file['name'])) ?>" 
           class="btn btn-primary btn-sm">
            <i class="bi bi-download"></i>
        </a>
    <?php endif; ?>
    <?php if ($file['is_deleteable']) : ?>
        <a href="<?= base_url('libOnline/delete/' . base64_encode($file['name'])) ?>" 
           class="btn btn-danger btn-sm">
            <i class="bi bi-trash-fill"></i>
        </a>
    <?php endif; ?>
</div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>
<?= $this->endSection() ?>

<?= $this->section('js') ?>
<script>
$(document).ready(function() {
    // Initialize DataTable
    $('#table').DataTable();

    // Handle file upload
    $('#uploadForm').on('submit', function(e) {
        e.preventDefault();

        var form = $(this);
        var formData = new FormData(this);
        var progress = $('.progress');
        var progressBar = $('#uploadProgress');
        var uploadBtn = $('#uploadBtn');
        var uploadStatus = $('#uploadStatus');
        var modal = $('#uploadModal');

        // Show progress bar and disable submit button
        progress.show();
        uploadBtn.prop('disabled', true);
        uploadStatus.hide();

        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            xhr: function() {
                var xhr = new window.XMLHttpRequest();
                xhr.upload.addEventListener("progress", function(evt) {
                    if (evt.lengthComputable) {
                        var percentComplete = (evt.loaded / evt.total) * 100;
                        progressBar.width(percentComplete + '%');
                        progressBar.html(Math.round(percentComplete) + '%');
                    }
                }, false);
                return xhr;
            },
            success: function(response) {
                if(response.status === 'success') {
                    uploadStatus.removeClass('alert-danger').addClass('alert-success')
                        .html(response.message).show();
                    
                    // Reset form and hide modal after 2 seconds
                    setTimeout(function() {
                        form[0].reset();
                        progress.hide();
                        progressBar.width('0%').html('0%');
                        modal.modal('hide');
                        window.location.reload(); // Reload page to show new file
                    }, 2000);
                } else {
                    uploadStatus.removeClass('alert-success').addClass('alert-danger')
                        .html(response.message).show();
                }
            },
            error: function(xhr, status, error) {
                uploadStatus.removeClass('alert-success').addClass('alert-danger')
                    .html('Upload failed: ' + error).show();
            },
            complete: function() {
                uploadBtn.prop('disabled', false);
            }
        });
    });

    // Handle delete confirmation
    $('a[href*="libOnline/delete"]').on('click', function(e) {
        e.preventDefault();
        var deleteUrl = $(this).attr('href');
        var fileName = deleteUrl.split('/').pop();
        fileName = atob(fileName);
        fileName = fileName.split('/').pop();

        if(confirm(`Are you sure you want to delete "${fileName}"?`)) {
            window.location.href = deleteUrl;
        }
    });

    // Reset modal when closed
    $('#uploadModal').on('hidden.bs.modal', function () {
        var form = $('#uploadForm');
        form[0].reset();
        $('.progress').hide();
        $('#uploadProgress').width('0%').html('0%');
        $('#uploadStatus').hide();
        $('#uploadBtn').prop('disabled', false);
    });
});
</script>
<?= $this->endSection() ?>