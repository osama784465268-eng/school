<?php
// تضمين الهيدر للتحقق من الهوية والاتصال بقاعدة البيانات
require_once __DIR__ . '/../includes/header.php';

// يمكن للجميع الدخول لصفحة الدرجات ولكن بصلاحيات مختلفة
$user_role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

$grades = [];

try {
    if ($user_role === 'admin') {
        // الأدمن يرى جميع الدرجات لكل الطلاب والمواد
        $sql = "SELECT g.id, g.exam_type, g.grade, g.grade_date, 
                       u_stud.full_name AS student_name, 
                       sub.subject_name, sub.subject_code 
                FROM grades g
                JOIN students s ON g.student_id = s.id
                JOIN users u_stud ON s.user_id = u_stud.id
                JOIN subjects sub ON g.subject_id = sub.id
                ORDER BY g.id DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $grades = $stmt->fetchAll();

    } elseif ($user_role === 'teacher') {
        // المعلم يرى فقط درجات المواد المسندة إليه
        $sql = "SELECT g.id, g.exam_type, g.grade, g.grade_date, 
                       u_stud.full_name AS student_name, 
                       sub.subject_name, sub.subject_code 
                FROM grades g
                JOIN students s ON g.student_id = s.id
                JOIN users u_stud ON s.user_id = u_stud.id
                JOIN subjects sub ON g.subject_id = sub.id
                JOIN teachers t ON sub.teacher_id = t.id
                WHERE t.user_id = :uid
                ORDER BY g.id DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['uid' => $user_id]);
        $grades = $stmt->fetchAll();

    } elseif ($user_role === 'student') {
        // الطالب يرى فقط درجاته الشخصية
        $sql = "SELECT g.id, g.exam_type, g.grade, g.grade_date, 
                       sub.subject_name, sub.subject_code, 
                       u_teach.full_name AS teacher_name
                FROM grades g
                JOIN students s ON g.student_id = s.id
                JOIN subjects sub ON g.subject_id = sub.id
                LEFT JOIN teachers t ON sub.teacher_id = t.id
                LEFT JOIN users u_teach ON t.user_id = u_teach.id
                WHERE s.user_id = :uid
                ORDER BY g.id DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['uid' => $user_id]);
        $grades = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    error_log("Error fetching grades: " . $e->getMessage());
    $_SESSION['error'] = "حدث خطأ في النظام أثناء جلب كشوفات الدرجات.";
}
?>

<div class="panel-card slide-up">
    <div class="panel-header">
        <span class="panel-title">📝 نظام رصد وعرض الدرجات الدراسية</span>
        <?php if ($user_role === 'admin' || $user_role === 'teacher'): ?>
            <a href="add.php" class="btn btn-primary btn-sm">➕ رصد درجة جديدة</a>
        <?php endif; ?>
    </div>
    
    <div class="panel-body">
        
        <?php if (empty($grades)): ?>
            <div style="text-align: center; padding: 40px; color: var(--text-muted);">
                <p style="font-size: 1.1rem; margin-bottom: 10px;">لا توجد درجات مرصودة حالياً.</p>
                <?php if ($user_role === 'admin' || $user_role === 'teacher'): ?>
                    <a href="add.php" class="btn btn-primary btn-sm">ابدأ برصد أول درجة الآن</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <?php if ($user_role !== 'student'): ?>
                                <th>اسم الطالب</th>
                            <?php endif; ?>
                            <th>اسم المادة (الرمز)</th>
                            <th>نوع الامتحان</th>
                            <th>الدرجة المرصودة</th>
                            <th>تاريخ الرصد</th>
                            <?php if ($user_role === 'student'): ?>
                                <th>معلم المادة</th>
                            <?php else: ?>
                                <th>العمليات</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($grades as $grade): ?>
                        <tr>
                            <?php if ($user_role !== 'student'): ?>
                                <td><strong><?php echo htmlspecialchars($grade['student_name']); ?></strong></td>
                            <?php endif; ?>
                            
                            <td>
                                <?php echo htmlspecialchars($grade['subject_name']); ?> 
                                (<code><?php echo htmlspecialchars($grade['subject_code']); ?></code>)
                            </td>
                            <td><span class="badge badge-warning"><?php echo htmlspecialchars($grade['exam_type']); ?></span></td>
                            <td>
                                <span class="badge badge-success" style="font-size: 0.95rem; font-weight: 700;">
                                    <?php echo number_format($grade['grade'], 2); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($grade['grade_date']); ?></td>
                            
                            <?php if ($user_role === 'student'): ?>
                                <td><?php echo htmlspecialchars($grade['teacher_name'] ?? 'لم يحدد بعد'); ?></td>
                            <?php else: ?>
                                <td class="table-actions">
                                    <a href="edit.php?id=<?php echo $grade['id']; ?>" class="btn btn-secondary btn-xs">✏️ تعديل</a>
                                    <a href="delete.php?id=<?php echo $grade['id']; ?>" 
                                       class="btn btn-danger btn-xs" 
                                       onclick="return confirm('هل أنت متأكد من حذف هذه الدرجة المحددة؟');">
                                        🗑️ حذف
                                    </a>
                                </td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        
    </div>
</div>

<?php 
// تضمين الفوتر
require_once __DIR__ . '/../includes/footer.php';
?>
