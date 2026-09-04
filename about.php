<?php
// تضمين الهيدر لتطبيق الحماية والتنسيق
require_once __DIR__ . '/includes/header.php';
?>

<div class="panel-card slide-up">
    <div class="panel-header">
        <span class="panel-title">حول النظام ℹ️</span>
    </div>
    
    <div class="panel-body">
        <div class="info-grid">
            <div>
                <p class="info-lead">
                    <strong>نظام إدارة المدرسة الإلكتروني</strong> هو منصة رقمية متكاملة مصممة لتسهيل وتبسيط العمليات التعليمية والإدارية اليومية في البيئة المدرسية. يهدف النظام إلى تمكين الإدارة، المعلمين، والطلاب من التفاعل وإنجاز المهام في بيئة برمجية سريعة وآمنة.
                </p>
                
                <h3 style="margin: 20px 0 10px 0; color: #1e1b4b;">المميزات الرئيسية للنظام:</h3>
                <ul class="info-list">
                    <li>
                        <span class="info-list-icon">🔒</span>
                        <span><strong>أمان الجلسات (Sessions):</strong> حماية كاملة لبيانات المستخدمين مع توجيه تلقائي للصفحات حسب الأدوار المصرح بها.</span>
                    </li>
                    <li>
                        <span class="info-list-icon">🎓</span>
                        <span><strong>إدارة الطلاب الشاملة:</strong> إضافة كاملة لبيانات الطلاب وتحديثها مع خيارات للبحث السريع والتعديل والحذف.</span>
                    </li>
                    <li>
                        <span class="info-list-icon">👨‍🏫</span>
                        <span><strong>إدارة كادر التدريس:</strong> سجل تنظيمي متكامل للمعلمين، مع ربطهم بالمواد الدراسية التي يقومون بتدريسها.</span>
                    </li>
                    <li>
                        <span class="info-list-icon">📚</span>
                        <span><strong>المواد الدراسية:</strong> تنظيم المواد، تصنيفها، وربطها بالمعلم والدرجات بيسر وسهولة.</span>
                    </li>
                    <li>
                        <span class="info-list-icon">⏱️</span>
                        <span><strong>تسجيل الحضور والغياب:</strong> واجهة تفاعلية لرصد الحضور وغياب الطلاب وإنشاء تقارير نسبية للمتابعة اليومية.</span>
                    </li>
                    <li>
                        <span class="info-list-icon">📝</span>
                        <span><strong>رصد الدرجات:</strong> إدخال وتعديل درجات الطلاب لمختلف أنواع الامتحانات وعرض كشوفات الدرجات التفصيلية.</span>
                    </li>
                </ul>
            </div>

            <div class="panel-card" style="margin-bottom: 0;">
                <div class="panel-header" style="background-color: rgba(79, 70, 229, 0.05);">
                    <span class="panel-title" style="color: var(--primary-color);">معلومات تقنية</span>
                </div>
                <div class="panel-body">
                    <ul class="info-list">
                        <li>
                            <span>💻 <strong>تقنية البكند:</strong> PHP 8.x (PDO)</span>
                        </li>
                        <li>
                            <span>🗄️ <strong>قاعدة البيانات:</strong> MySQL 8.x</span>
                        </li>
                        <li>
                            <span>🎨 <strong>التصميم:</strong> CSS3 متجاوب (Vanilla CSS)</span>
                        </li>
                        <li>
                            <span>🤖 <strong>التحكم:</strong> JavaScript (ES6)</span>
                        </li>
                        <li>
                            <span>🎓 <strong>الغرض:</strong> مشروع تخرج جامعي مادة تطوير الويب</span>
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
