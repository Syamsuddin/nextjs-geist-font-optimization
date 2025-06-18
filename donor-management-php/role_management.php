<?php
session_start();
require_once 'config.php';
require_once 'role_check.php';
require_once 'includes/logging.php';

require_admin();

if (!isset($_SESSION['user_id']) || !is_admin()) {
    header('Location: login.php');
    exit;
}

$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_permissions'])) {
    try {
        // Start transaction
        $conn->beginTransaction();

        $level_id = intval($_POST['level_id']);
        
        // Delete existing permissions for this level
        $stmt = $conn->prepare("DELETE FROM level_permissions WHERE level_id = ?");
        $stmt->execute([$level_id]);

        // Insert new permissions
        if (isset($_POST['permissions']) && is_array($_POST['permissions'])) {
            $stmt = $conn->prepare("INSERT INTO level_permissions (level_id, permission_id) VALUES (?, ?)");
            foreach ($_POST['permissions'] as $permission_id) {
                $stmt->execute([$level_id, $permission_id]);
            }
        }

        // Get level and permission details for logging
        $stmt = $conn->prepare("SELECT nama_level FROM level WHERE id = ?");
        $stmt->execute([$level_id]);
        $level = $stmt->fetch(PDO::FETCH_ASSOC);

        // Log the change
        $log_desc = sprintf(
            "Permissions updated for role: %s",
            $level['nama_level']
        );
        log_activity($_SESSION['user_id'], 'update_permissions', $log_desc);

        $conn->commit();
        $success = "Permissions berhasil diperbarui.";
    } catch (PDOException $e) {
        $conn->rollBack();
        error_log("Error updating permissions: " . $e->getMessage());
        $error = "Gagal memperbarui permissions: " . $e->getMessage();
    }
}

// Fetch all roles
try {
    $stmt = $conn->query("SELECT * FROM level ORDER BY id");
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching roles: " . $e->getMessage());
    $roles = [];
}

// Fetch all permissions
try {
    $stmt = $conn->query("SELECT * FROM permissions ORDER BY name");
    $permissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching permissions: " . $e->getMessage());
    $permissions = [];
}

// Fetch existing role permissions
$role_permissions = [];
try {
    $stmt = $conn->query("SELECT level_id, permission_id FROM level_permissions");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $role_permissions[$row['level_id']][] = $row['permission_id'];
    }
} catch (PDOException $e) {
    error_log("Error fetching role permissions: " . $e->getMessage());
}

include 'header.php';
?>

<div class="container">
    <h2 class="mb-4">Manajemen Role & Permissions</h2>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="card bg-dark text-white">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-dark table-bordered">
                    <thead>
                        <tr>
                            <th>Permissions</th>
                            <?php foreach ($roles as $role): ?>
                                <th class="text-center"><?= htmlspecialchars($role['nama_level']) ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($permissions as $permission): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($permission['name']) ?></strong><br>
                                    <small class="text-muted"><?= htmlspecialchars($permission['description']) ?></small>
                                </td>
                                <?php foreach ($roles as $role): ?>
                                    <td class="text-center">
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="level_id" value="<?= $role['id'] ?>">
                                            <div class="form-check d-flex justify-content-center">
                                                <input type="checkbox" 
                                                       class="form-check-input" 
                                                       name="permissions[]" 
                                                       value="<?= $permission['id'] ?>"
                                                       <?= in_array($permission['id'], $role_permissions[$role['id']] ?? []) ? 'checked' : '' ?>
                                                       <?= $role['id'] == 1 ? 'disabled' : '' ?>>
                                            </div>
                                        </form>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php foreach ($roles as $role): ?>
                <?php if ($role['id'] != 1): // Skip superadmin as it has all permissions by default ?>
                    <form method="POST" class="mt-3">
                        <input type="hidden" name="level_id" value="<?= $role['id'] ?>">
                        <button type="submit" name="save_permissions" class="btn btn-primary">
                            Save Permissions for <?= htmlspecialchars($role['nama_level']) ?>
                        </button>
                    </form>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
