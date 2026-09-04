<?php
// تضمين الهيدر للتحقق من الهوية والاتصال بقاعدة البيانات
require_once __DIR__ . '/../includes/header.php';

// فرض صلاحيات الأدمن فقط
checkRole(['admin']);

// جلب بارامتر البحث إن وُجد
$search = trim($_GET['q'] ?? '');

try {
    // بناء جملة الاستعلام الأساسية لربط جدول المعلمين بجدول المستخدمين
    $sql = "SELECT t.id, t.specialization, t.phone, t.address, t.hire_date, 
                   u.username, u.full_name, u.email 
            FROM teachers t
            JOIN users u ON t.user_id = u.id";
    
    $params = [];
    if ($search !== '') {
        $sql .= " WHERE u.full_name LIKE :search 
                  OR u.email LIKE :search 
                  OR u.username LIKE :search 
                  OR t.specialization LIKE :search 
                  OR t.phone LIKE :search";
        $params['search'] = "%$search%";
    }
    
    $sql .= " ORDER BY t.id DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $teachers = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log("Error fetching teachers: " . $e->getMessage());
    $_SESSION['error'] = "حدث خطأ أثناء جلب قائمة المعلمين.";
    $teachers = [];
}
?>

<div class="panel-card slide-up">
    <div class="panel-header">
        <span class="panel-title">👨‍🏫 إدارة المعلمين ونشر التخصصات</span>
        <a href="add.php" class="btn btn-primary btn-sm">➕ إضافة معلم جديد</a>
    </div>
    
    <div class="panel-body">
        
        <!-- بار البحث المتقدم -->
        <div class="table-filter-bar">
            <form action="index.php" method="GET" class="search-input-wrapper">
                <input type="text" 
                       name="q" 
                       class="form-control search-control" 
                       placeholder="ابحث بـ الاسم، التخصص، الهاتف..." 
                       value="<?php echo htmlspecialchars($search); ?>">
                <span class="search-icon">🔍</span>
            </form>
            
            <div>
                <span style="font-size: 0.9rem; color: var(--text-muted);">
                    عدد المعلمين المطابقين للبحث: <strong><?php echo count($teachers); ?> معلم</strong>
                </span>
            </div>
        </div>

        <?php if (empty($teachers)): ?>
            <div style="text-align: center; padding: 40px; color: var(--text-muted);">
                <p style="font-size: 1.1rem; margin-bottom: 10px;">لا يوجد معلمون مسجلون بالنظام حالياً.</p>
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
                            <th>التخصص</th>
                            <th>رقم الهاتف</th>
                            <th>العنوان</th>
                            <th>تاريخ التعيين</th>
                            <th>العمليات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($teachers as $teacher): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($teacher['full_name']); ?></strong></td>
                            <td><code><?php echo htmlspecialchars($teacher['username']); ?></code></td>
                            <td><?php echo htmlspecialchars($teacher['email']); ?></td>
                            <td><span class="badge badge-primary"><?php echo htmlspecialchars($teacher['specialization']); ?></span></td>
                            <td><?php echo htmlspecialchars($teacher['phone'] ?? 'غير متوفر'); ?></td>
                            <td><?php echo htmlspecialchars($teacher['address'] ?? 'غير متوفـر'); ?></td>
                            <td><?php echo htmlspecialchars($teacher['hire_date']); ?></td>
                            <td class="table-actions">
                                <a href="edit.php?id=<?php echo $teacher['id']; ?>" class="btn btn-secondary btn-xs">✏️ تعديل</a>
                                <a href="delete.php?id=<?php echo $teacher['id']; ?>" 
                                   class="btn btn-danger btn-xs" 
                                   onclick="return confirm('هل أنت متأكد من حذف هذا المعلم نهائياً من النظام؟ سيؤدي ذلك لحذف حسابه وإلغاء تبعية المواد الدراسية المرتبطة به.');">
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
