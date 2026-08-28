<?= $this->extend('Layout/Starter') ?>
<?= $this->section('content') ?>

<div class="justify-content-center">
  <div class="row mb-5">
      <div class="col-lg-6 mb-3 px-0 px-lg-3">
          <div class="mb-3">
              <?= $this->include('Layout/msgStatus') ?>
              <?php if (session()->getFlashdata('username')) : ?>
                  <div class="alert alert-success" role="alert">
                      Game: <?= esc(session()->getFlashdata('game')) ?> / <?= formatDuration(session()->getFlashdata('duration')) ?><br>
                      Key: <strong><?= esc(session()->getFlashdata('username')) ?></strong><br>
                      Available for <?= esc(session()->getFlashdata('max_devices')) ?> Devices<br>
                      <small>
                          <i>Duration will start when license login.</i>
                      </small>
                  </div>
              <?php endif; ?>
          </div>

          <div class="card mb-3">
              <div class="card-header">
                  <span class="card-title m-0">Create License</span>
              </div>
              <div class="card-body my-3">
                  <?= form_open() ?>
                  <div class="my-0">
                      <div class="row">
                          <div class="col-lg-6 mb-3">
                              <div class="input-group">
                                  <label for="game" class="input-group-text"><i class="bi bi-controller"></i></label>
                                  <select name="game" id="game" class="form-select" required>
                                      <option value="">Select Game</option>
                                      <?php foreach($games as $game): ?>
                                          <option value="<?= $game['package'] ?>"><?= $game['name'] ?></option>
                                      <?php endforeach; ?>
                                  </select>
                              </div>
                          </div>
                          <div class="col-lg-6 mb-3">
                              <div class="input-group">
                                  <label for="max_devices" class="input-group-text"><i class="bi bi-phone"></i></label>
                                  <input type="number" name="max_devices" id="max_devices" class="form-control" 
                                      value="<?= isset($settings['max_devices']) ? $settings['max_devices'] : 1 ?>" disabled>
                                  <div class="input-group-text">device</div>
                              </div>
                          </div>
                          <div class="col-lg-6 mb-3">
                              <div class="input-group">
                                  <label for="duration" class="input-group-text"><i class="bi bi-calendar-day"></i></label>
                                  <?= form_dropdown('duration', 
                                      [(isset($settings['key_duration']) ? $settings['key_duration'] : 1) => 
                                       formatDuration(isset($settings['key_duration']) ? $settings['key_duration'] : 1)], 
                                      (isset($settings['key_duration']) ? $settings['key_duration'] : 1), 
                                      'class="form-select" disabled') 
                                  ?>
                              </div>
                          </div>
                          <div class="col-lg-6 mb-3">
                              <div class="input-group">
                                  <label for="vip_key" class="input-group-text"><i class="bi bi-gem"></i></label>
                                  <?= form_dropdown('vip_key', ['1' => 'FREE'], '1', 'class="form-select" disabled') ?>
                              </div>
                          </div>
                      </div>
                      <div id="validationResult"></div>
                  </div>
                  <div class="mt-3 text-end">
                      <button type="submit" class="btn btn-sm btn-primary" id="btn_submit">
                          <i class="bi bi-box-arrow-in-right"></i> Generate
                      </button>
                  </div>
                  <?= form_close() ?>
              </div>
          </div>
      </div>
  </div>
</div>

<?= $this->endSection() ?>
