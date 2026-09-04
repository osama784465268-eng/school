<?php
/**
 * ====================================================
 * إعداد قاعدة البيانات الشامل - نظام إدارة المدرسة
 * بيانات طلاب ومعلمين عربية كاملة ودقيقة
 * ====================================================
 */
$db_host = getenv('MYSQLHOST') ?: getenv('MYSQL_HOST') ?: getenv('DB_HOST') ?: 'mysql.railway.internal';
$db_port = getenv('MYSQLPORT') ?: getenv('MYSQL_PORT') ?: getenv('DB_PORT') ?: '3306';
$db_user = getenv('MYSQLUSER') ?: getenv('MYSQL_USER') ?: getenv('DB_USER') ?: 'root';
$db_pass = getenv('MYSQLPASSWORD') !== false ? getenv('MYSQLPASSWORD') : (getenv('MYSQL_PASSWORD') !== false ? getenv('MYSQL_PASSWORD') : (getenv('DB_PASS') !== false ? getenv('DB_PASS') : 'mOMCCLFCXnBMBlHejlEtQWdmwyxCMnkH'));
$db_name = getenv('MYSQLDATABASE') ?: getenv('MYSQL_DATABASE') ?: getenv('DB_NAME') ?: 'railway';

$db_url = getenv('MYSQL_URL') ?: getenv('DATABASE_URL');
if ($db_url) {
    $parsed_url = parse_url($db_url);
    if ($parsed_url) {
        $db_host = $parsed_url['host'] ?? $db_host;
        $db_port = $parsed_url['port'] ?? $db_port;
        $db_user = $parsed_url['user'] ?? $db_user;
        $db_pass = $parsed_url['pass'] ?? $db_pass;
        $db_name = ltrim($parsed_url['path'] ?? '', '/') ?: $db_name;
    }
}

if (!defined('DB_HOST')) define('DB_HOST', $db_host);
if (!defined('DB_PORT')) define('DB_PORT', $db_port);
if (!defined('DB_USER')) define('DB_USER', $db_user);
if (!defined('DB_PASS')) define('DB_PASS', $db_pass);
if (!defined('DB_NAME')) define('DB_NAME', $db_name);

