<?php
session_start();
$page_title = "กำหนดแผนเอง";

// Establish database connection and include header
require_once 'includes/db_connect.php';
require_once 'includes/header.php';

// [เพิ่ม] 1. เพิ่ม Font Awesome CDN เพื่อให้สามารถใช้ไอคอนได้
echo '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" xintegrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />';

// Redirect to login if user is not authenticated
if (!isset($_SESSION['user_id'])) {
    echo '<script>window.location.href = "login.php";</script>';
    exit();
}

// --- Setup variables for calendar and user ---
$month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');
$today_str = date('Y-m-d');
$user_id = $_SESSION['user_id'];

// --- Fetch user profile and calculate recommended calories ---
require_once 'includes/functions.php';
$profile_sql = "SELECT * FROM user_profiles WHERE user_id = ?";
$profile_stmt = $conn->prepare($profile_sql);
$profile_stmt->bind_param("i", $user_id);
$profile_stmt->execute();
$user_profile = $profile_stmt->get_result()->fetch_assoc();
$profile_stmt->close();
$recommended_calories = 0;
if ($user_profile) {
    $recommended_calories = getRecommendedCalories($user_profile);
}

// --- Fetch existing monthly meal plans from the database ---
$monthly_plans = [];
try {
    $month_start = date('Y-m-01', strtotime($month));
    $month_end = date('Y-m-t', strtotime($month));

    // Fetch pre-calculated totals for better performance
    $sql = "SELECT id, plan_date, plan_data, total_calories, total_protein, total_carbs_g, total_fat_g
            FROM daily_plans
            WHERE user_id = ? AND plan_date BETWEEN ? AND ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iss", $user_id, $month_start, $month_end);
    $stmt->execute();
    $result = $stmt->get_result();
    $plans_from_db = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    foreach ($plans_from_db as $plan) {
        // Store plan data in an associative array keyed by date
        $monthly_plans[$plan['plan_date']] = json_encode([
            'plan_id'   => $plan['id'],
            'plan'      => json_decode($plan['plan_data'], true),
            'totals'    => [
                'calories' => $plan['total_calories'],
                'protein'  => $plan['total_protein'],
                'carbs'    => $plan['total_carbs_g'],
                'fat'      => $plan['total_fat_g']
            ]
        ]);
    }
} catch (Exception $e) {
    error_log("Error fetching meal plans: " . $e->getMessage());
}

// --- Generate date range for the calendar view ---
$start = new DateTime("first day of $month");
$end = new DateTime("last day of $month");
$start->modify('-' . $start->format('w') . ' days');
$end->modify('+' . (6 - $end->format('w')) . ' days');
$period = new DatePeriod($start, new DateInterval('P1D'), $end);
$current_month_num = date('m', strtotime($month));
$thai_month_year = get_thai_month_year($month);

// --- Fetch data for search filters ---
// Fetch diseases
$diseases = [];
$disease_result = $conn->query("SELECT id, name FROM diseases ORDER BY name");
if ($disease_result) {
    $diseases = $disease_result->fetch_all(MYSQLI_ASSOC);
}

// Fetch diet types
$diet_types = [];
$diet_type_result = $conn->query("SELECT id, name FROM diet_types ORDER BY name");
if ($diet_type_result) {
    $diet_types = $diet_type_result->fetch_all(MYSQLI_ASSOC);
}

// Fetch tags for the profile saving feature
$all_tags = [];
$tags_result = $conn->query("SELECT id, name FROM tags ORDER BY name");
if ($tags_result) {
    $all_tags = $tags_result->fetch_all(MYSQLI_ASSOC);
}
?>

