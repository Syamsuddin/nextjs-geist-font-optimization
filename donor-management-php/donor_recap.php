<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$year = date('Y');

// Fetch all donors with total donations for current year
$sql = "SELECT d.id, d.nama, d.alamat, COALESCE(SUM(ds.sumbangan), 0) AS total_sumbangan
        FROM donatur d
        LEFT JOIN donasi ds ON d.id = ds.id_donatur AND YEAR(ds.tanggal) = ?
        GROUP BY d.id, d.nama, d.alamat
        ORDER BY d.nama";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $year);
$stmt->execute();
$result = $stmt->get_result();

$donors = [];
while ($row = $result->fetch_assoc()) {
    $donors[] = $row;
}
$stmt->close();
?>

<?php include 'header.php'; ?>

<h2>Rekap Donatur Tahun <?= $year ?></h2>

<table class="table table-striped table-bordered">
    <thead>
        <tr>
            <th>ID Donatur</th>
            <th>Nama</th>
            <th>Alamat</th>
            <th>Total Sumbangan (Rp)</th>
            <th>Detail Donasi</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($donors as $donor): ?>
        <tr>
            <td><?= htmlspecialchars($donor['id']) ?></td>
            <td><?= htmlspecialchars($donor['nama']) ?></td>
            <td><?= htmlspecialchars($donor['alamat']) ?></td>
            <td><?= number_format($donor['total_sumbangan'], 2, ',', '.') ?></td>
            <td><a href="donor_detail.php?id=<?= $donor['id'] ?>" class="btn btn-sm btn-primary">Lihat Detail</a></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php include 'footer.php'; ?>
