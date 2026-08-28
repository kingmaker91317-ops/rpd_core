<?= $this->extend('Layout/Starter') ?>
<?= $this->section('content') ?>

<div class="row pb-5 justify-content-center">
    <div class="col-lg-10">
        <?= $this->include('Layout/msgStatus') ?>
        
        <div class="card">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">
                    <i class="bi bi-people"></i> Assign Users to Game: <strong><?= esc($game['name']) ?></strong>
                </h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <a href="<?= base_url('games') ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Games
                    </a>
                </div>

                <?= form_open("games/assign/{$game['id']}") ?>
                
                <div class="mb-3">
                    <label class="form-label">Select Users (Level 2 - Reseller, Level 3 - Tenant)</label>
                    <small class="text-muted d-block mb-2">Only selected users will be able to use this game</small>
                    
                    <div class="border rounded p-3" style="max-height: 400px; overflow-y: auto;">
                        <?php if (empty($all_users)) : ?>
                            <p class="text-muted">No users found (Level 2 or 3)</p>
                        <?php else : ?>
                            <div class="mb-2">
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="selectAll()">Select All</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="deselectAll()">Deselect All</button>
                            </div>
                            
                            <?php 
                            $levelNames = [1 => 'Admin', 2 => 'Reseller', 3 => 'Tenant'];
                            foreach ($all_users as $user) : 
                                $isChecked = in_array($user['id_users'], $assigned_user_ids);
                            ?>
                                <div class="form-check mb-2">
                                    <input class="form-check-input user-checkbox" 
                                           type="checkbox" 
                                           name="user_ids[]" 
                                           value="<?= $user['id_users'] ?>" 
                                           id="user_<?= $user['id_users'] ?>"
                                           <?= $isChecked ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="user_<?= $user['id_users'] ?>">
                                        <strong><?= esc($user['username']) ?></strong>
                                        <?php if ($user['fullname']) : ?>
                                            - <?= esc($user['fullname']) ?>
                                        <?php endif; ?>
                                        <span class="badge bg-info ms-2"><?= $levelNames[$user['level']] ?? 'Level ' . $user['level'] ?></span>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Save Assignments
                    </button>
                </div>
                
                <?= form_close() ?>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('js') ?>
<script>
function selectAll() {
    document.querySelectorAll('.user-checkbox').forEach(cb => cb.checked = true);
}

function deselectAll() {
    document.querySelectorAll('.user-checkbox').forEach(cb => cb.checked = false);
}
</script>
<?= $this->endSection() ?>

