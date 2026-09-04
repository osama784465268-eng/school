<?php
// إعدادات الاتصال بقاعدة البيانات (تدعم Railway ومحلي XAMPP)

$db_host = getenv('MYSQLHOST') ?: getenv('MYSQL_HOST') ?: getenv('DB_HOST') ?: 'mysql.railway.internal';
$db_port = getenv('MYSQLPORT') ?: getenv('MYSQL_PORT') ?: getenv('DB_PORT') ?: '3306';
$db_user = getenv('MYSQLUSER') ?: getenv('MYSQL_USER') ?: getenv('DB_USER') ?: 'root';
$db_pass = getenv('MYSQLPASSWORD') !== false ? getenv('MYSQLPASSWORD') : (getenv('MYSQL_PASSWORD') !== false ? getenv('MYSQL_PASSWORD') : (getenv('DB_PASS') !== false ? getenv('DB_PASS') : ''));
$db_name = getenv('MYSQLDATABASE') ?: getenv('MYSQL_DATABASE') ?: getenv('DB_NAME') ?: 'railway';

// دعم DATABASE_URL أو MYSQL_URL في حال توفر رابط مباشر
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

try {
    // DSN للاتصال بقاعدة البيانات مع ترميز الحروف utf8mb4 لدعم اللغة العربية
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    
    // خيارات PDO لضمان كود نظيف وتأمين متقدم
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    
    // إنشاء كائن الاتصال
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

} catch (PDOException $e) {
    // عرض الخطأ الفعلي
    error_log("Database connection failed: " . $e->getMessage());
    die("<div style='direction: rtl; text-align: center; margin-top: 50px; font-family: sans-serif;'>
            <h2 style='color: #d9534f;'>❌ فشل الاتصال بقاعدة البيانات!</h2>
            <p><strong>الخطأ الفعلي من السيرفر:</strong></p>
            <p style='color: #a94442; background: #f2dede; padding: 10px; border-radius: 5px; direction: ltr;'>" . htmlspecialchars($e->getMessage()) . "</p>
            <hr>
            <p>تأكد من إعداد متغيرات البيئة (MySQL Variables) في Railway أو تشغيل MySQL محلياً.</p>
         </div>");
}
?>