<?php
/**
 * Logging utility functions for tracking user activities
 */

/**
 * Get the client's IP address
 */
function get_client_ip() {
    $ip = '';
    if (isset($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } elseif (isset($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;
}

/**
 * Log a user activity
 * 
 * @param int $user_id The ID of the user performing the action
 * @param string $activity_type The type of activity (e.g., 'login', 'add_donor', 'add_donation')
 * @param string $description Description of the activity
 * @return bool True if logging was successful, false otherwise
 */
function log_activity($user_id, $activity_type, $description) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("INSERT INTO user_logs (id_user, activity_type, description, ip_address) VALUES (?, ?, ?, ?)");
        $ip = get_client_ip();
        $stmt->execute([$user_id, $activity_type, $description, $ip]);
        return true;
    } catch (PDOException $e) {
        error_log("Error logging activity: " . $e->getMessage());
        return false;
    }
}

/**
 * Get user activity logs with pagination
 * 
 * @param int $page Current page number
 * @param int $per_page Number of items per page
 * @param array $filters Optional filters (user_id, activity_type, date_from, date_to)
 * @return array Array containing logs and pagination info
 */
function get_activity_logs($page = 1, $per_page = 20, $filters = []) {
    global $conn;
    
    try {
        $where_conditions = [];
        $params = [];
        
        if (!empty($filters['user_id'])) {
            $where_conditions[] = "l.id_user = ?";
            $params[] = $filters['user_id'];
        }
        
        if (!empty($filters['activity_type'])) {
            $where_conditions[] = "l.activity_type = ?";
            $params[] = $filters['activity_type'];
        }
        
        if (!empty($filters['date_from'])) {
            $where_conditions[] = "DATE(l.created_at) >= ?";
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $where_conditions[] = "DATE(l.created_at) <= ?";
            $params[] = $filters['date_to'];
        }
        
        $where_clause = empty($where_conditions) ? "" : "WHERE " . implode(" AND ", $where_conditions);
        
        // Get total count
        $count_sql = "SELECT COUNT(*) FROM user_logs l $where_clause";
        $stmt = $conn->prepare($count_sql);
        $stmt->execute($params);
        $total_records = $stmt->fetchColumn();
        
        // Calculate pagination
        $total_pages = ceil($total_records / $per_page);
        $offset = ($page - 1) * $per_page;
        
        // Get logs with user information
        $sql = "SELECT l.*, u.nama as user_name 
                FROM user_logs l 
                LEFT JOIN users u ON l.id_user = u.id 
                $where_clause 
                ORDER BY l.created_at DESC 
                LIMIT ? OFFSET ?";
        
        $stmt = $conn->prepare($sql);
        $params[] = $per_page;
        $params[] = $offset;
        $stmt->execute($params);
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'logs' => $logs,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $per_page,
                'total_records' => $total_records,
                'total_pages' => $total_pages
            ]
        ];
    } catch (PDOException $e) {
        error_log("Error retrieving logs: " . $e->getMessage());
        return [
            'logs' => [],
            'pagination' => [
                'current_page' => 1,
                'per_page' => $per_page,
                'total_records' => 0,
                'total_pages' => 0
            ]
        ];
    }
}

/**
 * Get activity types for filtering
 * 
 * @return array List of distinct activity types
 */
function get_activity_types() {
    global $conn;
    
    try {
        $stmt = $conn->query("SELECT DISTINCT activity_type FROM user_logs ORDER BY activity_type");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        error_log("Error retrieving activity types: " . $e->getMessage());
        return [];
    }
}
