<?php
session_start();
require_once 'config.php';
require_once 'role_check.php';

require_operator_or_admin();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$action = $_GET['action'] ?? '';
$id = intval($_GET['id'] ?? 0);
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = $_POST['nama'] ?? '';
    $alamat = $_POST['alamat'] ?? '';
    $no_hp = $_POST['no_hp'] ?? '';

    if (!$nama) {
        $error = 'Nama harus diisi.';
    } else {
        if ($action === 'add') {
            $stmt = $conn->prepare("INSERT INTO donatur (nama, alamat, no_hp) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $nama, $alamat, $no_hp);
            if ($stmt->execute()) {
                $success = 'Donatur berhasil ditambahkan.';
            } else {
                $error = 'Gagal menambahkan donatur: ' . $conn->error;
            }
            $stmt->close();
        } elseif ($action === 'edit' && $id) {
            $stmt = $conn->prepare("UPDATE donatur SET nama = ?, alamat = ?, no_hp = ? WHERE id = ?");
            $stmt->bind_param("sssi", $nama, $alamat, $no_hp, $id);
            if ($stmt->execute()) {
                $success = 'Donatur berhasil diperbarui.';
            } else {
                $error = 'Gagal memperbarui donatur: ' . $conn->error;
            }
            $stmt->close();
        }
    }
}

if ($action === 'delete' && $id) {
    if (!is_admin()) {
        $error = 'Anda tidak memiliki izin untuk menghapus donatur.';
    } else {
        $stmt = $conn->prepare("DELETE FROM donatur WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $success = 'Donatur berhasil dihapus.';
        } else {
            $error = 'Gagal menghapus donatur: ' . $conn->error;
        }
        $stmt->close();
    }
}

$donors = [];
$result = $conn->query("SELECT id, nama, alamat, no_hp FROM donatur ORDER BY nama");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $donors[] = $row;
    }
}

$edit_donor = null;
if ($action === 'edit' && $id) {
    $stmt = $conn->prepare("SELECT id, nama, alamat, no_hp FROM donatur WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $edit_donor = $res->fetch_assoc();
    $stmt->close();
}
?>

<?php include 'header.php'; ?>

<h2>Manajemen Donatur</h2>

<?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<?php if ($action === 'add' || ($action === 'edit' && $edit_donor)): ?>
<form method="POST" action="donatur_management.php?action=<?= htmlspecialchars($action) ?><?= $action === 'edit' ? '&id=' . $edit_donor['id'] : '' ?>" class="mb-4 w-50">
    <div class="mb-3">
        <label for="nama" class="form-label">Nama</label>
        <input type="text" id="nama" name="nama" class="form-control" required value="<?= htmlspecialchars($edit_donor['nama'] ?? '') ?>" />
    </div>
    <div class="mb-3">
        <label for="alamat" class="form-label">Alamat</label>
        <textarea id="alamat" name="alamat" class="form-control"><?= htmlspecialchars($edit_donor['alamat'] ?? '') ?></textarea>
    </div>
    <div class="mb-3">
        <label for="no_hp" class="form-label">No HP</label>
        <input type="text" id="no_hp" name="no_hp" class="form-control" value="<?= htmlspecialchars($edit_donor['no_hp'] ?? '') ?>" />
    </div>
    <button type="submit" class="btn btn-primary"><?= $action === 'add' ? 'Tambah' : 'Perbarui' ?></button>
    <a href="donatur_management.php" class="btn btn-secondary">Batal</a>
</form>
<?php else: ?>
<a href="donatur_management.php?action=add" class="btn btn-success mb-3">Tambah Donatur</a>

<table class="table table-striped table-bordered">
    <thead>
        <tr>
            <th>Nama</th>
            <th>Alamat</th>
            <th>No HP</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($donors as $donor): ?>
        <tr>
            <td><?= htmlspecialchars($donor['nama']) ?></td>
            <td><?= htmlspecialchars($donor['alamat']) ?></td>
            <td><?= htmlspecialchars($donor['no_hp']) ?></td>
            <td>
                <a href="donatur_management.php?action=edit&id=<?= $donor['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                <a href="donatur_management.php?action=delete&id=<?= $donor['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin ingin menghapus donatur ini?')">Hapus</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

<?php include 'footer.php'; ?>
