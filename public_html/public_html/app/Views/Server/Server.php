<?= $this->extend('Layout/Starter') ?>
<?= $this->section('content') ?>

<div class="row justify-content-center">
    <div class="col-lg-6">
        <?= $this->include('Layout/msgStatus') ?>
        
        <div class="card shadow">
            <div class="card-header border-0">
                <span class="card-title m-0">Server Manager</span>
            </div>

            <div class="card-body">
                <?= form_open() ?>
                    <!-- Server Status -->
                    <div class="input-group mb-3">
                        <label class="input-group-text">
                            <i class="bi bi-hdd-network"></i>
                        </label>
                        <div class="form-control d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center">
                                <div class="form-check form-switch me-2">
                                    <input class="form-check-input" type="checkbox" 
                                           name="radios" id="serverSwitch" value="1"
                                           <?= $row->status === 'on' ? 'checked' : '' ?>>
                                </div>
                                <span id="statusText" class="text-muted">Offline Server</span>
                            </div>
                            <span class="badge <?= $row->status === 'on' ? 'bg-success' : 'bg-danger' ?>">
                                <?= $row->status === 'on' ? 'Online' : 'Offline' ?>
                            </span>
                        </div>
                    </div>

                    <!-- Information -->
                    <div class="input-group mb-3">
                        <label class="input-group-text">
                            <i class="bi bi-info-circle"></i>
                        </label>
                        <input type="text" name="modname" class="form-control"
                               value="<?= $row->modname ?>" placeholder="Input Information" required>
                    </div>

                    <!-- Offline Message -->
                    <div class="input-group mb-3">
                        <label class="input-group-text">
                            <i class="bi bi-chat-left-text"></i>
                        </label>
                        <textarea name="myInput" class="form-control" 
                                placeholder="Maintenance Message" rows="3" 
                                style="resize: none;"><?= $row->myinput ?></textarea>
                    </div>

                    <!-- Update Button -->
                    <div class="text-end">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-save me-1"></i> Save Changes
                        </button>
                    </div>
                <?= form_close() ?>
            </div>
        </div>
    </div>
</div>

<style>
.form-check.form-switch {
    padding-left: 0;
    min-width: 44px;
}

.form-check-input[type="checkbox"] {
    width: 44px !important;
    height: 22px;
    margin: 0;
    cursor: pointer;
    background-image: none;
    border-radius: 34px !important;
    background-color: #e9ecef !important;
    border: none !important;
    position: relative;
}

.form-check-input[type="checkbox"]:checked {
    background-color: #0d6efd !important;
}

.form-check-input[type="checkbox"]::before {
    content: "";
    position: absolute;
    height: 18px;
    width: 18px;
    left: 2px;
    top: 2px;
    background-color: white;
    border-radius: 50%;
    transform: translateX(0);
    transition: transform 0.25s ease;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.form-check-input[type="checkbox"]:checked::before {
    transform: translateX(22px);
}

.form-check-input:focus {
    box-shadow: none !important;
    border: none !important;
}

.input-group-text {
    background: transparent;
    border-right: 0;
}

.input-group .form-control {
    border-left: 0;
}

.input-group .form-control:focus {
    border-color: #dee2e6;
    box-shadow: none;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const serverSwitch = document.getElementById('serverSwitch');
    const statusText = document.getElementById('statusText');
    
    function updateStatus(checked) {
        statusText.textContent = checked ? 'Online Server' : 'Offline Server';
        serverSwitch.value = checked ? '1' : '2'; 
    }
    
    updateStatus(serverSwitch.checked);
    
    serverSwitch.addEventListener('change', function() {
        updateStatus(this.checked);
    });
});
</script>

<?= $this->endSection() ?>