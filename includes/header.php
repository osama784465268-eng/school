<?php
// =====================================================
// 0. بدء التخزين المؤقت للمخرجات (لمنع أخطاء headers)
// =====================================================
ob_start();

// عرض الأخطاء للتشخيص (يمكن إزالتها بعد التصحيح)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// =====================================================
// 1. بدء الجلسة أولاً
// =====================================================
session_start();

// =====================================================
// 2. تضمين الملفات المطلوبة
// =====================================================
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/auth_helper.php';

// =====================================================
// 3. التحقق من تسجيل الدخول (إذا لم يكن مسجلاً، يعيد التوجيه)
// =====================================================
checkLogin();

// =====================================================
// 4. استخراج بيانات المستخدم من الجلسة
// =====================================================
$user_role   = $_SESSION['role'] ?? '';
$user_full_name = $_SESSION['full_name'] ?? 'مستخدم غير معروف';
$username    = $_SESSION['username'] ?? '';

// =====================================================
// 5. تحضير الحرف الأول للاسم (للصورة الرمزية)
// =====================================================
$avatar_letter = mb_substr($user_full_name, 0, 1, 'utf-8');

// =====================================================
// 6. دالة مساعدة لتمييز الصفحة النشطة في القائمة الجانبية
// =====================================================
function isPageActive($page_name) {
    $current_uri = $_SERVER['REQUEST_URI'];
    return (strpos($current_uri, $page_name) !== false) ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة التحكم - نظام إدارة المدرسة الإلكتروني</title>
    <!-- ربط ملف التنسيق الموحد -->
    <link rel="stylesheet" href="<?php echo getBaseUrl(); ?>css/style.css">
</head>
<body>

<div class="app-container">

    <!-- القائمة الجانبية (Sidebar) -->
    <aside class="sidebar">
        <div class="sidebar-brand">
            <span class="sidebar-logo">🏫</span>
            <span class="sidebar-title">نظام المدرسة</span>
        </div>

        <ul class="sidebar-menu">
            <!-- لوحة التحكم (متاحة للجميع) -->
            <li class="sidebar-item <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : ''; ?>">
                <a href="<?php echo getBaseUrl(); ?>index.php">
                    <span>📊</span> لوحة التحكم
                </a>
            </li>

            <!-- إدارة الطلاب (للأدمن فقط) -->
            <?php if ($user_role === 'admin'): ?>
                <li class="sidebar-item <?php echo isPageActive('/students/'); ?>">
                    <a href="<?php echo getBaseUrl(); ?>students/index.php">
                        <span>🎓</span> إدارة الطلاب
                    </a>
                </li>
            <?php endif; ?>

            <!-- إدارة المعلمين (للأدمن فقط) -->
            <?php if ($user_role === 'admin'): ?>
                <li class="sidebar-item <?php echo isPageActive('/teachers/'); ?>">
                    <a href="<?php echo getBaseUrl(); ?>teachers/index.php">
                        <span>👨‍🏫</span> إدارة المعلمين
                    </a>
                </li>
            <?php endif; ?>

            <!-- إدارة المواد الدراسية (للأدمن فقط) -->
            <?php if ($user_role === 'admin'): ?>
                <li class="sidebar-item <?php echo isPageActive('/subjects/'); ?>">
                    <a href="<?php echo getBaseUrl(); ?>subjects/index.php">
                        <span>📚</span> المواد الدراسية
                    </a>
                </li>
            <?php endif; ?>

            <!-- إدارة الحضور والغياب (للجميع مع صلاحيات مختلفة) -->
            <li class="sidebar-item <?php echo isPageActive('/attendance/'); ?>">
                <a href="<?php echo getBaseUrl(); ?>attendance/index.php">
                    <span>⏱️</span> الحضور والغياب
                </a>
            </li>
            
            <!-- رصد وعرض الدرجات (للجميع) -->
            <li class="sidebar-item <?php echo isPageActive('/grades/'); ?>">
                <a href="<?php echo getBaseUrl(); ?>grades/index.php">
                    <span>📝</span> رصد وعرض الدرجات
                </a>
            </li>

            <!-- عن النظام (للجميع) -->
            <li class="sidebar-item <?php echo (basename($_SERVER['PHP_SELF']) == 'about.php') ? 'active' : ''; ?>">
                <a href="<?php echo getBaseUrl(); ?>about.php">
                    <span>ℹ️</span> عن النظام
                </a>
            </li>

            <!-- اتصل بنا (للجميع) -->
            <li class="sidebar-item <?php echo (basename($_SERVER['PHP_SELF']) == 'contact.php') ? 'active' : ''; ?>">
                <a href="<?php echo getBaseUrl(); ?>contact.php">
                    <span>📞</span> اتصل بنا
                </a>
            </li>
        </ul>

        <!-- فوتر القائمة الجانبية (معلومات المستخدم وزر الخروج) -->
        <div class="sidebar-footer">
            <div class="sidebar-user">
                <div class="user-avatar"><?php echo htmlspecialchars($avatar_letter); ?></div>
                <div class="user-info">
                    <span class="user-name"><?php echo htmlspecialchars($user_full_name); ?></span>
                    <span class="user-role">
                        <?php 
                        if ($user_role === 'admin') echo 'مدير النظام';
                        elseif ($user_role === 'teacher') echo 'مدرس';
                        else echo 'طالب';
                        ?>
                    </span>
                </div>
            </div>
            <a href="<?php echo getBaseUrl(); ?>auth/logout.php" class="btn btn-danger btn-sm btn-block" style="font-size: 0.8rem; padding: 8px;">
                🚪 تسجيل الخروج
            </a>
        </div>
    </aside>

    <!-- منطقة المحتوى الرئيسية -->
    <main class="main-content">
        
        <!-- الهيدر العلوي -->
        <header class="main-header">
            <div class="page-title">
                <h1>لوحة القيادة والمتابعة</h1>
                <p>مرحباً بك مجدداً في نظام المدرسة الإلكتروني</p>
            </div>
            
            <div class="header-actions">
                <span class="badge badge-primary">السنة الدراسية: 2026/2027</span>
                <span class="badge badge-success"><?php echo date('Y-m-d'); ?></span>
            </div>
        </header>
        
        <!-- حاوية عرض التنبيهات -->
        <div class="alerts-container">
            <?php showAlerts(); ?>
        </div>