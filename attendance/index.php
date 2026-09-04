<?php
// تضمين الهيدر وقاعدة البيانات
require_once __DIR__ . '/../includes/header.php';

$user_role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

$error_message = '';
$success_message = '';

// جلب تاريخ التحضير المختار (الافتراضي هو اليوم)
$attendance_date = $_GET['date'] ?? date('Y-m-d');

// معالجة تسجيل الحضور في البكند (للأدمن والمعلم)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($user_role === 'admin' || $user_role === 'teacher')) {
    $attendance_data = $_POST['att'] ?? []; // مصفوفة تحتوي على [student_id => status]
    $remarks_data = $_POST['remarks'] ?? []; // مصفوفة تحتوي على [student_id => remark]
    $post_date = $_POST['attendance_date'] ?? date('Y-m-d');

    if (empty($attendance_data)) {
        $error_message = "يرجى تحديد حالة الحضور للطلاب.";
    } else {
        try {
            // استخدام المعاملات لسرعة التنفيذ وحفظ البيانات دفعة واحدة
            $pdo->beginTransaction();
            
            // استخدام استعلام ذكي يدعم التحديث التلقائي في حال كان السجل مسجلاً من قبل في نفس التاريخ
            $stmt = $pdo->prepare("INSERT INTO attendance (student_id, attendance_date, status, remarks) 
                                   VALUES (:sid, :date, :status, :remarks) 
                                   ON DUPLICATE KEY UPDATE status = :status, remarks = :remarks");

            foreach ($attendance_data as $stud_id => $status) {
                $remark = trim($remarks_data[$stud_id] ?? '');
                $stmt->execute([
                    'sid' => intval($stud_id),
                    'date' => $post_date,
                    'status' => $status,
                    'remarks' => empty($remark) ? null : $remark
                ]);
            }
            
            $pdo->commit();
            $_SESSION['success'] = "تم حفظ وتحديث سجل حضور الطلاب لتاريخ " . htmlspecialchars($post_date) . " بنجاح.";
            header("Location: index.php?date=" . urlencode($post_date));
            exit;
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Error saving attendance: " . $e->getMessage());
            $error_message = "حدث خطأ أثناء حفظ كشف التحضير: " . $e->getMessage();
        }
    }
}

// ----------------------------------------------------
// جلب البيانات بناءً على دور المستخدم
// ----------------------------------------------------
$students_attendance_list = [];

