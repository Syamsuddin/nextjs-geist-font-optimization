<?php
session_start();
require_once 'config.php';
require_once 'role_check.php';

require_operator_or_admin();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch donors for selection
$donors = [];
$sql = "SELECT id, nama, alamat FROM donatur ORDER BY nama";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $donors[] = $row;
    }
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_donatur = intval($_POST['id_donatur']);
    $tanggal = $_POST['tanggal'];
    $waktu = $_POST['waktu'];
    $sumbangan = str_replace(['.', ','], ['', '.'], $_POST['sumbangan']); // Convert Rupiah format to decimal
    $sumbangan = floatval($sumbangan);

    if ($id_donatur && $tanggal && $waktu && $sumbangan > 0) {
        $stmt = $conn->prepare("INSERT INTO donasi (id_donatur, tanggal, waktu, sumbangan, id_user) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issdi", $id_donatur, $tanggal, $waktu, $sumbangan, $user_id);
        if ($stmt->execute()) {
            $success = "Donasi berhasil disimpan.";
        } else {
            $error = "Gagal menyimpan donasi: " . $conn->error;
        }
        $stmt->close();
    } else {
        $error = "Semua field harus diisi dengan benar.";
    }
}

// Function to get donation recap per month for current year for a donor
function getDonationRecap($conn, $donor_id) {
    $year = date('Y');
    $recap = array_fill(1, 12, 0);
    $sql = "SELECT MONTH(tanggal) as bulan, SUM(sumbangan) as total FROM donasi WHERE id_donatur = ? AND YEAR(tanggal) = ? GROUP BY bulan";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $donor_id, $year);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $recap[intval($row['bulan'])] = floatval($row['total']);
    }
    $stmt->close();
    return $recap;
}
?>

<?php include 'header.php'; ?>

<h2>Input Donasi</h2>

<?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="POST" action="donation_input.php" class="w-75">
    <div class="mb-3">
        <label for="id_donatur" class="form-label">ID Donatur</label>
        <select id="id_donatur" name="id_donatur" class="form-select" required onchange="fetchDonorInfo()">
            <option value="">Pilih Donatur</option>
            <?php foreach ($donors as $donor): ?>
                <option value="<?= $donor['id'] ?>"><?= htmlspecialchars($donor['id']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div id="donor-info" class="mb-3" style="display:none;">
        <p><strong>Nama:</strong> <span id="donor-nama"></span></p>
        <p><strong>Alamat:</strong> <span id="donor-alamat"></span></p>
        <p><strong>Rekap Sumbangan Tahun <?= date('Y') ?>:</strong></p>
        <ul id="donation-recap"></ul>
    </div>
    <div class="mb-3">
        <label for="tanggal" class="form-label">Tanggal</label>
        <input type="date" id="tanggal" name="tanggal" class="form-control" required value="<?= date('Y-m-d') ?>" />
    </div>
    <div class="mb-3">
        <label for="waktu" class="form-label">Waktu</label>
        <input type="time" id="waktu" name="waktu" class="form-control" required value="<?= date('H:i') ?>" />
    </div>
    <div class="mb-3">
        <label for="sumbangan" class="form-label">Jumlah Sumbangan (Rp)</label>
        <input type="text" id="sumbangan" name="sumbangan" class="form-control" required placeholder="0,00" oninput="formatRupiah(this)" />
    </div>
    <button type="submit" class="btn btn-primary">Simpan</button>
    <button type="reset" class="btn btn-secondary">Reset</button>
</form>

<script>
const donors = <?= json_encode($donors) ?>;

function fetchDonorInfo() {
    const select = document.getElementById('id_donatur');
    const donorId = select.value;
    const donorInfoDiv = document.getElementById('donor-info');
    const donorNamaSpan = document.getElementById('donor-nama');
    const donorAlamatSpan = document.getElementById('donor-alamat');
    const donationRecapUl = document.getElementById('donation-recap');

    if (!donorId) {
        donorInfoDiv.style.display = 'none';
        donorNamaSpan.textContent = '';
        donorAlamatSpan.textContent = '';
        donationRecapUl.innerHTML = '';
        return;
    }

    const donor = donors.find(d => d.id == donorId);
    if (donor) {
        donorNamaSpan.textContent = donor.nama;
        donorAlamatSpan.textContent = donor.alamat;
        donorInfoDiv.style.display = 'block';

        // Fetch donation recap via AJAX
        fetch('donation_recap_api.php?donor_id=' + donorId)
            .then(response => response.json())
            .then(data => {
                donationRecapUl.innerHTML = '';
                for (let month = 1; month <= 12; month++) {
                    const li = document.createElement('li');
                    li.textContent = new Date(0, month - 1).toLocaleString('id-ID', { month: 'long' }) + ': Rp ' + formatRupiahString(data[month] || 0);
                    donationRecapUl.appendChild(li);
                }
            });
    }
}

function formatRupiah(input) {
    let value = input.value.replace(/\D/g, '');
    value = new Intl.NumberFormat('id-ID').format(value);
    input.value = value;
}

function formatRupiahString(number) {
    return number.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}
</script>

<?php include 'footer.php'; ?>
