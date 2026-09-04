<?php
// تضمين الهيدر للتحقق والاتصال
require_once __DIR__ . '/../includes/header.php';

// فرض صلاحيات الأدمن فقط
checkRole(['admin']);

$error_message = '';
$teacher_id = intval($_GET['id'] ?? 0);

if (!$teacher_id) {
    $_SESSION['error'] = "معرف المعلم غير صحيح.";
    header("Location: index.php");
    exit;
}

try {
    // جلب البيانات الحالية للمعلم
    $stmt = $pdo->prepare("SELECT t.*, u.username, u.full_name, u.email 
                           FROM teachers t 
                           JOIN users u ON t.user_id = u.id 
                           WHERE t.id = :id LIMIT 1");
    $stmt->execute(['id' => $teacher_id]);
    $teacher = $stmt->fetch();

    if (!$teacher) {
        $_SESSION['error'] = "المعلم المطلوب غير موجود بالنظام.";
        header("Location: index.php");
        exit;
    }
} catch (PDOException $e) {
    error_log("Error fetching teacher details: " . $e->getMessage());
    $_SESSION['error'] = "حدث خطأ أثناء محاولة جلب بيانات المعلم.";
    header("Location: index.php");
    exit;
}

// معالجة نموذج التحديث
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    // توليد بريد إلكتروني تلقائي إذا كان فارغاً
    if (empty($email)) {
        $email = $teacher['email'] ?? strtolower($username) . '.' . time() . '@school.local';
    }
    
    $specialization = trim($_POST['specialization'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $hire_date = $_POST['hire_date'] ?? '';

    // التحقق من المدخلات الإلزامية
    if (empty($username) || empty($full_name) || empty($specialization) || empty($hire_date)) {
        $error_message = 'يرجى ملء جميع الحقول التي تحتوي على النجمة (*).';
    } else {
        try {
            // التحقق من عدم استخدام البريد الإلكتروني أو اسم المستخدم من قبل مستخدم آخر
            $chk_stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE (username = :u OR email = :e) AND id != :uid");
            $chk_stmt->execute([
                'u' => $username,
                'e' => $email,
                'uid' => $teacher['user_id']
            ]);

            if ($chk_stmt->fetchColumn() > 0) {
                $error_message = 'اسم المستخدم أو البريد الإلكتروني مستخدم بالفعل لحساب آخر!';
            } else {
                // بدء معاملة SQL
                $pdo->beginTransaction();

                // 1. تحديث بيانات جدول المستخدمين
                if (!empty($password)) {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $up_user_stmt = $pdo->prepare("UPDATE users SET username = :username, password = :password, full_name = :full_name, email = :email WHERE id = :uid");
                    $up_user_stmt->execute([
                        'username' => $username,
                        'password' => $hashed_password,
                        'full_name' => $full_name,
                        'email' => $email,
                        'uid' => $teacher['user_id']
                    ]);
                } else {
                    $up_user_stmt = $pdo->prepare("UPDATE users SET username = :username, full_name = :full_name, email = :email WHERE id = :uid");
                    $up_user_stmt->execute([
                        'username' => $username,
                        'full_name' => $full_name,
                        'email' => $email,
                        'uid' => $teacher['user_id']
                    ]);
                }

                // 2. تحديث بيانات جدول المعلمين
                $up_teacher_stmt = $pdo->prepare("UPDATE teachers 
                                                   SET specialization = :specialization, phone = :phone, address = :address, hire_date = :hire_date 
                                                   WHERE id = :id");
                $up_teacher_stmt->execute([
                    'specialization' => $specialization,
                    'phone' => $phone,
                    'address' => $address,
                    'hire_date' => $hire_date,
                    'id' => $teacher_id
                ]);

                // تثبيت المعاملة
                $pdo->commit();

                $_SESSION['success'] = "تم تحديث بيانات المعلم بنجاح.";
                header("Location: index.php");
                exit;
            }
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Error updating teacher: " . $e->getMessage());
            $error_message = 'حدث خطأ في النظام أثناء تحديث البيانات: ' . $e->getMessage();
        }
    }
}
?>

<div class="panel-card slide-up">
    <div class="panel-header">
        <span class="panel-title">✏️ تعديل بيانات المعلم: <?php echo htmlspecialchars($teacher['full_name']); ?></span>
        <a href="index.php" class="btn btn-light btn-sm">🗂️ العودة لقائمة المعلمين</a>
    </div>
    
    <div class="panel-body">
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger fade-in">
                <span class="alert-icon">✗</span>
                <span class="alert-text"><?php echo htmlspecialchars($error_message); ?></span>
            </div>
        <?php endif; ?>

        <form action="edit.php?id=<?php echo $teacher_id; ?>" method="POST">
            
            <div class="info-grid" style="grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <!-- حساب تسجيل الدخول -->
                <div class="panel-card" style="margin-bottom: 0;">
                    <div class="panel-header">
                        <span class="panel-title" style="font-size: 0.95rem;">🔒 حساب تسجيل الدخول (معلومات التحكم)</span>
                    </div>
                    <div class="panel-body">
                        <div class="form-group">
                            <label for="username" class="form-label">اسم المستخدم *</label>
                            <input type="text" id="username" name="username" class="form-control" required value="<?php echo htmlspecialchars($_POST['username'] ?? $teacher['username']); ?>">
                        </div>

                        <div class="form-group">
                            <label for="password" class="form-label">كلمة المرور (اتركه فارغاً للاحتفاظ بالقديمة)</label>
                            <input type="password" id="password" name="password" class="form-control" placeholder="••••••••">
                        </div>

                        <div class="form-group">
                            <label for="full_name" class="form-label">اسم المعلم الكامل *</label>
                            <input type="text" id="full_name" name="full_name" class="form-control" required value="<?php echo htmlspecialchars($_POST['full_name'] ?? $teacher['full_name']); ?>">
                        </div>

                        <div class="form-group">
                            <label for="email" class="form-label">البريد الإلكتروني (اختياري)</label>
                            <input type="email" id="email" name="email" class="form-control" placeholder="(اختياري)" value="<?php echo htmlspecialchars($_POST['email'] ?? $teacher['email']); ?>">
                        </div>
                    </div>
                </div>

                <!-- البيانات الشخصية والتخصص -->
                <div class="panel-card" style="margin-bottom: 0;">
                    <div class="panel-header">
                        <span class="panel-title" style="font-size: 0.95rem;">📋 البيانات الشخصية والتسجيل المهني</span>
                    </div>
                    <div class="panel-body">
                        <div class="form-group">
                            <label for="specialization" class="form-label">التخصص الدراسي *</label>
                            <input type="text" id="specialization" name="specialization" class="form-control" required value="<?php echo htmlspecialchars($_POST['specialization'] ?? $teacher['specialization']); ?>">
                        </div>

                        <div class="form-group">
                            <label for="phone" class="form-label">رقم الهاتف الشخصي</label>
                            <input type="text" id="phone" name="phone" class="form-control" value="<?php echo htmlspecialchars($_POST['phone'] ?? $teacher['phone']); ?>">
                        </div>

                        <div class="form-group">
                            <label for="address" class="form-label">العنوان السكني الحالي</label>
                            <input type="text" id="address" name="address" class="form-control" value="<?php echo htmlspecialchars($_POST['address'] ?? $teacher['address']); ?>">
                        </div>

                        <div class="form-group">
                            <label for="hire_date" class="form-label">تاريخ التوظيف والتعيين *</label>
                            <input type="date" id="hire_date" name="hire_date" class="form-control" required value="<?php echo htmlspecialchars($_POST['hire_date'] ?? $teacher['hire_date']); ?>">
                        </div>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-secondary">💾 تحديث وحفظ التعديلات</button>
        </form>

    </div>
</div>

<?php 
// تضمين الفوتر
require_once __DIR__ . '/../includes/footer.php';
?>
