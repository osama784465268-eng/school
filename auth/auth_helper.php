<?php
// auth_helper.php

// بدء الجلسة إذا لم تبدأ
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function checkLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: " . getBaseUrl() . "auth/login.php");
        exit;
    }
}

function getBaseUrl() {
    // عدّل هذا المسار حسب هيكل مجلداتك
    return '/school_system/'; // لأن المشروع في مجلد school_system
}

function showAlerts() {
    if (isset($_SESSION['success'])) {
        echo '<div class="alert alert-success fade-in">
                <span class="alert-icon">✔</span>
                <span class="alert-text">' . htmlspecialchars($_SESSION['success']) . '</span>
              </div>';
        unset($_SESSION['success']);
    }
    if (isset($_SESSION['error'])) {
        echo '<div class="alert alert-danger fade-in">
                <span class="alert-icon">✗</span>
                <span class="alert-text">' . htmlspecialchars($_SESSION['error']) . '</span>
              </div>';
        unset($_SESSION['error']);
    }
}

// ========== الدالة الجديدة للتحقق من الصلاحيات ==========
function checkRole($allowed_roles = []) {
    global $user_role;
    
    if (empty($allowed_roles)) {
        return;
    }
    
    if (!isset($user_role) || !in_array($user_role, $allowed_roles)) {
        header("Location: " . getBaseUrl() . "index.php?error=unauthorized");
        exit;
    }
}
?>