<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$year = date('Y');

// Fetch total donations per month for the year
$sql = "SELECT MONTH(tanggal) as bulan, SUM(sumbangan) as total FROM donasi WHERE YEAR(tanggal) = ? GROUP BY bulan ORDER BY bulan";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $year);
$stmt->execute();
$result = $stmt->get_result();

$donations = array_fill(1, 12, 0);
while ($row = $result->fetch_assoc()) {
    $donations[intval($row['bulan'])] = floatval($row['total']);
}
$stmt->close();
?>

<?php include 'header.php'; ?>

<h2>Grafik Batang Total Donasi per Bulan Tahun <?= $year ?></h2>

<canvas id="donationChart" width="800" height="400"></canvas>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('donationChart').getContext('2d');
const donationData = {
    labels: [
        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ],
    datasets: [{
        label: 'Total Donasi (Rp)',
        data: <?= json_encode(array_values($donations)) ?>,
        backgroundColor: 'rgba(54, 162, 235, 0.7)',
        borderColor: 'rgba(54, 162, 235, 1)',
        borderWidth: 1
    }]
};

const donationChart = new Chart(ctx, {
    type: 'bar',
    data: donationData,
    options: {
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return value.toLocaleString('id-ID', { style: 'currency', currency: 'IDR' });
                    }
                }
            }
        },
        responsive: true,
        plugins: {
            legend: {
                display: true,
                position: 'top',
            }
        }
    }
});
</script>

<?php include 'footer.php'; ?>
