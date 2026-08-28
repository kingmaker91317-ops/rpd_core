<?= $this->extend('Layout/Starter') ?>

<?= $this->section('content') ?>
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header pb-0">
                    <h6>Quản lý Hạn Hợp Đồng Seller</h6>
                </div>
                <div class="card-body px-0 pt-0 pb-2">
                    <div class="table-responsive">
                        <table class="table table-flush" id="datatable-basic">
                            <thead class="thead-light">
                                <tr>
                                    <th>Username</th>
                                    <th>Tên</th>
                                    <th>Mức</th>
                                    <th>Hạn Hợp Đồng</th>
                                    <th>Trạng Thái</th>
                                    <th>Hành Động</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sellers as $seller) : ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($seller['username']) ?></strong></td>
                                        <td><?= htmlspecialchars($seller['fullname'] ?? 'N/A') ?></td>
                                        <td>
                                            <?php 
                                                $levelNames = [1 => 'Admin', 2 => 'Reseller', 3 => 'Tenant'];
                                                echo htmlspecialchars($levelNames[$seller['level']] ?? 'Unknown');
                                            ?>
                                        </td>
                                        <td>
                                            <span class="seller-contract-date" id="date-<?= $seller['id_users'] ?>">
                                                <?= $seller['contract_expired_at'] ? date('d/m/Y', strtotime($seller['contract_expired_at'])) : '<span class="badge bg-info">Không giới hạn</span>' ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="seller-contract-status" id="status-<?= $seller['id_users'] ?>">
                                                <?php
                                                    if (!$seller['contract_expired_at']) {
                                                        echo '<span class="badge bg-success">Hoạt động</span>';
                                                    } else {
                                                        $expiredTime = strtotime($seller['contract_expired_at']);
                                                        if ($expiredTime < time()) {
                                                            echo '<span class="badge bg-danger">Đã hết hạn</span>';
                                                        } else {
                                                            $daysLeft = floor(($expiredTime - time()) / 86400);
                                                            echo '<span class="badge bg-warning">Còn ' . $daysLeft . ' ngày</span>';
                                                        }
                                                    }
                                                ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-primary edit-contract" 
                                                    data-seller-id="<?= $seller['id_users'] ?>"
                                                    data-seller-name="<?= htmlspecialchars($seller['username']) ?>"
                                                    data-contract-date="<?= $seller['contract_expired_at'] ? date('Y-m-d', strtotime($seller['contract_expired_at'])) : '' ?>">
                                                <i class="fas fa-edit"></i> Sửa
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Edit Contract -->
<div class="modal fade" id="editContractModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cập Nhật Hạn Hợp Đồng</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Seller: <strong id="sellerNameDisplay"></strong></label>
                </div>
                <div class="mb-3">
                    <label class="form-label">Hạn Hợp Đồng (để trống = không giới hạn)</label>
                    <input type="date" class="form-control" id="contractDate">
                    <small class="text-muted">Để trống nếu muốn seller này không giới hạn thời gian</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-primary" id="saveContractBtn">Lưu</button>
            </div>
        </div>
    </div>
</div>

<script>
let currentSellerId = null;

document.querySelectorAll('.edit-contract').forEach(btn => {
    btn.addEventListener('click', function() {
        currentSellerId = this.dataset.sellerId;
        document.getElementById('sellerNameDisplay').textContent = this.dataset.sellerName;
        document.getElementById('contractDate').value = this.dataset.contractDate;
        new bootstrap.Modal(document.getElementById('editContractModal')).show();
    });
});

document.getElementById('saveContractBtn').addEventListener('click', async function() {
    if (!currentSellerId) return;
    
    const contractDate = document.getElementById('contractDate').value;
    
    try {
        const response = await fetch(`<?= site_url('admin/seller-contracts/update') ?>/${currentSellerId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `contract_expired_at=${contractDate}`
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Update display
            const dateEl = document.getElementById(`date-${currentSellerId}`);
            const statusEl = document.getElementById(`status-${currentSellerId}`);
            
            if (contractDate) {
                const d = new Date(contractDate + 'T00:00:00');
                dateEl.innerHTML = d.toLocaleDateString('vi-VN');
                
                const daysLeft = Math.floor((d - new Date()) / 86400000);
                if (daysLeft < 0) {
                    statusEl.innerHTML = '<span class="badge bg-danger">Đã hết hạn</span>';
                } else {
                    statusEl.innerHTML = `<span class="badge bg-warning">Còn ${daysLeft} ngày</span>`;
                }
            } else {
                dateEl.innerHTML = '<span class="badge bg-info">Không giới hạn</span>';
                statusEl.innerHTML = '<span class="badge bg-success">Hoạt động</span>';
            }
            
            bootstrap.Modal.getInstance(document.getElementById('editContractModal')).hide();
            alert('Cập nhật thành công!');
        } else {
            alert('Lỗi: ' + result.message);
        }
    } catch (e) {
        alert('Lỗi kết nối: ' + e.message);
    }
});
</script>
<?= $this->endSection() ?>
