<?php
// تضمين قواعد البيانات والتحقق
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/auth_helper.php';

// حماية الصفحة: الأدمن والمعلمين فقط
checkLogin();
checkRole(['admin', 'teacher']);

$grade_id = intval($_GET['id'] ?? 0);
$user_role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

if ($grade_id > 0) {
    try {
        // جلب تفاصيل المادة للحماية الأمنية للمعلمين
        $stmt = $pdo->prepare("SELECT g.id, sub.teacher_id, t.user_id AS teacher_user_id
                               FROM grades g
                               JOIN subjects sub ON g.subject_id = sub.id
                               LEFT JOIN teachers t ON sub.teacher_id = t.id
                               WHERE g.id = :id 
                               LIMIT 1");
        $stmt->execute(['id' => $grade_id]);
        $grade = $stmt->fetch();

        if ($grade) {
            // التحقق من الصلاحية الأمنية للمعلم
            if ($user_role === 'teacher' && $grade['teacher_user_id'] != $user_id) {
                $_SESSION['error'] = "لا يمكنك حذف درجة مادة لا تدرّسها.";
            } else {
                $del_stmt = $pdo->prepare("DELETE FROM grades WHERE id = :id");
                $del_stmt->execute(['id' => $grade_id]);
                $_SESSION['success'] = "تم حذف درجة الطالب بنجاح.";
            }
        } else {
            $_SESSION['error'] = "الدرجة المستهدفة غير متوفرة بالنظام.";
        }
    } catch (PDOException $e) {
        error_log("Error deleting grade: " . $e->getMessage());
        $_SESSION['error'] = "فشل حذف الدرجة بسبب خلل في البكند. تفاصيل: " . $e->getMessage();
    }
} else {
    $_SESSION['error'] = "معرف الدرجة غير صحيح.";
}

// العودة لكشوف الدرجات
header("Location: index.php");
exit;
?>