try {
    if ($user_role === 'student') {
        // الطالب يرى سجل غيابه وحضوره التفصيلي فقط
        $stmt = $pdo->prepare("SELECT a.attendance_date, a.status, a.remarks 
                               FROM attendance a 
                               JOIN students s ON a.student_id = s.id 
                               WHERE s.user_id = :uid 
                               ORDER BY a.attendance_date DESC");
        $stmt->execute(['uid' => $user_id]);
        $students_attendance_list = $stmt->fetchAll();
        
    } else {
        // الأدمن والمعلم يرى قائمة الطلاب مع حالة حضورهم في التاريخ المختار لغرض التسجيل أو التعديل
        // نجري LEFT JOIN مع جدول حضور الطالب في التاريخ المحدد
        $stmt = $pdo->prepare("SELECT s.id AS student_id, u.full_name AS student_name, 
                                      u.username, a.status, a.remarks 
                               FROM students s 
                               JOIN users u ON s.user_id = u.id 
                               LEFT JOIN attendance a ON s.id = a.student_id AND a.attendance_date = :att_date 
                               ORDER BY u.full_name ASC");
        $stmt->execute(['att_date' => $attendance_date]);
        $students_attendance_list = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    error_log("Error loading attendance view: " . $e->getMessage());
}
?>

<div class="panel-card slide-up">
    <div class="panel-header">
        <span class="panel-title">⏱️ إدارة الحضور والغياب اليومي للطلاب</span>
    </div>
    
    <div class="panel-body">
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger">
                <span class="alert-icon">✗</span>
                <span class="alert-text"><?php echo htmlspecialchars($error_message); ?></span>
            </div>
        <?php endif; ?>

        <?php if ($user_role === 'student'): ?>
            <!-- ==================== واجهة الطالب للتصفح ==================== -->
            <h3 style="margin-bottom: 15px; color: #1e1b4b;">سجل حضورك ونشاطك اليومي</h3>
            
            <?php if (empty($students_attendance_list)): ?>
                <p style="text-align: center; color: var(--text-muted); padding: 20px;">لم يتم تسجيل حضور أول غياب لك بعد.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table-custom">
                        <thead>
                            <tr>
                                <th>التاريخ المدرسي</th>
                                <th>الحالة</th>
                                <th>ملاحظات المعلم</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students_attendance_list as $row): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($row['attendance_date']); ?></strong></td>
                                <td>
                                    <?php 
                                    if ($row['status'] === 'present') echo '<span class="badge badge-success">حاضر ✓</span>';
                                    elseif ($row['status'] === 'absent') echo '<span class="badge badge-danger">غائب ✗</span>';
                                    elseif ($row['status'] === 'late') echo '<span class="badge badge-warning">متأخر ⏳</span>';
                                    elseif ($row['status'] === 'excused') echo '<span class="badge badge-primary">غائب بعذر ✉️</span>';
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($row['remarks'] ?? 'لا يوجد ملاحظات'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <!-- ==================== واجهة الأدمن والمعلم للتسجيل ==================== -->
            
            <!-- فلتر اختيار تاريخ التحضير -->
            <form action="index.php" method="GET" style="margin-bottom: 25px; background: #f1f5f9; padding: 15px; border-radius: var(--radius-md); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 15px;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <label for="date" style="font-weight: 700; font-size: 0.95rem;">تاريخ التحضير:</label>
                    <input type="date" id="date" name="date" class="form-control" style="width: auto; padding: 6px 12px;" value="<?php echo htmlspecialchars($attendance_date); ?>" onchange="this.form.submit()">
                </div>
                <div>
                    <span style="font-size: 0.85rem; color: var(--text-muted);">
                        * يمكنك تعديل أو مراجعة الحضور لأي يوم سابق عن طريق اختيار التاريخ.
                    </span>
                </div>
            </form>

            <form action="index.php" method="POST">
                <!-- تمرير التاريخ المسجل كمدخل مخفي لضمان الدقة -->
                <input type="hidden" name="attendance_date" value="<?php echo htmlspecialchars($attendance_date); ?>">

                <div class="table-responsive">
                    <table class="table-custom">
                        <thead>
                            <tr>
                                <th>اسم الطالب</th>
                                <th style="text-align: center; width: 330px;">الحالة المدرسية اليومية</th>
                                <th>ملاحظات إضافية</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students_attendance_list as $row): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($row['student_name']); ?></strong>
                                    <br>
                                    <small style="color: var(--text-muted);"><code><?php echo htmlspecialchars($row['username']); ?></code></small>
                                </td>
                                <td style="text-align: center; vertical-align: middle;">
                                    <div style="display: inline-flex; gap: 12px; justify-content: center; align-items: center;">
                                        <label style="cursor: pointer; display: flex; align-items: center; gap: 4px;">
                                            <input type="radio" name="att[<?php echo $row['student_id']; ?>]" value="present" <?php echo ($row['status'] === 'present' || is_null($row['status'])) ? 'checked' : ''; ?>>
                                            <span style="color: var(--success-color); font-weight: 700; font-size: 0.9rem;">حاضر</span>
                                        </label>
                                        
                                        <label style="cursor: pointer; display: flex; align-items: center; gap: 4px;">
                                            <input type="radio" name="att[<?php echo $row['student_id']; ?>]" value="absent" <?php echo ($row['status'] === 'absent') ? 'checked' : ''; ?>>
                                            <span style="color: var(--error-color); font-weight: 700; font-size: 0.9rem;">غائب</span>
                                        </label>
                                        
                                        <label style="cursor: pointer; display: flex; align-items: center; gap: 4px;">
                                            <input type="radio" name="att[<?php echo $row['student_id']; ?>]" value="late" <?php echo ($row['status'] === 'late') ? 'checked' : ''; ?>>
                                            <span style="color: var(--warning-color); font-weight: 700; font-size: 0.9rem;">متأخر</span>
                                        </label>
                                        
                                        <label style="cursor: pointer; display: flex; align-items: center; gap: 4px;">
                                            <input type="radio" name="att[<?php echo $row['student_id']; ?>]" value="excused" <?php echo ($row['status'] === 'excused') ? 'checked' : ''; ?>>
                                            <span style="color: var(--primary-color); font-weight: 700; font-size: 0.9rem;">بعذر</span>
                                        </label>
                                    </div>
                                </td>
                                <td>
                                    <input type="text" 
                                           name="remarks[<?php echo $row['student_id']; ?>]" 
                                           class="form-control" 
                                           style="padding: 6px 12px; font-size: 0.85rem;" 
                                           placeholder="مثل: تأخر الحافلة، شهادة طبية..." 
                                           value="<?php echo htmlspecialchars($row['remarks'] ?? ''); ?>">
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div style="margin-top: 25px; display: flex; gap: 15px;">
                    <button type="submit" class="btn btn-primary">💾 حفظ كشف التحضير والتثبيت</button>
                    <a href="<?php echo getBaseUrl(); ?>index.php" class="btn btn-light">الخروج دون حفظ</a>
                </div>
            </form>
        <?php endif; ?>
        
    </div>
</div>

<?php 
// تضمين الفوتر
require_once __DIR__ . '/../includes/footer.php';
?>
