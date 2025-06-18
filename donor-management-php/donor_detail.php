<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if (!isset($_GET['id'])) {
    header('Location: donor_recap.php');
    exit;
}

$donor_id = intval($_GET['id']);

// Fetch donor info
$sql = "SELECT * FROM donatur WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $donor_id);
$stmt->execute();
$result = $stmt->get_result();
$donor = $result->fetch_assoc();
$stmt->close();

if (!$donor) {
    header('Location: donor_recap.php');
    exit;
}

// Fetch donations for donor
$sql = "SELECT tanggal, waktu, sumbangan FROM donasi WHERE id_donatur = ? ORDER BY tanggal DESC, waktu DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $donor_id);
$stmt->execute();
$result = $stmt->get_result();

$donations = [];
while ($row = $result->fetch_assoc()) {
    $donations[] = $row;
}
$stmt->close();
?>

<?php include 'header.php'; ?>

<h2>Detail Donasi Donatur: <?= htmlspecialchars($donor['nama']) ?></h2>
<p><strong>Alamat:</strong> <?= htmlspecialchars($donor['alamat']) ?></p>
<p><strong>No HP:</strong> <?= htmlspecialchars($donor['no_hp']) ?></p>

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

<a href="donor_recap.php" class="btn btn-secondary">Kembali</a>

<?php include 'footer.php'; ?>
