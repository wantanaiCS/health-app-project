<?php
// --- ส่วน PHP ด้านบนสุดของไฟล์ ---
session_start();

// 1. กำหนด Title ของหน้านี้ (จะถูกนำไปใช้ใน header.php)
$page_title = "แดชบอร์ด"; 

// 2. เรียกใช้ Header (ซึ่งจะรวม <head> และ <body> ให้เรา)
require_once 'includes/header.php'; 
require_once 'includes/db_connect.php'; // เรียกใช้ db connect หลัง header

// ตรวจสอบการล็อกอิน (ย้ายมาไว้หลัง header เพื่อให้โครงสร้างชัดเจน)
if (!isset($_SESSION['user_id'])) {
    // ใช้ javascript redirect ถ้า header ถูกส่งไปแล้ว
    echo '<script>window.location.href = "login.php";</script>';
    exit();
}

$user_id = $_SESSION['user_id'];

// ดึงแผนล่าสุดจากฐานข้อมูล
$sql = "SELECT plan_data, created_at FROM weekly_plans WHERE user_id = ? ORDER BY created_at DESC LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$plan_row = $result->fetch_assoc();
$stmt->close();
$conn->close();

$weekly_plan = null;
if ($plan_row) {
    $weekly_plan = json_decode($plan_row['plan_data'], true);
    // คำนวณวันที่เริ่มต้นของแผน
    $plan_start_date = new DateTime($plan_row['created_at']);
}
?>

<?php
// ส่วนนี้ใช้สำหรับสร้างปฏิทินของเดือนปัจจุบัน ไม่เกี่ยวกับข้อมูลแผนโดยตรง
$today = new DateTime();
$year = $today->format('Y');
$month = $today->format('m');
$month_start = new DateTime("$year-$month-01");
$month_end = (clone $month_start)->modify('last day of this month');

$start_week_day = $month_start->format('w'); // Sunday = 0
$end_week_day = $month_end->format('w');

$calendar_start = (clone $month_start)->modify("-$start_week_day days");
$calendar_end = (clone $month_end)->modify("+" . (6 - $end_week_day) . " days");

$interval = new DateInterval('P1D');
$period = new DatePeriod($calendar_start, $interval, $calendar_end->modify('+1 day'));
?>

