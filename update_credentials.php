<?php
require_once 'config/db.php';

$new_username = 'yura';
$new_password_plain = '123';
$new_password_hash = password_hash($new_password_plain, PASSWORD_DEFAULT);

echo "<!DOCTYPE html><html lang='ar'><head><meta charset='UTF-8'>
<style>
body{font-family:Arial,sans-serif;direction:rtl;text-align:right;padding:30px;background:#f5f5f5;}
.box{background:#fff;padding:20px;border-radius:10px;max-width:600px;margin:auto;box-shadow:0 2px 10px rgba(0,0,0,.1);}
table{width:100%;border-collapse:collapse;margin-top:15px;}
th,td{border:1px solid #ddd;padding:10px;text-align:right;}
th{background:#4CAF50;color:#fff;}
.ok{color:green;font-weight:bold;font-size:1.2em;}
.err{color:red;font-weight:bold;}
</style></head><body><div class='box'>";

try {
    // 1) Check if any user exists
    $count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

    if ($count == 0) {
        // No users exist - INSERT new admin
        $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, email, role) 
                               VALUES (:u, :p, 'مدير النظام', 'admin@school.com', 'admin')");
        $stmt->execute(['u' => $new_username, 'p' => $new_password_hash]);
        echo "<p class='ok'>✅ تم إنشاء المستخدم بنجاح!</p>";
    } else {
        // Update the first admin user found, or update user with id=1
        $stmt = $pdo->prepare("UPDATE users SET username = :u, password = :p, full_name = 'مدير النظام' WHERE role = 'admin' LIMIT 1");
        $stmt->execute(['u' => $new_username, 'p' => $new_password_hash]);

        if ($stmt->rowCount() == 0) {
            // Try updating id=1
            $stmt2 = $pdo->prepare("UPDATE users SET username = :u, password = :p WHERE id = 1");
            $stmt2->execute(['u' => $new_username, 'p' => $new_password_hash]);
        }
        echo "<p class='ok'>✅ تم تحديث بيانات الدخول بنجاح!</p>";
    }

    echo "<hr>
    <h3>📋 بيانات الدخول الجديدة:</h3>
    <table>
      <tr><th>الحقل</th><th>القيمة</th></tr>
      <tr><td>اسم المستخدم</td><td><strong style='color:#2196F3;font-size:1.2em'>$new_username</strong></td></tr>
      <tr><td>كلمة المرور</td><td><strong style='color:#2196F3;font-size:1.2em'>$new_password_plain</strong></td></tr>
      <tr><td>الدور</td><td>admin (مدير)</td></tr>
    </table>";

    // Show all users
    $all = $pdo->query("SELECT id, username, full_name, role FROM users")->fetchAll();
    echo "<h3>👥 جميع المستخدمين في قاعدة البيانات:</h3><table>
    <tr><th>ID</th><th>اسم المستخدم</th><th>الاسم الكامل</th><th>الدور</th></tr>";
    foreach ($all as $u) {
        echo "<tr><td>{$u['id']}</td><td>{$u['username']}</td><td>{$u['full_name']}</td><td>{$u['role']}</td></tr>";
    }
    echo "</table>";

    echo "<hr><p style='text-align:center'>
    <a href='auth/login.php' style='background:#4CAF50;color:#fff;padding:12px 30px;border-radius:8px;text-decoration:none;font-size:1.1em'>
    🔐 الذهاب إلى صفحة الدخول</a></p>";

} catch (PDOException $e) {
    echo "<p class='err'>❌ خطأ: " . $e->getMessage() . "</p>";
    echo "<p>تأكد من أن MySQL يعمل في XAMPP وأن قاعدة البيانات <strong>school_db</strong> موجودة.</p>";
}

echo "</div></body></html>";
?>