<div class="container py-5">
    <div class="text-center mb-4" style="padding-top: 50px;">
        <h1 class="fw-bold gradient-text"><i class="bi bi-calendar3 me-2"></i> ปฏิทินกำหนดแผนอาหาร</h1>
        <p>ปรับแต่งแผนเมนูประจําสัปดาห์ให้ตรงกับรสนิยมและเป้าหมายของคุณ</p>
    </div>

    <div class="container text-center mb-4">
        <div class="btn-group" role="group" aria-label="Planner Actions">
            <button id="show-save-profile-modal-btn" class="btn btn-success"><i class="bi bi-bookmark-plus-fill me-2"></i>บันทึกแผนทั้งหมดเป็นโปรไฟล์</button>
            <button id="clear-all-plans-btn" class="btn btn-danger"><i class="bi bi-trash3-fill me-2"></i>ล้างแผนทั้งหมดในเดือนนี้</button>
        </div>
    </div>
    <div class="text-center mb-4">
        <p class="text-muted mt-2">คลิกปุ่ม <i class="bi bi-plus-circle-dotted"></i> เพื่อสร้าง/แก้ไขแผน (ไม่สามารถแก้ไขวันที่ผ่านมาแล้วได้)</p>
        <a href="?month=<?php echo date('Y-m', strtotime($month . ' -1 month')); ?>" class="btn btn-outline-secondary">&lt; เดือนก่อนหน้า</a>
        <span class="fs-5 mx-3"><?php echo $thai_month_year; ?></span>
        <a href="?month=<?php echo date('Y-m', strtotime($month . ' +1 month')); ?>" class="btn btn-outline-secondary">เดือนถัดไป &gt;</a>
    </div>

    <div class="table-responsive" style="min-height: 500px;">
        <table class="table table-bordered text-center align-middle">
            <thead>
                <tr>
                    <th class="th-sun">อา</th> <th class="th-mon">จ</th> <th class="th-tue">อ</th>
                    <th class="th-wed">พ</th> <th class="th-thu">พฤ</th> <th class="th-fri">ศ</th> <th class="th-sat">ส</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $day_count = 0;
                foreach ($period as $date) {
                    if ($day_count % 7 === 0) echo '<tr>';

                    $date_str_ymd = $date->format('Y-m-d');
                    $display_day = $date->format('j');
                    $is_current_month = $date->format('m') === $current_month_num;
                    $is_past_date = $date_str_ymd < $today_str;
                    $has_plan = isset($monthly_plans[$date_str_ymd]);

                    $td_classes_arr = [];
                    if (!$is_current_month) $td_classes_arr[] = 'text-muted bg-light';
                    if ($is_past_date) $td_classes_arr[] = 'opacity-50';
                    $td_classes = implode(' ', $td_classes_arr);

                    echo '<td class="' . $td_classes . '">';
                    echo '<div class="calendar-day p-2 ' . ($has_plan ? 'has-plan' : '') . '" id="day-'. $date_str_ymd .'">';
                    echo '<strong>' . $display_day . '</strong>';

                    $plan_data_json = $has_plan ? $monthly_plans[$date_str_ymd] : '';

                    // Display summary and comparison with recommended calories
                    $summary_html = '';
                    if ($has_plan) {
                        $plan_details = json_decode($plan_data_json, true);
                        $plan_calories = $plan_details['totals']['calories'];
                        $comparison_html = '';

                        if ($recommended_calories > 0) {
                            $lower_bound = $recommended_calories - 100;
                            $upper_bound = $recommended_calories + 100;
                            if ($plan_calories >= $lower_bound && $plan_calories <= $upper_bound) {
                                $comparison_html = ' <span class="text-success small">(เหมาะสม)</span>';
                            } elseif ($plan_calories > $upper_bound) {
                                $comparison_html = ' <span class="text-danger small">(เกิน)</span>';
                            } else {
                                $comparison_html = ' <span class="text-warning small">(น้อย)</span>';
                            }
                        }
                        $summary_html = '<div class="day-plan-summary"><strong></strong> ' . round($plan_calories) . ' Kcal' . $comparison_html . '</div>';
                    }

                    echo '<div class="plan-container mt-1" data-plan=\''.$plan_data_json.'\'>' . $summary_html . '</div>';

                    // Logic for main action button (Add/Edit/View)
                    $is_readonly = ($is_past_date && $has_plan) ? 'true' : 'false';
                    $main_button_disabled = ($is_past_date && !$has_plan) ? 'disabled' : '';
                    $main_button_text = ($is_past_date && $has_plan) ? 'ดูรายละเอียด' : 'เพิ่ม/แก้ไข';
                    $main_button_icon = ($is_past_date && $has_plan) ? 'bi-search' : 'bi-plus-circle-dotted';

                    echo '<button class="btn btn-sm btn-outline-primary mt-2" onclick="openPlanner(\''.$date_str_ymd.'\', this, '.$is_readonly.')" '.$main_button_disabled.'><i class="bi '.$main_button_icon.'"></i> '.$main_button_text.'</button>';

                    // Display Copy/Delete buttons only for days with plans
                    echo '<div class="day-actions">';
                    if ($has_plan && !$is_past_date) {
                        echo '<button class="btn btn-sm btn-outline-info py-0 px-1" onclick="openCopyModal(\''.$date_str_ymd.'\')" title="คัดลอกแผน"><i class="bi bi-clipboard-plus"></i></button>';
                        echo '<button class="btn btn-sm btn-outline-danger py-0 px-1" onclick="openDeleteModal(\''.$date_str_ymd.'\')" title="ลบแผน"><i class="bi bi-trash"></i></button>';
                    }
                    echo '</div>';
                    echo '</div></td>';

                    if ($day_count % 7 === 6) echo '</tr>';
                    $day_count++;
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="plannerModal" tabindex="-1" aria-labelledby="plannerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="plannerModalLabel"><i class="bi bi-pencil-square"></i> สร้าง/แก้ไขแผนอาหารสำหรับวันที่: <span id="modal-date-display"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="container-fluid">
                    <input type="hidden" id="current-planning-date" value="">
                    <div class="row g-4 align-items-stretch">
                        <div class="col-lg-4">
                            <div class="planner-section h-100 d-flex flex-column">
                                <h4 class="planner-section-header"><i class="bi bi-search me-2"></i>ค้นหาเมนูอาหาร</h4>
                                <div class="row g-2 mb-3">
                                    <div class="col-md-6"><label for="search-input" class="form-label small">ค้นหาตามชื่อ:</label><input type="text" id="search-input" class="form-control form-control-sm" placeholder="เช่น 'อกไก่ย่าง'..."></div>
                                    <div class="col-md-6"><label for="category-filter" class="form-label small">ประเภทมื้ออาหาร:</label><select id="category-filter" class="form-select form-select-sm"><option value="">ทั้งหมด</option><option value="มื้อเช้า">มื้อเช้า</option><option value="มื้อกลางวัน">มื้อกลางวัน</option><option value="มื้อเย็น">มื้อเย็น</option><option value="ของว่าง">ของว่าง</option></select></div>
                                    <div class="col-md-6"><label for="diet-type-filter" class="form-label small">ประเภทการกิน:</label><select id="diet-type-filter" class="form-select form-select-sm"><option value="">ทั้งหมด</option><?php foreach ($diet_types as $diet): ?><option value="<?php echo htmlspecialchars($diet['id']); ?>"><?php echo htmlspecialchars($diet['name']); ?></option><?php endforeach; ?></select></div>
                                    <div class="col-md-6"><label for="disease-filter" class="form-label small">เหมาะสำหรับโรค:</label><select id="disease-filter" class="form-select form-select-sm"><option value="">ทั้งหมด</option><?php foreach ($diseases as $disease): ?><option value="<?php echo htmlspecialchars($disease['id']); ?>"><?php echo htmlspecialchars($disease['name']); ?></option><?php endforeach; ?></select></div>
                                </div>
                                <div id="search-results" style="min-height: 400px; overflow-y: auto;"></div>
                            </div>
                        </div>
                        <div class="col-lg-8">
                            <div class="planner-section h-100">
                                <h4 class="planner-section-header"><i class="bi bi-calendar-check me-2"></i>แผนอาหารประจำวัน</h4>
                                <div class="row g-2 mb-4">
                                    <div class="col-6 col-lg"><div class="card h-100"><div class="card-header fw-bold text-white" style="background-color: #2FACAA;">มื้อเช้า <i class="fas fa-coffee"></i></div><ul id="meal-breakfast" class="list-group list-group-flush"></ul></div></div>
                                    <div class="col-6 col-lg"><div class="card h-100"><div class="card-header fw-bold text-white" style="background-color: #B7D971;">มื้อสาย <i class="fa-solid fa-bread-slice"></i></div><ul id="meal-brunch" class="list-group list-group-flush"></ul></div></div>
                                    <div class="col-12 col-lg"><div class="card h-100 mt-2 mt-lg-0"><div class="card-header fw-bold text-white" style="background-color: #FFB405;">มื้อเที่ยง <i class="fa-solid fa-burger"></i></div><ul id="meal-lunch" class="list-group list-group-flush"></ul></div></div>
                                    <div class="col-6 col-lg"><div class="card h-100 mt-2 mt-lg-0"><div class="card-header fw-bold text-white" style="background-color: #E3812B;">มื้อบ่าย <i class="fa-solid fa-cookie-bite"></i></div><ul id="meal-afternoon_snack" class="list-group list-group-flush"></ul></div></div>
                                    <div class="col-6 col-lg"><div class="card h-100 mt-2 mt-lg-0"><div class="card-header fw-bold text-white" style="background-color: #7E72DA;">มื้อเย็น <i class="fa-solid fa-utensils"></i></div><ul id="meal-dinner" class="list-group list-group-flush"></ul></div></div>
                                </div>
                                
                                <h5>สรุปโภชนาการรวม</h5>
                                <div class="nutrition-summary-grid flex-shrink-0">
                                    <div class="nutrition-stat-card"><div class="label">พลังงาน</div><div><span id="summary-calories" class="value">0</span> <span class="unit">Kcal</span></div></div>
                                    <div class="nutrition-stat-card"><div class="label">โปรตีน</div><div><span id="summary-protein" class="value">0.0</span> <span class="unit">g</span></div></div>
                                    <div class="nutrition-stat-card"><div class="label">คาร์โบไฮเดรต</div><div><span id="summary-carbs" class="value">0.0</span> <span class="unit">g</span></div></div>
                                    <div class="nutrition-stat-card"><div class="label">ไขมัน</div><div><span id="summary-fat" class="value">0.0</span> <span class="unit">g</span></div></div>
                                    <div class="nutrition-stat-card"><div class="label">ไขมันอิ่มตัว</div><div><span id="summary-saturated_fat_g" class="value">0.0</span> <span class="unit">g</span></div></div>
                                    <div class="nutrition-stat-card"><div class="label">คอเลสเตอรอล</div><div><span id="summary-cholesterol_mg" class="value">0</span> <span class="unit">mg</span></div></div>
                                    <div class="nutrition-stat-card"><div class="label">โซเดียม</div><div><span id="summary-sodium_mg" class="value">0</span> <span class="unit">mg</span></div></div>
                                    <div class="nutrition-stat-card"><div class="label">ใยอาหาร</div><div><span id="summary-fiber_g" class="value">0.0</span> <span class="unit">g</span></div></div>
                                    <div class="nutrition-stat-card"><div class="label">น้ำตาล</div><div><span id="summary-sugar_g" class="value">0.0</span> <span class="unit">g</span></div></div>
                                    <div class="nutrition-stat-card"><div class="label">โพแทสเซียม</div><div><span id="summary-potassium_mg" class="value">0</span> <span class="unit">mg</span></div></div>
                                    <div class="nutrition-stat-card"><div class="label">แคลเซียม</div><div><span id="summary-calcium_mg" class="value">0</span> <span class="unit">mg</span></div></div>
                                    <div class="nutrition-stat-card"><div class="label">ธาตุเหล็ก</div><div><span id="summary-iron_mg" class="value">0.0</span> <span class="unit">mg</span></div></div>
                                    <div class="nutrition-stat-card"><div class="label">วิตามิน A</div><div><span id="summary-vitamin_a_ug" class="value">0</span> <span class="unit">µg</span></div></div>
                                    <div class="nutrition-stat-card"><div class="label">วิตามิน B</div><div><span id="summary-vitamin_b_mg" class="value">0.0</span> <span class="unit">mg</span></div></div>
                                    <div class="nutrition-stat-card"><div class="label">วิตามิน C</div><div><span id="summary-vitamin_c_mg" class="value">0.0</span> <span class="unit">mg</span></div></div>
                                    <div class="nutrition-stat-card"><div class="label">วิตามิน D</div><div><span id="summary-vitamin_d_ug" class="value">0</span> <span class="unit">µg</span></div></div>
                                    <div class="nutrition-stat-card"><div class="label">วิตามิน E</div><div><span id="summary-vitamin_e_mg" class="value">0.0</span> <span class="unit">mg</span></div></div>
                                    <div class="nutrition-stat-card"><div class="label">วิตามิน K</div><div><span id="summary-vitamin_k_ug" class="value">0</span> <span class="unit">µg</span></div></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="button" id="save-plan-btn" class="btn btn-primary"><i class="bi bi-save"></i> บันทึกแผนสำหรับวันนี้</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title" id="deleteModalLabel">ยืนยันการลบแผน</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">คุณแน่ใจหรือไม่ว่าต้องการลบแผนสำหรับวันที่ <strong id="delete-date-display"></strong>? การกระทำนี้ไม่สามารถย้อนกลับได้</div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button><button type="button" id="confirm-delete-btn" class="btn btn-danger">ยืนยันการลบ</button></div>
    </div>
  </div>
</div>

<div class="modal fade" id="copyPlanModal" tabindex="-1" aria-labelledby="copyPlanModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title" id="copyPlanModalLabel">คัดลอกแผนไปยัง...</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div id="mini-calendar-nav" class="d-flex justify-content-between align-items-center mb-3">
            <button id="mini-cal-prev" class="btn btn-sm btn-outline-secondary">&lt;</button>
            <strong id="mini-cal-month-year"></strong>
            <button id="mini-cal-next" class="btn btn-sm btn-outline-secondary">&gt;</button>
        </div>
        <div id="mini-calendar-container"></div>
        <div class="form-text mt-2">คุณสามารถเลือกวันที่ต้องการได้หลายวัน</div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button><button type="button" id="confirm-copy-btn" class="btn btn-primary">ยืนยันการคัดลอก</button></div>
    </div>
  </div>
</div>

<div class="modal fade" id="saveProfileModal" tabindex="-1" aria-labelledby="saveProfileModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title" id="saveProfileModalLabel">สร้างโปรไฟล์แผนอาหาร</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <form id="save-profile-form" onsubmit="return false;">
                    <div class="mb-3">
                        <label for="profile-name-input" class="form-label">ชื่อโปรไฟล์แผน <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="profile-name-input" required placeholder="เช่น: แผน 3 วัน (ปลาย ก.ค. - ต้น ส.ค.)">
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label for="profile-start-date" class="form-label">วันเริ่มต้น <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="profile-start-date" required>
                        </div>
                        <div class="col-md-6">
                            <label for="profile-end-date" class="form-label">วันสิ้นสุด <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="profile-end-date" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="profile-description-input" class="form-label">คำอธิบาย (ถ้ามี)</label>
                        <textarea class="form-control" id="profile-description-input" rows="2" placeholder="เช่น: เน้นโปรตีนสูง คาร์บต่ำ"></textarea>
                    </div>
                
                    <div class="mb-3">
                        <label for="profile-tags-input" class="form-label">แท็ก</label>
                        <div id="selected-tags-container" class="mb-2 d-flex flex-wrap gap-1"></div>
                        <input type="text" class="form-control" id="profile-tags-input" placeholder="พิมพ์แท็กใหม่แล้วกด Enter...">
                        <label class="form-label mt-2 small text-muted">หรือเลือกจากแท็กที่มีอยู่:</label>
                        <div id="existing-tags-container" class="d-flex flex-wrap gap-2 mt-1"></div>
                    </div>
                </form>
                <div class="form-text">ระบบจะดึงข้อมูลแผนทั้งหมดในช่วงวันที่ที่เลือกเพื่อสร้างเป็นโปรไฟล์</div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button><button type="button" id="confirm-save-profile-btn" class="btn btn-primary">ยืนยันการสร้างโปรไฟล์</button></div>
        </div>
    </div>
</div>

<div class="modal fade" id="clearAllConfirmModal" tabindex="-1" aria-labelledby="clearAllModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title text-danger" id="clearAllModalLabel"><i class="bi bi-exclamation-triangle-fill"></i> ยืนยันการล้างแผนทั้งหมด</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        คุณแน่ใจหรือไม่ว่าต้องการลบแผนอาหารทั้งหมดในเดือน <strong id="clear-month-display"></strong>?
        <br><br>
        <strong class="text-danger">การกระทำนี้ไม่สามารถย้อนกลับได้</strong>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
        <button type="button" id="confirm-clear-all-btn" class="btn btn-danger">ยืนยันการลบทั้งหมด</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
// --- GLOBAL STATE & ELEMENTS ---
const plannerModalEl = document.getElementById('plannerModal');
const deleteConfirmModalEl = document.getElementById('deleteConfirmModal');
const copyPlanModalEl = document.getElementById('copyPlanModal');
const saveProfileModalEl = document.getElementById('saveProfileModal');
const clearAllConfirmModalEl = document.getElementById('clearAllConfirmModal');

let plannerModal, deleteConfirmModal, copyPlanModal, saveProfileModal, clearAllConfirmModal;
let miniCalDate = new Date();

// Data passed from PHP
const CURRENT_MONTH = '<?php echo $month; ?>';
const CURRENT_THAI_MONTH = '<?php echo $thai_month_year; ?>';
const datesWithPlans = <?php echo json_encode(array_keys($monthly_plans)); ?>;
const allTagsData = <?php echo json_encode($all_tags); ?>;
const RECOMMENDED_CALORIES = <?php echo $recommended_calories; ?>;

// App state
let selectedTags = new Set();
const state = {
    plan: { breakfast: [], brunch: [], lunch: [], dinner: [], afternoon_snack: [] }
};
let currentTotals = {}; // Will be populated by updateTotals

// --- CORE PLANNER FUNCTIONS ---

function openPlanner(dateStr, buttonElement, isReadOnly = false) {
    if (buttonElement && buttonElement.disabled) return;
    
    document.getElementById('current-planning-date').value = dateStr;
    const dateObj = new Date(dateStr + 'T00:00:00');
    document.getElementById('modal-date-display').textContent = dateObj.toLocaleDateString('th-TH', { day: 'numeric', month: 'long', year: 'numeric' });

    const dayContainer = document.getElementById(`day-${dateStr}`);
    const existingPlanJson = dayContainer.querySelector('.plan-container').getAttribute('data-plan');
    const defaultPlan = { breakfast: [], brunch: [], lunch: [], dinner: [], afternoon_snack: [] };

    state.plan = existingPlanJson ? { ...defaultPlan, ...JSON.parse(existingPlanJson).plan } : defaultPlan;
    
    // Toggle read-only mode UI elements
    const searchColumn = plannerModalEl.querySelector('.col-lg-4');
    const planColumn = plannerModalEl.querySelector('.col-lg-8');
    const saveButton = document.getElementById('save-plan-btn');

    searchColumn.style.display = isReadOnly ? 'none' : 'block';
    planColumn.className = isReadOnly ? 'col-lg-12' : 'col-lg-8';
    saveButton.style.display = isReadOnly ? 'none' : 'inline-block';
    plannerModalEl.dataset.isReadonly = isReadOnly;

    updatePlanUI();
    updateTotals();
    plannerModal.show();
}

async function saveDailyPlan() {
    const saveButton = document.getElementById('save-plan-btn');
    const dateToSave = document.getElementById('current-planning-date').value;

    if (Object.values(state.plan).every(meal => meal.length === 0)) {
        alert("กรุณาเพิ่มเมนูอาหารอย่างน้อย 1 รายการ");
        return;
    }

    saveButton.disabled = true;
    saveButton.innerHTML = `<span class="spinner-border spinner-border-sm"></span> กำลังบันทึก...`;

    // Use currentTotals which is kept up-to-date
    const dataToSave = {
        plan_date: dateToSave,
        plan_data: state.plan,
        total_calories: Math.round(currentTotals.calories),
        total_protein: parseFloat(currentTotals.protein.toFixed(2)),
        total_carbs: parseFloat(currentTotals.carbs.toFixed(2)),
        total_fat: parseFloat(currentTotals.fat.toFixed(2))
    };

    try {
        const response = await fetch('process/save_daily_plan.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(dataToSave)
        });
        const result = await response.json();
        if (response.ok && result.success) {
            alert('บันทึกแผนสำเร็จ!');
            window.location.reload();
        } else {
            throw new Error(result.message || 'Server error');
        }
    } catch (error) {
        alert('เกิดข้อผิดพลาดในการบันทึก: ' + error.message);
    } finally {
        saveButton.disabled = false;
        saveButton.innerHTML = `<i class="bi bi-save"></i> บันทึกแผนสำหรับวันนี้`;
    }
}

