<?php
session_start();
$page_title = "วิธีใช้งาน FitMealWeek";
require_once 'includes/header.php';
?>

<div class="container-fluid page-header" style="padding-top: 120px;">
    <div class="container">
        <h1 class="display-6 text-center animated slideInDown gradient-text"><?php echo $page_title; ?></h1>
    </div>
</div>
<div class="container py-5">
    <div class="row">
        <div class="col-lg-8 mx-auto">

            <p class="lead mb-5 text-center">เริ่มต้นเส้นทางสู่สุขภาพที่ดีกับ FitMealWeek ได้ง่ายๆ เพียงทำตามขั้นตอนต่อไปนี้</p>

            <div class="d-flex align-items-start mb-4">
                <i class="bi bi-1-circle-fill text-primary fs-2 me-3"></i>
                <div>
                    <h4 class="mb-2">ตั้งค่าโปรไฟล์สุขภาพ</h4>
                    <p class="text-muted">ขั้นตอนแรกและสำคัญที่สุดคือการกรอกข้อมูลสุขภาพของคุณ เพื่อให้ AI สามารถคำนวณและแนะนำแผนอาหารที่เหมาะสมกับคุณโดยเฉพาะ</p>
                    <ul>
                        <li>ไปที่เมนูผู้ใช้ (ชื่อของคุณ)มุมบนขวา แล้วเลือก "แก้ไขข้อมูลสุขภาพ"</li>
                        <li>กรอกข้อมูล อายุ, เพศ, น้ำหนัก, ส่วนสูง, ระดับกิจกรรม, และโรคประจำตัว (ถ้ามี) ให้ครบถ้วน</li>
                        <li>กด "บันทึกข้อมูล" ระบบจะคำนวณค่า BMI, BMR, และ TDEE ของคุณให้อัตโนมัติ</li>
                    </ul>
                </div>
            </div>
            <hr class="my-4">

            <div class="d-flex align-items-start mb-4">
                <i class="bi bi-2-circle-fill text-primary fs-2 me-3"></i>
                <div>
                    <h4 class="mb-2">การใช้แผนจาก AI</h4>
                    <p class="text-muted">วิธีที่ง่ายและรวดเร็วที่สุดในการเริ่มต้นวางแผนอาหาร</p>
                    <ul>
                        <li>ไปที่หน้า "แผนจาก AI" จากเมนูบาร์</li>
                        <li>กดปุ่ม "สร้างแผนอาหาร 7 วันให้ฉัน!"</li>
                        <li>ระบบจะสร้างแผนอาหาร 7 วันให้คุณโดยอัตโนมัติ โดยอิงจากข้อมูลสุขภาพของคุณ</li>
                    </ul>
                </div>
            </div>
            <hr class="my-4">

            <div class="d-flex align-items-start mb-4">
                <i class="bi bi-3-circle-fill text-primary fs-2 me-3"></i>
                <div>
                    <h4 class="mb-2">การกำหนดแผนเอง</h4>
                    <p class="text-muted">สำหรับผู้ที่ต้องการความยืดหยุ่นและเลือกเมนูที่ชอบด้วยตัวเอง</p>
                    <ul>
                        <li>ไปที่หน้า "กำหนดแผนเอง"</li>
                        <li>ใช้ช่องค้นหาและตัวกรองเพื่อค้นหาเมนูที่ต้องการ</li>
                        <li>กดปุ่มเครื่องหมายบวก (+) เพื่อเพิ่มเมนูลงในมื้อเช้า, กลางวัน, หรือเย็น</li>
                        <li>ดูสรุปโภชนาการรวมที่เปลี่ยนแปลงตามเมนูที่คุณเลือกได้ทันที</li>
                        <li>เมื่อจัดแผนเสร็จแล้ว อย่าลืมกด "บันทึกแผนประจำวันนี้"</li>
                    </ul>
                </div>
            </div>
            <hr class="my-4">

            <div class="d-flex align-items-start mb-4">
                <i class="bi bi-4-circle-fill text-primary fs-2 me-3"></i>
                <div>
                    <h4 class="mb-2">การติดตามความคืบหน้า</h4>
                    <p class="text-muted">บันทึกความสำเร็จในแต่ละวันเพื่อสร้างแรงจูงใจ</p>
                    <ul>
                        <li>ไปที่หน้า "ประวัติและแผนของฉัน"</li>
                        <li>ในแต่ละวันของแผน จะมีช่องให้ติ๊ก "ทำสำเร็จแล้ว"</li>
                        <li>เมื่อคุณทำตามแผนของวันไหนสำเร็จ ให้ติ๊กที่ช่องนั้นเพื่อบันทึกความคืบหน้าของคุณ</li>
                    </ul>
                </div>
            </div>

        </div>
    </div>
</div>
<?php require_once 'includes/footer.php'; ?>