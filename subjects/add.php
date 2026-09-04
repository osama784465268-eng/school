<?php
// تضمين الهيدر لتطبيق التحقق من الهوية والاتصال بقاعدة البيانات
require_once __DIR__ . '/../includes/header.php';

// فرض صلاحيات الأدمن فقط
checkRole(['admin']);

$error_message = '';

// جلب قائمة المعلمين لعرضهم في حقل الاختيار (Select)
try {
    $teachers_stmt = $pdo->query("SELECT t.id, u.full_name, t.specialization 
                                  FROM teachers t 
                                  JOIN users u ON t.user_id = u.id 
                                  ORDER BY u.full_name ASC");
    $teachers = $teachers_stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching teachers: " . $e->getMessage());
    $teachers = [];
}

// معالجة إرسال النموذج (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject_name = trim($_POST['subject_name'] ?? '');
    $subject_code = trim($_POST['subject_code'] ?? '');
    $teacher_id = !empty($_POST['teacher_id']) ? intval($_POST['teacher_id']) : null;

    if (empty($subject_name) || empty($subject_code)) {
        $error_message = 'يرجى إدخال اسم المادة ورمز المادة.';
    } else {
        try {
            // التحقق من تكرار اسم المادة أو الرمز
            $chk_stmt = $pdo->prepare("SELECT COUNT(*) FROM subjects WHERE subject_name = :n OR subject_code = :c");
            $chk_stmt->execute(['n' => $subject_name, 'c' => $subject_code]);
            
            if ($chk_stmt->fetchColumn() > 0) {
                $error_message = 'اسم المادة أو رمز المادة مسجل مسبقاً بالنظام!';
            } else {
                // إدخال المادة الجديدة
                $stmt = $pdo->prepare("INSERT INTO subjects (subject_name, subject_code, teacher_id) 
                                       VALUES (:subject_name, :subject_code, :teacher_id)");
                $stmt->execute([
                    'subject_name' => $subject_name,
                    'subject_code' => $subject_code,
                    'teacher_id' => $teacher_id
                ]);

                $_SESSION['success'] = "تم إضافة المادة الدراسية بنجاح.";
                header("Location: index.php");
                exit;
            }
        } catch (PDOException $e) {
            error_log("Error adding subject: " . $e->getMessage());
            $error_message = 'حدث خطأ أثناء حفظ المادة الدراسية: ' . $e->getMessage();
        }
    }
}
?>

<div class="panel-card slide-up">
    <div class="panel-header">
        <span class="panel-title">➕ إضافة مادة دراسية جديدة</span>
        <a href="index.php" class="btn btn-light btn-sm">🗂️ العودة لقائمة المواد</a>
    </div>
    
    <div class="panel-body">
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger fade-in">
                <span class="alert-icon">✗</span>
                <span class="alert-text"><?php echo htmlspecialchars($error_message); ?></span>
            </div>
        <?php endif; ?>

        <form action="add.php" method="POST" style="max-width: 600px; margin: 0 auto;">
            <div class="form-group">
                <label for="subject_name" class="form-label">اسم المادة الدراسية *</label>
                <input type="text" id="subject_name" name="subject_name" class="form-control" placeholder="مثال: لغة عربية، فيزياء حديثة" required value="<?php echo isset($_POST['subject_name']) ? htmlspecialchars($_POST['subject_name']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="subject_code" class="form-label">رمز المادة الفريد *</label>
                <input type="text" id="subject_code" name="subject_code" class="form-control" placeholder="مثال: ARB-101 أو PHYS-202" required value="<?php echo isset($_POST['subject_code']) ? htmlspecialchars($_POST['subject_code']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="teacher_id" class="form-label">المعلم المدرس للمادة (يمكن تعيينه لاحقاً)</label>
                <select id="teacher_id" name="teacher_id" class="form-control">
                    <option value="">-- اختر معلماً من القائمة (اختياري) --</option>
                    <?php foreach ($teachers as $t): ?>
                        <option value="<?php echo $t['id']; ?>" <?php echo (isset($_POST['teacher_id']) && $_POST['teacher_id'] == $t['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($t['full_name']) . " (" . htmlspecialchars($t['specialization']) . ")"; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="btn btn-primary">💾 إدراج المادة وحفظها</button>
        </form>

    </div>
</div>

<?php 
// تضمين الفوتر
require_once __DIR__ . '/../includes/footer.php';
?>