function updatePlanUI() {
    const isReadOnly = plannerModalEl.dataset.isReadonly === 'true';
    for (const mealType of ['breakfast', 'brunch', 'lunch', 'dinner', 'afternoon_snack']) {
        const mealContainer = document.getElementById(`meal-${mealType}`);
        if (!mealContainer) continue;
        mealContainer.innerHTML = '';
        if (state.plan[mealType]) {
            state.plan[mealType].forEach((recipe, index) => {
                const removeButtonHTML = isReadOnly ? '' : `<button class="btn btn-sm btn-outline-danger py-0 px-1" onclick="removeFromPlan('${mealType}', ${index})"><i class="bi bi-x"></i></button>`;
                mealContainer.innerHTML += `
                    <li class="list-group-item d-flex justify-content-between align-items-center small p-2">
                        <span>${recipe.name}</span>
                        ${removeButtonHTML}
                    </li>`;
            });
        }
    }
}

// --- RECIPE MANAGEMENT & NUTRITION CALCULATION ---
function addToPlan(recipe, mealType) {
    if (!state.plan[mealType]) state.plan[mealType] = [];
    state.plan[mealType].push(recipe);
    updatePlanUI();
    updateTotals();
}

function removeFromPlan(mealType, index) {
    state.plan[mealType].splice(index, 1);
    updatePlanUI();
    updateTotals();
}

