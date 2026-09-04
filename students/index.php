<?php
// عرض الأخطاء للتشخيص (يمكنك إزالتها بعد التصحيح)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// تضمين الهيدر
require_once __DIR__ . '/../includes/header.php';

// التحقق من صلاحية الأدمن (بدلاً من الدالة الغير معرفة)
if ($user_role !== 'admin') {
    header("Location: " . getBaseUrl() . "index.php");
    exit;
}

// جلب بارامتر البحث
$search = trim($_GET['q'] ?? '');

try {
    $sql = "SELECT s.id, s.gender, s.dob, s.phone, s.address, s.enrollment_date, 
                   u.username, u.full_name, u.email 
            FROM students s
            JOIN users u ON s.user_id = u.id";
    
    $params = [];
    if ($search !== '') {
        $sql .= " WHERE u.full_name LIKE :search 
                  OR u.email LIKE :search 
                  OR u.username LIKE :search 
                  OR s.phone LIKE :search";
        $params['search'] = "%$search%";
    }
    
    $sql .= " ORDER BY s.id DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $students = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log("Error fetching students: " . $e->getMessage());
    $_SESSION['error'] = "حدث خطأ أثناء جلب قائمة الطلاب.";
    $students = [];
}
?>

<div class="panel-card slide-up">
    <div class="panel-header">
        <span class="panel-title">🎓 إدارة الطلاب ونظام البحث</span>
        <a href="add.php" class="btn btn-primary btn-sm">➕ إضافة طالب جديد</a>
    </div>
    
    <div class="panel-body">
        
        <!-- بار البحث -->
        <div class="table-filter-bar">
            <form action="index.php" method="GET" class="search-input-wrapper">
                <input type="text" 
                       name="q" 
                       class="form-control search-control" 
                       placeholder="ابحث بـ الاسم، البريد، الهاتف..." 
                       value="<?php echo htmlspecialchars($search); ?>">
                <span class="search-icon">🔍</span>
            </form>
            
            <div>
                <span style="font-size: 0.9rem; color: var(--text-muted);">
                    عدد الطلاب المطابقين للبحث: <strong><?php echo count($students); ?> طالب</strong>
                </span>
            </div>
        </div>

        <?php if (empty($students)): ?>
            <div style="text-align: center; padding: 40px; color: var(--text-muted);">
                <p style="font-size: 1.1rem; margin-bottom: 10px;">لا يوجد طلاب مسجلين بالنظام حالياً.</p>
                <?php if ($search !== ''): ?>
                    <a href="index.php" class="btn btn-light btn-sm">إعادة تهيئة البحث</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>الاسم الكامل</th>
                            <th>المعرف (Username)</th>
                            <th>البريد الإلكتروني</th>
                            <th>الجنس</th>
                            <th>تاريخ الميلاد</th>
                            <th>رقم الهاتف</th>
                            <th>تاريخ الالتحاق</th>
                            <th>العمليات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($student['full_name']); ?></strong></td>
                            <td><code><?php echo htmlspecialchars($student['username']); ?></code></td>
                            <td><?php echo htmlspecialchars($student['email']); ?></td>
                            <td><?php echo ($student['gender'] === 'male') ? 'ذكر' : 'أنثى'; ?></td>
                            <td><?php echo htmlspecialchars($student['dob']); ?></td>
                            <td><?php echo htmlspecialchars($student['phone'] ?? 'غير متوفر'); ?></td>
                            <td><?php echo htmlspecialchars($student['enrollment_date']); ?></td>
                            <td class="table-actions">
                                <a href="edit.php?id=<?php echo $student['id']; ?>" class="btn btn-secondary btn-xs">✏️ تعديل</a>
                                <a href="delete.php?id=<?php echo $student['id']; ?>" 
                                   class="btn btn-danger btn-xs" 
                                   onclick="return confirm('هل أنت متأكد من حذف هذا الطالب نهائياً من النظام؟ سيؤدي ذلك لحذف درجاته وحسابه بالكامل.');">
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
require_once __DIR__ . '/../includes/footer.php';
?>