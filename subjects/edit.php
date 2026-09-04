<?php
// تضمين الهيدر للتحقق والاتصال
require_once __DIR__ . '/../includes/header.php';

// فرض صلاحيات الأدمن فقط
checkRole(['admin']);

$error_message = '';
$subject_id = intval($_GET['id'] ?? 0);

if (!$subject_id) {
    $_SESSION['error'] = "معرف المادة الدراسية غير معروف.";
    header("Location: index.php");
    exit;
}

try {
    // جلب البيانات الحالية للمادة
    $stmt = $pdo->prepare("SELECT * FROM subjects WHERE id = :id LIMIT 1");
    $stmt->execute(['id' => $subject_id]);
    $subject = $stmt->fetch();

    if (!$subject) {
        $_SESSION['error'] = "المادة المطلوبة غير متوفرة بالنظام.";
        header("Location: index.php");
        exit;
    }

    // جلب قائمة المعلمين لعرضهم في القائمة المنسدلة
    $teachers_stmt = $pdo->query("SELECT t.id, u.full_name, t.specialization 
                                  FROM teachers t 
                                  JOIN users u ON t.user_id = u.id 
                                  ORDER BY u.full_name ASC");
    $teachers = $teachers_stmt->fetchAll();

} catch (PDOException $e) {
    error_log("Error fetching subject details: " . $e->getMessage());
    $_SESSION['error'] = "حدث خطأ في النظام أثناء جلب البيانات.";
    header("Location: index.php");
    exit;
}

// معالجة نموذج التحديث
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject_name = trim($_POST['subject_name'] ?? '');
    $subject_code = trim($_POST['subject_code'] ?? '');
    $teacher_id = !empty($_POST['teacher_id']) ? intval($_POST['teacher_id']) : null;

    if (empty($subject_name) || empty($subject_code)) {
        $error_message = 'يرجى إدخال اسم المادة ورمز المادة.';
    } else {
        try {
            // التحقق من أن الاسم والرمز غير مستعملين في مادة أخرى
            $chk_stmt = $pdo->prepare("SELECT COUNT(*) FROM subjects WHERE (subject_name = :n OR subject_code = :c) AND id != :id");
            $chk_stmt->execute([
                'n' => $subject_name,
                'c' => $subject_code,
                'id' => $subject_id
            ]);

            if ($chk_stmt->fetchColumn() > 0) {
                $error_message = 'اسم المادة أو رمز المادة مستخدم بالفعل لمادة أخرى!';
            } else {
                // تحديث المادة
                $up_stmt = $pdo->prepare("UPDATE subjects 
                                           SET subject_name = :subject_name, subject_code = :subject_code, teacher_id = :teacher_id 
                                           WHERE id = :id");
                $up_stmt->execute([
                    'subject_name' => $subject_name,
                    'subject_code' => $subject_code,
                    'teacher_id' => $teacher_id,
                    'id' => $subject_id
                ]);

                $_SESSION['success'] = "تم تحديث بيانات المادة الدراسية بنجاح.";
                header("Location: index.php");
                exit;
            }
        } catch (PDOException $e) {
            error_log("Error updating subject: " . $e->getMessage());
            $error_message = 'حدث خطأ في النظام أثناء التحديث: ' . $e->getMessage();
        }
    }
}
?>

<div class="panel-card slide-up">
    <div class="panel-header">
        <span class="panel-title">✏️ تعديل بيانات المادة الدراسية</span>
        <a href="index.php" class="btn btn-light btn-sm">🗂️ العودة لقائمة المواد</a>
    </div>
    
    <div class="panel-body">
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger fade-in">
                <span class="alert-icon">✗</span>
                <span class="alert-text"><?php echo htmlspecialchars($error_message); ?></span>
            </div>
        <?php endif; ?>

        <form action="edit.php?id=<?php echo $subject_id; ?>" method="POST" style="max-width: 600px; margin: 0 auto;">
            <div class="form-group">
                <label for="subject_name" class="form-label">اسم المادة الدراسية *</label>
                <input type="text" id="subject_name" name="subject_name" class="form-control" required value="<?php echo htmlspecialchars($_POST['subject_name'] ?? $subject['subject_name']); ?>">
            </div>

            <div class="form-group">
                <label for="subject_code" class="form-label">رمز المادة الفريد *</label>
                <input type="text" id="subject_code" name="subject_code" class="form-control" required value="<?php echo htmlspecialchars($_POST['subject_code'] ?? $subject['subject_code']); ?>">
            </div>

            <div class="form-group">
                <label for="teacher_id" class="form-label">المعلم المسؤول (مدرس المادة)</label>
                <select id="teacher_id" name="teacher_id" class="form-control">
                    <option value="">-- اختر معلماً من القائمة (أو اتركها دون معلم حالياً) --</option>
                    <?php foreach ($teachers as $t): ?>
                        <option value="<?php echo $t['id']; ?>" <?php echo (($subject['teacher_id'] == $t['id']) ? 'selected' : ''); ?>>
                            <?php echo htmlspecialchars($t['full_name']) . " (" . htmlspecialchars($t['specialization']) . ")"; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="btn btn-secondary">💾 تحديث وحفظ البيانات</button>
        </form>

    </div>
</div>

<?php 
// تضمين الفوتر
require_once __DIR__ . '/../includes/footer.php';
?>