function updateTotals() {
    // Define all nutrients to be calculated
    const totals = {
        calories: 0, protein: 0, carbs: 0, fat: 0,
        saturated_fat_g: 0, cholesterol_mg: 0, sodium_mg: 0,
        fiber_g: 0, sugar_g: 0, potassium_mg: 0, calcium_mg: 0,
        iron_mg: 0, vitamin_a_ug: 0, vitamin_b_mg: 0, vitamin_c_mg: 0,
        vitamin_d_ug: 0, vitamin_e_mg: 0, vitamin_k_ug: 0
    };
    const keysToSum = Object.keys(totals);

    // Sum up nutrients from all recipes in the plan
    for (const mealType in state.plan) {
        if (Array.isArray(state.plan[mealType])) {
            state.plan[mealType].forEach(recipe => {
                for (const key of keysToSum) {
                    totals[key] += Number(recipe[key] || 0);
                }
            });
        }
    }
    
    // Update global state for use in other functions
    currentTotals = totals;

    // Update the UI with the calculated totals
    for (const key of keysToSum) {
        const el = document.getElementById(`summary-${key}`);
        if (el) {
            let value = totals[key];
            // Format numbers with integers for specific units, otherwise use one decimal place
            if (['calories', 'cholesterol_mg', 'sodium_mg', 'potassium_mg', 'calcium_mg', 'vitamin_a_ug', 'vitamin_d_ug', 'vitamin_k_ug'].includes(key)) {
                el.textContent = Math.round(value);
            } else {
                el.textContent = value.toFixed(1);
            }
        }
    }
}


