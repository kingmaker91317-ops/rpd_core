<header>
    <nav class="navbar navbar-expand-md navbar-dark site-nav shadow-sm align-middle">
        <div class="container px-3">
            <a class="navbar-brand d-flex align-items-center" href="<?= site_url() ?>">
                <i class="bi bi-box px-2"></i>
                <?= esc(BASE_NAME) ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <?php if (!session()->has('userid')) : ?>
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <a class="nav-link text-white" href="<?= site_url('login') ?>">Login</a>
                        </li>
                    </ul>
                <?php endif; ?>
                
                <?php if (session()->has('userid')) : ?>
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                        <li class="nav-item">
                            <a class="nav-link text-white" href="<?= site_url('keys') ?>">
                                <i class="bi bi-key"></i> Manage Key
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="<?= site_url('keys/generate') ?>">
                                <i class="bi bi-plus-circle"></i> Generate Key
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="<?= site_url('Server/moduleSettings') ?>">
                                <i class="bi bi-pencil-square me-1"></i> Module Settings
                            </a>
                        </li>
                    </ul>
                    
                    <div class="float-right">
                        <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle text-white" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                    <i class="bi bi-person-circle pe-2"></i>
                                    <?= getName($user) ?>
                                    <span class="badge bg-light text-primary ms-1">
                                        <?= $user->level == 1 ? 'Admin' : ($user->level == 3 ? 'Tenant' : 'Reseller') ?>
                                    </span>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <div class="dropdown-item text-muted">
                                            <small>Balance: $<?= number_format($user->saldo, 2) ?></small>
                                        </div>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item" href="<?= site_url('settings') ?>">
                                            <i class="bi bi-gear"></i> Settings
                                        </a>
                                    </li>
                                    
                                    <?php if ($user->level == 1): // Admin Menu ?>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <div class="dropdown-header"><strong>Admin Panel</strong></div>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="<?= site_url('admin/manage-users') ?>">
                                                <i class="bi bi-people"></i> Manage Users
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="<?= site_url('admin/create-referral') ?>">
                                                <i class="bi bi-person-plus"></i> Create Referral
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="<?= site_url('admin/seller-contracts') ?>">
                                                <i class="bi bi-calendar-check"></i> Seller Contracts
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="<?= site_url('Server') ?>">
                                                <i class="bi bi-hdd-rack"></i> Server Manager
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="<?= site_url('games') ?>">
                                                <i class="bi bi-controller"></i> Games Manager
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="<?= site_url('libOnline') ?>">
                                                <i class="bi bi-collection"></i> Library Manager
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="<?= site_url('keys/admin/key-free-settings') ?>">
                                                <i class="bi bi-key"></i> Key Free Manager
                                            </a>
                                        </li>
                                    
                                    <?php elseif ($user->level == 3): // Tenant Menu ?>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <div class="dropdown-header"><strong>Tenant Panel</strong></div>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="<?= site_url('keys/admin/key-free-settings') ?>">
                                                <i class="bi bi-key"></i> Key Free Manager
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="<?= site_url('admin/manage-users') ?>">
                                                <i class="bi bi-people"></i> Manage Resellers
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="<?= site_url('admin/create-referral') ?>">
                                                <i class="bi bi-person-plus"></i> Create Referral
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item text-danger" href="<?= site_url('logout') ?>">
                                            <i class="bi bi-box-arrow-right"></i> Logout
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </nav>
</header>
