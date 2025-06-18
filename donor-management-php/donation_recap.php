<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$filter = 'daily';
$start_date = '';
$end_date = '';
$year = date('Y');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $filter = $_POST['filter'] ?? 'daily';
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
}

$whereClause = "";
$params = [];
$types = "";

switch ($filter) {
    case 'daily':
        $whereClause = "tanggal = CURDATE()";
        break;
    case 'weekly':
        $whereClause = "YEARWEEK(tanggal, 1) = YEARWEEK(CURDATE(), 1)";
        break;
    case 'monthly':
        $whereClause = "YEAR(tanggal) = ? AND MONTH(tanggal) = MONTH(CURDATE())";
        $params[] = $year;
        $types .= "i";
        break;
    case 'yearly':
        $whereClause = "YEAR(tanggal) = ?";
        $params[] = $year;
        $types .= "i";
        break;
    case 'custom':
        if ($start_date && $end_date) {
            $whereClause = "tanggal BETWEEN ? AND ?";
            $params[] = $start_date;
            $params[] = $end_date;
            $types .= "ss";
        }
        break;
    default:
        $whereClause = "tanggal = CURDATE()";
}

$sql = "SELECT tanggal, waktu, sumbangan FROM donasi";
if ($whereClause) {
    $sql .= " WHERE $whereClause";
}
$sql .= " ORDER BY tanggal DESC, waktu DESC";

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$donations = [];
while ($row = $result->fetch_assoc()) {
    $donations[] = $row;
}
$stmt->close();
?>

<?php include 'header.php'; ?>

<h2>Rekap Donasi</h2>

<form method="POST" action="donation_recap.php" class="row g-3 mb-4">
    <div class="col-auto">
        <label for="filter" class="form-label">Filter</label>
        <select id="filter" name="filter" class="form-select" onchange="toggleDateInputs()">
            <option value="daily" <?= $filter === 'daily' ? 'selected' : '' ?>>Harian</option>
            <option value="weekly" <?= $filter === 'weekly' ? 'selected' : '' ?>>Mingguan</option>
            <option value="monthly" <?= $filter === 'monthly' ? 'selected' : '' ?>>Bulanan</option>
            <option value="yearly" <?= $filter === 'yearly' ? 'selected' : '' ?>>Tahunan</option>
            <option value="custom" <?= $filter === 'custom' ? 'selected' : '' ?>>Custom</option>
        </select>
    </div>
    <div class="col-auto" id="start-date-container" style="display: none;">
        <label for="start_date" class="form-label">Tanggal Mulai</label>
        <input type="date" id="start_date" name="start_date" class="form-control" value="<?= htmlspecialchars($start_date) ?>" />
    </div>
    <div class="col-auto" id="end-date-container" style="display: none;">
        <label for="end_date" class="form-label">Tanggal Akhir</label>
        <input type="date" id="end_date" name="end_date" class="form-control" value="<?= htmlspecialchars($end_date) ?>" />
    </div>
    <div class="col-auto align-self-end">
        <button type="submit" class="btn btn-primary">Tampilkan</button>
    </div>
</form>

<table class="table table-striped table-bordered">
    <thead>
        <tr>
            <th>Tanggal</th>
            <th>Waktu</th>
            <th>Sumbangan (Rp)</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($donations as $donation): ?>
        <tr>
            <td><?= htmlspecialchars($donation['tanggal']) ?></td>
            <td><?= htmlspecialchars($donation['waktu']) ?></td>
            <td><?= number_format($donation['sumbangan'], 2, ',', '.') ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<script>
function toggleDateInputs() {
    const filter = document.getElementById('filter').value;
    const startDateContainer = document.getElementById('start-date-container');
    const endDateContainer = document.getElementById('end-date-container');
    if (filter === 'custom') {
        startDateContainer.style.display = 'block';
        endDateContainer.style.display = 'block';
    } else {
        startDateContainer.style.display = 'none';
        endDateContainer.style.display = 'none';
    }
}
window.onload = toggleDateInputs;
</script>

<?php include 'footer.php'; ?>
