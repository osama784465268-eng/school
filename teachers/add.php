<?php
// تضمين الهيدر لتطبيق التحقق من الهوية والاتصال بقاعدة البيانات
require_once __DIR__ . '/../includes/header.php';

// فرض صلاحيات الأدمن فقط
checkRole(['admin']);

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // جلب المدخلات وتطهيرها
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    // توليد بريد إلكتروني تلقائي إذا كان فارغاً
    if (empty($email)) {
        $email = strtolower($username) . '.' . time() . '@school.local';
    }
    
    $specialization = trim($_POST['specialization'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $hire_date = $_POST['hire_date'] ?? date('Y-m-d');

    // التحقق من الحقول الإلزامية
    if (empty($username) || empty($password) || empty($full_name) || empty($specialization) || empty($hire_date)) {
        $error_message = 'يرجى ملء جميع الحقول التي تحتوي على النجمة (*).';
    } else {
        try {
            // التحقق من تكرار اسم المستخدم أو البريد الإلكتروني
            $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = :u OR email = :e");
            $check_stmt->execute(['u' => $username, 'e' => $email]);
            if ($check_stmt->fetchColumn() > 0) {
                $error_message = 'اسم المستخدم أو البريد الإلكتروني مسجل بالفعل لمستخدم آخر!';
            } else {
                // تفعيل المعاملات الآمنة (Transaction)
                $pdo->beginTransaction();

                // 1. تشفير الباسورد وإضافته لجدول المستخدمين بدور teacher
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt_user = $pdo->prepare("INSERT INTO users (username, password, full_name, email, role) 
                                           VALUES (:username, :password, :full_name, :email, 'teacher')");
                $stmt_user->execute([
                    'username' => $username,
                    'password' => $hashed_password,
                    'full_name' => $full_name,
                    'email' => $email
                ]);
                
                // جلب معرف العميل المستحدث
                $user_id = $pdo->lastInsertId();

                // 2. إضافة تفاصيل المعلم
                $stmt_teacher = $pdo->prepare("INSERT INTO teachers (user_id, specialization, phone, address, hire_date) 
                                               VALUES (:user_id, :specialization, :phone, :address, :hire_date)");
                $stmt_teacher->execute([
                    'user_id' => $user_id,
                    'specialization' => $specialization,
                    'phone' => $phone,
                    'address' => $address,
                    'hire_date' => $hire_date
                ]);

                // إنهاء المعاملة وحفظ البيانات
                $pdo->commit();

                // حفظ رسالة نجاح وتوجيه العميل
                $_SESSION['success'] = "تم تسجيل المعلم بنجاح وإنشاء حسابه التعليمي.";
                header("Location: index.php");
                exit;
            }
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Error adding teacher: " . $e->getMessage());
            $error_message = 'حدث خطأ في النظام أثناء إدخال البيانات: ' . $e->getMessage();
        }
    }
}
?>

<div class="panel-card slide-up">
    <div class="panel-header">
        <span class="panel-title">➕ تسجيل معلم جديد بالنظام</span>
        <a href="index.php" class="btn btn-light btn-sm">🗂️ العودة لقائمة المعلمين</a>
    </div>
    
    <div class="panel-body">
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger fade-in">
                <span class="alert-icon">✗</span>
                <span class="alert-text"><?php echo htmlspecialchars($error_message); ?></span>
            </div>
        <?php endif; ?>

        <form action="add.php" method="POST">
            
            <div class="info-grid" style="grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <!-- حساب تسجيل الدخول -->
                <div class="panel-card" style="margin-bottom: 0;">
                    <div class="panel-header">
                        <span class="panel-title" style="font-size: 0.95rem;">🔒 حساب تسجيل الدخول للموقع</span>
                    </div>
                    <div class="panel-body">
                        <div class="form-group">
                            <label for="username" class="form-label">اسم المستخدم (المعرف) *</label>
                            <input type="text" id="username" name="username" class="form-control" placeholder="مثال: ahmad_ali" required value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                        </div>

                        <div class="form-group">
                            <label for="password" class="form-label">كلمة المرور المؤقتة *</label>
                            <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required>
                        </div>

                        <div class="form-group">
                            <label for="full_name" class="form-label">اسم المعلم الكامل *</label>
                            <input type="text" id="full_name" name="full_name" class="form-control" placeholder="أدخل اسم المعلم ثلاثياً" required value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>">
                        </div>

                        <div class="form-group">
                            <label for="email" class="form-label">البريد الإلكتروني (اختياري)</label>
                            <input type="email" id="email" name="email" class="form-control" placeholder="teacher@school.com (اختياري)" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
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
                            <input type="text" id="specialization" name="specialization" class="form-control" placeholder="مثال: الرياضيات، الفيزياء، كيمياء" required value="<?php echo isset($_POST['specialization']) ? htmlspecialchars($_POST['specialization']) : ''; ?>">
                        </div>

                        <div class="form-group">
                            <label for="phone" class="form-label">رقم الهاتف الشخصي</label>
                            <input type="text" id="phone" name="phone" class="form-control" placeholder="777XXXXXX" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                        </div>

                        <div class="form-group">
                            <label for="address" class="form-label">العنوان السكني الحالي</label>
                            <input type="text" id="address" name="address" class="form-control" placeholder="المحافظة - المدينة" value="<?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?>">
                        </div>

                        <div class="form-group">
                            <label for="hire_date" class="form-label">تاريخ التوظيف والتعيين *</label>
                            <input type="date" id="hire_date" name="hire_date" class="form-control" required value="<?php echo isset($_POST['hire_date']) ? htmlspecialchars($_POST['hire_date']) : date('Y-m-d'); ?>">
                        </div>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Save 💾 حفظ بيانات المعلم</button>
        </form>

    </div>
</div>

<?php 
// تضمين الفوتر
require_once __DIR__ . '/../includes/footer.php';
?>
