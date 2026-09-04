<?php
// تضمين قواعد البيانات والتحقق
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/auth_helper.php';

// حماية الصفحة: الأدمن فقط من يحق له الحذف
checkLogin();
checkRole(['admin']);

$student_id = intval($_GET['id'] ?? 0);

if ($student_id > 0) {
    try {
        // 1. جلب معرف المستخدم المرتبط بالطالب
        $stmt = $pdo->prepare("SELECT user_id FROM students WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $student_id]);
        $user_id = $stmt->fetchColumn();

        if ($user_id) {
            // 2. حذف المستخدم من جدول users
            // بفضل علاقات FOREIGN KEY ... ON DELETE CASCADE في الداتابيز، 
            // سيتم تلقائياً حذف سجل الطالب من جدول students، ودرجاته من جدول grades، وحضوره وغيابه من جدول attendance!
            $del_stmt = $pdo->prepare("DELETE FROM users WHERE id = :uid");
            $del_stmt->execute(['uid' => $user_id]);
            
            $_SESSION['success'] = "تم حذف الطالب وحسابه وسجلاته بالكامل بنجاح.";
        } else {
            $_SESSION['error'] = "الطالب المراد حذفه غير موجود بالنظام.";
        }
    } catch (PDOException $e) {
        error_log("Error deleting student: " . $e->getMessage());
        $_SESSION['error'] = "فشل في إتمام عملية الحذف من النظام. تفاصيل: " . $e->getMessage();
    }
} else {
    $_SESSION['error'] = "معرف الطالب غير صحيح.";
}

// تحويل العميل للصحفة الرئيسية للطلاب
header("Location: index.php");
exit;
?>
