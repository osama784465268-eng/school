<?php
// تضمين قواعد البيانات والتحقق
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/auth_helper.php';

// حماية الصفحة: الأدمن فقط من يحق له الحذف
checkLogin();
checkRole(['admin']);

$subject_id = intval($_GET['id'] ?? 0);

if ($subject_id > 0) {
    try {
        // حذف المادة الدراسية
        // درجات الطلاب المرتبطة بهذه المادة سيتم حذفها تلقائياً بسبب CASCADE في FOREIGN KEY في جدول grades
        $del_stmt = $pdo->prepare("DELETE FROM subjects WHERE id = :id");
        $del_stmt->execute(['id' => $subject_id]);
        
        $_SESSION['success'] = "تم حذف المادة الدراسية والدرجات المرتبطة بها بنجاح.";
    } catch (PDOException $e) {
        error_log("Error deleting subject: " . $e->getMessage());
        $_SESSION['error'] = "فشل في عملية الحذف. تفاصيل: " . $e->getMessage();
    }
} else {
    $_SESSION['error'] = "معرف المادة غير صحيح.";
}

// تحويل العميل للصحفة الرئيسية للمواد
header("Location: index.php");
exit;
?>
