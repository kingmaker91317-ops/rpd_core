<?= $this->extend('Layout/Starter') ?>
<?= $this->section('content') ?>

<div class="row justify-content-center">
   <div class="col-lg-6">
       <?= $this->include('Layout/msgStatus') ?>
       
       <div class="card shadow">
           <div class="card-header border-0">
               <span class="card-title m-0">Cài Đặt Menu</span>
           </div>
           <div class="card-body">
               <?= form_open_multipart('Server/moduleSettings') ?>
                   <!-- Module Name -->
                   <div class="mb-3">
                       <label class="form-label">Tên Menu</label>
                       <div class="input-group">
                           <span class="input-group-text">
                               <i class="bi bi-pencil-square"></i>
                           </span>
                           <input type="text" name="modname" class="form-control"
                                  value="<?= isset($moduleSettings['modname']) ? $moduleSettings['modname'] : '' ?>" 
                                  placeholder="Điền Tên Menu Của Bạn Vào Đây">
                       </div>
                   </div>

                   <!-- Current Icon Preview -->
                   <?php if(isset($moduleSettings['icon_path']) && !empty($moduleSettings['icon_path'])): ?>
                   <div class="mb-3 text-center">
                       <label class="form-label">Current Icon</label><br>
                       <img src="<?= base_url($moduleSettings['icon_path']) ?>" 
                            class="img-thumbnail" style="max-width: 100px;">
                       <button type="submit" name="delete_icon" value="1" 
                               class="btn btn-danger btn-sm mt-2 d-block mx-auto">
                           <i class="bi bi-trash"></i> Delete Icon
                       </button>
                   </div>
                   <?php endif; ?>

                   <!-- Icon Upload -->
                   <div class="mb-3">
                       <label class="form-label">Ảnh Menu</label>
                       <div class="input-group">
                           <span class="input-group-text">
                               <i class="bi bi-file-image"></i>
                           </span>
                           <input type="file" name="icon" class="form-control" 
                                  accept="image/png,image/jpeg">
                       </div>
                       <div class="form-text">
                           Recommended size: 50x50px. Max size: 1MB. Formats: PNG, JPG
                       </div>
                   </div>

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
.img-thumbnail {
   padding: 0.25rem;
   background-color: var(--bs-body-bg);
   border: 1px solid var(--bs-border-color);
}
</style>

<?= $this->endSection() ?>