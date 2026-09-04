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
    
    $gender = $_POST['gender'] ?? 'male';
    $dob = $_POST['dob'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $enrollment_date = $_POST['enrollment_date'] ?? date('Y-m-d');

    // التحقق من الحقول الإلزامية
    if (empty($username) || empty($password) || empty($full_name) || empty($dob) || empty($enrollment_date)) {
        $error_message = 'يرجى ملء جميع الحقول التي تحتوي على النجمة (*).';
    } else {
        try {
            // التحقق من تكرار اسم المستخدم أو البريد الإلكتروني
            $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = :u OR email = :e");
            $check_stmt->execute(['u' => $username, 'e' => $email]);
            if ($check_stmt->fetchColumn() > 0) {
                $error_message = 'اسم المستخدم أو البريد الإلكتروني مسجل مسبقاً بالنظام!';
            } else {
                // تفعيل المعاملات الآمنة (Transaction)
                $pdo->beginTransaction();

                // 1. تشفير الباسورد وإضافته لجدول المستخدمين
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt_user = $pdo->prepare("INSERT INTO users (username, password, full_name, email, role) 
                                           VALUES (:username, :password, :full_name, :email, 'student')");
                $stmt_user->execute([
                    'username' => $username,
                    'password' => $hashed_password,
                    'full_name' => $full_name,
                    'email' => $email
                ]);
                
                // جلب معرف العميل المستحدث
                $user_id = $pdo->lastInsertId();

                // 2. إضافة تفاصيل الطالب بدلالة معرف المستخدم الجديد
                $stmt_student = $pdo->prepare("INSERT INTO students (user_id, gender, dob, phone, address, enrollment_date) 
                                               VALUES (:user_id, :gender, :dob, :phone, :address, :enrollment_date)");
                $stmt_student->execute([
                    'user_id' => $user_id,
                    'gender' => $gender,
                    'dob' => $dob,
                    'phone' => $phone,
                    'address' => $address,
                    'enrollment_date' => $enrollment_date
                ]);

                // إنهاء المعاملة وحفظ البيانات بنجاح
                $pdo->commit();

                // حفظ رسالة نجاح وتوجيه العميل
                $_SESSION['success'] = "تم إضافة الطالب بنجاح وإنشاء حسابه الشخصي.";
                header("Location: index.php");
                exit;
            }
        } catch (PDOException $e) {
            // التراجع عن التغييرات في حال حدوث خطأ
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Error adding student: " . $e->getMessage());
            $error_message = 'حدث خطأ في النظام أثناء إدخال البيانات. تفاصيل المطور: ' . $e->getMessage();
        }
    }
}
?>

<div class="panel-card slide-up">
    <div class="panel-header">
        <span class="panel-title">➕ تسجيل طالب جديد بالنظام</span>
        <a href="index.php" class="btn btn-light btn-sm">🗂️ العودة لقائمة الطلاب</a>
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
                <!-- القسم الأول: حساب الدخول -->
                <div class="panel-card" style="margin-bottom: 0;">
                    <div class="panel-header">
                        <span class="panel-title" style="font-size: 0.95rem;">🔒 بيانات حساب تسجيل الدخول للموقع</span>
                    </div>
                    <div class="panel-body">
                        <div class="form-group">
                            <label for="username" class="form-label">اسم المستخدم (المعرف) *</label>
                            <input type="text" id="username" name="username" class="form-control" placeholder="ستُستعمل لدخول الموقع" required value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                        </div>

                        <div class="form-group">
                            <label for="password" class="form-label">كلمة المرور المؤقتة *</label>
                            <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required>
                        </div>

                        <div class="form-group">
                            <label for="full_name" class="form-label">اسم الطالب الثلاثي الكامل *</label>
                            <input type="text" id="full_name" name="full_name" class="form-control" placeholder="أدخل اسم الطالب رباعياً" required value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>">
                        </div>

                        <div class="form-group">
                            <label for="email" class="form-label">البريد الإلكتروني (اختياري)</label>
                            <input type="email" id="email" name="email" class="form-control" placeholder="student@school.com (اختياري)" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                        </div>
                    </div>
                </div>

                <!-- القسم الثاني: البيانات الشخصية والالتحاق -->
                <div class="panel-card" style="margin-bottom: 0;">
                    <div class="panel-header">
                        <span class="panel-title" style="font-size: 0.95rem;">📋 البيانات الشخصية والمدرسية</span>
                    </div>
                    <div class="panel-body">
                        <div class="form-group">
                            <label for="gender" class="form-label">نوع الجنس *</label>
                            <select id="gender" name="gender" class="form-control" required>
                                <option value="male" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'male') ? 'selected' : ''; ?>>ذكر</option>
                                <option value="female" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'female') ? 'selected' : ''; ?>>أنثى</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="dob" class="form-label">تاريخ الميلاد *</label>
                            <input type="date" id="dob" name="dob" class="form-control" required value="<?php echo isset($_POST['dob']) ? htmlspecialchars($_POST['dob']) : ''; ?>">
                        </div>

                        <div class="form-group">
                            <label for="phone" class="form-label">رقم الهاتف (للتواصل أو ولي الأمر)</label>
                            <input type="text" id="phone" name="phone" class="form-control" placeholder="777XXXXXX" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                        </div>

                        <div class="form-group">
                            <label for="address" class="form-label">العنوان الحالي للطلاب</label>
                            <input type="text" id="address" name="address" class="form-control" placeholder="المحافظة - المديرية - الشارع" value="<?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?>">
                        </div>

                        <div class="form-group">
                            <label for="enrollment_date" class="form-label">تاريخ التسجيل المكتبي *</label>
                            <input type="date" id="enrollment_date" name="enrollment_date" class="form-control" required value="<?php echo isset($_POST['enrollment_date']) ? htmlspecialchars($_POST['enrollment_date']) : date('Y-m-d'); ?>">
                        </div>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">💾 تأكيد التسجيل وحفظ بيانات الطالب</button>
        </form>

    </div>
</div>

<?php 
// تضمين الفوتر
require_once __DIR__ . '/../includes/footer.php';
?>
