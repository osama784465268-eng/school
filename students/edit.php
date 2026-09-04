<?php
// تضمين الهيدر للتحقق والاتصال
require_once __DIR__ . '/../includes/header.php';

// فرض صلاحيات الأدمن فقط
checkRole(['admin']);

$error_message = '';
$student_id = intval($_GET['id'] ?? 0);

if (!$student_id) {
    $_SESSION['error'] = "معرف الطالب غير صحيح.";
    header("Location: index.php");
    exit;
}

try {
    // جلب البيانات الحالية للطالب
    $stmt = $pdo->prepare("SELECT s.*, u.username, u.full_name, u.email 
                           FROM students s 
                           JOIN users u ON s.user_id = u.id 
                           WHERE s.id = :id LIMIT 1");
    $stmt->execute(['id' => $student_id]);
    $student = $stmt->fetch();

    if (!$student) {
        $_SESSION['error'] = "الطالب المطلوب غير موجود بالنظام.";
        header("Location: index.php");
        exit;
    }
} catch (PDOException $e) {
    error_log("Error fetching student details: " . $e->getMessage());
    $_SESSION['error'] = "حدث خطأ في النظام أثناء محاولة جلب بيانات الطالب.";
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
        $email = $student['email'] ?? strtolower($username) . '.' . time() . '@school.local';
    }
    
    $gender = $_POST['gender'] ?? 'male';
    $dob = $_POST['dob'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $enrollment_date = $_POST['enrollment_date'] ?? '';

    // التحقق من المدخلات الإلزامية
    if (empty($username) || empty($full_name) || empty($dob) || empty($enrollment_date)) {
        $error_message = 'يرجى ملء جميع الحقول التي تحتوي على النجمة (*).';
    } else {
        try {
            // التحقق من عدم استخدام البريد الإلكتروني أو اسم المستخدم من قبل مستخدم آخر
            $chk_stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE (username = :u OR email = :e) AND id != :uid");
            $chk_stmt->execute([
                'u' => $username,
                'e' => $email,
                'uid' => $student['user_id']
            ]);

            if ($chk_stmt->fetchColumn() > 0) {
                $error_message = 'اسم المستخدم أو البريد الإلكتروني مستخدم بالفعل لحساب آخر!';
            } else {
                // بدء معاملة SQL
                $pdo->beginTransaction();

                // 1. تحديث بيانات جدول المستخدمين
                if (!empty($password)) {
                    // إذا أدخل كلمة مرور جديدة نقوم بتحديثها مشفرة
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $up_user_stmt = $pdo->prepare("UPDATE users SET username = :username, password = :password, full_name = :full_name, email = :email WHERE id = :uid");
                    $up_user_stmt->execute([
                        'username' => $username,
                        'password' => $hashed_password,
                        'full_name' => $full_name,
                        'email' => $email,
                        'uid' => $student['user_id']
                    ]);
                } else {
                    // إذا لم يدخل كلمة مرور، نحدث بقية البيانات فقط
                    $up_user_stmt = $pdo->prepare("UPDATE users SET username = :username, full_name = :full_name, email = :email WHERE id = :uid");
                    $up_user_stmt->execute([
                        'username' => $username,
                        'full_name' => $full_name,
                        'email' => $email,
                        'uid' => $student['user_id']
                    ]);
                }

                // 2. تحديث بيانات جدول الطلاب
                $up_student_stmt = $pdo->prepare("UPDATE students 
                                                   SET gender = :gender, dob = :dob, phone = :phone, address = :address, enrollment_date = :enrollment_date 
                                                   WHERE id = :id");
                $up_student_stmt->execute([
                    'gender' => $gender,
                    'dob' => $dob,
                    'phone' => $phone,
                    'address' => $address,
                    'enrollment_date' => $enrollment_date,
                    'id' => $student_id
                ]);

                // تثبيت المعاملة
                $pdo->commit();

                $_SESSION['success'] = "تم تحديث بيانات الطالب بنجاح.";
                header("Location: index.php");
                exit;
            }
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Error updating student: " . $e->getMessage());
            $error_message = 'حدث خطأ في قاعدة البيانات أثناء تحديث الملف. تفاصيل: ' . $e->getMessage();
        }
    }
}
?>

<div class="panel-card slide-up">
    <div class="panel-header">
        <span class="panel-title">✏️ تعديل بيانات الطالب: <?php echo htmlspecialchars($student['full_name']); ?></span>
        <a href="index.php" class="btn btn-light btn-sm">🗂️ العودة لقائمة الطلاب</a>
    </div>
    
    <div class="panel-body">
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger fade-in">
                <span class="alert-icon">✗</span>
                <span class="alert-text"><?php echo htmlspecialchars($error_message); ?></span>
            </div>
        <?php endif; ?>

        <form action="edit.php?id=<?php echo $student_id; ?>" method="POST">
            
            <div class="info-grid" style="grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <!-- حساب تسجيل الدخول -->
                <div class="panel-card" style="margin-bottom: 0;">
                    <div class="panel-header">
                        <span class="panel-title" style="font-size: 0.95rem;">🔒 حساب تسجيل الدخول (معلومات التحكم)</span>
                    </div>
                    <div class="panel-body">
                        <div class="form-group">
                            <label for="username" class="form-label">اسم المستخدم *</label>
                            <input type="text" id="username" name="username" class="form-control" required value="<?php echo htmlspecialchars($_POST['username'] ?? $student['username']); ?>">
                        </div>

                        <div class="form-group">
                            <label for="password" class="form-label">كلمة المرور (اتركه فارغاً للاحتفاظ بالقديمة)</label>
                            <input type="password" id="password" name="password" class="form-control" placeholder="••••••••">
                        </div>

                        <div class="form-group">
                            <label for="full_name" class="form-label">اسم الطالب الرباعي الكامل *</label>
                            <input type="text" id="full_name" name="full_name" class="form-control" required value="<?php echo htmlspecialchars($_POST['full_name'] ?? $student['full_name']); ?>">
                        </div>

                        <div class="form-group">
                            <label for="email" class="form-label">البريد الإلكتروني (اختياري)</label>
                            <input type="email" id="email" name="email" class="form-control" placeholder="(اختياري)" value="<?php echo htmlspecialchars($_POST['email'] ?? $student['email']); ?>">
                        </div>
                    </div>
                </div>

                <!-- البيانات الشخصية والمدرسية -->
                <div class="panel-card" style="margin-bottom: 0;">
                    <div class="panel-header">
                        <span class="panel-title" style="font-size: 0.95rem;">📋 البيانات الشخصية والمدرسية</span>
                    </div>
                    <div class="panel-body">
                        <div class="form-group">
                            <label for="gender" class="form-label">نوع الجنس *</label>
                            <select id="gender" name="gender" class="form-control" required>
                                <option value="male" <?php echo (($_POST['gender'] ?? $student['gender']) === 'male') ? 'selected' : ''; ?>>ذكر</option>
                                <option value="female" <?php echo (($_POST['gender'] ?? $student['gender']) === 'female') ? 'selected' : ''; ?>>أنثى</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="dob" class="form-label">تاريخ الميلاد *</label>
                            <input type="date" id="dob" name="dob" class="form-control" required value="<?php echo htmlspecialchars($_POST['dob'] ?? $student['dob']); ?>">
                        </div>

                        <div class="form-group">
                            <label for="phone" class="form-label">رقم الهاتف (للتواصل أو ولي الأمر)</label>
                            <input type="text" id="phone" name="phone" class="form-control" value="<?php echo htmlspecialchars($_POST['phone'] ?? $student['phone']); ?>">
                        </div>

                        <div class="form-group">
                            <label for="address" class="form-label">العنوان الحالي للطلاب</label>
                            <input type="text" id="address" name="address" class="form-control" value="<?php echo htmlspecialchars($_POST['address'] ?? $student['address']); ?>">
                        </div>

                        <div class="form-group">
                            <label for="enrollment_date" class="form-label">تاريخ التسجيل المكتبي *</label>
                            <input type="date" id="enrollment_date" name="enrollment_date" class="form-control" required value="<?php echo htmlspecialchars($_POST['enrollment_date'] ?? $student['enrollment_date']); ?>">
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