async function searchRecipes(query, category, dietTypeId, diseaseId) {
    const searchResultsContainer = document.getElementById('search-results');
    searchResultsContainer.innerHTML = `<div class="text-center p-3"><span class="spinner-border spinner-border-sm"></span></div>`;
    
    const params = new URLSearchParams({ search: query, category, diet_type_id: dietTypeId, disease_id: diseaseId });
    try {
        const response = await fetch(`api/recipe_api.php?${params.toString()}`);
        if (!response.ok) throw new Error(`Error: ${response.status}`);
        const result = await response.json();
        displayResults(result.data);
    } catch (error) {
        searchResultsContainer.innerHTML = `<p class="text-danger text-center p-3">ไม่สามารถโหลดเมนูได้: ${error.message}</p>`;
    }
}

function displayResults(recipes) {
    const container = document.getElementById('search-results');
    container.innerHTML = (!recipes || recipes.length === 0) ? `<p class="text-muted text-center mt-3">ไม่พบเมนูที่ค้นหา</p>` : '';
    if (!recipes) return;

    recipes.forEach(recipe => {
        const safeRecipe = JSON.stringify(recipe).replace(/'/g, "&apos;").replace(/"/g, "&quot;");
        const addButtonsHTML = `
            <div class="mt-2 btn-group" role="group">
                <button class="btn btn-sm text-white" style="background-color: #2FACAA;" onclick='addToPlan(${safeRecipe}, "breakfast")' title="เพิ่มมื้อเช้า"><i class="fas fa-coffee"></i></button>
                <button class="btn btn-sm text-white" style="background-color: #B7D971;" onclick='addToPlan(${safeRecipe}, "brunch")' title="เพิ่มมื้อสาย"><i class="fa-solid fa-bread-slice"></i></button>
                <button class="btn btn-sm text-white" style="background-color: #FFB405;" onclick='addToPlan(${safeRecipe}, "lunch")' title="เพิ่มมื้อกลางวัน"><i class="fa-solid fa-burger"></i></button>
                <button class="btn btn-sm text-white" style="background-color: #E3812B;" onclick='addToPlan(${safeRecipe}, "afternoon_snack")' title="เพิ่มมื้อบ่าย"><i class="fa-solid fa-cookie-bite"></i></button>
                <button class="btn btn-sm text-white" style="background-color: #7E72DA;" onclick='addToPlan(${safeRecipe}, "dinner")' title="เพิ่มมื้อเย็น"><i class="fa-solid fa-utensils"></i></button>
            </div>`;

        container.insertAdjacentHTML('beforeend', `
            <div class="card recipe-search-item mb-2 shadow-sm">
                <div class="card-body p-2 d-flex align-items-start">
                    <img src="${recipe.image_url || 'https://via.placeholder.com/60'}" class="rounded me-3 flex-shrink-0" style="width:60px;height:60px;object-fit:cover;" alt="${recipe.name}">
                    <div class="flex-grow-1"><h6 class="mb-1 small">${recipe.name}</h6><small class="text-muted">${recipe.calories} Kcal</small>${addButtonsHTML}</div>
                </div>
            </div>`);
    });
}

// --- DELETE & COPY FUNCTIONS ---
function openDeleteModal(dateStr) {
    const planJson = document.querySelector(`#day-${dateStr} .plan-container`).getAttribute('data-plan');
    if (!planJson) return;
    const planData = JSON.parse(planJson);
    document.getElementById('delete-date-display').textContent = new Date(dateStr + 'T00:00:00').toLocaleDateString('th-TH', { dateStyle: 'long' });
    document.getElementById('confirm-delete-btn').dataset.planIdToDelete = planData.plan_id;
    deleteConfirmModal.show();
}

async function executeDeletePlan() {
    const deleteBtn = document.getElementById('confirm-delete-btn');
    const planId = deleteBtn.dataset.planIdToDelete;
    deleteBtn.disabled = true;
    deleteBtn.innerHTML = `<span class="spinner-border spinner-border-sm"></span> กำลังลบ...`;
    try {
        const response = await fetch('process/delete_daily_plan.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ plan_id: parseInt(planId) })
        });
        const result = await response.json();
        if (response.ok && result.success) {
            alert('ลบแผนสำเร็จ!'); window.location.reload();
        } else { throw new Error(result.message || 'Server error'); }
    } catch (error) {
        alert('เกิดข้อผิดพลาดในการลบ: ' + error.message);
    } finally {
        deleteBtn.disabled = false; deleteBtn.innerHTML = 'ยืนยันการลบ';
    }
}

