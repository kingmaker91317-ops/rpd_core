<?= $this->extend('Layout/Starter') ?>
<?= $this->section('content') ?>
<main>
    <div class="col-lg-12">
        <?= $this->include('Layout/msgStatus') ?>
    </div>
    <div class="card mb-3">
        <div class="card-header">
            <div class="row align-items-center">
                <div class="col card-title m-0"><span>Games Manager</span></div>
                <div class="col text-end">
                    <button type="button" class="btn btn-default btn-sm ms-auto" data-bs-toggle="modal" data-bs-target="#addGameModal">
                        Add Game
                    </button>
                    <!-- Add Game Modal -->
                    <div class="modal fade" id="addGameModal" tabindex="-1" aria-labelledby="addGameModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title text-dark" id="addGameModalLabel">Add New Game</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body text-start">
                                    <?= form_open('games/add', ['id' => 'addGameForm']); ?>
                                    <div class="mb-3">
                                        <label class="form-label text-start">Game Name</label>
                                        <input type="text" name="name" class="form-control text-start" 
                                               placeholder="Game Name" minlength="3" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label text-start">Package Name</label>
                                        <input type="text" name="package" class="form-control text-start" 
                                               placeholder="Package Name" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label text-start">Allowed for Levels</label>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="allowed_levels[]" value="1" id="addLevel1" checked>
                                            <label class="form-check-label" for="addLevel1">
                                                Level 1 - Admin
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="allowed_levels[]" value="2" id="addLevel2">
                                            <label class="form-check-label" for="addLevel2">
                                                Level 2 - Reseller
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="allowed_levels[]" value="3" id="addLevel3">
                                            <label class="form-check-label" for="addLevel3">
                                                Level 3 - Tenant
                                            </label>
                                        </div>
                                        <small class="form-text text-muted">If none selected, game is available for all levels</small>
                                    </div>
                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="require_password" value="1" id="addRequirePassword">
                                            <label class="form-check-label" for="addRequirePassword">
                                                Require Password for Keys
                                            </label>
                                        </div>
                                        <small class="form-text text-muted">If checked, users must provide password when creating keys for this game</small>
                                    </div>
                                    <button type="submit" class="btn btn-danger">Add Game</button>
                                    <?= form_close(); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="table" class="table table-sm table-borderless table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Package Name</th>
                            <th>Allowed Levels</th>
                            <th>Require Password</th>
                            <th>Status</th>
                            <th>Maintenance</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($games as $game) : ?>
                            <tr>
                                <td><span class="align-middle badge text-body"><?= $game['id'] ?></span></td>
                                <td><span class="align-middle badge text-body"><?= $game['name'] ?></span></td>
                                <td><span class="align-middle badge text-body"><?= $game['package'] ?></span></td>
                                <td>
                                    <?php 
                                        $allowedLevels = isset($game['allowed_levels']) && !empty($game['allowed_levels']) 
                                            ? explode(',', $game['allowed_levels']) 
                                            : ['1', '2', '3'];
                                        $levelNames = [
                                            '1' => 'Admin',
                                            '2' => 'Reseller',
                                            '3' => 'Tenant'
                                        ];
                                        $levelBadges = array_map(function($level) use ($levelNames) {
                                            return '<span class="badge bg-primary">' . ($levelNames[$level] ?? $level) . '</span>';
                                        }, $allowedLevels);
                                        echo implode(' ', $levelBadges);
                                    ?>
                                </td>
                                <td>
                                    <span class="align-middle badge <?= isset($game['require_password']) && $game['require_password'] == 1 ? 'bg-info' : 'bg-secondary' ?>">
                                        <?= isset($game['require_password']) && $game['require_password'] == 1 ? 'Yes' : 'No' ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="align-middle badge <?= $game['status'] == 'active' ? 'bg-success' : 'bg-warning' ?>">
                                        <?= ucfirst($game['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="align-middle badge <?= $game['maintenance'] == 1 ? 'bg-info' : 'bg-secondary' ?>">
                                        <?= $game['maintenance'] == 1 ? 'Maintenance' : 'Normal' ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="<?= base_url('games/assign/' . $game['id']) ?>" 
                                           class="btn btn-info btn-sm" 
                                           title="Assign to users">
                                            <i class="bi bi-people"></i>
                                        </a>
                                        
                                        <button type="button" class="btn btn-primary btn-sm" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editGameModal<?= $game['id'] ?>">
                                            <i class="bi bi-pencil-fill"></i>
                                        </button>
                                        
                                        <a href="<?= base_url('games/toggle/' . $game['id']) ?>" 
                                           class="btn <?= $game['status'] == 'active' ? 'btn-warning' : 'btn-success' ?> btn-sm"
                                           title="<?= $game['status'] == 'active' ? 'Pause game' : 'Activate game' ?>">
                                            <i class="bi <?= $game['status'] == 'active' ? 'bi-pause-fill' : 'bi-play-fill' ?>"></i>
                                        </a>

<button type="button" class="btn <?= $game['maintenance'] == 1 ? 'btn-info' : 'btn-secondary' ?> btn-sm"
        data-bs-toggle="modal" 
        data-bs-target="#maintenanceModal<?= $game['id'] ?>">
    <i class="bi bi-wrench"></i>
</button>

                                        <a href="<?= base_url('games/delete/' . $game['id']) ?>" 
                                           class="btn btn-danger btn-sm" 
                                           onclick="return confirm('Are you sure you want to delete this game?');">
                                            <i class="bi bi-trash-fill"></i>
                                        </a>
                                    </div>

                                    <!-- Edit Game Modal -->
<div class="modal fade" id="editGameModal<?= $game['id'] ?>" tabindex="-1" 
     aria-labelledby="editGameModalLabel<?= $game['id'] ?>" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-dark" id="editGameModalLabel<?= $game['id'] ?>">
                    Edit Game: <?= $game['name'] ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-start">
                <?= form_open('games/edit/' . $game['id'], ['class' => 'editGameForm']); ?>
                <div class="mb-3">
                    <label class="form-label text-start">Game Name</label>
                    <input type="text" name="name" class="form-control text-start" 
                           value="<?= $game['name'] ?>" minlength="3" required>
                </div>
                <div class="mb-3">
                    <label class="form-label text-start">Package Name</label>
                    <input type="text" name="package" class="form-control text-start" 
                           value="<?= $game['package'] ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label text-start">Version</label>
                    <input type="text" name="version" class="form-control text-start" 
                           value="<?= $game['version'] ?>" placeholder="1.0.0">
                </div>
                <div class="mb-3">
                    <label class="form-label text-start">Link Update</label>
                    <input type="url" name="link_update" class="form-control text-start" 
                           value="<?= $game['link_update'] ?>" placeholder="https://example.com/update">
                </div>
                <div class="mb-3">
                    <label class="form-label text-start">Allowed for Levels</label>
                    <?php 
                        $allowedLevels = isset($game['allowed_levels']) ? explode(',', $game['allowed_levels']) : [];
                    ?>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="allowed_levels[]" value="1" 
                               id="editLevel1_<?= $game['id'] ?>" 
                               <?= in_array('1', $allowedLevels) || empty($game['allowed_levels']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="editLevel1_<?= $game['id'] ?>">
                            Level 1 - Admin
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="allowed_levels[]" value="2" 
                               id="editLevel2_<?= $game['id'] ?>" 
                               <?= in_array('2', $allowedLevels) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="editLevel2_<?= $game['id'] ?>">
                            Level 2 - Reseller
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="allowed_levels[]" value="3" 
                               id="editLevel3_<?= $game['id'] ?>" 
                               <?= in_array('3', $allowedLevels) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="editLevel3_<?= $game['id'] ?>">
                            Level 3 - Tenant
                        </label>
                    </div>
                    <small class="form-text text-muted">If none selected, game is available for all levels</small>
                </div>
                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="require_password" value="1" 
                               id="editRequirePassword_<?= $game['id'] ?>"
                               <?= isset($game['require_password']) && $game['require_password'] == 1 ? 'checked' : '' ?>>
                        <label class="form-check-label" for="editRequirePassword_<?= $game['id'] ?>">
                            Require Password for Keys
                        </label>
                    </div>
                    <small class="form-text text-muted">If checked, users must provide password when creating keys for this game</small>
                </div>
                <button type="submit" class="btn btn-primary">Update Game</button>
                <?= form_close(); ?>
            </div>
        </div>
    </div>
</div>

                                    <!-- Maintenance Modal -->
                                    <div class="modal fade" id="maintenanceModal<?= $game['id'] ?>" tabindex="-1" 
                                         aria-labelledby="maintenanceModalLabel<?= $game['id'] ?>" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title text-dark" id="maintenanceModalLabel<?= $game['id'] ?>">
                                                        Maintenance Settings: <?= $game['name'] ?>
                                                    </h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body text-start">
                                                    <?= form_open('games/maintenance/' . $game['id']); ?>
                                                    <div class="mb-3">
                                                        <div class="form-check form-switch">
                                                            <input class="form-check-input" type="checkbox" name="maintenance" 
                                                                   value="1" id="maintenanceSwitch<?= $game['id'] ?>"
                                                                   <?= $game['maintenance'] == 1 ? 'checked' : '' ?>>
                                                            <label class="form-check-label" for="maintenanceSwitch<?= $game['id'] ?>">
                                                                Enable Maintenance Mode
                                                            </label>
                                                        </div>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Maintenance Message</label>
                                                        <textarea name="maintenance_msg" class="form-control" 
                                                                  rows="3"><?= $game['maintenance_msg'] ?></textarea>
                                                    </div>
                                                    <button type="submit" class="btn btn-primary">Save Settings</button>
                                                    <?= form_close(); ?>
                                                </div>
                                            </div>
                                        </div>
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
    $('#table').DataTable();

});
</script>
<?= $this->endSection() ?>