<div class="container py-5" >
    <div class="text-center mb-4" style="padding-top: 50px;">
        <h1 class="fw-bold gradient-text">
            <i class="bi bi-calendar3 me-2"></i> ปฏิทินแผนอาหาร AI
        </h1>
        <p>เราได้ใช้ข้อมูลของคุณเพื่อวางแผนมื้ออาหารให้เหมาะกับร่างกายและไลฟ์สไตล์ของคุณ <br>เพื่อให้ระบบสามารถสร้างแผนอาหารเฉพาะบุคคลได้อย่างมีประสิทธิภาพ <br> ให้คุณได้แผนอาหารที่ดีต่อสุขภาพ เข้ากับเป้าหมาย และง่ายต่อการทําตามในชีวิตประจําวัน</p>
    </div>

    <div class="alert alert-warning d-flex align-items-center mb-4" role="alert">
        <i class="bi bi-info-circle-fill flex-shrink-0 me-3 fs-4"></i>
        <div>
            <strong>ข้อควรทราบ:</strong> แผนอาหารนี้สร้างขึ้นโดย AI เพื่อเป็นแนวทางเบื้องต้น แผนที่ได้อาจมีความคลาดเคลื่อนและไม่สามารถใช้แทนคำแนะนำจากแพทย์หรือนักโภชนาการได้ หากคุณมีข้อกังวลด้านสุขภาพหรือโรคประจำตัว ควรปรึกษาผู้เชี่ยวชาญก่อนเริ่มแผนอาหารใหม่เสมอ
        </div>
    </div>

    <div class="text-center mb-4" >
        <p class="text-muted">เดือน <?php echo $month_start->format('F Y'); ?> - คลิกวันที่เพื่อดูแผนเมนู</p>
    </div>

    <?php if ($weekly_plan): ?>
        <div class="table-responsive" style="min-height: 450px;">
            <table class="table table-bordered text-center align-middle">
                <thead>
                    <tr>
                        <th class="th-sun">อา</th>
                        <th class="th-mon">จ</th>
                        <th class="th-tue">อ</th>
                        <th class="th-wed">พ</th>
                        <th class="th-thu">พฤ</th>
                        <th class="th-fri">ศ</th>
                        <th class="th-sat">ส</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $day_count = 0;
                    foreach ($period as $date) {
                        if ($day_count % 7 === 0) echo '<tr>';

                        $date_str_ymd = $date->format('Y-m-d');
                        $display_day = $date->format('j');
                        $is_current_month = $date->format('m') === $month;

                        $td_classes = [];
                        if (!$is_current_month) $td_classes[] = 'text-muted bg-light';

                        // ตรวจสอบว่าวันนี้มีแผนหรือไม่
                        $has_plan_for_this_date = false;
                        $day_offset = 0;
                        if (is_array($weekly_plan)) {
                            foreach ($weekly_plan as $day_key => $meals) {
                                $current_plan_date = clone $plan_start_date;
                                $current_plan_date->modify("+$day_offset days");
                                if ($current_plan_date->format('Y-m-d') === $date_str_ymd) {
                                    $has_plan_for_this_date = true;
                                    break;
                                }
                                $day_offset++;
                            }
                        }

                        if ($has_plan_for_this_date) {
                            $td_classes[] = 'table-success';
                        }
                        
                        echo '<td class="' . implode(' ', $td_classes) . '">';
                        echo '<div><strong>' . $display_day . '</strong><br>';

                        if ($has_plan_for_this_date) {
                            echo '<button class="btn btn-sm btn-outline-primary mt-1" data-bs-toggle="modal" data-bs-target="#modal-' . $date->format('Ymd') . '">ดูแผน</button>';
                        } else {
                            if ($is_current_month) {
                                echo '<span class="text-muted small">ไม่มีแผน</span>';
                            }
                        }

                        echo '</div></td>';

                        if ($day_count % 7 === 6) echo '</tr>';
                        $day_count++;
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <?php
        $offset = 0;
        foreach ($weekly_plan as $day_key => $daily_plan):
            $date = clone $plan_start_date;
            $date->modify("+$offset days");
            $date_str = $date->format('Ymd');
            $formatted_date = $date->format('d/m/Y');
            $offset++;
        ?>
            <div class="modal fade" id="modal-<?php echo $date_str; ?>" tabindex="-1" aria-labelledby="label-<?php echo $date_str; ?>" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 id="label-<?php echo $date_str; ?>" class="modal-title">
                                <i class="bi bi-journal-text me-2 text-primary"></i> แผนอาหารวันที่ <?php echo $formatted_date; ?> (<?php echo htmlspecialchars($day_key); ?>)
                            </h5>
                            <button class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body bg-light">
                            <?php if (is_array($daily_plan) && !empty($daily_plan)): ?>
                                <div class="row g-3">
                                    <?php foreach ($daily_plan as $meal_name => $recipe_details): ?>
                                        <?php if (is_array($recipe_details) && isset($recipe_details['recipe_name'])): ?>
                                            <div class="col-md-6">
                                                <div class="card h-100 shadow-sm">
                                                    <div class="row g-0 align-items-center">
                                                        <div class="col-4">
                                                            <?php 
                                                            $image_url = !empty($recipe_details['image_url']) ? htmlspecialchars($recipe_details['image_url']) : 'https://via.placeholder.com/150'; 
                                                            ?>
                                                            <img src="<?php echo $image_url; ?>" class="img-fluid rounded-start recipe-image" style="width:100%; height:150px; object-fit:cover;" alt="<?php echo htmlspecialchars($recipe_details['recipe_name']); ?>">
                                                        </div>
                                                        <div class="col-8">
                                                            <div class="card-body">
                                                                <h6 class="card-title text-primary"><?php echo htmlspecialchars($meal_name); ?></h6>
                                                                <p class="card-text mb-1 recipe-name"><?php echo htmlspecialchars($recipe_details['recipe_name']); ?></p>
                                                                <p class="card-text">
                                                                    <small class="text-muted recipe-calories"><?php echo htmlspecialchars($recipe_details['calories']); ?> Kcal</small>
                                                                </p>
                                                                <div class="btn-group mt-2" role="group">
                                                                    <button class="btn btn-sm btn-outline-info view-details-btn"
                                                                            data-details='<?php echo htmlspecialchars(json_encode($recipe_details, JSON_UNESCAPED_UNICODE)); ?>'>
                                                                        <i class="bi bi-info-circle"></i> ดูข้อมูล
                                                                    </button>
                                                                    <button class="btn btn-sm btn-outline-secondary change-meal-btn" 
                                                                            data-day-key="<?php echo htmlspecialchars($day_key); ?>"
                                                                            data-meal-name="<?php echo htmlspecialchars($meal_name); ?>">
                                                                        <i class="bi bi-arrow-repeat"></i> เปลี่ยน
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-center text-muted">ไม่พบข้อมูลเมนูสำหรับวันนี้</p>
                            <?php endif; ?>
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <div class="text-end mt-4">
            <a href="process/generate_plan_process.php" class="btn btn-danger"><i class="bi bi-arrow-repeat"></i> สร้างแผนใหม่</a>
        </div>
    <?php else: ?>
        <div class="text-center mt-5">
            <i class="bi bi-magic fs-1 text-primary"></i>
            <h2 class="mt-3 text-dark">ยังไม่มีแผนอาหาร</h2>
            <p class="text-muted">กดปุ่มด้านล่างเพื่อให้ AI สร้างแผนอาหารสุขภาพสำหรับคุณ</p>
            <a href="process/generate_plan_process.php" class="btn btn-primary btn-lg mt-3 shadow">
                <i class="bi bi-robot me-1"></i> สร้างแผนอาหาร 7 วัน
            </a>
        </div>
    <?php endif; ?>
</div>

<!-- Modal สำหรับแสดงข้อมูลโภชนาการ -->
<div class="modal fade" id="nutritionDetailModal" tabindex="-1" aria-labelledby="nutritionDetailModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="nutritionDetailModalLabel"><i class="bi bi-bar-chart-line-fill text-success me-2"></i> ข้อมูลโภชนาการ</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="nutrition-modal-body">
        <!-- Content will be loaded by JavaScript -->
      </div>
       <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
        </div>
    </div>
  </div>
</div>


<!-- เพิ่มโค้ด JavaScript ส่วนนี้ก่อนการเรียก footer -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const nutritionModalEl = document.getElementById('nutritionDetailModal');
    const nutritionModal = nutritionModalEl ? new bootstrap.Modal(nutritionModalEl) : null;
    const nutritionModalBody = document.getElementById('nutrition-modal-body');

    // Store the original HTML structure of the modal body
    const originalModalBodyHTML = `
        <h4 id="detail-recipe-name" class="text-center mb-3"></h4>
        <div class="row">
            <div class="col-md-6">
                 <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center"><strong>พลังงาน</strong> <span id="detail-calories" class="badge bg-primary rounded-pill"></span></li>
                    <li class="list-group-item d-flex justify-content-between align-items-center"><strong>โปรตีน</strong> <span id="detail-protein" class="badge bg-secondary rounded-pill"></span></li>
                    <li class="list-group-item d-flex justify-content-between align-items-center"><strong>คาร์โบไฮเดรต</strong> <span id="detail-carbs" class="badge bg-secondary rounded-pill"></span></li>
                    <li class="list-group-item d-flex justify-content-between align-items-center"><strong>ไขมัน</strong> <span id="detail-fat" class="badge bg-secondary rounded-pill"></span></li>
                    <li class="list-group-item d-flex justify-content-between align-items-center"><strong>ไขมันอิ่มตัว</strong> <span id="detail-saturated_fat_g" class="badge bg-secondary rounded-pill"></span></li>
                    <li class="list-group-item d-flex justify-content-between align-items-center"><strong>คอเลสเตอรอล</strong> <span id="detail-cholesterol_mg" class="badge bg-secondary rounded-pill"></span></li>
                    <li class="list-group-item d-flex justify-content-between align-items-center"><strong>โซเดียม</strong> <span id="detail-sodium_mg" class="badge bg-secondary rounded-pill"></span></li>
                    <li class="list-group-item d-flex justify-content-between align-items-center"><strong>น้ำตาล</strong> <span id="detail-sugar_g" class="badge bg-secondary rounded-pill"></span></li>
                    <li class="list-group-item d-flex justify-content-between align-items-center"><strong>ใยอาหาร</strong> <span id="detail-fiber_g" class="badge bg-secondary rounded-pill"></span></li>
                </ul>
            </div>
             <div class="col-md-6">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center"><strong>วิตามิน A</strong> <span id="detail-vitamin_a_ug" class="badge bg-secondary rounded-pill"></span></li>
                    <li class="list-group-item d-flex justify-content-between align-items-center"><strong>วิตามิน B</strong> <span id="detail-vitamin_b_mg" class="badge bg-secondary rounded-pill"></span></li>
                    <li class="list-group-item d-flex justify-content-between align-items-center"><strong>วิตามิน C</strong> <span id="detail-vitamin_c_mg" class="badge bg-secondary rounded-pill"></span></li>
                    <li class="list-group-item d-flex justify-content-between align-items-center"><strong>วิตามิน D</strong> <span id="detail-vitamin_d_ug" class="badge bg-secondary rounded-pill"></span></li>
                    <li class="list-group-item d-flex justify-content-between align-items-center"><strong>แคลเซียม</strong> <span id="detail-calcium_mg" class="badge bg-secondary rounded-pill"></span></li>
                    <li class="list-group-item d-flex justify-content-between align-items-center"><strong>ธาตุเหล็ก</strong> <span id="detail-iron_mg" class="badge bg-secondary rounded-pill"></span></li>
                    <li class="list-group-item d-flex justify-content-between align-items-center"><strong>โพแทสเซียม</strong> <span id="detail-potassium_mg" class="badge bg-secondary rounded-pill"></span></li>
                    <li class="list-group-item d-flex justify-content-between align-items-center"><strong>แมกนีเซียม</strong> <span id="detail-magnesium_mg" class="badge bg-secondary rounded-pill"></span></li>
                    <li class="list-group-item d-flex justify-content-between align-items-center"><strong>โอเมก้า 3</strong> <span id="detail-omega_3_g" class="badge bg-secondary rounded-pill"></span></li>
                </ul>
            </div>
        </div>
    `;

    document.body.addEventListener('click', async function(event) {
        
        const changeBtn = event.target.closest('.change-meal-btn');
        const detailsBtn = event.target.closest('.view-details-btn');

        // --- จัดการปุ่ม "ดูข้อมูล" ---
        if (detailsBtn && nutritionModal) {
            const initialDetailsJson = detailsBtn.dataset.details;
            if (!initialDetailsJson) return;

            // Show a loading state in the modal
            nutritionModalBody.innerHTML = '<div class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div><p class="mt-2">กำลังโหลดข้อมูล...</p></div>';
            nutritionModal.show();

            try {
                const initialDetails = JSON.parse(initialDetailsJson);
                const recipeId = initialDetails.id;

                if (!recipeId) {
                    throw new Error('ไม่พบ ID ของเมนู');
                }

                // Fetch full details from the new API
                const response = await fetch(`api/get_recipe_details.php?id=${recipeId}`);
                if (!response.ok) {
                    throw new Error(`Server responded with status: ${response.status}`);
                }
                
                const result = await response.json();
                if (result.success && result.data) {
                    const details = result.data;
                    
                    // Restore original modal content structure before populating
                    nutritionModalBody.innerHTML = originalModalBodyHTML;
                    
                    // Populate the restored content
                    document.getElementById('detail-recipe-name').textContent = details.recipe_name || 'N/A';
                    document.getElementById('detail-calories').textContent = (details.calories || 'N/A') + ' Kcal';
                    document.getElementById('detail-protein').textContent = (details.protein || 'N/A') + ' g';
                    document.getElementById('detail-carbs').textContent = (details.carbs || 'N/A') + ' g';
                    document.getElementById('detail-fat').textContent = (details.fat || 'N/A') + ' g';
                    document.getElementById('detail-saturated_fat_g').textContent = (details.saturated_fat_g || 'N/A') + ' g';
                    document.getElementById('detail-cholesterol_mg').textContent = (details.cholesterol_mg || 'N/A') + ' mg';
                    document.getElementById('detail-sodium_mg').textContent = (details.sodium_mg || 'N/A') + ' mg';
                    document.getElementById('detail-sugar_g').textContent = (details.sugar_g || 'N/A') + ' g';
                    document.getElementById('detail-fiber_g').textContent = (details.fiber_g || 'N/A') + ' g';
                    document.getElementById('detail-vitamin_a_ug').textContent = (details.vitamin_a_ug || 'N/A') + ' µg';
                    document.getElementById('detail-vitamin_b_mg').textContent = (details.vitamin_b_mg || 'N/A') + ' mg';
                    document.getElementById('detail-vitamin_c_mg').textContent = (details.vitamin_c_mg || 'N/A') + ' mg';
                    document.getElementById('detail-vitamin_d_ug').textContent = (details.vitamin_d_ug || 'N/A') + ' µg';
                    document.getElementById('detail-calcium_mg').textContent = (details.calcium_mg || 'N/A') + ' mg';
                    document.getElementById('detail-iron_mg').textContent = (details.iron_mg || 'N/A') + ' mg';
                    document.getElementById('detail-potassium_mg').textContent = (details.potassium_mg || 'N/A') + ' mg';
                    document.getElementById('detail-magnesium_mg').textContent = (details.magnesium_mg || 'N/A') + ' mg';
                    document.getElementById('detail-omega_3_g').textContent = (details.omega_3_g || 'N/A') + ' g';

                } else {
                    throw new Error(result.message || 'ไม่สามารถดึงข้อมูลเมนูได้');
                }

            } catch (e) {
                console.error("Error fetching or parsing recipe details:", e);
                nutritionModalBody.innerHTML = `<div class="text-center text-danger"><i class="bi bi-exclamation-triangle-fill fs-3"></i><p class="mt-2">เกิดข้อผิดพลาดในการโหลดข้อมูล: ${e.message}</p></div>`;
            }
        }

        // --- จัดการปุ่ม "เปลี่ยนเมนู" ---
        if (changeBtn) {
            event.preventDefault();

            const dayKey = changeBtn.dataset.dayKey;
            const mealName = changeBtn.dataset.mealName;
            
            const card = changeBtn.closest('.card');
            if (!card) return;

            const originalButtonContent = changeBtn.innerHTML;
            changeBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
            changeBtn.disabled = true;

            try {
                const response = await fetch('ajax/change_meal.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        day_key: dayKey,
                        meal_name: mealName
                    })
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const result = await response.json();

                if (result.success && result.data.new_meal) {
                    const newMeal = result.data.new_meal;
                    card.querySelector('.recipe-name').textContent = newMeal.recipe_name;
                    card.querySelector('.recipe-calories').textContent = newMeal.calories + ' Kcal';
                    
                    const image = card.querySelector('.recipe-image');
                    const placeholder = 'https://via.placeholder.com/150';
                    image.src = newMeal.image_url ? newMeal.image_url : placeholder;
                    image.alt = newMeal.recipe_name;

                    // อัปเดตข้อมูลสำหรับปุ่ม "ดูข้อมูล" ด้วย
                    const detailsButton = card.querySelector('.view-details-btn');
                    if(detailsButton) {
                        detailsButton.dataset.details = JSON.stringify(newMeal);
                    }

                } else {
                    alert('ไม่สามารถเปลี่ยนเมนูได้: ' + (result.message || 'เกิดข้อผิดพลาดไม่ทราบสาเหตุ'));
                }

            } catch (error) {
                console.error('Error changing meal:', error);
                alert('เกิดข้อผิดพลาดในการเชื่อมต่อเพื่อเปลี่ยนเมนู');
            } finally {
                changeBtn.innerHTML = originalButtonContent;
                changeBtn.disabled = false;
            }
        }
    });
});

        // Mobile Modal Enhancements
        document.addEventListener('DOMContentLoaded', function() {
            if (window.innerWidth <= 768) {
                // ทำให้ modal เต็มหน้าจอบนมือถือ
                document.querySelectorAll('.modal-dialog').forEach(dialog => {
                    dialog.classList.add('modal-fullscreen-sm-down');
                });
                
                // ปรับขนาด calendar cells
                document.querySelectorAll('.table td').forEach(cell => {
                    cell.addEventListener('click', function(e) {
                        if (window.innerWidth <= 480) {
                            // เปิด modal แบบเต็มหน้าจอบนมือถือขนาดเล็ก
                            const btn = this.querySelector('.btn');
                            if (btn) btn.click();
                        }
                    });
                });
            }
        });

</script>

<?php require_once 'includes/footer.php'; ?>
