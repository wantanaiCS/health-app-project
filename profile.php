<?php
session_start();
$page_title = "จัดการข้อมูลสุขภาพ";
require_once 'includes/header.php';
require_once 'includes/db_connect.php';

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id'])) {
    echo '<script>window.location.href = "login.php";</script>';
    exit();
}

$user_id = $_SESSION['user_id'];

// --- ดึงข้อมูลโปรไฟล์เดิมของผู้ใช้ ---
$current_profile = null;
$sql = "SELECT * FROM user_profiles WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $current_profile = $result->fetch_assoc();
}
$stmt->close();

// แปลงข้อมูลโรคประจำตัว (ถ้ามี) ให้เป็น array สำหรับ checkbox
$user_diseases = [];
if (isset($current_profile['disease']) && !empty($current_profile['disease']) && is_string($current_profile['disease'])) {
    $user_diseases = explode(',', $current_profile['disease']);
}

// [NEW] Logic for Date of Birth dropdowns
$dob_parts = ['year' => null, 'month' => null, 'day' => null];
if (isset($current_profile['date_of_birth']) && !empty($current_profile['date_of_birth'])) {
    try {
        $dob_date = new DateTime($current_profile['date_of_birth']);
        $dob_parts['year'] = (int)$dob_date->format('Y') + 543; // Convert to B.E.
        $dob_parts['month'] = (int)$dob_date->format('m');
        $dob_parts['day'] = (int)$dob_date->format('d');
    } catch (Exception $e) { /* Handle error if date is invalid */ }
}
$thai_months = [
    1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน',
    5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม',
    9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
];

?>

