<?php
if (!function_exists('is_admin')) {
    function is_admin() {
        return isset($_SESSION['level']) && $_SESSION['level'] == 1;
    }
}

if (!function_exists('require_admin')) {
    function require_admin() {
        if (!isset($_SESSION['user_id']) || !is_admin()) {
            header('Location: login.php');
            exit;
        }
    }
}

if (!function_exists('is_operator_or_admin')) {
    function is_operator_or_admin() {
        return isset($_SESSION['level']) && in_array($_SESSION['level'], [1, 2]);
    }
}

if (!function_exists('require_operator_or_admin')) {
    function require_operator_or_admin() {
        if (!isset($_SESSION['user_id']) || !is_operator_or_admin()) {
            header('Location: login.php');
            exit;
        }
    }
}

// New permission-based functions
if (!function_exists('has_permission')) {
    function has_permission($permission_name) {
        global $conn;
        
        if (!isset($_SESSION['level'])) {
            return false;
        }

        try {
            $stmt = $conn->prepare("
                SELECT COUNT(*) as has_perm 
                FROM level_permissions lp 
                JOIN permissions p ON lp.permission_id = p.id 
                WHERE lp.level_id = ? AND p.name = ?
            ");
            $stmt->execute([$_SESSION['level'], $permission_name]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result['has_perm'] > 0;
        } catch (PDOException $e) {
            error_log("Error checking permission: " . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('require_permission')) {
    function require_permission($permission_name) {
        if (!isset($_SESSION['user_id']) || !has_permission($permission_name)) {
            header('Location: login.php');
            exit;
        }
    }
}

// Helper function to get all permissions for a user
if (!function_exists('get_user_permissions')) {
    function get_user_permissions($user_level) {
        global $conn;
        
        try {
            $stmt = $conn->prepare("
                SELECT p.name, p.description 
                FROM level_permissions lp 
                JOIN permissions p ON lp.permission_id = p.id 
                WHERE lp.level_id = ?
            ");
            $stmt->execute([$user_level]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting user permissions: " . $e->getMessage());
            return [];
        }
    }
}