function openCopyModal(sourceDateStr) {
    const planJson = document.querySelector(`#day-${sourceDateStr} .plan-container`).getAttribute('data-plan');
    if (!planJson) { alert('ไม่มีแผนให้คัดลอก'); return; }
    copyPlanModalEl.dataset.sourceDate = sourceDateStr;
    miniCalDate = new Date();
    generateMiniCalendar(miniCalDate.getFullYear(), miniCalDate.getMonth());
    copyPlanModal.show();
}

function generateMiniCalendar(year, month) {
    const container = document.getElementById('mini-calendar-container');
    const monthYearEl = document.getElementById('mini-cal-month-year');
    const today = new Date(); 
    today.setHours(0, 0, 0, 0);
    const firstDay = new Date(year, month, 1);
    monthYearEl.textContent = firstDay.toLocaleDateString('th-TH', { month: 'long', year: 'numeric' });
    let html = `<table class="table table-sm table-borderless mini-cal"><thead><tr><th>อา</th><th>จ</th><th>อ</th><th>พ</th><th>พฤ</th><th>ศ</th><th>ส</th></tr></thead><tbody><tr>`;
    for (let i = 0; i < firstDay.getDay(); i++) { html += '<td></td>'; }
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    for (let day = 1; day <= daysInMonth; day++) {
        const currentDate = new Date(year, month, day);

        const year_local = currentDate.getFullYear();
        const month_local = String(currentDate.getMonth() + 1).padStart(2, '0');
        const day_local = String(currentDate.getDate()).padStart(2, '0');
        const dateStr = `${year_local}-${month_local}-${day_local}`;

        const isPast = currentDate < today;
        const hasPlan = datesWithPlans.includes(dateStr);
        let dayClass = 'mini-calendar-day';
        if (isPast || hasPlan) dayClass += ' disabled';
        if (currentDate.getDay() === 0 && day > 1) { html += '</tr><tr>'; }
        html += `<td><div class="${dayClass}">`;
        if (isPast || hasPlan) {
            html += `<span>${day}</span>`;
        } else {
            html += `<label for="copy-day-${dateStr}">${day}</label><input type="checkbox" value="${dateStr}" id="copy-day-${dateStr}">`;
        }
        html += `</div></td>`;
    }
    const lastDayOfMonth = new Date(year, month, daysInMonth);
    for (let i = lastDayOfMonth.getDay() + 1; i <= 6; i++) { html += '<td></td>'; }
    html += '</tr></tbody></table>';
    container.innerHTML = html;
}

