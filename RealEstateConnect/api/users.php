<?php
require_once '../inc/db.php';
require_once '../inc/functions.php';
require_once '../inc/auth.php';

// Start session
startSession();

// Set header to return JSON
header('Content-Type: application/json');

// Handle request based on action
$action = $_GET['action'] ?? ($_POST['action'] ?? '');

switch ($action) {
    case 'get_stats':
        getUserStats();
        break;
    case 'update_status':
        updateUserStatus();
        break;
    case 'delete_user':
        deleteUserAccount();
        break;
    case 'update_profile':
        updateUserProfile();
        break;
    case 'change_password':
        changeUserPassword();
        break;
    default:
        echo json_encode([
            'success' => false,
            'message' => 'Invalid action'
        ]);
        break;
}

/**
 * Get user statistics for admin dashboard
 */
function getUserStats() {
    // Check if user is logged in and is an admin
    if (!isLoggedIn() || $_SESSION['user_role'] !== 'admin') {
        echo json_encode([
            'success' => false,
            'message' => 'You do not have permission to access this data'
        ]);
        return;
    }
    
    // Get user counts by role
    $sql = "SELECT role, COUNT(*) as count FROM users GROUP BY role ORDER BY count DESC";
    $stats = fetchAll($sql);
    
    $labels = [];
    $values = [];
    
    foreach ($stats as $stat) {
        $labels[] = ucfirst($stat['role']); // Capitalize first letter
        $values[] = (int)$stat['count'];
    }
    
    echo json_encode([
        'success' => true,
        'labels' => $labels,
        'values' => $values
    ]);
}

/**
 * Update user status (admin only)
 */
function updateUserStatus() {
    // Check if user is logged in and is an admin
    if (!isLoggedIn() || $_SESSION['user_role'] !== 'admin') {
        echo json_encode([
            'success' => false,
            'message' => 'You do not have permission to perform this action'
        ]);
        return;
    }
    
    $userId = (int)($_POST['user_id'] ?? 0);
    $status = sanitizeInput($_POST['status'] ?? '');
    
    if ($userId <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid user ID'
        ]);
        return;
    }
    
    if (!in_array($status, ['active', 'inactive', 'banned'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid status'
        ]);
        return;
    }
    
    // Don't allow changing own status
    if ($userId === (int)$_SESSION['user_id']) {
        echo json_encode([
            'success' => false,
            'message' => 'You cannot change your own status'
        ]);
        return;
    }
    
    // Update user status
    $result = changeUserStatus($userId, $status);
    
    echo json_encode($result);
}

/**
 * Delete user account (admin only)
 */
function deleteUserAccount() {
    // Check if user is logged in and is an admin
    if (!isLoggedIn() || $_SESSION['user_role'] !== 'admin') {
        echo json_encode([
            'success' => false,
            'message' => 'You do not have permission to perform this action'
        ]);
        return;
    }
    
    $userId = (int)($_POST['user_id'] ?? 0);
    
    if ($userId <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid user ID'
        ]);
        return;
    }
    
    // Don't allow deleting own account
    if ($userId === (int)$_SESSION['user_id']) {
        echo json_encode([
            'success' => false,
            'message' => 'You cannot delete your own account'
        ]);
        return;
    }
    
    // Delete user account
    $result = deleteUser($userId);
    
    echo json_encode($result);
}

/**
 * Update user profile
 */
function updateUserProfile() {
    // Check if user is logged in
    if (!isLoggedIn()) {
        echo json_encode([
            'success' => false,
            'message' => 'You must be logged in to update your profile'
        ]);
        return;
    }
    
    $userId = $_SESSION['user_id'];
    $fullName = sanitizeInput($_POST['full_name'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $address = sanitizeInput($_POST['address'] ?? '');
    $city = sanitizeInput($_POST['city'] ?? '');
    $state = sanitizeInput($_POST['state'] ?? '');
    $zipCode = sanitizeInput($_POST['zip_code'] ?? '');
    $companyName = sanitizeInput($_POST['company_name'] ?? '');
    $bio = sanitizeInput($_POST['bio'] ?? '');
    
    // Validate required fields
    if (empty($fullName)) {
        echo json_encode([
            'success' => false,
            'message' => 'Full name is required'
        ]);
        return;
    }
    
    // Build query based on user role and available fields
    $sql = "UPDATE users SET full_name = ?, phone = ?, updated_at = NOW()";
    $params = [$fullName, $phone];
    $types = "ss";
    
    // Add address fields if provided
    if (!empty($address) || !empty($city) || !empty($state) || !empty($zipCode)) {
        $sql .= ", address = ?, city = ?, state = ?, zip_code = ?";
        $params[] = $address;
        $params[] = $city;
        $params[] = $state;
        $params[] = $zipCode;
        $types .= "ssss";
    }
    
    // Add seller-specific fields if provided and user is a seller
    if ($_SESSION['user_role'] === 'seller' && (!empty($companyName) || !empty($bio))) {
        $sql .= ", company_name = ?, bio = ?";
        $params[] = $companyName;
        $params[] = $bio;
        $types .= "ss";
    }
    
    // Complete the query
    $sql .= " WHERE id = ?";
    $params[] = $userId;
    $types .= "i";
    
    // Execute the update
    $result = updateData($sql, $types, $params);
    
    if ($result) {
        // Update session data
        $_SESSION['user_name'] = $fullName;
        
        echo json_encode([
            'success' => true,
            'message' => 'Profile updated successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to update profile'
        ]);
    }
}

/**
 * Change user password
 */
function changeUserPassword() {
    // Check if user is logged in
    if (!isLoggedIn()) {
        echo json_encode([
            'success' => false,
            'message' => 'You must be logged in to change your password'
        ]);
        return;
    }
    
    $userId = $_SESSION['user_id'];
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_new_password'] ?? '';
    
    // Validate password fields
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        echo json_encode([
            'success' => false,
            'message' => 'All password fields are required'
        ]);
        return;
    }
    
    if ($newPassword !== $confirmPassword) {
        echo json_encode([
            'success' => false,
            'message' => 'New password and confirmation do not match'
        ]);
        return;
    }
    
    if (strlen($newPassword) < 6) {
        echo json_encode([
            'success' => false,
            'message' => 'New password must be at least 6 characters long'
        ]);
        return;
    }
    
    // Get current user data
    $sql = "SELECT password FROM users WHERE id = ?";
    $user = fetchOne($sql, "i", [$userId]);
    
    if (!$user) {
        echo json_encode([
            'success' => false,
            'message' => 'User not found'
        ]);
        return;
    }
    
    // Verify current password
    if (!verifyPassword($currentPassword, $user['password'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Current password is incorrect'
        ]);
        return;
    }
    
    // Update password
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    $sql = "UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?";
    $result = updateData($sql, "si", [$hashedPassword, $userId]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Password changed successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to change password'
        ]);
    }
}
?>
