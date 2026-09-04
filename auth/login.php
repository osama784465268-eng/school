<?php
session_start();

// تضمين ملفات الإعدادات والتحقق
require_once '../config/db.php';
require_once 'auth_helper.php';

// إذا كان المستخدم مسجل دخول بالفعل، يتم تحويله للوحة التحكم مباشرة
if (isset($_SESSION['user_id'])) {
    header("Location: " . getBaseUrl() . "index.php");
    exit;
}

$error_message = '';

// معالجة إرسال النموذج (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error_message = 'يرجى إدخال اسم المستخدم وكلمة المرور.';
    } else {
        try {
            // الاستعلام عن المستخدم من قاعدة البيانات
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username LIMIT 1");
            $stmt->execute(['username' => $username]);
            $user = $stmt->fetch();

            // التحقق من صحة كلمة المرور بـ password_verify
            if ($user && password_verify($password, $user['password'])) {
                // حفظ البيانات في الجلسة (Session)
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];
                
                // تعيين رسالة نجاح وتوجيه العميل للـ Dashboard
                $_SESSION['success'] = "أهلاً بك يا " . $user['full_name'] . "، تم تسجيل الدخول بنجاح.";
                header("Location: " . getBaseUrl() . "index.php");
                exit;
            } else {
                $error_message = 'اسم المستخدم أو كلمة المرور غير صحيحة!';
            }
        } catch (PDOException $e) {
            error_log("Error in login: " . $e->getMessage());
            $error_message = 'حدث خطأ في النظام البكند. يرجى المحاولة لاحقاً.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول - نظام إدارة المدرسة</title>
    <!-- ربط ملف التنسيق -->
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="auth-wrapper">

    <div class="auth-card">
        <div class="auth-header">
            <div class="auth-logo">🏫</div>
            <h2 class="auth-title">بوابة المدرسة الإلكترونية</h2>
            <p class="auth-subtitle">برجاء إدخال بيانات الاعتماد لتسجيل الدخول</p>
        </div>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger fade-in">
                <span class="alert-icon">✗</span>
                <span class="alert-text"><?php echo htmlspecialchars($error_message); ?></span>
            </div>
        <?php endif; ?>

        <?php showAlerts(); // عرض أي رسائل نجاح / فشل عامة مخزنة ?>

        <form action="login.php" method="POST" autocomplete="off">
            <div class="form-group">
                <label for="username" class="form-label">اسم المستخدم (المعرف)</label>
                <input type="text" 
                       id="username" 
                       name="username" 
                       class="form-control" 
                       placeholder="مثال: admin" 
                       required 
                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="password" class="form-label">كلمة المرور</label>
                <input type="password" 
                       id="password" 
                       name="password" 
                       class="form-control" 
                       placeholder="••••••••" 
                       required>
            </div>

            <button type="submit" class="btn btn-primary btn-block">تسجيل الدخول</button>
        </form>

        <div style="text-align: center; margin-top: 20px; font-size: 0.85rem; color: var(--text-muted);">
            جميع الحقوق محفوظة للمشروع الجامعي &copy; 2026
        </div>
    </div>

</body>
</html>
