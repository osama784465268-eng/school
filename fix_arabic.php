<?php
// إصلاح شامل: إعادة إدخال البيانات بترميز UTF-8 الصحيح
require_once 'config/db.php';

// تأكيد الاتصال بـ UTF-8
$pdo->exec("SET NAMES 'utf8mb4'");
$pdo->exec("SET CHARACTER SET utf8mb4");

$errors = [];
$success = [];

try {
    // ============================================
    // 1. إصلاح أسماء المستخدمين (users)
    // ============================================
    $users_fix = [
        // [id, full_name]
        [1, 'مدير النظام العام'],
        [2, 'أ. أحمد علي المصعبي'],
        [3, 'أ. فاطمة عمر عثمان'],
        [4, 'خالد محمد اليزيدي'],
        [5, 'آلاء فضل الحميري'],
    ];
    $stmt = $pdo->prepare("UPDATE users SET full_name = ? WHERE id = ?");
    foreach ($users_fix as $u) {
        $stmt->execute([$u[1], $u[0]]);
    }
    $success[] = "✅ تم إصلاح أسماء المستخدمين";

    // ============================================
    // 2. إصلاح أسماء المواد الدراسية (subjects)
    // ============================================
    $subjects_fix = [
        [1, 'الرياضيات العامة'],
        [2, 'الجبر والهندسة'],
        [3, 'اللغة الإنجليزية الأساسية'],
    ];
    $stmt2 = $pdo->prepare("UPDATE subjects SET subject_name = ? WHERE id = ?");
    foreach ($subjects_fix as $s) {
        $stmt2->execute([$s[1], $s[0]]);
    }
    $success[] = "✅ تم إصلاح أسماء المواد الدراسية";

    // ============================================
    // 3. إصلاح تخصصات المعلمين (teachers)
    // ============================================
    $teachers_fix = [
        [1, 'الرياضيات'],
        [2, 'اللغة الإنجليزية'],
    ];
    $stmt3 = $pdo->prepare("UPDATE teachers SET specialization = ? WHERE id = ?");
    foreach ($teachers_fix as $t) {
        $stmt3->execute([$t[1], $t[0]]);
    }
    $success[] = "✅ تم إصلاح تخصصات المعلمين";

    // ============================================
    // 4. إصلاح عناوين وهواتف الطلاب والمعلمين
    // ============================================
    $students_fix = [
        [1, 'صنعاء - الأصبحي'],
        [2, 'صنعاء - شيرتون'],
    ];
    $stmt4 = $pdo->prepare("UPDATE students SET address = ? WHERE id = ?");
    foreach ($students_fix as $st) {
        $stmt4->execute([$st[1], $st[0]]);
    }
    $success[] = "✅ تم إصلاح عناوين الطلاب";

    $teachers_addr = [
        [1, 'صنعاء - الدائري'],
        [2, 'صنعاء - حدة'],
    ];
    $stmt5 = $pdo->prepare("UPDATE teachers SET address = ? WHERE id = ?");
    foreach ($teachers_addr as $ta) {
        $stmt5->execute([$ta[1], $ta[0]]);
    }
    $success[] = "✅ تم إصلاح عناوين المعلمين";

    // ============================================
    // 5. إصلاح بيانات الحضور والغياب
    // ============================================
    $pdo->exec("UPDATE attendance SET remarks = 'حضور بالوقت المحدد' WHERE id IN (1,2)");
    $pdo->exec("UPDATE attendance SET remarks = 'غائب بدون عذر' WHERE id = 3");
    $pdo->exec("UPDATE attendance SET remarks = 'تأخر 15 دقيقة' WHERE id = 4");
    $success[] = "✅ تم إصلاح ملاحظات الحضور والغياب";

} catch (PDOException $e) {
    $errors[] = "❌ خطأ: " . $e->getMessage();
}

// عرض النتائج
echo "<!DOCTYPE html><html lang='ar'><head><meta charset='UTF-8'>
<style>
body{font-family:Arial,sans-serif;direction:rtl;padding:30px;background:#f0f4f8;}
.box{background:#fff;padding:25px;border-radius:12px;max-width:650px;margin:auto;box-shadow:0 4px 15px rgba(0,0,0,.1);}
h2{color:#1a73e8;border-bottom:2px solid #1a73e8;padding-bottom:10px;}
.ok{color:#2e7d32;padding:8px 12px;background:#e8f5e9;border-radius:6px;margin:6px 0;display:block;}
.err{color:#c62828;padding:8px 12px;background:#ffebee;border-radius:6px;margin:6px 0;display:block;}
.btn{display:inline-block;margin-top:20px;background:#1a73e8;color:#fff;padding:12px 30px;border-radius:8px;text-decoration:none;font-size:1em;}
</style></head><body><div class='box'>";

echo "<h2>🔧 إصلاح ترميز البيانات العربية</h2>";

foreach ($success as $s) echo "<span class='ok'>$s</span>";
foreach ($errors as $e) echo "<span class='err'>$e</span>";

if (empty($errors)) {
    echo "<br><div style='background:#e3f2fd;padding:15px;border-radius:8px;border-right:4px solid #1a73e8;'>
    <strong>🎉 تم إصلاح جميع البيانات بنجاح!</strong><br>
    البيانات العربية الآن ستظهر بشكل صحيح في النظام.
    </div>
    <a href='auth/login.php' class='btn'>🔐 الذهاب لصفحة الدخول</a>";
}

echo "</div></body></html>";
?>
