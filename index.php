<?php
// index.php - الصفحة الرئيسية بعد تسجيل الدخول
require_once __DIR__ . '/includes/header.php'; // يحتوي على session_start والتحقق وتعريف المتغيرات

// جلب الإحصائيات العامة
$students_count = 0;
$teachers_count = 0;
$subjects_count = 0;

try {
    $students_count = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
    $teachers_count = $pdo->query("SELECT COUNT(*) FROM teachers")->fetchColumn();
    $subjects_count = $pdo->query("SELECT COUNT(*) FROM subjects")->fetchColumn();
} catch (PDOException $e) {
    error_log("Error fetching system stats: " . $e->getMessage());
}
?>

<div class="panel-card slide-up">
    <div class="panel-header">
        <span class="panel-title">لوحة التحكم الرئيسية 🛡️</span>
        <span class="badge badge-primary">حساب: <?php echo htmlspecialchars($username); ?></span>
    </div>
    <div class="panel-body">
        <h2>أهلاً بك، <?php echo htmlspecialchars($user_full_name); ?>!</h2>
        <p style="color: var(--text-muted); margin-top: 5px;">أنت تقوم بتصفح النظام بصفتك: 
            <strong>
                <?php 
                if ($user_role === 'admin') echo 'مدير النظام الكامل (Administrator)';
                elseif ($user_role === 'teacher') echo 'كادر تدريسي (Teacher)';
                else echo 'طالب مدرسي (Student)';
                ?>
            </strong>
        </p>
    </div>
</div>

<?php if ($user_role === 'admin'): ?>
    <!-- ==================== واجهة الأدمن ==================== -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-info">
                <span class="stat-label">إجمالي الطلاب المسجلين</span>
                <span class="stat-value"><?php echo $students_count; ?></span>
            </div>
            <div class="stat-icon">🎓</div>
        </div>
        <div class="stat-card">
            <div class="stat-info">
                <span class="stat-label">المعلمين وأعضاء التدريس</span>
                <span class="stat-value"><?php echo $teachers_count; ?></span>
            </div>
            <div class="stat-icon teal-theme">👨‍🏫</div>
        </div>
        <div class="stat-card">
            <div class="stat-info">
                <span class="stat-label">المواد الدراسية المقررة</span>
                <span class="stat-value"><?php echo $subjects_count; ?></span>
            </div>
            <div class="stat-icon orange-theme">📚</div>
        </div>
    </div>

    <div class="info-grid">
        <div class="panel-card">
            <div class="panel-header">
                <span class="panel-title">المستخدمين التجريبيين في النظام</span>
            </div>
            <div class="panel-body">
                <div class="table-responsive">
                    <table class="table-custom">
                        <thead>
                            <tr>
                                <th>اسم المستخدم</th>
                                <th>الاسم الكامل</th>
                                <th>الصلاحية (الدور)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // تم حذف عمود email من الاستعلام لتجنب الخطأ
                            $stmt = $pdo->query("SELECT username, full_name, role FROM users ORDER BY id ASC");
                            while ($row = $stmt->fetch()):
                            ?>
                            <tr>
                                <td><code><?php echo htmlspecialchars($row['username']); ?></code></td>
                                <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                                <td>
                                    <?php if ($row['role'] === 'admin'): ?>
                                        <span class="badge badge-danger">أدمن</span>
                                    <?php elseif ($row['role'] === 'teacher'): ?>
                                        <span class="badge badge-primary">معلم</span>
                                    <?php else: ?>
                                        <span class="badge badge-success">طالب</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="panel-card">
            <div class="panel-header">
                <span class="panel-title">إجراءات سريعة</span>
            </div>
            <div class="panel-body" style="display: flex; flex-direction: column; gap: 12px;">
                <a href="students/index.php" class="btn btn-primary btn-block">➕ إضافة أو تعديل طالب</a>
                <a href="teachers/index.php" class="btn btn-secondary btn-block">➕ إضافة أو تعديل معلم</a>
                <a href="subjects/index.php" class="btn btn-light btn-block" style="text-align: right; justify-content: flex-start; gap: 10px;">➕ إدارة المواد الدراسية</a>
                <a href="attendance/index.php" class="btn btn-light btn-block" style="text-align: right; justify-content: flex-start; gap: 10px;">⏱️ تسجيل حضور الطلاب اليوم</a>
            </div>
        </div>
    </div>