header('Content-Type: text/html; charset=UTF-8');
echo '<!DOCTYPE html><html lang="ar"><head><meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>إعداد قاعدة البيانات</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:Arial,sans-serif;direction:rtl;background:#f0f4f8;padding:20px}
.wrap{max-width:960px;margin:auto;background:#fff;border-radius:14px;padding:28px;box-shadow:0 4px 20px rgba(0,0,0,.1)}
h2{color:#1565c0;border-bottom:3px solid #1565c0;padding-bottom:10px;margin-bottom:18px}
h3{color:#333;margin:22px 0 10px;font-size:1.05em}
.step{padding:9px 14px;border-radius:7px;margin:6px 0;font-size:.92em}
.ok{background:#e8f5e9;color:#2e7d32;border-right:4px solid #43a047}
.err{background:#ffebee;color:#c62828;border-right:4px solid #e53935}
.info{background:#e3f2fd;color:#1565c0;border-right:4px solid #1e88e5;margin-top:14px}
table{width:100%;border-collapse:collapse;margin:8px 0;font-size:.88em}
th{background:#1565c0;color:#fff;padding:9px 10px;text-align:right;font-weight:600}
td{border:1px solid #e0e0e0;padding:8px 10px;text-align:right}
tr:nth-child(even) td{background:#f9f9f9}
.badge{display:inline-block;padding:2px 10px;border-radius:20px;font-size:.8em;font-weight:bold}
.male{background:#e3f2fd;color:#1565c0}
.female{background:#fce4ec;color:#880e4f}
.btn{display:inline-block;margin-top:22px;background:#1565c0;color:#fff;padding:13px 34px;border-radius:9px;text-decoration:none;font-size:1em;font-weight:bold}
.btn:hover{background:#0d47a1}
.grid{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin:10px 0}
@media(max-width:600px){.grid{grid-template-columns:1fr}}
</style></head><body><div class="wrap">
<h2>🏫 إعداد نظام إدارة المدرسة — قاعدة البيانات الشاملة</h2>';

$steps = [];

try {
    $pdo = new PDO(
        "mysql:host=".DB_HOST.";port=".DB_PORT.";charset=utf8mb4",
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
         PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
         PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"]
    );
    $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `".DB_NAME."` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `".DB_NAME."`");
    $steps[] = ['ok','✅ تم الاتصال وإنشاء قاعدة البيانات'];

    // حذف الجداول القديمة
    $pdo->exec("SET FOREIGN_KEY_CHECKS=0");
    foreach(['attendance','grades','subjects','students','teachers','users'] as $t){
        $pdo->exec("DROP TABLE IF EXISTS `$t`");
    }
    $pdo->exec("SET FOREIGN_KEY_CHECKS=1");
    $steps[] = ['ok','✅ تم حذف الجداول القديمة'];

    // إنشاء الجداول
    $pdo->exec("CREATE TABLE `users`(
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `username` VARCHAR(50) NOT NULL UNIQUE,
        `password` VARCHAR(255) NOT NULL,
        `full_name` VARCHAR(100) NOT NULL,
        `email` VARCHAR(100) NOT NULL UNIQUE,
        `role` ENUM('admin','teacher','student') NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE `teachers`(
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL UNIQUE,
        `specialization` VARCHAR(100) NOT NULL,
        `phone` VARCHAR(20) DEFAULT NULL,
        `address` VARCHAR(255) DEFAULT NULL,
        `hire_date` DATE NOT NULL,
        FOREIGN KEY(`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE `students`(
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL UNIQUE,
        `gender` ENUM('male','female') NOT NULL,
        `dob` DATE NOT NULL,
        `phone` VARCHAR(20) DEFAULT NULL,
        `address` VARCHAR(255) DEFAULT NULL,
        `enrollment_date` DATE NOT NULL,
        FOREIGN KEY(`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE `subjects`(
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `subject_name` VARCHAR(100) NOT NULL UNIQUE,
        `subject_code` VARCHAR(20) NOT NULL UNIQUE,
        `teacher_id` INT DEFAULT NULL,
        FOREIGN KEY(`teacher_id`) REFERENCES `teachers`(`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE `grades`(
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `student_id` INT NOT NULL,
        `subject_id` INT NOT NULL,
        `exam_type` VARCHAR(50) NOT NULL,
        `grade` DECIMAL(5,2) NOT NULL,
        `grade_date` DATE NOT NULL,
        FOREIGN KEY(`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
        FOREIGN KEY(`subject_id`) REFERENCES `subjects`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE `attendance`(
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `student_id` INT NOT NULL,
        `attendance_date` DATE NOT NULL,
        `status` ENUM('present','absent','late','excused') NOT NULL,
        `remarks` VARCHAR(255) DEFAULT NULL,
        FOREIGN KEY(`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
        UNIQUE KEY `unique_sd`(`student_id`,`attendance_date`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $steps[] = ['ok','✅ تم إنشاء جميع الجداول بترميز UTF-8'];

    // ═══════════════════════════════════════════
    // بيانات المستخدمين (مدير + معلمون + طلاب)
    // ═══════════════════════════════════════════
    $pass = password_hash('123', PASSWORD_DEFAULT);

    // [username, password, full_name, email, role]
    $users = [
        // المدير
        ['yura',       $pass, 'مدير النظام',                 'admin@school.local',      'admin'],
        // المعلمون
        ['t_ahmed',    $pass, 'أ. أحمد علي المصعبي',          'ahmed@school.local',      'teacher'],
        ['t_fatima',   $pass, 'أ. فاطمة عمر البلوشي',         'fatima@school.local',     'teacher'],
        ['t_nasser',   $pass, 'أ. ناصر يوسف الغامدي',         'nasser@school.local',     'teacher'],
        ['t_huda',     $pass, 'أ. هدى سعيد الزهراني',         'huda@school.local',       'teacher'],
        // الطلاب الذكور
        ['s_khaled',   $pass, 'خالد محمد اليزيدي',            'khaled@school.local',     'student'],
        ['s_hassan',   $pass, 'حسن عبدالله القحطاني',          'hassan@school.local',     'student'],
        ['s_omar',     $pass, 'عمر سعيد الشهري',               'omar@school.local',       'student'],
        ['s_ali',      $pass, 'علي صالح الحربي',               'ali@school.local',        'student'],
        ['s_ibrahim',  $pass, 'إبراهيم عبدالرحمن الدوسري',     'ibrahim@school.local',    'student'],
        ['s_yasser',   $pass, 'ياسر عادل النجار',              'yasser@school.local',     'student'],
        ['s_tariq',    $pass, 'طارق محمود عسيري',               'tariq@school.local',      'student'],
        ['s_faisal',   $pass, 'فيصل راشد العمري',              'faisal@school.local',     'student'],
        // الطالبات
        ['s_alaa',     $pass, 'آلاء فضل الحميري',              'alaa@school.local',       'student'],
        ['s_mariam',   $pass, 'مريم سعيد الشهراني',            'mariam@school.local',     'student'],
        ['s_nora',     $pass, 'نورة أحمد القرني',               'nora@school.local',       'student'],
        ['s_sara',     $pass, 'سارة يوسف المالكي',              'sara@school.local',       'student'],
        ['s_lina',     $pass, 'لينا عبدالعزيز العتيبي',         'lina@school.local',       'student'],
        ['s_hana',     $pass, 'هناء صالح الجهني',               'hana@school.local',       'student'],
        ['s_reem',     $pass, 'ريم محمد الثقفي',                'reem@school.local',       'student'],
        ['s_dina',     $pass, 'دينا خالد الزبيدي',              'dina@school.local',       'student'],
    ];

    $su = $pdo->prepare("INSERT INTO users(username,password,full_name,email,role) VALUES(?,?,?,?,?)");
    foreach($users as $u) $su->execute($u);
    $steps[] = ['ok','✅ تم إدخال '.count($users).' مستخدماً'];

    // ═══════════════════════════════════════════
    // بيانات المعلمين (user_id: 2,3,4,5)
    // ═══════════════════════════════════════════
    $teachers_data = [
        // [user_id, specialization, phone, address, hire_date]
        [2, 'الرياضيات والإحصاء',         '777-112-233', 'صنعاء - الدائري',   '2022-09-01'],
        [3, 'اللغة الإنجليزية وآدابها',    '777-445-566', 'صنعاء - حدة',       '2023-01-15'],
        [4, 'الفيزياء والكيمياء',           '777-889-900', 'صنعاء - الجراف',    '2023-09-01'],
        [5, 'اللغة العربية والتربية الإسلامية', '777-334-455', 'صنعاء - شميلة', '2024-01-10'],
    ];
    $st = $pdo->prepare("INSERT INTO teachers(user_id,specialization,phone,address,hire_date) VALUES(?,?,?,?,?)");
    foreach($teachers_data as $t) $st->execute($t);
    $steps[] = ['ok','✅ تم إدخال '.count($teachers_data).' معلمين'];

    // ═══════════════════════════════════════════
    // بيانات الطلاب (user_id: 6 إلى 21)
    // ═══════════════════════════════════════════
    // [user_id, gender, dob, phone, address, enrollment_date]
    $students_data = [
        // الطلاب الذكور
        [6,  'male',   '2010-03-15', '771-234-567', 'صنعاء - الأصبحي',       '2025-09-01'],
        [7,  'male',   '2010-07-22', '772-345-678', 'صنعاء - العروضي',       '2025-09-01'],
        [8,  'male',   '2011-01-10', '773-456-789', 'صنعاء - القيادة',       '2025-09-01'],
        [9,  'male',   '2010-11-05', '774-567-890', 'صنعاء - المدينة',       '2025-09-01'],
        [10, 'male',   '2011-04-18', '775-678-901', 'صنعاء - بيت بوس',       '2025-09-01'],
        [11, 'male',   '2010-09-25', '776-789-012', 'صنعاء - سبأ',           '2025-09-01'],
        [12, 'male',   '2011-06-12', '777-890-123', 'صنعاء - النهضة',        '2025-09-01'],
        [13, 'male',   '2010-12-30', '778-901-234', 'صنعاء - الحصبة',        '2025-09-01'],
        // الطالبات
        [14, 'female', '2010-05-20', '771-111-222', 'صنعاء - شيرتون',        '2025-09-01'],
        [15, 'female', '2011-02-14', '772-222-333', 'صنعاء - السبعين',       '2025-09-01'],
        [16, 'female', '2010-08-08', '773-333-444', 'صنعاء - العاصمة',       '2025-09-01'],
        [17, 'female', '2011-10-03', '774-444-555', 'صنعاء - الصافية',       '2025-09-01'],
        [18, 'female', '2010-06-17', '775-555-666', 'صنعاء - اليمن',         '2025-09-01'],
        [19, 'female', '2011-03-29', '776-666-777', 'صنعاء - الزبيري',       '2025-09-01'],
        [20, 'female', '2010-01-11', '777-777-888', 'صنعاء - الحي السياسي',  '2025-09-01'],
        [21, 'female', '2011-09-06', '778-888-999', 'صنعاء - الجامعة',       '2025-09-01'],
    ];
    $ss = $pdo->prepare("INSERT INTO students(user_id,gender,dob,phone,address,enrollment_date) VALUES(?,?,?,?,?,?)");
    foreach($students_data as $s) $ss->execute($s);
    $steps[] = ['ok','✅ تم إدخال '.count($students_data).' طالباً وطالبة'];

    // ═══════════════════════════════════════════
    // المواد الدراسية (مرتبطة بالمعلمين)
    // ═══════════════════════════════════════════
    // [subject_name, subject_code, teacher_id]
    $subjects_data = [
        ['الرياضيات العامة',              'MATH-101', 1],
        ['الجبر الخطي',                   'MATH-102', 1],
        ['الإحصاء التطبيقي',              'STAT-101', 1],
        ['اللغة الإنجليزية الأساسية',    'ENG-101',  2],
        ['الإنجليزية المتقدمة',           'ENG-201',  2],
        ['الفيزياء العامة',               'PHY-101',  3],
        ['الكيمياء العامة',               'CHEM-101', 3],
        ['اللغة العربية',                 'ARB-101',  4],
        ['التربية الإسلامية',             'ISL-101',  4],
    ];
    $ssub = $pdo->prepare("INSERT INTO subjects(subject_name,subject_code,teacher_id) VALUES(?,?,?)");
    foreach($subjects_data as $sb) $ssub->execute($sb);
    $steps[] = ['ok','✅ تم إدخال '.count($subjects_data).' مواد دراسية'];

    // ═══════════════════════════════════════════
    // درجات الطلاب (student_id: 1-16)
    // ═══════════════════════════════════════════
    // توزيع الدرجات لكل طالب في عدة مواد
    $grades_data = [];
    $exam_types = ['Midterm','Final'];
    // درجات عشوائية منطقية لكل طالب
    $student_grades = [
        // [student_id, subject_id, exam_type, grade, date]
        [1,1,'Midterm',85.50,'2026-04-10'],[1,1,'Final',90.00,'2026-06-15'],
        [1,4,'Midterm',78.00,'2026-04-11'],[1,8,'Midterm',88.00,'2026-04-12'],
        [2,1,'Midterm',92.00,'2026-04-10'],[2,1,'Final',95.00,'2026-06-15'],
        [2,3,'Midterm',80.00,'2026-04-10'],[2,4,'Final',87.00,'2026-06-15'],
        [3,1,'Midterm',70.00,'2026-04-10'],[3,5,'Midterm',75.00,'2026-04-12'],
        [3,6,'Final',68.00,'2026-06-15'],  [3,8,'Midterm',82.00,'2026-04-12'],
        [4,2,'Midterm',88.00,'2026-04-10'],[4,4,'Final',91.00,'2026-06-15'],
        [4,7,'Midterm',77.00,'2026-04-11'],[4,9,'Midterm',85.00,'2026-04-12'],
        [5,1,'Midterm',60.00,'2026-04-10'],[5,4,'Midterm',72.00,'2026-04-11'],
        [5,8,'Final',65.00,'2026-06-15'],
        [6,2,'Midterm',93.00,'2026-04-10'],[6,5,'Final',89.00,'2026-06-15'],
        [6,6,'Midterm',91.00,'2026-04-11'],
        [7,1,'Midterm',74.00,'2026-04-10'],[7,3,'Final',81.00,'2026-06-15'],
        [7,9,'Midterm',79.00,'2026-04-12'],
        [8,1,'Final',97.00,'2026-06-15'],  [8,4,'Midterm',95.00,'2026-04-11'],
        [8,8,'Final',98.00,'2026-06-15'],
        // الطالبات
        [9,3,'Midterm',88.00,'2026-04-10'], [9,4,'Final',92.00,'2026-06-15'],
        [9,8,'Midterm',90.00,'2026-04-12'],
        [10,1,'Midterm',76.00,'2026-04-10'],[10,5,'Final',83.00,'2026-06-15'],
        [10,9,'Midterm',87.00,'2026-04-12'],
        [11,2,'Midterm',94.00,'2026-04-10'],[11,4,'Midterm',96.00,'2026-04-11'],
        [11,7,'Final',93.00,'2026-06-15'],
        [12,1,'Midterm',69.00,'2026-04-10'],[12,6,'Midterm',72.00,'2026-04-11'],
        [13,3,'Final',85.00,'2026-06-15'],  [13,8,'Midterm',88.50,'2026-04-12'],
        [13,9,'Midterm',91.00,'2026-04-12'],
        [14,1,'Midterm',77.00,'2026-04-10'],[14,4,'Final',80.00,'2026-06-15'],
        [15,2,'Midterm',89.00,'2026-04-10'],[15,5,'Final',92.00,'2026-06-15'],
        [15,8,'Midterm',86.00,'2026-04-12'],
        [16,1,'Final',73.00,'2026-06-15'],  [16,6,'Midterm',68.00,'2026-04-11'],
    ];
    $sg = $pdo->prepare("INSERT INTO grades(student_id,subject_id,exam_type,grade,grade_date) VALUES(?,?,?,?,?)");
    foreach($student_grades as $g) $sg->execute($g);
    $steps[] = ['ok','✅ تم إدخال '.count($student_grades).' درجة للطلاب'];

    // ═══════════════════════════════════════════
    // سجلات الحضور والغياب
    // ═══════════════════════════════════════════
    $att_data = [];
    $dates = ['2026-07-10','2026-07-13'];
    $statuses = ['present','present','present','present','absent','late','excused'];

    // يكفي إدخال سجلات يدوية لتجنب التعقيد
    $att_manual = [
        [1,'2026-07-10','present','حضور منتظم'],[2,'2026-07-10','present','حضور منتظم'],
        [3,'2026-07-10','absent','غياب بدون عذر'],[4,'2026-07-10','late','تأخر 10 دقائق'],
        [5,'2026-07-10','present','حضور منتظم'],[6,'2026-07-10','present','حضور في الوقت'],
        [7,'2026-07-10','excused','غياب بعذر مرضي'],[8,'2026-07-10','present','حضور منتظم'],
        [9,'2026-07-10','present','حضور منتظم'],[10,'2026-07-10','late','تأخر 20 دقيقة'],
        [11,'2026-07-10','present','حضور منتظم'],[12,'2026-07-10','absent','غياب'],
        [13,'2026-07-10','present','حضور منتظم'],[14,'2026-07-10','present','حضور منتظم'],
        [15,'2026-07-10','present','حضور منتظم'],[16,'2026-07-10','excused','إجازة رسمية'],
        [1,'2026-07-13','present','حضور منتظم'],[2,'2026-07-13','present','حضور منتظم'],
        [3,'2026-07-13','present','حضور منتظم'],[4,'2026-07-13','absent','غياب'],
        [5,'2026-07-13','late','تأخر 5 دقائق'],[6,'2026-07-13','present','حضور منتظم'],
        [7,'2026-07-13','present','حضور منتظم'],[8,'2026-07-13','present','حضور منتظم'],
        [9,'2026-07-13','absent','غياب بدون مبرر'],[10,'2026-07-13','present','حضور منتظم'],
    ];
    $sa = $pdo->prepare("INSERT INTO attendance(student_id,attendance_date,status,remarks) VALUES(?,?,?,?)");
    foreach($att_manual as $a) $sa->execute($a);
    $steps[] = ['ok','✅ تم إدخال '.count($att_manual).' سجل حضور وغياب'];

} catch(PDOException $e){
    $steps[] = ['err','❌ خطأ: '.$e->getMessage()];
}

// ═══════════════════ عرض النتائج ════════════════════

foreach($steps as $s) echo "<div class='step {$s[0]}'>{$s[1]}</div>";

$hasErr = array_filter($steps, fn($s)=>$s[0]==='err');

if(!$hasErr && isset($pdo)){
    // جدول المعلمين
    echo "<h3>👨‍🏫 المعلمون المضافون</h3><table>
    <tr><th>#</th><th>الاسم الكامل</th><th>التخصص</th><th>الهاتف</th><th>العنوان</th></tr>";
    $ts = $pdo->query("SELECT u.full_name,t.specialization,t.phone,t.address 
                       FROM teachers t JOIN users u ON t.user_id=u.id ORDER BY t.id")->fetchAll();
    foreach($ts as $i=>$t){
        echo "<tr><td>".($i+1)."</td><td>{$t['full_name']}</td><td>{$t['specialization']}</td><td>{$t['phone']}</td><td>{$t['address']}</td></tr>";
    }
    echo "</table>";

    // جدول الطلاب الذكور
    echo "<h3>👦 الطلاب الذكور</h3><table>
    <tr><th>#</th><th>الاسم الكامل</th><th>اسم المستخدم</th><th>تاريخ الميلاد</th><th>الهاتف</th><th>العنوان</th></tr>";
    $ms = $pdo->query("SELECT u.full_name,u.username,s.dob,s.phone,s.address 
                       FROM students s JOIN users u ON s.user_id=u.id 
                       WHERE s.gender='male' ORDER BY s.id")->fetchAll();
    foreach($ms as $i=>$s){
        echo "<tr><td>".($i+1)."</td><td>{$s['full_name']}</td><td>{$s['username']}</td><td>{$s['dob']}</td><td>{$s['phone']}</td><td>{$s['address']}</td></tr>";
    }
    echo "</table>";

    // جدول الطالبات
    echo "<h3>👧 الطالبات</h3><table>
    <tr><th>#</th><th>الاسم الكامل</th><th>اسم المستخدم</th><th>تاريخ الميلاد</th><th>الهاتف</th><th>العنوان</th></tr>";
    $fs = $pdo->query("SELECT u.full_name,u.username,s.dob,s.phone,s.address 
                       FROM students s JOIN users u ON s.user_id=u.id 
                       WHERE s.gender='female' ORDER BY s.id")->fetchAll();
    foreach($fs as $i=>$s){
        echo "<tr><td>".($i+1)."</td><td>{$s['full_name']}</td><td>{$s['username']}</td><td>{$s['dob']}</td><td>{$s['address']}</td></tr>";
    }
    echo "</table>";

    // جدول المواد
    echo "<h3>📚 المواد الدراسية</h3><table>
    <tr><th>#</th><th>اسم المادة</th><th>الرمز</th><th>المعلم المسؤول</th></tr>";
    $subs = $pdo->query("SELECT s.subject_name,s.subject_code,u.full_name 
                         FROM subjects s LEFT JOIN teachers t ON s.teacher_id=t.id 
                         LEFT JOIN users u ON t.user_id=u.id ORDER BY s.id")->fetchAll();
    foreach($subs as $i=>$s){
        echo "<tr><td>".($i+1)."</td><td>{$s['subject_name']}</td><td>{$s['subject_code']}</td><td>{$s['full_name']}</td></tr>";
    }
    echo "</table>";

    echo "<div class='step info'>
    📌 <strong>بيانات الدخول:</strong> اسم المستخدم: <strong>yura</strong> &nbsp;|&nbsp; كلمة المرور: <strong>123</strong>
    (جميع المستخدمين كلمة مرورهم: 123)
    </div>
    <a href='auth/login.php' class='btn'>🔐 الدخول للنظام الآن</a>";
}

echo "</div></body></html>";
?>
