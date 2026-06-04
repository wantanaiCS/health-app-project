<?php
session_start();
$page_title = "ติดต่อเรา";
require_once 'includes/header.php';
?>

<div class="container-fluid page-header" style="padding-top: 120px;">
    <div class="container">
        <h1 class="display-6 text-center animated slideInDown gradient-text"><?php echo $page_title; ?></h1>
    </div>
</div>
<div class="container py-5">
    <div class="row g-5">
        <div class="col-md-6">
            <h3 class="mb-4">ข้อมูลการติดต่อ</h3>
            <div class="d-flex align-items-center mb-3">
                <div class="flex-shrink-0 btn-square bg-primary rounded-circle me-3">
                    <i class="fa fa-map-marker-alt text-white"></i>
                </div>
                <span>มหาวิทยาลัยเทคโนโลยีราชมงคลสุวรรณภูมิ (ศูนย์หันตรา)</span>
            </div>
            <div class="d-flex align-items-center mb-3">
                <div class="flex-shrink-0 btn-square bg-primary rounded-circle me-3">
                    <i class="fa fa-graduation-cap text-white"></i>
                </div>
                <span>สาขาวิชาวิทยาการคอมพิวเตอร์</span>
            </div>
             <div class="d-flex align-items-center mb-3">
                <div class="flex-shrink-0 btn-square bg-primary rounded-circle me-3">
                    <i class="fa fa-envelope-open text-white"></i>
                </div>
                <span>[ใส่อีเมลของคุณที่นี่]</span>
            </div>
        </div>

        <div class="col-md-6">
             <h3 class="mb-4">แผนที่</h3>
             <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3874.075484553229!2d100.4901968749179!3d13.83416689431835!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x30e29b8782131235%3A0x6445903462660461!2sRajamangala%20University%20of%20Technology%20Suvarnabhumi%2C%20Nonthaburi%20Campus!5e0!3m2!1sen!2sth!4v1718944517316!5m2!1sen!2sth" width="100%" height="300" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
        </div>
    </div>
</div>
<?php require_once 'includes/footer.php'; ?>