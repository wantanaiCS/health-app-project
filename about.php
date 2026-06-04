<?php
session_start();
$page_title = "เกี่ยวกับเรา";
require_once 'includes/header.php';
?>

<div class="container-fluid page-header" style="padding-top: 120px;">
    <div class="container">
        <h1 class="display-6 text-center animated slideInDown gradient-text"><?php echo $page_title; ?></h1>
    </div>
</div>
<div class="container py-5">
    <div class="row g-5">
        <div class="col-lg-8 mx-auto">
            <section class="mb-5">
                <h3 class="mb-3 ">เกี่ยวกับ FitMealWeek</h3>
                <p>
                    FitMealWeek คือเว็บแอปพลิเคชันที่ถูกสร้างขึ้นเพื่อช่วยให้ผู้ที่ใส่ใจสุขภาพสามารถวางแผนมื้ออาหารในแต่ละสัปดาห์ได้อย่างง่ายดายและมีประสิทธิภาพ โดยอิงตามข้อมูลสุขภาพส่วนบุคคล
                </p>
            </section>

            <section class="mb-5">
                <h3 class="mb-3">หลักการทำงานและทฤษฎี</h3>
                <p>
                    ระบบ AI แนะนำแผนอาหารของเราทำงานโดยใช้หลักการคำนวณพลังงานที่ร่างกายต้องการในแต่ละวัน (Total Daily Energy Expenditure - TDEE) ซึ่งคำนวณมาจากอัตราการเผาผลาญพื้นฐาน (Basal Metabolic Rate - BMR) ตามสูตรของ Harris-Benedict และปรับค่าตามระดับกิจกรรมของผู้ใช้
                </p>
                <p>
                    จากนั้น AI จะคัดเลือกเมนูอาหารจากคลังข้อมูลของเราโดยพิจารณาจาก:
                </p>
                <ul>
                    <li><strong>งบประมาณแคลอรี่:</strong> จัดสรรแคลอรี่ในแต่ละมื้อให้เหมาะสมกับค่า TDEE ของผู้ใช้</li>
                    <li><strong>เงื่อนไขสุขภาพ:</strong> กรองเมนูอาหารตาม Tags ที่เกี่ยวข้องกับโรคประจำตัวของผู้ใช้ (เช่น เบาหวาน, โรคไต) เพื่อความปลอดภัย</li>
                    <li><strong>ความหลากหลาย:</strong> ระบบจะพยายามสุ่มเลือกเมนูที่ไม่ซ้ำกันเพื่อสร้างความหลากหลายในแต่ละสัปดาห์</li>
                </ul>
            </section>

            <section>
                <h3 class="mb-3">ผู้จัดทำ</h3>
                <p>
                    โปรเจกต์นี้เป็นส่วนหนึ่งของการศึกษารายวิชาโครงงาน สำหรับนักศึกษาชั้นปีที่ 4 สาขาวิชาวิทยาการคอมพิวเตอร์ คณะวิทยาศาสตร์และเทคโนโลยี มหาวิทยาลัยเทคโนโลยีราชมงคลสุวรรณภูมิ (ศูนย์หันตรา) จัดทำโดย:
                </p>
                <ul>
                    <li>นายรัฐพงษ์ เต่าแก้ว</li>
                    <li>นายวรรธนัย กิ่งไทร</li>
                </ul>
            </section>

        </div>
    </div>
</div>
<?php require_once 'includes/footer.php'; ?>