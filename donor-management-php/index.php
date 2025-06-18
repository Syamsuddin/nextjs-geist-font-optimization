<?php
session_start();
require_once 'config.php';
require_once 'role_check.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch statistics from database using PDO
// Total number of donors
$total_donors = 0;
$stmt = $conn->query("SELECT COUNT(*) as total FROM donatur");
if ($stmt) {
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_donors = $row['total'];
}

// Total donations amount
$total_donations = 0;
$stmt = $conn->query("SELECT SUM(sumbangan) as total FROM donasi");
if ($stmt) {
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_donations = $row['total'];
}

// Donations amount for current year
$current_year = date('Y');
$yearly_donations = 0;
$stmt = $conn->prepare("SELECT SUM(sumbangan) as total FROM donasi WHERE YEAR(tanggal) = ?");
$stmt->execute([$current_year]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if ($row) {
    $yearly_donations = $row['total'];
}

// Number of donation transactions
$total_transactions = 0;
$stmt = $conn->query("SELECT COUNT(*) as total FROM donasi");
if ($stmt) {
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_transactions = $row['total'];
}

// Total donations input by logged-in user today
$user_today_donations = 0;
$today = date('Y-m-d');
$stmt = $conn->prepare("SELECT SUM(sumbangan) as total FROM donasi WHERE id_user = ? AND tanggal = ?");
$stmt->execute([$user_id, $today]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if ($row) {
    $user_today_donations = $row['total'] ?? 0;
}

// Average donation amount
$average_donation = 0;
$stmt = $conn->query("SELECT AVG(sumbangan) as avg_donation FROM donasi");
if ($stmt) {
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $average_donation = $row['avg_donation'];
}

// Number of active donors in last year (donated in last 365 days)
$active_donors = 0;
$stmt = $conn->prepare("SELECT COUNT(DISTINCT id_donatur) as total FROM donasi WHERE tanggal >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)");
$stmt->execute();
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if ($row) {
    $active_donors = $row['total'];
}

// Recent donation activity summary (last 7 days)
$recent_donations = 0;
$stmt = $conn->prepare("SELECT SUM(sumbangan) as total FROM donasi WHERE tanggal >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
$stmt->execute();
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if ($row) {
    $recent_donations = $row['total'];
}

// Monthly recap amounts for Jan to Jun
$monthly_recap = array_fill(1, 6, 0);
$stmt = $conn->prepare("SELECT MONTH(tanggal) as bulan, SUM(sumbangan) as total FROM donasi WHERE YEAR(tanggal) = ? AND MONTH(tanggal) BETWEEN 1 AND 6 GROUP BY bulan");
$stmt->execute([$current_year]);
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $bulan = intval($row['bulan']);
    if ($bulan >= 1 && $bulan <= 6) {
        $monthly_recap[$bulan] = $row['total'];
    }
}

?>

<?php include 'header.php'; ?>

<h2>Dashboard Statistik Donasi</h2>

<div class="row row-cols-1 row-cols-md-3 g-4 mb-4">
  <div class="col">
    <div class="card text-bg-dark h-100">
      <div class="card-body">
        <h5 class="card-title">Jumlah Donatur</h5>
        <p class="card-text fs-4"><?= number_format($total_donors) ?></p>
      </div>
    </div>
  </div>
  <div class="col">
    <div class="card text-bg-dark h-100">
      <div class="card-body">
        <h5 class="card-title">Jumlah Total Sumbangan</h5>
        <p class="card-text fs-4">Rp <?= number_format($total_donations, 2, ',', '.') ?></p>
      </div>
    </div>
  </div>
  <div class="col">
    <div class="card text-bg-dark h-100">
      <div class="card-body">
        <h5 class="card-title">Jumlah Sumbangan Tahun <?= $current_year ?></h5>
        <p class="card-text fs-4">Rp <?= number_format($yearly_donations, 2, ',', '.') ?></p>
      </div>
    </div>
  </div>
  <div class="col">
    <div class="card text-bg-dark h-100">
      <div class="card-body">
        <h5 class="card-title">Jumlah Transaksi Donasi</h5>
        <p class="card-text fs-4"><?= number_format($total_transactions) ?></p>
      </div>
    </div>
  </div>
  <div class="col">
    <div class="card text-bg-dark h-100">
      <div class="card-body">
        <h5 class="card-title">Sumbangan Anda Hari Ini</h5>
        <p class="card-text fs-4">Rp <?= number_format($user_today_donations, 2, ',', '.') ?></p>
      </div>
    </div>
  </div>
  <div class="col">
    <div class="card text-bg-dark h-100">
      <div class="card-body">
        <h5 class="card-title">Rata-rata Sumbangan</h5>
        <p class="card-text fs-4">Rp <?= number_format($average_donation, 2, ',', '.') ?></p>
      </div>
    </div>
  </div>
  <div class="col">
    <div class="card text-bg-dark h-100">
      <div class="card-body">
        <h5 class="card-title">Donatur Aktif 1 Tahun Terakhir</h5>
        <p class="card-text fs-4"><?= number_format($active_donors) ?></p>
      </div>
    </div>
  </div>
  <div class="col">
    <div class="card text-bg-dark h-100">
      <div class="card-body">
        <h5 class="card-title">Sumbangan 7 Hari Terakhir</h5>
        <p class="card-text fs-4">Rp <?= number_format($recent_donations, 2, ',', '.') ?></p>
      </div>
    </div>
  </div>
</div>

<h3>Rekap Bulanan (Jan - Jun)</h3>
<div class="mb-4">
  <div class="progress" style="height: 30px;">
    <?php
    $max_value = max($monthly_recap) ?: 1;
    for ($m = 1; $m <= 6; $m++):
        $width = ($monthly_recap[$m] / $max_value) * 100;
    ?>
    <div class="progress-bar bg-info" role="progressbar" style="width: <?= $width ?>%" aria-valuenow="<?= $monthly_recap[$m] ?>" aria-valuemin="0" aria-valuemax="<?= $max_value ?>">
      <?= date('M', mktime(0, 0, 0, $m, 10)) ?>: Rp <?= number_format($monthly_recap[$m], 0, ',', '.') ?>
    </div>
    <?php endfor; ?>
  </div>
</div>

<h3>Menu Cepat</h3>
<div class="d-flex flex-wrap gap-3 mb-5">
  <a href="donatur_management.php" class="btn btn-outline-light">Lihat Daftar Donatur</a>
  <a href="donation_recap.php" class="btn btn-outline-light">Lihat Daftar Sumbangan</a>
  <a href="donation_recap_api.php" class="btn btn-outline-light">Lihat Total Sumbangan Harian</a>
  <a href="rekapbulanan.php" class="btn btn-outline-light">Lihat Total Sumbangan Perbulan</a>
  <a href="donation_input.php" class="btn btn-outline-light">Lihat Sumbangan Anda</a>
</div>

<footer class="text-center text-muted border-top pt-3">
  <p>Haul Abah Guru Sekumpul tinggal 190 hari lagi! -- Haul Abah Guru Kapuh tinggal 10 hari lagi!</p>
  <p>Copyright &copy; 2016-<?= date('Y') ?>, Yayasan Ibnu Atha'illah – All Right Reserved</p>
</footer>

<?php include 'footer.php'; ?>
