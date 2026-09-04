<?php
// تضمين قواعد البيانات والتحقق
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/auth_helper.php';

// حماية الصفحة: الأدمن فقط من يحق له الحذف
checkLogin();
checkRole(['admin']);

$teacher_id = intval($_GET['id'] ?? 0);

if ($teacher_id > 0) {
    try {
        // 1. جلب معرف المستخدم المرتبط بالمعلم
        $stmt = $pdo->prepare("SELECT user_id FROM teachers WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $teacher_id]);
        $user_id = $stmt->fetchColumn();

        if ($user_id) {
            // 2. حذف المستخدم من جدول users
            // سيتم تلقائياً حذف سجل المعلم من جدول teachers بسبب CASCADE،
            // وستصبح قيمة معلم المادة (teacher_id) في جدول المواد subjects تساوي NULL تلقائياً بفضل FOREIGN KEY ON DELETE SET NULL.
            $del_stmt = $pdo->prepare("DELETE FROM users WHERE id = :uid");
            $del_stmt->execute(['uid' => $user_id]);
            
            $_SESSION['success'] = "تم حذف المعلم وحسابه بالكامل بنجاح.";
        } else {
            $_SESSION['error'] = "المعلم المراد حذفه غير موجود بالنظام.";
        }
    } catch (PDOException $e) {
        error_log("Error deleting teacher: " . $e->getMessage());
        $_SESSION['error'] = "فشل في إتمام عملية الحذف. تفاصيل: " . $e->getMessage();
    }
} else {
    $_SESSION['error'] = "معرف المعلم غير صحيح.";
}

// تحويل العميل للصحفة الرئيسية للمعلمين
header("Location: index.php");
exit;
?>
