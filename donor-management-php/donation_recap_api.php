<?php
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_GET['donor_id'])) {
    echo json_encode([]);
    exit;
}

$donor_id = intval($_GET['donor_id']);
$year = date('Y');

$sql = "SELECT MONTH(tanggal) as bulan, SUM(sumbangan) as total FROM donasi WHERE id_donatur = ? AND YEAR(tanggal) = ? GROUP BY bulan";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $donor_id, $year);
$stmt->execute();
$result = $stmt->get_result();

$recap = array_fill(1, 12, 0);
while ($row = $result->fetch_assoc()) {
    $recap[intval($row['bulan'])] = floatval($row['total']);
}
$stmt->close();

echo json_encode($recap);
?>
