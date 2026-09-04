<?php
// تضمين الهيدر للتحقق من الموثوقية وقواعد الاتصال
require_once __DIR__ . '/../includes/header.php';

// يسمح للأدمن والمعلمين بالتعديل فقط
checkRole(['admin', 'teacher']);

$error_message = '';
$grade_id = intval($_GET['id'] ?? 0);
$user_role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

if (!$grade_id) {
    $_SESSION['error'] = "معرف الدرجة غير صحيح.";
    header("Location: index.php");
    exit;
}

try {
    // جلب بيانات الدرجة الحالية مع ربطها بالطالب والمعلم للتحقق من الصلاحية
    $stmt = $pdo->prepare("SELECT g.*, u_stud.full_name AS student_name, 
                                  sub.subject_name, sub.subject_code, sub.teacher_id, 
                                  t.user_id AS teacher_user_id
                           FROM grades g
                           JOIN students s ON g.student_id = s.id
                           JOIN users u_stud ON s.user_id = u_stud.id
                           JOIN subjects sub ON g.subject_id = sub.id
                           LEFT JOIN teachers t ON sub.teacher_id = t.id
                           WHERE g.id = :id 
                           LIMIT 1");
    $stmt->execute(['id' => $grade_id]);
    $grade_detail = $stmt->fetch();

    if (!$grade_detail) {
        $_SESSION['error'] = "الدرجة المطلوبة غير موجودة بنظام المدرسة.";
        header("Location: index.php");
        exit;
    }

    // التحقق من الصلاحية الأمنية: المعلم يمكنه فقط تعديل درجات المواد المسندة إليه
    if ($user_role === 'teacher' && $grade_detail['teacher_user_id'] != $user_id) {
        $_SESSION['error'] = "عذراً، لا تمتلك الصلاحية لتعديل درجة مادة لا تقوم بتدريسها!";
        header("Location: index.php");
        exit;
    }

} catch (PDOException $e) {
    error_log("Error fetching grade for editing: " . $e->getMessage());
    $_SESSION['error'] = "حدث خطأ في النظام أثناء محاولة جلب الدرجة.";
    header("Location: index.php");
    exit;
}

// معالجة تحديث الدرجة
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $exam_type = trim($_POST['exam_type'] ?? '');
    $grade_val = floatval($_POST['grade'] ?? -1);
    $grade_date = $_POST['grade_date'] ?? '';

    if (empty($exam_type) || $_POST['grade'] === '' || empty($grade_date)) {
        $error_message = 'يرجى ملء جميع الحقول المطلوبة.';
    } elseif ($grade_val < 0 || $grade_val > 100) {
        $error_message = 'يجب أن تكون الدرجة بين 0 و 100.';
    } else {
        try {
            $up_stmt = $pdo->prepare("UPDATE grades 
                                       SET exam_type = :exam_type, grade = :grade, grade_date = :grade_date 
                                       WHERE id = :id");
            $up_stmt->execute([
                'exam_type' => $exam_type,
                'grade' => $grade_val,
                'grade_date' => $grade_date,
                'id' => $grade_id
            ]);

            $_SESSION['success'] = "تم تحديث درجة الطالب المحددة بنجاح.";
            header("Location: index.php");
            exit;
        } catch (PDOException $e) {
            error_log("Error updating grade: " . $e->getMessage());
            $error_message = 'حدث خطأ في البكند أثناء حفظ التعديل: ' . $e->getMessage();
        }
    }
}
?>

<div class="panel-card slide-up">
    <div class="panel-header">
        <span class="panel-title">✏️ تعديل درجة الطالب: <?php echo htmlspecialchars($grade_detail['student_name']); ?></span>
        <a href="index.php" class="btn btn-light btn-sm">🗂️ العودة لكشوف الدرجات</a>
    </div>
    
    <div class="panel-body">
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger fade-in">
                <span class="alert-icon">✗</span>
                <span class="alert-text"><?php echo htmlspecialchars($error_message); ?></span>
            </div>
        <?php endif; ?>

        <form action="edit.php?id=<?php echo $grade_id; ?>" method="POST" style="max-width: 600px; margin: 0 auto;">
            
            <div class="form-group">
                <label class="form-label">اسم الطالب</label>
                <input type="text" class="form-control" value="<?php echo htmlspecialchars($grade_detail['student_name']); ?>" disabled style="background-color: #f1f5f9;">
            </div>

            <div class="form-group">
                <label class="form-label">المادة الدراسية</label>
                <input type="text" class="form-control" value="<?php echo htmlspecialchars($grade_detail['subject_name']) . ' (' . htmlspecialchars($grade_detail['subject_code']) . ')'; ?>" disabled style="background-color: #f1f5f9;">
            </div>

            <div class="form-group">
                <label for="exam_type" class="form-label">نوع الامتحان / الرصد *</label>
                <select id="exam_type" name="exam_type" class="form-control" required>
                    <option value="امتحان نصفي (Midterm)" <?php echo (($grade_detail['exam_type'] === 'امتحان نصفي (Midterm)') ? 'selected' : ''); ?>>امتحان نصفي (Midterm)</option>
                    <option value="امتحان نهائي (Final)" <?php echo (($grade_detail['exam_type'] === 'امتحان نهائي (Final)') ? 'selected' : ''); ?>>امتحان نهائي (Final)</option>
                    <option value="أنشطة وأعمال سنة (Activities)" <?php echo (($grade_detail['exam_type'] === 'أنشطة وأعمال سنة (Activities)') ? 'selected' : ''); ?>>أنشطة وأعمال سنة (Activities)</option>
                </select>
            </div>

            <div class="form-group">
                <label for="grade" class="form-label">درجة الطالب المستحقة (من 100) *</label>
                <input type="number" step="0.01" min="0" max="100" id="grade" name="grade" class="form-control" required value="<?php echo htmlspecialchars($_POST['grade'] ?? $grade_detail['grade']); ?>">
            </div>

            <div class="form-group">
                <label for="grade_date" class="form-label">تاريخ رصد الدرجة *</label>
                <input type="date" id="grade_date" name="grade_date" class="form-control" required value="<?php echo htmlspecialchars($_POST['grade_date'] ?? $grade_detail['grade_date']); ?>">
            </div>

            <button type="submit" class="btn btn-secondary">💾 تحديث وحفظ الدرجة المعدلة</button>
        </form>

    </div>
</div>

<?php 
// تضمين الفوتر
require_once __DIR__ . '/../includes/footer.php';
?>
