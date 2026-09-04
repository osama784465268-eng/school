<?php
// تضمين الهيدر للتحقق من الحماية والتنسيق
require_once __DIR__ . '/includes/header.php';

$success_message = '';
$err_message = '';

// التحقق من إرسال مدخلات الاتصال
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cn_name = trim($_POST['cn_name'] ?? '');
    $cn_email = trim($_POST['cn_email'] ?? '');
    $cn_subject = trim($_POST['cn_subject'] ?? '');
    $cn_content = trim($_POST['cn_content'] ?? '');

    if (empty($cn_name) || empty($cn_email) || empty($cn_subject) || empty($cn_content)) {
        $err_message = 'يرجى ملء جميع الحقول المطلوبة لإرسال الرسالة.';
    } elseif (!filter_var($cn_email, FILTER_VALIDATE_EMAIL)) {
        $err_message = 'البريد الإلكتروني المدخل غير صالح.';
    } else {
        // محاكاة إرسال رسالة نجاح عبر حفظ تعليق في الجلسة أو العرض
        $success_message = 'شكراً لتواصلك معنا! لقد تلقينا رسالتك بنجاح وسنقوم بالرد عليك في غضون 24 ساعة.';
    }
}
?>

<div class="panel-card slide-up">
    <div class="panel-header">
        <span class="panel-title">اتصل بنا 📞</span>
    </div>
    
    <div class="panel-body">
        
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success fade-in">
                <span class="alert-icon">✓</span>
                <span class="alert-text"><?php echo htmlspecialchars($success_message); ?></span>
            </div>
        <?php endif; ?>

        <?php if (!empty($err_message)): ?>
            <div class="alert alert-danger fade-in">
                <span class="alert-icon">✗</span>
                <span class="alert-text"><?php echo htmlspecialchars($err_message); ?></span>
            </div>
        <?php endif; ?>

        <div class="info-grid">
            <!-- نموذج الاتصال -->
            <div class="panel-card" style="margin-bottom: 0; border: none; box-shadow: none;">
                <div class="panel-body" style="padding: 0;">
                    <form action="contact.php" method="POST">
                        <div class="form-group">
                            <label for="cn_name" class="form-label">الاسم الكامل *</label>
                            <input type="text" id="cn_name" name="cn_name" class="form-control" placeholder="أدخل اسمك الكريم" required value="<?php echo isset($_POST['cn_name']) && empty($success_message) ? htmlspecialchars($_POST['cn_name']) : ''; ?>">
                        </div>

                        <div class="form-group">
                            <label for="cn_email" class="form-label">البريد الإلكتروني *</label>
                            <input type="email" id="cn_email" name="cn_email" class="form-control" placeholder="example@mail.com" required value="<?php echo isset($_POST['cn_email']) && empty($success_message) ? htmlspecialchars($_POST['cn_email']) : ''; ?>">
                        </div>

                        <div class="form-group">
                            <label for="cn_subject" class="form-label">موضوع الرسالة *</label>
                            <input type="text" id="cn_subject" name="cn_subject" class="form-control" placeholder="ما هو استفسارك؟" required value="<?php echo isset($_POST['cn_subject']) && empty($success_message) ? htmlspecialchars($_POST['cn_subject']) : ''; ?>">
                        </div>

                        <div class="form-group">
                            <label for="cn_content" class="form-label">نص الرسالة *</label>
                            <textarea id="cn_content" name="cn_content" class="form-control" rows="5" placeholder="اكتب تفاصيل استفسارك هنا..." required style="resize: vertical; font-family: inherit; font-size: 0.95rem;"><?php echo isset($_POST['cn_content']) && empty($success_message) ? htmlspecialchars($_POST['cn_content']) : ''; ?></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary">✉️ إرسال رسالتك</button>
                    </form>
                </div>
            </div>

            <!-- معلومات التواصل المباشر مع المدرسة -->
            <div class="panel-card" style="margin-bottom: 0; background-color: rgba(13, 148, 136, 0.03);">
                <div class="panel-header" style="background-color: rgba(13, 148, 136, 0.08);">
                    <span class="panel-title" style="color: var(--secondary-color);">معلومات الاتصال</span>
                </div>
                <div class="panel-body">
                    <ul class="info-list">
                        <li>
                            <span class="info-list-icon">📍</span>
                            <span><strong>العنوان:</strong> اليمن - صنعاء - شارع الستين الرئيسي.</span>
                        </li>
                        <li>
                            <span class="info-list-icon">📞</span>
                            <span><strong>رقم الهاتف:</strong> 01-443322 (أو موبايل: 777777777).</span>
                        </li>
                        <li>
                            <span class="info-list-icon">📧</span>
                            <span><strong>البريد الإلكتروني:</strong> info@elec-school.edu.ye</span>
                        </li>
                        <li>
                            <span class="info-list-icon">⏰</span>
                            <span><strong>ساعات الدوام الرسمية:</strong> السبت - الأربعاء (من 8:00 صباحاً إلى 2:00 ظهراً).</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

    </div>
</div>

<?php
// تضمين الفوتر
require_once __DIR__ . '/includes/footer.php';
?>
