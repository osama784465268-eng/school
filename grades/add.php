<?php
// تضمين الهيدر لتطبيق التحقق من الهوية والاتصال بقاعدة البيانات
require_once __DIR__ . '/../includes/header.php';

// يسمح للأدمن والمعلم برصد الدرجات فقط
checkRole(['admin', 'teacher']);

$error_message = '';
$user_role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

// 1. جلب قائمة الطلاب لعرضهم في حقل الاختيار
try {
    $students_stmt = $pdo->query("SELECT s.id, u.full_name 
                                  FROM students s 
                                  JOIN users u ON s.user_id = u.id 
                                  ORDER BY u.full_name ASC");
    $students = $students_stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching students: " . $e->getMessage());
    $students = [];
}

// 2. جلب قائمة المواد بحسب صلاحية المستخدم
try {
    if ($user_role === 'admin') {
        // الأدمن يرى جميع المواد
        $subjects_stmt = $pdo->query("SELECT id, subject_name, subject_code FROM subjects ORDER BY subject_name ASC");
    } else {
        // المعلم يرى فقط المواد التي يدرسها
        $subjects_stmt = $pdo->prepare("SELECT s.id, s.subject_name, s.subject_code 
                                        FROM subjects s 
                                        JOIN teachers t ON s.teacher_id = t.id 
                                        WHERE t.user_id = :uid 
                                        ORDER BY s.subject_name ASC");
        $subjects_stmt->execute(['uid' => $user_id]);
    }
    $subjects = $subjects_stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching subjects: " . $e->getMessage());
    $subjects = [];
}

// 3. معالجة إرسال رصد الدرجة
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = intval($_POST['student_id'] ?? 0);
    $subject_id = intval($_POST['subject_id'] ?? 0);
    $exam_type = trim($_POST['exam_type'] ?? '');
    $grade = floatval($_POST['grade'] ?? -1);
    $grade_date = $_POST['grade_date'] ?? date('Y-m-d');

    if (!$student_id || !$subject_id || empty($exam_type) || $_POST['grade'] === '') {
        $error_message = 'يرجى ملء جميع الحقول المطلوبة.';
    } elseif ($grade < 0 || $grade > 100) {
        $error_message = 'يجب أن تكون الدرجة بين 0 و 100.';
    } else {
        try {
            // إدخال السجل الجديد
            $stmt = $pdo->prepare("INSERT INTO grades (student_id, subject_id, exam_type, grade, grade_date) 
                                   VALUES (:student_id, :subject_id, :exam_type, :grade, :grade_date)");
            $stmt->execute([
                'student_id' => $student_id,
                'subject_id' => $subject_id,
                'exam_type' => $exam_type,
                'grade' => $grade,
                'grade_date' => $grade_date
            ]);

            $_SESSION['success'] = "تم رصد درجة الطالب الجديدة بنجاح.";
            header("Location: index.php");
            exit;
        } catch (PDOException $e) {
            error_log("Error adding grade: " . $e->getMessage());
            $error_message = 'حدث خطأ أثناء رصد الدرجة: ' . $e->getMessage();
        }
    }
}
?>

<div class="panel-card slide-up">
    <div class="panel-header">
        <span class="panel-title">➕ رصد درجة جديدة لطالب</span>
        <a href="index.php" class="btn btn-light btn-sm">🗂️ العودة لكشوف الدرجات</a>
    </div>
    
    <div class="panel-body">
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger fade-in">
                <span class="alert-icon">✗</span>
                <span class="alert-text"><?php echo htmlspecialchars($error_message); ?></span>
            </div>
        <?php endif; ?>

        <?php if (empty($subjects) && $user_role === 'teacher'): ?>
            <div class="alert alert-danger">
                عذراً، لم يتم إسناد أي مواد دراسية لحسابك التعليمي بعد. يرجى مراجعة إدارة المدرسة (الأدمن) لإسناد المواد المناسبة أولاً.
            </div>
        <?php else: ?>
            <form action="add.php" method="POST" style="max-width: 600px; margin: 0 auto;">
                
                <div class="form-group">
                    <label for="student_id" class="form-label">اسم الطالب المستحق للدرجة *</label>
                    <select id="student_id" name="student_id" class="form-control" required>
                        <option value="">-- اختر طالب من القائمة --</option>
                        <?php foreach ($students as $s): ?>
                            <option value="<?php echo $s['id']; ?>" <?php echo (isset($_POST['student_id']) && $_POST['student_id'] == $s['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($s['full_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="subject_id" class="form-label">المادة الدراسية *</label>
                    <select id="subject_id" name="subject_id" class="form-control" required>
                        <option value="">-- اختر المادة الدراسية --</option>
                        <?php foreach ($subjects as $sub): ?>
                            <option value="<?php echo $sub['id']; ?>" <?php echo (isset($_POST['subject_id']) && $_POST['subject_id'] == $sub['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($sub['subject_name']) . " (" . htmlspecialchars($sub['subject_code']) . ")"; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="exam_type" class="form-label">نوع الامتحان / الرصد *</label>
                    <select id="exam_type" name="exam_type" class="form-control" required>
                        <option value="">-- اختر الفئة --</option>
                        <option value="امتحان نصفي (Midterm)" <?php echo (isset($_POST['exam_type']) && $_POST['exam_type'] === 'امتحان نصفي (Midterm)') ? 'selected' : ''; ?>>امتحان نصفي (Midterm)</option>
                        <option value="امتحان نهائي (Final)" <?php echo (isset($_POST['exam_type']) && $_POST['exam_type'] === 'امتحان نهائي (Final)') ? 'selected' : ''; ?>>امتحان نهائي (Final)</option>
                        <option value="أنشطة وأعمال سنة (Activities)" <?php echo (isset($_POST['exam_type']) && $_POST['exam_type'] === 'أنشطة وأعمال سنة (Activities)') ? 'selected' : ''; ?>>أنشطة وأعمال سنة (Activities)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="grade" class="form-label">درجة الطالب المستحقة (من 100) *</label>
                    <input type="number" step="0.01" min="0" max="100" id="grade" name="grade" class="form-control" placeholder="0.00 - 100.00" required value="<?php echo isset($_POST['grade']) ? htmlspecialchars($_POST['grade']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="grade_date" class="form-label">تاريخ رصد الدرجة *</label>
                    <input type="date" id="grade_date" name="grade_date" class="form-control" required value="<?php echo isset($_POST['grade_date']) ? htmlspecialchars($_POST['grade_date']) : date('Y-m-d'); ?>">
                </div>

                <button type="submit" class="btn btn-primary">💾 حفظ ورصد الدرجة</button>
            </form>
        <?php endif; ?>

    </div>
</div>

<?php 
// تضمين الفوتر
require_once __DIR__ . '/../includes/footer.php';
?>
