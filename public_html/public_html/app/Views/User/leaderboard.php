<?php
/**
 * Leaderboard - Top 5 Sellers
 * Displays top 5 sellers by number of keys created
 */
$medals = [
    1 => ['icon' => '🥇', 'color' => 'warning', 'label' => 'Gold'],
    2 => ['icon' => '🥈', 'color' => 'secondary', 'label' => 'Silver'],
    3 => ['icon' => '🥉', 'color' => 'danger', 'label' => 'Bronze'],
    4 => ['icon' => '🥉', 'color' => 'danger', 'label' => 'Bronze'],
    5 => ['icon' => '🥉', 'color' => 'danger', 'label' => 'Bronze'],
];
?>

<div class="card">
    <div class="card-header p-3 bg-primary text-white">
        <h6 class="mb-0"><i class="bi bi-trophy"></i> Top Sellers Leaderboard</h6>
    </div>
    <div class="card-body p-0">
        <?php if (!empty($topSellers)): ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th style="width: 50px;">Rank</th>
                            <th>Seller</th>
                            <th style="width: 120px;" class="text-end">Total Keys</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($topSellers as $rank => $seller): ?>
                            <?php $position = $rank + 1; ?>
                            <?php $medal = $medals[$position] ?? ['icon' => '#' . $position, 'color' => 'light', 'label' => 'Rank ' . $position]; ?>
                            <tr class="align-middle">
                                <td>
                                    <span class="badge bg-<?= $medal['color'] ?> fs-6">
                                        <?= $medal['icon'] ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-semibold"><?= htmlspecialchars($seller['username'] ?? 'N/A') ?></div>
                                </td>
                                <td class="text-end">
                                    <span class="badge bg-primary">
                                        <?= (int) ($seller['total_keys'] ?? 0) ?> keys
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="p-4 text-center text-muted">
                <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                <p class="mt-2 mb-0">No seller data available</p>
            </div>
        <?php endif; ?>
    </div>
</div>
