<?php
require_once 'auth_helper.php';

// تفريغ كافة متغيرات الجلسة (Session Variables)
$_SESSION = [];

// حذف ملفات تعريف الارتباط بالجلسة (Session Cookie) من متصفح العميل
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// تدمير ملفات الجلسة على السيرفر
session_destroy();

// تفعيل الجلسة مؤقتاً لتخزين رسالة الخروج فقط
session_start();
$_SESSION['success'] = "تم تسجيل الخروج من النظام بنجاح.";

// تحويل المستخدم لصفحة تسجيل الدخول
header("Location: login.php");
exit;
?>
