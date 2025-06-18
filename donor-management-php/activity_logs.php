<?php
session_start();
require_once 'config.php';
require_once 'role_check.php';
require_once 'includes/logging.php';

// Ensure user is admin
require_admin();

// Get filter parameters
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 20;

$filters = [
    'user_id' => isset($_GET['user_id']) ? intval($_GET['user_id']) : null,
    'activity_type' => isset($_GET['activity_type']) ? $_GET['activity_type'] : null,
    'date_from' => isset($_GET['date_from']) ? $_GET['date_from'] : null,
    'date_to' => isset($_GET['date_to']) ? $_GET['date_to'] : null
];

// Get logs with pagination
$result = get_activity_logs($page, $per_page, $filters);
$logs = $result['logs'];
$pagination = $result['pagination'];

// Get activity types for filter dropdown
$activity_types = get_activity_types();

// Get users for filter dropdown
$users = [];
try {
    $stmt = $conn->query("SELECT id, nama FROM users ORDER BY nama");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching users: " . $e->getMessage());
}

include 'header.php';
?>

<div class="container mt-4">
    <h2>Log Aktivitas Pengguna</h2>

    <!-- Filters -->
    <div class="card bg-dark text-white mb-4">
        <div class="card-body">
            <form method="GET" action="activity_logs.php" class="row g-3">
                <div class="col-md-3">
                    <label for="user_id" class="form-label">Pengguna</label>
                    <select name="user_id" id="user_id" class="form-select">
                        <option value="">Semua Pengguna</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?= $user['id'] ?>" <?= ($filters['user_id'] == $user['id'] ? 'selected' : '') ?>>
                                <?= htmlspecialchars($user['nama']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="activity_type" class="form-label">Jenis Aktivitas</label>
                    <select name="activity_type" id="activity_type" class="form-select">
                        <option value="">Semua Aktivitas</option>
                        <?php foreach ($activity_types as $type): ?>
                            <option value="<?= $type ?>" <?= ($filters['activity_type'] == $type ? 'selected' : '') ?>>
                                <?= htmlspecialchars($type) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="date_from" class="form-label">Dari Tanggal</label>
                    <input type="date" class="form-control" id="date_from" name="date_from" 
                           value="<?= $filters['date_from'] ?>">
                </div>
                <div class="col-md-2">
                    <label for="date_to" class="form-label">Sampai Tanggal</label>
                    <input type="date" class="form-control" id="date_to" name="date_to" 
                           value="<?= $filters['date_to'] ?>">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">Filter</button>
                    <a href="activity_logs.php" class="btn btn-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Logs Table -->
    <div class="table-responsive">
        <table class="table table-dark table-striped table-hover">
            <thead>
                <tr>
                    <th>Waktu</th>
                    <th>Pengguna</th>
                    <th>Aktivitas</th>
                    <th>Deskripsi</th>
                    <th>IP Address</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="5" class="text-center">Tidak ada log aktivitas yang ditemukan.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?= date('d/m/Y H:i:s', strtotime($log['created_at'])) ?></td>
                            <td><?= htmlspecialchars($log['user_name']) ?></td>
                            <td><?= htmlspecialchars($log['activity_type']) ?></td>
                            <td><?= htmlspecialchars($log['description']) ?></td>
                            <td><?= htmlspecialchars($log['ip_address']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($pagination['total_pages'] > 1): ?>
        <nav aria-label="Page navigation">
            <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= ($page - 1) ?>&user_id=<?= $filters['user_id'] ?>&activity_type=<?= $filters['activity_type'] ?>&date_from=<?= $filters['date_from'] ?>&date_to=<?= $filters['date_to'] ?>">
                            Previous
                        </a>
                    </li>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                    <li class="page-item <?= ($i == $page ? 'active' : '') ?>">
                        <a class="page-link" href="?page=<?= $i ?>&user_id=<?= $filters['user_id'] ?>&activity_type=<?= $filters['activity_type'] ?>&date_from=<?= $filters['date_from'] ?>&date_to=<?= $filters['date_to'] ?>">
                            <?= $i ?>
                        </a>
                    </li>
                <?php endfor; ?>

                <?php if ($page < $pagination['total_pages']): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= ($page + 1) ?>&user_id=<?= $filters['user_id'] ?>&activity_type=<?= $filters['activity_type'] ?>&date_from=<?= $filters['date_from'] ?>&date_to=<?= $filters['date_to'] ?>">
                            Next
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
