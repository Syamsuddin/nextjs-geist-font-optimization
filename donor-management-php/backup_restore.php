<?php
session_start();
require_once 'config.php';
require_once 'role_check.php';

require_admin();

if (!isset($_SESSION['user_id']) || !is_admin()) {
    header('Location: login.php');
    exit;
}

$backup_file = 'backup_' . date('Ymd_His') . '.sql';
$message = '';

if (isset($_POST['backup'])) {
    $command = "mysqldump -u root " . escapeshellarg('donor_management') . " > " . escapeshellarg($backup_file);
    exec($command, $output, $return_var);
    if ($return_var === 0) {
        $message = "Backup berhasil dibuat: <a href=\"$backup_file\">$backup_file</a>";
    } else {
        $message = "Backup gagal.";
    }
}

if (isset($_POST['restore']) && isset($_FILES['restore_file'])) {
    $tmp_name = $_FILES['restore_file']['tmp_name'];
    if (is_uploaded_file($tmp_name)) {
        $command = "mysql -u root " . escapeshellarg('donor_management') . " < " . escapeshellarg($tmp_name);
        exec($command, $output, $return_var);
        if ($return_var === 0) {
            $message = "Restore berhasil.";
        } else {
            $message = "Restore gagal.";
        }
    } else {
        $message = "File restore tidak valid.";
    }
}
?>

<?php include 'header.php'; ?>

<h2>Backup & Restore Database</h2>

<?php if ($message): ?>
    <div class="alert alert-info"><?= $message ?></div>
<?php endif; ?>

<form method="POST" action="backup_restore.php" enctype="multipart/form-data" class="mb-4">
    <button type="submit" name="backup" class="btn btn-success mb-3">Backup Database</button>
</form>

<form method="POST" action="backup_restore.php" enctype="multipart/form-data">
    <div class="mb-3">
        <label for="restore_file" class="form-label">Pilih File Restore (.sql)</label>
        <input type="file" id="restore_file" name="restore_file" class="form-control" accept=".sql" required />
    </div>
    <button type="submit" name="restore" class="btn btn-danger">Restore Database</button>
</form>

<?php include 'footer.php'; ?>
