<?php
// تضمين الهيدر لتطبيق التحقق من الهوية والاتصال بقاعدة البيانات
require_once __DIR__ . '/../includes/header.php';

// فرض صلاحيات الأدمن فقط
checkRole(['admin']);

try {
    // جلب المواد الدراسية مع إظهار اسم المعلم المدرس لها إن وُجد
    $sql = "SELECT s.id, s.subject_name, s.subject_code, u.full_name AS teacher_name 
            FROM subjects s
            LEFT JOIN teachers t ON s.teacher_id = t.id
            LEFT JOIN users u ON t.user_id = u.id
            ORDER BY s.id DESC";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $subjects = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log("Error fetching subjects: " . $e->getMessage());
    $_SESSION['error'] = "حدث خطأ أثناء جلب قائمة المواد الدراسية.";
    $subjects = [];
}
?>

<div class="panel-card slide-up">
    <div class="panel-header">
        <span class="panel-title">📚 إدارة المواد الدراسية</span>
        <a href="add.php" class="btn btn-primary btn-sm">➕ إضافة مادة جديدة</a>
    </div>
    
    <div class="panel-body">
        
        <?php if (empty($subjects)): ?>
            <div style="text-align: center; padding: 40px; color: var(--text-muted);">
                <p style="font-size: 1.1rem; margin-bottom: 10px;">لا يوجد مواد دراسية مسجلة حالياً.</p>
                <a href="add.php" class="btn btn-primary btn-sm">قم بإضافة المادة الأولى الآن</a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>رمز المادة</th>
                            <th>اسم المادة الدراسية</th>
                            <th>المعلم المسؤول</th>
                            <th>العمليات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($subjects as $subject): ?>
                        <tr>
                            <td><code><?php echo htmlspecialchars($subject['subject_code']); ?></code></td>
                            <td><strong><?php echo htmlspecialchars($subject['subject_name']); ?></strong></td>
                            <td>
                                <?php if ($subject['teacher_name']): ?>
                                    <span class="badge badge-primary">👨‍🏫 <?php echo htmlspecialchars($subject['teacher_name']); ?></span>
                                <?php else: ?>
                                    <span class="badge badge-warning">لم يُسند لمعلم بعد</span>
                                <?php endif; ?>
                            </td>
                            <td class="table-actions">
                                <a href="edit.php?id=<?php echo $subject['id']; ?>" class="btn btn-secondary btn-xs">✏️ تعديل</a>
                                <a href="delete.php?id=<?php echo $subject['id']; ?>" 
                                   class="btn btn-danger btn-xs" 
                                   onclick="return confirm('هل أنت متأكد من حذف هذه المادة؟ سيؤدي ذلك لحذف درجاتها للطلاب بالكامل.');">
                                    🗑️ حذف
                                </a>
                            </td>
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
