<?php
session_start();
require_once 'config.php';
require_once 'role_check.php';

require_admin();

if (!isset($_SESSION['user_id']) || !is_admin()) {
    header('Location: login.php');
    exit;
}

$action = $_GET['action'] ?? '';
$id = intval($_GET['id'] ?? 0);
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = $_POST['nama'] ?? '';
    $password = $_POST['password'] ?? '';
    $level = intval($_POST['level'] ?? 0);
    $keterangan = $_POST['keterangan'] ?? '';

    if (!$nama) {
        $error = 'Nama harus diisi.';
    } elseif ($action === 'add' && !$password) {
        $error = 'Password harus diisi untuk user baru.';
    } elseif (!$level) {
        $error = 'Level harus dipilih.';
    } else {
        if ($action === 'add') {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (nama, password, level, keterangan) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssis", $nama, $hashed_password, $level, $keterangan);
            if ($stmt->execute()) {
                $success = 'User berhasil ditambahkan.';
            } else {
                $error = 'Gagal menambahkan user: ' . $conn->error;
            }
            $stmt->close();
        } elseif ($action === 'edit' && $id) {
            if ($password) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET nama = ?, password = ?, level = ?, keterangan = ? WHERE id = ?");
                $stmt->bind_param("ssisi", $nama, $hashed_password, $level, $keterangan, $id);
            } else {
                $stmt = $conn->prepare("UPDATE users SET nama = ?, level = ?, keterangan = ? WHERE id = ?");
                $stmt->bind_param("sisi", $nama, $level, $keterangan, $id);
            }
            if ($stmt->execute()) {
                $success = 'User berhasil diperbarui.';
            } else {
                $error = 'Gagal memperbarui user: ' . $conn->error;
            }
            $stmt->close();
        }
    }
}

if ($action === 'delete' && $id) {
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $success = 'User berhasil dihapus.';
    } else {
        $error = 'Gagal menghapus user: ' . $conn->error;
    }
    $stmt->close();
}

$users = [];
$result = $conn->query("SELECT id, nama, keterangan FROM users ORDER BY nama");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}

$edit_user = null;
if ($action === 'edit' && $id) {
    $stmt = $conn->prepare("SELECT id, nama, keterangan FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $edit_user = $res->fetch_assoc();
    $stmt->close();
}
?>

<?php include 'header.php'; ?>

<h2>Manajemen User</h2>

<?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<?php if ($action === 'add' || ($action === 'edit' && $edit_user)): ?>
<form method="POST" action="user_management.php?action=<?= htmlspecialchars($action) ?><?= $action === 'edit' ? '&id=' . $edit_user['id'] : '' ?>" class="mb-4 w-50">
    <div class="mb-3">
        <label for="nama" class="form-label">Nama</label>
        <input type="text" id="nama" name="nama" class="form-control" required value="<?= htmlspecialchars($edit_user['nama'] ?? '') ?>" />
    </div>
    <div class="mb-3">
        <label for="password" class="form-label">Password <?= $action === 'edit' ? '(kosongkan jika tidak diubah)' : '' ?></label>
        <input type="password" id="password" name="password" class="form-control" <?= $action === 'add' ? 'required' : '' ?> />
    </div>
    <div class="mb-3">
        <label for="level" class="form-label">Level</label>
        <select id="level" name="level" class="form-select" required>
            <option value="">Pilih Level</option>
            <?php
            $levels = [];
            $level_result = $conn->query("SELECT id, nama_level FROM level ORDER BY id");
            if ($level_result) {
                while ($row = $level_result->fetch_assoc()) {
                    $levels[] = $row;
                }
            }
            foreach ($levels as $level) {
                $selected = ($edit_user && $edit_user['level'] == $level['id']) ? 'selected' : '';
                echo "<option value=\"" . htmlspecialchars($level['id']) . "\" $selected>" . htmlspecialchars($level['nama_level']) . "</option>";
            }
            ?>
        </select>
    </div>
    <button type="submit" class="btn btn-primary"><?= $action === 'add' ? 'Tambah' : 'Perbarui' ?></button>
    <a href="user_management.php" class="btn btn-secondary">Batal</a>
</form>
<?php else: ?>
<a href="user_management.php?action=add" class="btn btn-success mb-3">Tambah User</a>

<table class="table table-striped table-bordered">
    <thead>
        <tr>
            <th>Nama</th>
            <th>Keterangan</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($users as $user): ?>
        <tr>
            <td><?= htmlspecialchars($user['nama']) ?></td>
            <td><?= htmlspecialchars($user['keterangan']) ?></td>
            <td>
                <a href="user_management.php?action=edit&id=<?= $user['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                <a href="user_management.php?action=delete&id=<?= $user['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin ingin menghapus user ini?')">Hapus</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

<?php include 'footer.php'; ?>
