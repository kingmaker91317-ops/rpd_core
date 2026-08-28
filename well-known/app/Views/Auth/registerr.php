<?php

include('conn.php');
include('mail.php');

// For Credits
$sql = "SELECT * FROM credit where id=1";
$result = mysqli_query($conn, $sql);
$credit = mysqli_fetch_assoc($result);

?>

<?= $this->extend('Layout/Starter') ?>
<?= $this->section('content') ?>

<!-- Bootstrap Icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

<div class="row justify-content-center pt-5">
    <div class="col-lg-4">
        <?= $this->include('Layout/msgStatus') ?>
        <div class="card shadow-sm mb-5 border-success">
            <div class="card-header h5 p-3 text-white bg-success">
                REGISTER
            </div>
            <div class="card-body bg-white">
                <?= form_open() ?>

                <!-- Hidden Email Field -->
                <input type="hidden" name="email" value="tgkarthi9751@gmail.com">

                <!-- Username -->
                <div class="form-group mb-3">
                    <label for="username" class="text-dark">Username</label>
                    <div class="input-group mt-2">
                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                        <input type="text" class="form-control" name="username" id="username" placeholder="Your username" minlength="4" maxlength="24" value="<?= old('username') ?>" required>
                    </div>
                    <?php if ($validation->hasError('username')) : ?>
                        <small id="help-username" class="form-text text-danger"><?= $validation->getError('username') ?></small>
                    <?php endif; ?>
                </div>

                <!-- Password -->
                <div class="form-group mb-3">
                    <label for="password" class="text-dark">Password</label>
                    <div class="input-group mt-2">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" class="form-control fa fa-fw fa-eye field_icon toggle-password" name="password" id="password" aria-describedby="help-password" placeholder="Enter password" minlength="6" maxlength="24" required>
                    </div>
                    <?php if ($validation->hasError('password')) : ?>
                        <small id="help-password" class="form-text text-danger"><?= $validation->getError('password') ?></small>
                    <?php endif; ?>
                </div>

                <!-- Confirm Password -->
                <div class="form-group mb-3">
                    <label for="password2" class="text-dark">Confirm Password</label>
                    <div class="input-group mt-2">
                        <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                        <input type="password" name="password2" id="password2" class="form-control fa fa-fw fa-eye field_icon toggle-password2" placeholder="Confirm password" minlength="6" maxlength="24" required>
                    </div>
                    <?php if ($validation->hasError('password2')) : ?>
                        <small id="help-password2" class="form-text text-danger"><?= $validation->getError('password2') ?></small>
                    <?php endif; ?>
                </div>

                <!-- Credit Token Code -->
                <div class="form-group mb-3">
                    <label for="referral" class="text-dark">Credit Token Code</label>
                    <div class="input-group mt-2">
                        <span class="input-group-text"><i class="bi bi-card-list"></i></span>
                        <input type="text" name="referral" id="referral" class="form-control" placeholder="Your referral code" value="<?= old('referral') ?>" maxlength="25" required>
                    </div>
                    <?php if ($validation->hasError('referral')) : ?>
                        <small id="help-referral" class="form-text text-danger"><?= $validation->getError('referral') ?></small>
                    <?php endif; ?>
                </div>

                <!-- Submit -->
                <div class="form-group mb-2 text-white">
                    <button type="submit" class="btn btn-outline-secondary"><i class="bi bi-box-arrow-in-right"></i> Register</button>
                </div>

                <?= form_close() ?>
            </div>
        </div>

        <p class="text-center text-muted after-card">
            <small class="px-auto p-2 rounded">
                Already have an account?
                <a href="<?= site_url('login') ?>" class="text-danger">Login here</a>
            </small>
        </p>
    </div>
</div>

<?= $this->endSection() ?>