async function executeMultipleCopy() {
    const confirmBtn = document.getElementById('confirm-copy-btn');
    const sourceDateStr = copyPlanModalEl.dataset.sourceDate;
    const targetDates = Array.from(document.querySelectorAll('#mini-calendar-container input:checked')).map(cb => cb.value);
    if (targetDates.length === 0) { alert('กรุณาเลือกวันที่ที่ต้องการ'); return; }
    
    const sourcePlanJson = document.querySelector(`#day-${sourceDateStr} .plan-container`).getAttribute('data-plan');
    if (!sourcePlanJson) { alert('ไม่พบข้อมูลแผนต้นทาง'); return; }
    
    const sourceData = JSON.parse(sourcePlanJson);
    confirmBtn.disabled = true;
    confirmBtn.innerHTML = `<span class="spinner-border spinner-border-sm"></span> กำลังคัดลอก...`;
    
    const copyPromises = targetDates.map(targetDate => {
        const dataToSave = {
            plan_date: targetDate,
            plan_data: sourceData.plan,
            total_calories: Math.round(sourceData.totals.calories),
            total_protein: parseFloat(sourceData.totals.protein || 0),
            total_carbs: parseFloat(sourceData.totals.carbs || 0),
            total_fat: parseFloat(sourceData.totals.fat || 0)
        };
        return fetch('process/save_daily_plan.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(dataToSave)
        });
    });

    try {
        const responses = await Promise.all(copyPromises);
        if (responses.some(res => !res.ok)) { throw new Error('เกิดข้อผิดพลาดในการคัดลอกบางรายการ'); }
        alert(`คัดลอกแผนไปยัง ${targetDates.length} วันสำเร็จ!`);
        window.location.reload();
    } catch (error) {
        alert('เกิดข้อผิดพลาด: ' + error.message);
    } finally {
        confirmBtn.disabled = false;
        confirmBtn.innerHTML = 'ยืนยันการคัดลอก';
    }
}

// --- PROFILE SAVING & TAGGING SYSTEM ---
function openSaveProfileModal() {
    document.getElementById('save-profile-form').reset();
    selectedTags.clear();
    renderTags();

    // Set default date range to the current viewing month
    const year = new Date(CURRENT_MONTH + '-01').getFullYear();
    const month = new Date(CURRENT_MONTH + '-01').getMonth();
    const firstDay = new Date(year, month, 1).toISOString().split('T')[0];
    const lastDay = new Date(year, month + 1, 0).toISOString().split('T')[0];

    document.getElementById('profile-start-date').value = firstDay;
    document.getElementById('profile-end-date').value = lastDay;

    saveProfileModal.show();
}

async function savePlanProfile() {
    const saveBtn = document.getElementById('confirm-save-profile-btn');
    const profileName = document.getElementById('profile-name-input').value.trim();
    const startDate = document.getElementById('profile-start-date').value;
    const endDate = document.getElementById('profile-end-date').value;

    // --- Validation ---
    if (!profileName) {
        alert('กรุณาตั้งชื่อโปรไฟล์แผน');
        return;
    }
    if (!startDate || !endDate) {
        alert('กรุณาเลือกช่วงวันที่เริ่มต้นและสิ้นสุด');
        return;
    }
    if (new Date(startDate) > new Date(endDate)) {
        alert('วันที่สิ้นสุดต้องอยู่หลังหรือวันเดียวกับวันเริ่มต้น');
        return;
    }

    saveBtn.disabled = true;
    saveBtn.innerHTML = `<span class="spinner-border spinner-border-sm"></span> กำลังดึงข้อมูลและบันทึก...`;

    try {
        // Step 1: Fetch plan data for the selected date range from a new API
        const apiResponse = await fetch(`api/get_plans_by_range.php?start=${startDate}&end=${endDate}`);
        if (!apiResponse.ok) {
            throw new Error('ไม่สามารถดึงข้อมูลแผนได้');
        }
        const planDataInRange = await apiResponse.json();

        if (Object.keys(planDataInRange).length === 0) {
            alert('ไม่พบข้อมูลแผนอาหารในช่วงวันที่ที่เลือก');
            throw new Error('No plans found in range.');
        }

        // Step 2: Prepare data and save the profile
        const dataToSend = {
            profile_name: profileName,
            description: document.getElementById('profile-description-input').value.trim(),
            tags: [...selectedTags],
            plan_data: planDataInRange // Use the data fetched from the API
        };

        const saveResponse = await fetch('process/save_plan_profile.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(dataToSend)
        });
        
        const result = await saveResponse.json();
        if (saveResponse.ok && result.success) {
            alert('บันทึกโปรไฟล์แผนสำเร็จ!');
            saveProfileModal.hide();
        } else {
            throw new Error(result.message || 'เกิดข้อผิดพลาดในการบันทึกโปรไฟล์');
        }

    } catch (error) {
        // Don't show alert for 'No plans found' as it's already handled
        if (error.message !== 'No plans found in range.') {
            alert(error.message);
        }
    } finally {
        saveBtn.disabled = false;
        saveBtn.innerHTML = 'ยืนยันการสร้างโปรไฟล์';
    }
}

function renderTags() {
    const selectedContainer = document.getElementById('selected-tags-container');
    const existingContainer = document.getElementById('existing-tags-container');
    selectedContainer.innerHTML = [...selectedTags].map(tag => `<span class="badge bg-primary me-1">${tag} <i class="bi bi-x-circle" onclick="removeTag('${tag}')" style="cursor:pointer;"></i></span>`).join('');
    existingContainer.innerHTML = allTagsData.filter(tag => !selectedTags.has(tag.name)).map(tag => `<span class="badge bg-secondary" onclick="addTag('${tag.name}')" style="cursor:pointer;">${tag.name}</span>`).join(' ');
}

function addTag(tagName) {
    const cleanTag = tagName.trim();
    if (cleanTag) {
        selectedTags.add(cleanTag);
        document.getElementById('profile-tags-input').value = '';
        renderTags();
    }
}

function removeTag(tagName) {
    selectedTags.delete(tagName);
    renderTags();
}

