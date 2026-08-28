<?= $this->extend('Layout/Starter') ?>
<?= $this->section('content') ?>
<div class="col-lg-12">
   <?= $this->include('Layout/msgStatus') ?>
</div>
<div class="card shadow">
   <div class="card-header d-flex align-items-center justify-content-between">
       <span class="card-title m-0">Key Free Settings</span>
       <div>
           <button type="button" class="btn btn-sm btn-primary" onclick="copyShareURL()">
               <i class="bi bi-share"></i> Share URL
           </button>
       </div>
   </div>
   <div class="card-body">
       <?= form_open() ?>
           <div class="row">
               <div class="col-12 mb-3">
                   <div class="input-group">
                       <label class="input-group-text"><i class="bi bi-link"></i></label>
                       <input type="text" class="form-control" 
                              value="<?= site_url('keys/free/new') ?>" 
                              readonly>
                       <button type="button" class="btn btn-primary" onclick="copyNewURL()">
                           <i class="bi bi-files"></i>
                       </button>
                   </div>
                   <small class="text-muted mt-1">This is the key generation URL. Please shorten it before sharing.</small>
               </div>

               <div class="col-md-6 mb-3">
                   <div class="input-group">
                       <label class="input-group-text"><i class="bi bi-key"></i></label>
                       <input type="number" name="max_keys_per_day" class="form-control" 
                           placeholder="Max Keys Per Day"
                           value="<?= old('max_keys_per_day', $settings['max_keys_per_day'] ?? 1) ?>">
                   </div>
                   <small class="text-muted mt-1">Adjust the number of times you want users to pass the link.</small>
               </div>
               <div class="col-md-6 mb-3">
                   <div class="input-group">
                       <label class="input-group-text"><i class="bi bi-calendar-day"></i></label>
                       <input type="number" name="key_duration" class="form-control" 
                           placeholder="Key Duration (days)"
                           value="<?= old('key_duration', $settings['key_duration'] ?? 1) ?>">
                   </div>
               </div>
               <div class="col-md-6 mb-3">
                   <div class="input-group">
                       <label class="input-group-text"><i class="bi bi-phone"></i></label>
                       <input type="number" name="max_devices" class="form-control" 
                           placeholder="Max Devices"
                           value="<?= old('max_devices', $settings['max_devices'] ?? 1) ?>">
                   </div>
               </div>
               <div class="col-md-6 mb-3">
                   <div class="input-group">
                       <label class="input-group-text">
                           <i class="bi bi-toggle-on"></i>
                       </label>
                       <div class="form-control d-flex align-items-center justify-content-between">
                           <div class="d-flex align-items-center">
                               <div class="form-check form-switch me-2">
                                   <input class="form-check-input" type="checkbox" 
                                          name="status" id="statusSwitch" value="1"
                                          <?= (old('status', $settings['status'] ?? 1) == 1) ? 'checked' : '' ?>>
                               </div>
                               <span id="statusText" class="text-muted">Disabled</span>
                           </div>
                           <span class="badge" id="statusBadge">Offline</span>
                       </div>
                   </div>
               </div>
               <div class="col-12 mb-3">
                   <div class="input-group">
                       <label class="input-group-text"><i class="bi bi-link-45deg"></i></label>
                       <textarea name="shortlinks" class="form-control" rows="5" 
                           placeholder="Enter your shortlinks (one per line)"><?= old('shortlinks', $settings['shortlinks'] ?? '') ?></textarea>
                   </div>
               </div>
               <div class="col-12 text-end">
                   <button type="submit" class="btn btn-primary">
                       <i class="bi bi-save"></i> Save Changes
                   </button>
               </div>
           </div>
       <?= form_close() ?>
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
function copyNewURL() {
   var url = '<?= site_url('keys/free/new') ?>';
   navigator.clipboard.writeText(url).then(function() {
       alert('URL copied to clipboard!');
   });
}

function copyShareURL() {
   var url = '<?= site_url('keys/free?admin=' . $user->username) ?>';
   navigator.clipboard.writeText(url).then(function() {
       alert('URL copied to clipboard!');
   });
}

document.addEventListener('DOMContentLoaded', function() {
   const statusSwitch = document.getElementById('statusSwitch');
   const statusText = document.getElementById('statusText');
   const statusBadge = document.getElementById('statusBadge');
   
   function updateStatus(checked) {
       statusText.textContent = checked ? 'Key Free' : 'Key Free';
       statusBadge.textContent = checked ? 'Enabled' : 'Disabled';
       statusBadge.className = checked ? 'badge bg-success' : 'badge bg-danger';
       statusSwitch.value = checked ? '1' : '0';
   }
   
   updateStatus(statusSwitch.checked);
   
   statusSwitch.addEventListener('change', function() {
       updateStatus(this.checked);
   });
});
</script>

<?= $this->endSection() ?>