<style>
    body {
        background-color: #f8f9fa;
    }
    .planner-section {
        background-color: #ffffff;
        padding: 2.5rem;
        border-radius: 1rem;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
    }
    .planner-section-header {
        font-weight: 600;
        color: #343a40;
    }
    .form-label.section-title {
        font-weight: 600;
        font-size: 1.1rem;
        margin-top: 1.5rem;
        margin-bottom: 1rem;
        display: block;
        border-bottom: 1px solid #dee2e6;
        padding-bottom: 0.5rem;
    }
    .btn-check:checked+.btn-outline-secondary {
        background-color: #B7D971;
        border-color: #B7D971;
        color: #333;
        font-weight: bold;
    }
    .btn-check+.btn-outline-secondary:hover {
        background-color: #f0f5e5;
        border-color: #B7D971;
        color: #333;
    }
    .btn-check:disabled+.btn-outline-secondary {
        background-color: #f8f9fa;
        color: #adb5bd;
        border-color: #dee2e6;
        cursor: not-allowed;
    }
     .btn-check:disabled+.btn-outline-secondary:hover {
        background-color: #f8f9fa;
        color: #adb5bd;
        border-color: #dee2e6;
    }
    .form-control:focus, .form-select:focus {
        border-color: #B7D971;
        box-shadow: 0 0 0 0.25rem rgba(183, 217, 113, 0.5);
    }
    #bmi-category.underweight { color: #0dcaf0; }
    #bmi-category.normal { color: #198754; }
    #bmi-category.overweight { color: #ffc107; }
    #bmi-category.obese { color: #dc3545; }

    .btn-primary {
        background-color: #8db632;
        border-color: #8db632;
        padding: 0.75rem 1.25rem;
        font-size: 1.1rem;
    }
    .btn-primary:hover {
        background-color: #7a9e2a;
        border-color: #7a9e2a;
    }
    .summary-card {
        background-color: #f8f9fa;
        border: none;
    }
    .summary-item {
        background-color: #ffffff;
        border: 1px solid #e9ecef;
    }

    .gradient-text-basic {
      background: linear-gradient(135deg, #0d6efd 0%, #6f42c1 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      color: transparent;
    }
    .gradient-text-lifestyle {
      background: linear-gradient(135deg, #fd7e14 0%, #ffc107 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      color: transparent;
    }
    .gradient-text-goal {
      background: linear-gradient(135deg, #198754 0%, #20c997 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      color: transparent;
    }
    
    /* ===โค้ดสำหรับปรับดีไซน์ตามที่ขอ=== */

    /* [NEW] สไตล์สำหรับกรอบ Gradient ของส่วนสรุปข้อมูล */
    .summary-card-gradient-border {
        background: linear-gradient(135deg, #2FC2A0 0%, #B7D971 100%);
        border-radius: 1rem;
        padding: 3px; /* ความหนาของกรอบ */
    }
    #health-summary.card {
        border: none;
        border-radius: 0.85rem; /* ทำให้มุมมนด้านในสวยงาม */
    }


    /* 1. สไตล์หัวข้อย่อย (ปรับปรุง) */
    .form-label.section-title {
        font-weight: 600;
        font-size: 1.1rem;
        margin-top: 1.5rem;
        margin-bottom: 1rem;
        display: block;
        border-bottom: 1px solid #dee2e6;
        padding-bottom: 0.5rem;
    }

    /* 2. สไตล์ปุ่มตัวเลือก */

    /* --- รูปแบบพื้นฐานของปุ่ม (ยังไม่ถูกเลือก) --- */
    .btn-check+.btn-gradient {
        border: 2px solid #dee2e6; /* สีขอบเริ่มต้น */
        background-color: transparent;
        color: #495057;
        font-weight: 500;
        transition: all 0.2s ease-in-out;
    }

    .btn-check+.btn-gradient:hover {
        background-color: #f8f9fa; /* สีพื้นหลังเมื่อเอาเมาส์ไปชี้ */
    }

    /* --- กำหนดสี "เส้นขอบ" ของปุ่มเมื่อยังไม่ถูกเลือก --- */
    .btn-check+.btn-gradient-male { border-color: #0d6efd; color: #0d6efd; }
    .btn-check+.btn-gradient-female { border-color: #d63384; color: #d63384; }
    .btn-check+.btn-gradient-no-ex { border-color: #a8e063; color: #a8e063; }
    .btn-check+.btn-gradient-light-ex { border-color: #4facfe; color: #4facfe; }
    .btn-check+.btn-gradient-mid-ex { border-color: #6f42c1; color: #6f42c1; }
    .btn-check+.btn-gradient-hard-ex { border-color: #fd7e14; color: #fd7e14; }
    .btn-check+.btn-gradient-heavy-ex { border-color: #dc3545; color: #dc3545; }
    .btn-check+.btn-gradient-disease { border-color: #ffc107; color: #ffc107; }
    .btn-check+.btn-gradient-maintain { border-color: #198754; color: #198754; }
    .btn-check+.btn-gradient-lose { border-color: #0dcaf0; color: #0dcaf0; }
    .btn-check+.btn-gradient-gain { border-color: #fd7e14; color: #fd7e14; }

    /* --- รูปแบบ Gradient ของปุ่ม (เมื่อถูกเลือก) --- */
    .btn-check:checked+.btn-gradient {
        color: #fff !important;
        border-width: 2px;
        background-image: none; /* ล้างค่าเก่า */
    }

    /* ชาย = กาเดียนสีน้ำเงิน */
    #gender-male:checked+.btn-gradient-male {
        background-image: linear-gradient(135deg, #0d6efd 0%, #0dcaf0 100%);
        border-color: #0d6efd;
    }

    /* หญิง = กาเดียนสีชมพู */
    #gender-female:checked+.btn-gradient-female {
        background-image: linear-gradient(135deg, #d63384 0%, #ffc0cb 100%);
        border-color: #d63384;
    }

    /* ไม่ออกกำลังกาย = กาเดียนสีเขียวอ่อน */
    #activity-1:checked+.btn-gradient-no-ex {
        background-image: linear-gradient(135deg, #a8e063 0%, #56ab2f 100%);
        border-color: #56ab2f;
    }

    /* น้อย = กาเดียนสีฟ้า */
    #activity-2:checked+.btn-gradient-light-ex {
        background-image: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        border-color: #4facfe;
    }

    /* ปานกลาง = กาเดียนสีม่วง */
    #activity-3:checked+.btn-gradient-mid-ex {
        background-image: linear-gradient(135deg, #6f42c1 0%, #a878e3 100%);
        border-color: #6f42c1;
    }

    /* หนัก = กาเดียนสีส้ม */
    #activity-4:checked+.btn-gradient-hard-ex {
        background-image: linear-gradient(135deg, #fd7e14 0%, #fec853 100%);
        border-color: #fd7e14;
    }

    /* หนักมาก = กาเดียนสีแดง */
    #activity-5:checked+.btn-gradient-heavy-ex {
        background-image: linear-gradient(135deg, #dc3545 0%, #ff7854 100%);
        border-color: #dc3545;
    }

    /* โรคประจำตัว = กาเดียนสีส้มเหลือง */
    input[name="disease[]"]:checked+.btn-gradient-disease {
        background-image: linear-gradient(135deg, #ffc107 0%, #ffeb3b 100%);
        border-color: #ffc107;
    }

    /* รักษาน้ำหนัก = กาเดียนสีเขียว */
    #goal-maintain:checked+.btn-gradient-maintain {
        background-image: linear-gradient(135deg, #198754 0%, #20c997 100%);
        border-color: #198754;
    }

    /* ลดน้ำหนัก = กาเดียนสีฟ้า */
    #goal-lose:checked+.btn-gradient-lose {
        background-image: linear-gradient(135deg, #0dcaf0 0%, #0d6efd 100%);
        border-color: #0dcaf0;
    }

    /* เพิ่มน้ำหนัก = กาเดียนสีส้ม */
    #goal-gain:checked+.btn-gradient-gain {
        background-image: linear-gradient(135deg, #fd7e14 0%, #ffc107 100%);
        border-color: #fd7e14;
    }

    /* 3. ปุ่มบันทึก */
    .btn-save {
        background-image: linear-gradient(135deg, #2FC2A0 0%, #B7D971 100%);
        border: none;
        color: white;
        font-weight: bold;
        padding: 0.75rem 1.25rem;
        font-size: 1.1rem;
    }
    .btn-save:hover {
        opacity: 0.9;
        color: white;
    }
    /* === จบส่วนโค้ดสำหรับปรับดีไซน์ === */
</style>

<div class="container my-5" style="padding-top: 50px;">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="planner-section">
                <h4 class="planner-section-header gradient-text"><i class="bi bi-person-lines-fill me-2"></i>ข้อมูลสุขภาพส่วนตัว</h4>
                <p class="text-muted mb-4">ข้อมูลเหล่านี้จะถูกนำไปใช้ในการคำนวณและแนะนำแผนอาหารที่เหมาะสมกับคุณ</p>

                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['success_message']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['success_message']); ?>
                <?php endif; ?>
                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['error_message']; ?>
                         <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['error_message']); ?>
                <?php endif; ?>


                <div id="health-summary-wrapper" class="summary-card-gradient-border mb-4" style="display: none;">
                    <div id="health-summary" class="card summary-card">
                        <div class="card-body p-3">
                             <h5 class="mb-3 fw-bold small text-uppercase text-muted"><i class="bi bi-clipboard2-data-fill me-2"></i>ข้อมูลสรุปสุขภาพของคุณ</h5>
                            <div class="row g-3 text-center">
                                <div class="col-6">
                                    <div class="p-3 rounded summary-item">
                                        <h6 class="text-muted small">ค่าดัชนีมวลกาย (BMI)</h6>
                                        <p class="fs-4 fw-bold mb-0" id="bmi-result">-</p>
                                        <p class="fw-bold mb-0 small" id="bmi-category">-</p>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="p-3 rounded summary-item">
                                        <h6 class="text-muted small">แคลอรี่ที่แนะนำ/วัน</h6>
                                        <p class="fs-4 fw-bold mb-0" id="tdee-result">-</p>
                                        <p class="text-muted mb-0 small">Kcal</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>


                <form action="process/profile_process.php" method="POST" id="profile-form">

                    <label class="form-label section-title gradient-text-basic">
                        <i class="bi bi-person-vcard-fill me-2"></i>ข้อมูลพื้นฐาน
                    </label>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">เพศ</label>
                            <div class="d-flex">
                                <div class="flex-fill me-1">
                                    <input type="radio" class="btn-check" name="gender" id="gender-male" value="male" autocomplete="off" <?php echo (isset($current_profile['gender']) && $current_profile['gender'] == 'male') ? 'checked' : ''; ?>>
                                    <label class="btn btn-gradient btn-gradient-male w-100" for="gender-male"><i class="bi bi-gender-male me-1"></i>ชาย</label>
                                </div>
                                <div class="flex-fill ms-1">
                                    <input type="radio" class="btn-check" name="gender" id="gender-female" value="female" autocomplete="off" <?php echo (isset($current_profile['gender']) && $current_profile['gender'] == 'female') ? 'checked' : ''; ?>>
                                    <label class="btn btn-gradient btn-gradient-female w-100" for="gender-female"><i class="bi bi-gender-female me-1"></i>หญิง</label>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">วันเกิด</label>
                            <div class="row g-2">
                                <div class="col-4">
                                     <select name="dob_day" id="dob_day" class="form-select">
                                        <option value="">วัน</option>
                                        <?php for ($i = 1; $i <= 31; $i++): ?>
                                            <option value="<?php echo $i; ?>" <?php if($dob_parts['day'] == $i) echo 'selected'; ?>><?php echo $i; ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="col-4">
                                    <select name="dob_month" id="dob_month" class="form-select">
                                        <option value="">เดือน</option>
                                         <?php foreach ($thai_months as $num => $name): ?>
                                            <option value="<?php echo $num; ?>" <?php if($dob_parts['month'] == $num) echo 'selected'; ?>><?php echo $name; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-4">
                                     <select name="dob_year" id="dob_year" class="form-select">
                                        <option value="">ปี (พ.ศ.)</option>
                                        <?php for ($i = date('Y') + 543; $i >= date('Y') + 443; $i--): ?>
                                            <option value="<?php echo $i; ?>" <?php if($dob_parts['year'] == $i) echo 'selected'; ?>><?php echo $i; ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label for="weight" class="form-label">น้ำหนัก (kg)</label>
                            <input type="number" step="0.1" class="form-control" id="weight" name="weight" placeholder="เช่น 65.5" value="<?php echo $current_profile['weight'] ?? ''; ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="height" class="form-label">ส่วนสูง (cm)</label>
                            <input type="number" class="form-control" id="height" name="height" placeholder="เช่น 170" value="<?php echo $current_profile['height'] ?? ''; ?>">
                        </div>
                    </div>


                    <label class="form-label section-title gradient-text-lifestyle">
                        <i class="bi bi-activity me-2"></i>ไลฟ์สไตล์
                    </label>

                    <div class="mb-3">
                        <label class="form-label">การออกกำลังกาย</label>
                        <div class="row g-2 row-cols-2 row-cols-md-3">
                            <div>
                                <input type="radio" class="btn-check" name="activity_level" id="activity-1" value="1.2" <?php echo (isset($current_profile['activity_level']) && $current_profile['activity_level'] == '1.2') ? 'checked' : ''; ?>>
                                <label class="btn btn-gradient btn-gradient-no-ex w-100" for="activity-1">ไม่ออกกำลังกาย </label>
                            </div>
                             <div>
                                <input type="radio" class="btn-check" name="activity_level" id="activity-2" value="1.375" <?php echo (isset($current_profile['activity_level']) && $current_profile['activity_level'] == '1.375') ? 'checked' : ''; ?>>
                                <label class="btn btn-gradient btn-gradient-light-ex w-100" for="activity-2">น้อย (1-3 วัน/สัปดาห์)</label>
                            </div>
                             <div>
                                <input type="radio" class="btn-check" name="activity_level" id="activity-3" value="1.55" <?php echo (isset($current_profile['activity_level']) && $current_profile['activity_level'] == '1.55') ? 'checked' : ''; ?>>
                                <label class="btn btn-gradient btn-gradient-mid-ex w-100" for="activity-3">กลาง (3-5 วัน/สัปดาห์)</label>
                            </div>
                             <div>
                                <input type="radio" class="btn-check" name="activity_level" id="activity-4" value="1.725" <?php echo (isset($current_profile['activity_level']) && $current_profile['activity_level'] == '1.725') ? 'checked' : ''; ?>>
                                <label class="btn btn-gradient btn-gradient-hard-ex w-100" for="activity-4">หนัก (6-7 วัน/สัปดาห์)</label>
                            </div>
                            <div>
                                <input type="radio" class="btn-check" name="activity_level" id="activity-5" value="1.9" <?php echo (isset($current_profile['activity_level']) && $current_profile['activity_level'] == '1.9') ? 'checked' : ''; ?>>
                                <label class="btn btn-gradient btn-gradient-heavy-ex w-100" for="activity-5">หนักมาก (ทุกวัน/นักกีฬา)</label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                         <label class="form-label">โรคประจำตัว (เลือกได้มากกว่า 1 ข้อ)</label>
                          <div class="row g-2 row-cols-2 row-cols-md-3">
                            <?php
                                // [UPDATED] รายการโรคให้ตรงกับคำแนะนำใหม่
                                $diseases = ['โรคอ้วน', 'โรคเบาหวาน', 'ความดันโลหิตสูง', 'โรคไขมันในเลือดสูง', 'โรคไต'];
                                foreach ($diseases as $disease):
                                    $disease_id = 'disease-' . strtolower(str_replace(' ', '-', $disease));
                                    $is_checked = in_array($disease, $user_diseases);
                            ?>
                            <div>
                                <input type="checkbox" class="btn-check disease-checkbox" name="disease[]" id="<?php echo $disease_id; ?>" value="<?php echo $disease; ?>" autocomplete="off" <?php if ($is_checked) echo 'checked'; ?>>
                                <label class="btn btn-gradient btn-gradient-disease w-100" for="<?php echo $disease_id; ?>"><?php echo $disease; ?></label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>


                     <label class="form-label section-title gradient-text-goal">
                        <i class="bi bi-bullseye me-2"></i>เป้าหมาย
                    </label>

                    <div class="mb-4">
                         <div class="row g-2 row-cols-3">
                            <div>
                                <input type="radio" class="btn-check" name="goal" id="goal-maintain" value="รักษาน้ำหนัก" <?php echo (isset($current_profile['goal']) && $current_profile['goal'] == 'รักษาน้ำหนัก') ? 'checked' : ''; ?>>
                                <label class="btn btn-gradient btn-gradient-maintain w-100" for="goal-maintain">รักษาน้ำหนัก</label>
                            </div>
                             <div>
                                <input type="radio" class="btn-check" name="goal" id="goal-lose" value="ลดน้ำหนัก" <?php echo (isset($current_profile['goal']) && $current_profile['goal'] == 'ลดน้ำหนัก') ? 'checked' : ''; ?>>
                                <label class="btn btn-gradient btn-gradient-lose w-100" for="goal-lose">ลดน้ำหนัก</label>
                            </div>
                             <div>
                                <input type="radio" class="btn-check" name="goal" id="goal-gain" value="เพิ่มน้ำหนัก" <?php echo (isset($current_profile['goal']) && $current_profile['goal'] == 'เพิ่มน้ำหนัก') ? 'checked' : ''; ?>>
                                <label class="btn btn-gradient btn-gradient-gain w-100" for="goal-gain">เพิ่มน้ำหนัก</label>
                            </div>
                        </div>
                    </div>


                    <div class="d-grid">
                        <button type="submit" class="btn btn-save w-100">
                            <i class="bi bi-save-fill me-2"></i>บันทึกข้อมูล
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('profile-form');
    const formInputs = form.querySelectorAll('input, select');
    // [UPDATED] เปลี่ยนเป้าหมายเป็น div ตัวนอกที่ครอบ
    const healthSummary = document.getElementById('health-summary-wrapper');

    // --- Goal disabling logic ---
    const diseaseCheckboxes = document.querySelectorAll('.disease-checkbox');
    const goalGainRadio = document.getElementById('goal-gain');


// ส่วนของ JavaScript ใน profile.php ที่ต้องแก้ไข
// แทนที่โค้ดเดิมในฟังก์ชัน calculateHealthMetrics()

        function calculateHealthMetrics() {
            const weight = parseFloat(document.getElementById('weight').value);
            const height = parseFloat(document.getElementById('height').value);
            const dobDay = document.getElementById('dob_day').value;
            const dobMonth = document.getElementById('dob_month').value;
            const dobYearBE = document.getElementById('dob_year').value;
            const gender = form.querySelector('input[name="gender"]:checked');
            const activityLevel = form.querySelector('input[name="activity_level"]:checked');
            const goal = form.querySelector('input[name="goal"]:checked');

            let allFieldsFilled = weight > 0 && height > 0 && dobDay && dobMonth && dobYearBE && gender && activityLevel && goal;

            healthSummary.style.display = allFieldsFilled ? 'block' : 'none';

            if (!allFieldsFilled) {
                document.getElementById('bmi-result').textContent = '-';
                document.getElementById('bmi-category').textContent = '-';
                document.getElementById('tdee-result').textContent = '-';
                return;
            }

            // BMI Calculation (เหมือนเดิม)
            const heightInMeters = height / 100;
            const bmi = weight / (heightInMeters * heightInMeters);
            document.getElementById('bmi-result').textContent = bmi.toFixed(2);
            
            const bmiCategoryEl = document.getElementById('bmi-category');
            bmiCategoryEl.className = 'fw-bold mb-0 small';
            if (bmi < 18.5) {
                bmiCategoryEl.textContent = 'น้ำหนักน้อย';
                bmiCategoryEl.classList.add('underweight');
            } else if (bmi < 24.9) {
                bmiCategoryEl.textContent = 'ปกติ';
                bmiCategoryEl.classList.add('normal');
            } else if (bmi < 29.9) {
                bmiCategoryEl.textContent = 'น้ำหนักเกิน';
                bmiCategoryEl.classList.add('overweight');
            } else {
                bmiCategoryEl.textContent = 'อ้วน';
                bmiCategoryEl.classList.add('obese');
            }

            // คำนวณอายุ
            const dobYearAD = parseInt(dobYearBE) - 543;
            const dob = new Date(dobYearAD, parseInt(dobMonth) - 1, parseInt(dobDay));
            const today = new Date();
            let age = today.getFullYear() - dob.getFullYear();
            const monthDiff = today.getMonth() - dob.getMonth();
            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < dob.getDate())) {
                age--;
            }

            // *** คำนวณ BMR ด้วยสูตร Mifflin-St Jeor (แม่นยำกว่า Harris-Benedict) ***
            let bmr;
            if (gender.value === 'male') {
                // สูตรสำหรับชาย: (10 × น้ำหนัก) + (6.25 × ส่วนสูง) - (5 × อายุ) + 5
                bmr = (10 * weight) + (6.25 * height) - (5 * age) + 5;
            } else {
                // สูตรสำหรับหญิง: (10 × น้ำหนัก) + (6.25 × ส่วนสูง) - (5 × อายุ) - 161
                bmr = (10 * weight) + (6.25 * height) - (5 * age) - 161;
            }

            // คำนวณ TDEE (Total Daily Energy Expenditure)
            let tdee = bmr * parseFloat(activityLevel.value);
            
            // ปรับ TDEE ตามเป้าหมาย
            switch (goal.value) {
                case 'ลดน้ำหนัก':
                    tdee -= 500; // ลด 500 แคลอรี่ต่อวันเพื่อลดน้ำหนักประมาณ 0.5 kg/สัปดาห์
                    break;
                case 'เพิ่มน้ำหนัก':
                    tdee += 500; // เพิ่ม 500 แคลอรี่ต่อวันเพื่อเพิ่มน้ำหนักประมาณ 0.5 kg/สัปดาห์
                    break;
                case 'รักษาน้ำหนัก':
                default:
                    // ไม่ปรับแก้
                    break;
            }

            // แสดงผล TDEE
            document.getElementById('tdee-result').textContent = isNaN(tdee) ? '-' : Math.round(tdee).toLocaleString();
        }

/*
คำอธิบายสูตร Mifflin-St Jeor:

1. สูตรนี้แม่นยำกว่า Harris-Benedict Formula เดิมประมาณ 5%
2. พัฒนาในปี 1990 และได้รับการยอมรับจาก American Dietetic Association
3. คำนวณจากปัจจัย:
   - น้ำหนัก (กิโลกรัม)
   - ส่วนสูง (เซนติเมตร)
   - อายุ (ปี)
   - เพศ (ชายมีค่าคงที่ +5, หญิงมีค่าคงที่ -161)

4. TDEE = BMR × Activity Level:
   - 1.2 = ไม่ออกกำลังกาย
   - 1.375 = ออกกำลังกายเบา (1-3 วัน/สัปดาห์)
   - 1.55 = ออกกำลังกายปานกลาง (3-5 วัน/สัปดาห์)
   - 1.725 = ออกกำลังกายหนัก (6-7 วัน/สัปดาห์)
   - 1.9 = ออกกำลังกายหนักมาก (ทุกวัน/นักกีฬา)

5. การปรับแต่งตามเป้าหมาย:
   - ลดน้ำหนัก: -500 kcal (จะได้การสูญเสียน้ำหนักประมาณ 0.5 kg/สัปดาห์)
   - เพิ่มน้ำหนัก: +500 kcal (จะได้การเพิ่มน้ำหนักประมาณ 0.5 kg/สัปดาห์)
   - รักษาน้ำหนัก: ไม่ปรับแก้
*/

    function updateGoalOptions() {
        const hasDisease = Array.from(diseaseCheckboxes).some(cb => cb.checked);

        goalGainRadio.disabled = hasDisease;

        if (hasDisease && goalGainRadio.checked) {
            goalGainRadio.checked = false;
            document.getElementById('goal-maintain').checked = true;
        }

        calculateHealthMetrics();
    }



    formInputs.forEach(input => {
        input.addEventListener('input', calculateHealthMetrics);
        input.addEventListener('change', calculateHealthMetrics);
    });

    diseaseCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateGoalOptions);
    });

    // Initial calculation and goal check on page load
    calculateHealthMetrics();
    updateGoalOptions();
});
</script>

<?php 
$conn->close();
require_once 'includes/footer.php'; 
?>