// --- CLEAR ALL PLANS FUNCTION ---
function openClearAllModal() {
    document.getElementById('clear-month-display').textContent = CURRENT_THAI_MONTH;
    clearAllConfirmModal.show();
}

async function executeClearAllPlans() {
    const confirmBtn = document.getElementById('confirm-clear-all-btn');
    confirmBtn.disabled = true;
    confirmBtn.innerHTML = `<span class="spinner-border spinner-border-sm"></span> กำลังลบ...`;

    try {
        const response = await fetch('process/clear_monthly_plans.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ month: CURRENT_MONTH })
        });
        const result = await response.json();
        if (response.ok && result.success) {
            alert('ล้างแผนทั้งหมดในเดือนนี้สำเร็จ!');
            window.location.reload();
        } else {
            throw new Error(result.message || 'Server error');
        }
    } catch (error) {
        alert('เกิดข้อผิดพลาดในการลบข้อมูล: ' + error.message);
    } finally {
        confirmBtn.disabled = false;
        confirmBtn.innerHTML = 'ยืนยันการลบทั้งหมด';
        clearAllConfirmModal.hide();
    }
}


// --- EVENT LISTENERS ---
document.addEventListener('DOMContentLoaded', () => {
    // Initialize Bootstrap Modals
    plannerModal = new bootstrap.Modal(plannerModalEl);
    deleteConfirmModal = new bootstrap.Modal(deleteConfirmModalEl);
    copyPlanModal = new bootstrap.Modal(copyPlanModalEl);
    saveProfileModal = new bootstrap.Modal(saveProfileModalEl);
    clearAllConfirmModal = new bootstrap.Modal(clearAllConfirmModalEl);
    
    // Add an event listener to reset the planner modal when it's closed (BUG FIX)
    plannerModalEl.addEventListener('hidden.bs.modal', () => {
        // Reset the layout to its default (non-read-only) state
        const searchColumn = plannerModalEl.querySelector('.col-lg-4, .col-lg-12');
        const planColumn = plannerModalEl.querySelector('.col-lg-8, .col-lg-12');
        const saveButton = document.getElementById('save-plan-btn');

        // Restore visibility and layout
        if(searchColumn) searchColumn.style.display = 'block';
        if(planColumn) planColumn.className = 'col-lg-8';
        if(saveButton) saveButton.style.display = 'inline-block';

        // Clear any potentially lingering read-only state attribute
        delete plannerModalEl.dataset.isReadonly;

        // Optional but recommended: Clear the form and data for the next use
        document.getElementById('current-planning-date').value = '';
        document.getElementById('modal-date-display').textContent = '';
        const defaultPlan = { breakfast: [], brunch: [], lunch: [], dinner: [], afternoon_snack: [] };
        state.plan = defaultPlan;
        updatePlanUI();
        updateTotals();
    });

    // Initial recipe search
    searchRecipes('', '', '', '');
    
    // Search and filter listeners with debounce
    let debounceTimeout;
    const handleSearchAndFilter = () => {
        clearTimeout(debounceTimeout);
        debounceTimeout = setTimeout(() => {
            searchRecipes(
                document.getElementById('search-input').value,
                document.getElementById('category-filter').value,
                document.getElementById('diet-type-filter').value,
                document.getElementById('disease-filter').value
            );
        }, 300);
    };
    ['search-input', 'category-filter', 'diet-type-filter', 'disease-filter'].forEach(id => {
        document.getElementById(id).addEventListener('input', handleSearchAndFilter);
    });

    // Modal action buttons
    document.getElementById('save-plan-btn').addEventListener('click', saveDailyPlan);
    document.getElementById('confirm-delete-btn').addEventListener('click', executeDeletePlan);
    document.getElementById('confirm-copy-btn').addEventListener('click', executeMultipleCopy);
    document.getElementById('show-save-profile-modal-btn').addEventListener('click', openSaveProfileModal);
    document.getElementById('confirm-save-profile-btn').addEventListener('click', savePlanProfile);
    document.getElementById('clear-all-plans-btn').addEventListener('click', openClearAllModal);
    document.getElementById('confirm-clear-all-btn').addEventListener('click', executeClearAllPlans);


    // Mini calendar navigation
    document.getElementById('mini-cal-prev').addEventListener('click', () => {
        miniCalDate.setMonth(miniCalDate.getMonth() - 1);
        generateMiniCalendar(miniCalDate.getFullYear(), miniCalDate.getMonth());
    });
    document.getElementById('mini-cal-next').addEventListener('click', () => {
        miniCalDate.setMonth(miniCalDate.getMonth() + 1);
        generateMiniCalendar(miniCalDate.getFullYear(), miniCalDate.getMonth());
    });

    // Tag input listener
    document.getElementById('profile-tags-input').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            addTag(this.value);
        }
    });
});

    // Mobile Planner Optimizations
    if (window.innerWidth <= 768) {
        // Stack modal columns on mobile
        plannerModalEl.addEventListener('shown.bs.modal', function() {
            const cols = this.querySelectorAll('.col-lg-4, .col-lg-8');
            cols.forEach(col => {
                col.classList.remove('col-lg-4', 'col-lg-8');
                col.classList.add('col-12', 'mb-3');
            });
        });
        ndarCells.forEach(cell => {
        cell.style.cursor = 'pointer';
        cell.addEventListener('touchstart', function(e) {
            this.style.backgroundColor = '#f0f8ff';
        });
        cell.addEventListener('touchend', function(e) {
            this.style.backgroundColor = '';
        });
    });
        // Simplify nutrition grid on mobile
        const nutritionGrid = document.querySelector('.nutrition-summary-grid');
        if (nutritionGrid && window.innerWidth <= 480) {
            nutritionGrid.style.gridTemplateColumns = 'repeat(2, 1fr)';
        }
    }

    // Touch-friendly calendar navigation
    const calendarCells = document.querySelectorAll('.calendar-day');
    cale

</script>

<?php require_once 'includes/footer.php'; ?>