<?php elseif ($user_role === 'teacher'): ?>
    <!-- ==================== واجهة المعلم ==================== -->
    <?php
    $teacher_id = null;
    $stmt = $pdo->prepare("SELECT id FROM teachers WHERE user_id = ?");
    $stmt->execute([$user_id]); // استخدم $user_id من header.php
    $teacher = $stmt->fetch();
    if ($teacher) {
        $teacher_id = $teacher['id'];
    }

    $my_subjects = [];
    if ($teacher_id) {
        $stmt = $pdo->prepare("SELECT * FROM subjects WHERE teacher_id = ?");
        $stmt->execute([$teacher_id]);
        $my_subjects = $stmt->fetchAll();
    }
    ?>
    
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-info">
                <span class="stat-label">المواد الدراسية التي تدرسها</span>
                <span class="stat-value"><?php echo count($my_subjects); ?></span>
            </div>
            <div class="stat-icon">📚</div>
        </div>
        <div class="stat-card">
            <div class="stat-info">
                <span class="stat-label">إجمالي طلاب المدرسة</span>
                <span class="stat-value"><?php echo $students_count; ?></span>
            </div>
            <div class="stat-icon teal-theme">🎓</div>
        </div>
    </div>

    <div class="panel-card">
        <div class="panel-header">
            <span class="panel-title">موادك الدراسية الحالية</span>
        </div>
        <div class="panel-body">
            <?php if (empty($my_subjects)): ?>
                <p style="text-align: center; color: var(--text-muted);">لا توجد مواد دراسية مسندة إليك حالياً.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table-custom">
                        <thead>
                            <tr>
                                <th>اسم المادة</th>
                                <th>رمز المادة</th>
                                <th>إجراءات الرصد</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($my_subjects as $sub): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($sub['subject_name']); ?></strong></td>
                                <td><code><?php echo htmlspecialchars($sub['subject_code']); ?></code></td>
                                <td>
                                    <a href="grades/index.php" class="btn btn-primary btn-xs">📝 رصد الدرجات</a>
                                    <a href="attendance/index.php" class="btn btn-secondary btn-xs">⏱️ تسجيل الغياب</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

<?php elseif ($user_role === 'student'): ?>
    <!-- ==================== واجهة الطالب ==================== -->
    <?php
    $student_id = null;
    $stmt = $pdo->prepare("SELECT id FROM students WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $student = $stmt->fetch();
    if ($student) {
        $student_id = $student['id'];
    }

    $grades_list = [];
    $attendance_present = 0;
    $attendance_absent = 0;
    
    if ($student_id) {
        $stmt = $pdo->prepare("SELECT g.grade, g.exam_type, g.grade_date, s.subject_name 
                               FROM grades g 
                               JOIN subjects s ON g.subject_id = s.id 
                               WHERE g.student_id = ?");
        $stmt->execute([$student_id]);
        $grades_list = $stmt->fetchAll();

        $stmt = $pdo->prepare("SELECT status, COUNT(*) as count FROM attendance WHERE student_id = ? GROUP BY status");
        $stmt->execute([$student_id]);
        $att_stats = $stmt->fetchAll();
        foreach ($att_stats as $stat) {
            if ($stat['status'] === 'present') {
                $attendance_present = $stat['count'];
            } elseif ($stat['status'] === 'absent') {
                $attendance_absent = $stat['count'];
            }
        }
    }
    ?>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-info">
                <span class="stat-label">المواد التي حصلت على درجات فيها</span>
                <span class="stat-value"><?php echo count($grades_list); ?></span>
            </div>
            <div class="stat-icon">📝</div>
        </div>
        <div class="stat-card">
            <div class="stat-info">
                <span class="stat-label">أيام الحضور المسجلة</span>
                <span class="stat-value"><?php echo $attendance_present; ?></span>
            </div>
            <div class="stat-icon teal-theme">✓</div>
        </div>
        <div class="stat-card">
            <div class="stat-info">
                <span class="stat-label">أيام الغياب غير المعذرة</span>
                <span class="stat-value text-danger" style="color: var(--error-color);"><?php echo $attendance_absent; ?></span>
            </div>
            <div class="stat-icon orange-theme">✗</div>
        </div>
    </div>

    <div class="info-grid">
        <div class="panel-card">
            <div class="panel-header">
                <span class="panel-title">كشف درجاتك الأخير</span>
            </div>
            <div class="panel-body">
                <?php if (empty($grades_list)): ?>
                    <p style="text-align: center; color: var(--text-muted);">لم يتم رصد أي درجات لك حتى الآن.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table-custom">
                            <thead>
                                <tr>
                                    <th>المادة الدراسية</th>
                                    <th>نوع الرصد</th>
                                    <th>الدرجة</th>
                                    <th>تاريخ الرصد</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($grades_list as $g): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($g['subject_name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($g['exam_type']); ?></td>
                                    <td><span class="badge badge-primary"><?php echo number_format((float)$g['grade'], 2); ?></span></td>
                                    <td><?php echo htmlspecialchars($g['grade_date']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="panel-card">
            <div class="panel-header">
                <span class="panel-title">حالة حضورك الإجمالية</span>
            </div>
            <div class="panel-body">
                <ul class="info-list">
                    <li><span class="info-list-icon">🟢</span> أيام الحضور الفعلي: <strong><?php echo $attendance_present; ?> يوم</strong></li>
                    <li><span class="info-list-icon">🔴</span> أيام الغياب المسجل: <strong><?php echo $attendance_absent; ?> يوم</strong></li>
                </ul>
                <div style="margin-top: 20px;">
                    <a href="attendance/index.php" class="btn btn-secondary btn-block btn-sm">🗒️ عرض التقرير المفصل</a>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php
// تضمين الفوتر
require_once __DIR__ . '/includes/footer.php';
?>