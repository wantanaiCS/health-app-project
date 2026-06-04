<?php
session_start();
$page_title = "โปรไฟล์แผนของฉัน";
require_once 'includes/header.php';
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    echo '<script>window.location.href = "login.php";</script>';
    exit();
}
$user_id = $_SESSION['user_id'];

// 1. ดึงโปรไฟล์แผนที่ผู้ใช้สร้างเอง (พร้อม Tags)
$custom_profiles = [];
$sql_custom = "SELECT p.id, p.profile_name, p.description, p.plan_data, p.created_at, 
                      GROUP_CONCAT(t.name) as tags
               FROM plan_profiles p
               LEFT JOIN plan_profile_tags pt ON p.id = pt.plan_profile_id
               LEFT JOIN tags t ON pt.tag_id = t.id
               WHERE p.user_id = ?
               GROUP BY p.id
               ORDER BY p.created_at DESC";
$stmt_custom = $conn->prepare($sql_custom);
$stmt_custom->bind_param("i", $user_id);
$stmt_custom->execute();
$result_custom = $stmt_custom->get_result();
while ($row = $result_custom->fetch_assoc()) {
    $custom_profiles[] = $row;
}
$stmt_custom->close();

// 2. ดึงแผนทั้งหมดที่สร้างโดย AI
$ai_plans = [];
$sql_ai = "SELECT id, plan_data, created_at FROM weekly_plans WHERE user_id = ? ORDER BY created_at DESC";
$stmt_ai = $conn->prepare($sql_ai);
$stmt_ai->bind_param("i", $user_id);
$stmt_ai->execute();
$result_ai = $stmt_ai->get_result();
while ($row = $result_ai->fetch_assoc()) {
    $ai_plans[] = $row;
}
$stmt_ai->close();

$conn->close();
?>

<style>
    .profile-card { transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out; }
    .profile-card:hover { transform: translateY(-5px); box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important; }
    .profile-card-icon { font-size: 1.5rem; }
    #viewProfileModal .modal-body { background-color: #f8f9fa; }
    
    #plan-day-nav .list-group-item { border-radius: 0.5rem; margin-bottom: 0.5rem; }
    #plan-day-nav .list-group-item.active {
        background-color: var(--bs-primary);
        border-color: var(--bs-primary);
    }
    .day-detail-pane h5 { color: var(--bs-primary); }

    .calendar-table { width: 100%; text-align: center; border-collapse: collapse; }
    .calendar-table th { padding: 0.5rem; font-weight: bold; color: #6c757d; }
    .calendar-table td {
        border: 1px solid #dee2e6;
        vertical-align: top;
        text-align: left;
        padding: 8px;
        width: calc(100% / 7);
        aspect-ratio: 1 / 1;
    }
    .calendar-table td.day-with-plan {
        background-color: #e2f5ea;
        font-weight: bold;
        color: #155724;
    }
    .calendar-table td.clickable-day { cursor: pointer; }
    .calendar-table td.clickable-day:hover { background-color: #c8e6c9; }
    .calendar-table td .day-number { font-size: 1.1rem; }
    .calendar-table td.empty-day { background-color: #f8f9fa; }
    .calendar-table td.other-month-day {
        opacity: 0.7;
    }
</style>

<div class="container my-5" style="padding-top: 50px;">
    <div class="">
        <h1 class="text-center gradient-text"><i class="bi bi-bookmarks-fill text-primary"></i> โปรไฟล์แผนของฉัน</h1>
    </div>

    <p class="text-muted mb-4 text-center">
        เลือกโปรไฟล์แผนที่คุณต้องการ แล้วกด "นำไปใช้" เพื่อเริ่มแผนในวันที่คุณต้องการ
    </p>
    <div class="mb-3" id="source-filter-container">
        <strong class="me-2">ประเภทแผน:</strong>
        <div class="btn-group flex-wrap gap-2" role="group" id="source-buttons">
            <button type="button" class="btn btn-sm btn-secondary active" data-source="all">ทั้งหมด</button>
            <button type="button" class="btn btn-sm btn-outline-secondary" data-source="custom">แผนกำหนดเอง</button>
            <button type="button" class="btn btn-sm btn-outline-secondary" data-source="ai">แผนจาก AI</button>
        </div>
    </div>
    <div class="mb-4" id="tag-filter-container" style="display: none;">
        <strong class="me-2">กรองตามแท็ก:</strong>
        <div class="btn-group flex-wrap gap-2" role="group" id="tag-buttons">
            <button type="button" class="btn btn-sm btn-primary active" data-tag="all">ทั้งหมด</button>
        </div>
    </div>

    <div class="row g-4">
        <?php foreach ($custom_profiles as $profile): ?>
            <div class="col-md-6 col-lg-4" data-type="custom">
                <div class="card h-100 profile-card" id="profile-card-custom-<?php echo $profile['id']; ?>" data-tags="<?php echo htmlspecialchars($profile['tags']); ?>">
                    <div class="card-body">
                        <div class="d-flex align-items-start">
                            <i class="bi bi-person-badge-fill text-success profile-card-icon me-3"></i>
                            <div>
                                <h5 class="card-title"><?php echo htmlspecialchars($profile['profile_name']); ?></h5>
                                <p class="card-text small text-muted"><?php echo !empty($profile['description']) ? htmlspecialchars($profile['description']) : '<i>ไม่มีคำอธิบาย</i>'; ?></p>
                            </div>
                        </div>
                        <?php if (!empty($profile['tags'])): ?>
                            <div class="mt-2">
                                <?php
                                $tags_array = explode(',', $profile['tags']);
                                foreach ($tags_array as $tag):
                                ?>
                                    <span class="badge bg-info text-dark"><?php echo htmlspecialchars($tag); ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer bg-transparent border-0 d-flex justify-content-between align-items-center">
                        <span class="text-muted small">สร้างเมื่อ: <?php echo format_thai_date($profile['created_at']); ?></span>
                        <div class="btn-group">
                            <button class="btn btn-sm btn-success" onclick="openApplyModal(<?php echo $profile['id']; ?>, 'custom')"><i class="bi bi-calendar-plus-fill"></i> นำไปใช้</button>
                            <button class="btn btn-sm btn-outline-primary" onclick="viewProfileFromButton(<?php echo $profile['id']; ?>, 'custom', '<?php echo htmlspecialchars(addslashes($profile['profile_name'])); ?>')"><i class="bi bi-eye-fill"></i></button>
                            <button class="btn btn-sm btn-outline-danger" onclick="deleteProfile(<?php echo $profile['id']; ?>, 'custom')"><i class="bi bi-trash3-fill"></i></button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <?php foreach ($ai_plans as $plan): ?>
            <div class="col-md-6 col-lg-4" data-type="ai">
                <div class="card h-100 profile-card" id="profile-card-ai-<?php echo $plan['id']; ?>">
                       <div class="card-body">
                        <div class="d-flex align-items-start">
                            <i class="bi bi-robot text-info profile-card-icon me-3"></i>
                            <div>
                                <h5 class="card-title">แผนจาก AI</h5>
                                <p class="card-text small text-muted">แผนอาหาร 7 วันที่สร้างโดยอัตโนมัติ</p>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-transparent border-0 d-flex justify-content-between align-items-center">
                        <span class="text-muted small">สร้างเมื่อ: <?php echo format_thai_date($plan['created_at']); ?></span>
                        <div class="btn-group">
                            <button class="btn btn-sm btn-success" onclick="openApplyModal(<?php echo $plan['id']; ?>, 'ai')"><i class="bi bi-calendar-plus-fill"></i> นำไปใช้</button>
                            <button class="btn btn-sm btn-outline-primary" onclick="viewProfileFromButton(<?php echo $plan['id']; ?>, 'ai', 'แผนจาก AI (<?php echo htmlspecialchars(addslashes(format_thai_date($plan['created_at']))); ?>)', '<?php echo $plan['created_at']; ?>')"><i class="bi bi-eye-fill"></i></button>
                            <button class="btn btn-sm btn-outline-danger" onclick="deleteProfile(<?php echo $plan['id']; ?>, 'ai')"><i class="bi bi-trash3-fill"></i></button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if (empty($custom_profiles) && empty($ai_plans)): ?>
            <div class="col-12">
                <div class="text-center p-5 border rounded bg-light">
                    <i class="bi bi-journal-x fs-1 text-muted"></i>
                    <h3 class="mt-3">ไม่พบโปรไฟล์แผนอาหาร</h3>
                    <p class="text-muted">คุณยังไม่เคยบันทึกแผนอาหารของตัวเอง หรือยังไม่เคยให้ AI สร้างแผนให้</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="viewProfileModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div id="viewProfileModalHeader" class="d-flex justify-content-between align-items-center w-100">
                    <h5 class="modal-title" id="viewProfileModalLabel">รายละเอียดโปรไฟล์</h5>
                    </div>
                <button type="button" class="btn-close ms-2" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="viewProfileModalBody"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="applyPlanModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="applyPlanModalLabel">นำโปรไฟล์แผนไปใช้</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>เลือกวันที่คุณต้องการเริ่มต้นแผนนี้</p>
                <div class="mb-3">
                    <label for="start-date-input" class="form-label"><strong>เลือกวันเริ่มต้น</strong></label>
                    <input type="date" class="form-control" id="start-date-input">
                </div>
                <div class="form-text text-danger">
                    ข้อควรระวัง: หากในวันที่เลือกมีแผนอยู่แล้ว แผนเดิมจะถูกเขียนทับด้วยแผนใหม่นี้
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="button" id="confirm-apply-btn" class="btn btn-primary">ยืนยันการนำไปใช้</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // ---- Part 1: Prepare Data from PHP ----
    const allPlanData = {
        custom: {
            <?php
            $custom_items = [];
            foreach ($custom_profiles as $profile) {
                $plan_json = json_decode($profile['plan_data']) ? $profile['plan_data'] : '{}';
                $custom_items[] = "'{$profile['id']}': " . $plan_json;
            }
            echo implode(',', $custom_items);
            ?>
        },
        ai: {
            <?php
            $ai_items = [];
            foreach ($ai_plans as $plan) {
                 $plan_json = json_decode($plan['plan_data']) ? $plan['plan_data'] : '{}';
                $ai_items[] = "'{$plan['id']}': " . $plan_json;
            }
            echo implode(',', $ai_items);
            ?>
        }
    };

    // ---- Part 2: Prepare Tools and Variables ----
    const viewProfileModalEl = document.getElementById('viewProfileModal');
    const applyPlanModalEl = document.getElementById('applyPlanModal');

    if (!viewProfileModalEl || !applyPlanModalEl) {
        console.error('Modal elements not found!');
        return;
    }

    const viewProfileModal = new bootstrap.Modal(viewProfileModalEl);
    const applyPlanModal = new bootstrap.Modal(applyPlanModalEl);
    let currentPlanToApply = null;

    const mealNameMapping = {
        breakfast: 'มื้อเช้า', brunch: 'มื้อสาย', lunch: 'มื้อกลางวัน',
        afternoon_snack: 'มื้อบ่าย', dinner: 'มื้อเย็น'
    };
    
    // ---- Part 3: Make Functions Globally Accessible ----
    window.viewProfileFromButton = function(planId, planType, profileName, createdAt = null) {
        const planData = allPlanData[planType][planId];
        renderProfileModal(profileName, planData, planType, createdAt);
    };

    window.openApplyModal = function(planId, planType) {
        // [FIX] Store the ID and type, NOT the full plan data
        currentPlanToApply = { plan_id: planId, plan_type: planType }; 
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        const startDateInput = document.getElementById('start-date-input');
        if(startDateInput) {
            startDateInput.value = tomorrow.toISOString().split('T')[0];
            startDateInput.min = new Date().toISOString().split('T')[0]; // Prevent selecting past dates
        }
        applyPlanModal.show();
    };

    window.deleteProfile = async function(profileId, planType) {
        if (!confirm(`คุณแน่ใจหรือไม่ว่าต้องการลบโปรไฟล์แผนนี้อย่างถาวร?`)) return;
        try {
            const response = await fetch('process/delete_plan_profile.php', {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ profile_id: profileId, plan_type: planType })
            });
            const result = await response.json();
            if (result.success) {
                alert('ลบโปรไฟล์สำเร็จ');
                const cardElement = document.getElementById(`profile-card-${planType}-${profileId}`);
                if (cardElement) {
                    cardElement.style.transition = 'opacity 0.5s ease';
                    cardElement.style.opacity = '0';
                    setTimeout(() => cardElement.parentElement.remove(), 500);
                }
            } else { throw new Error(result.message || 'เกิดข้อผิดพลาดในการลบ'); }
        } catch (error) {
            console.error('Delete error:', error);
            alert(error.message);
        }
    };

    // ---- Part 4: Helper Functions for Modal Views ----
    
    function generateSingleCalendarHtml(year, month, planData) {
        const firstDayOfMonth = new Date(year, month, 1);
        const lastDayOfMonth = new Date(year, month + 1, 0);
        let html = '<table class="calendar-table"><thead><tr><th>อา</th><th>จ</th><th>อ</th><th>พ</th><th>พฤ</th><th>ศ</th><th>ส</th></tr></thead><tbody>';
        
        let currentDay = new Date(firstDayOfMonth);
        currentDay.setDate(currentDay.getDate() - currentDay.getDay());

        let lastDayInGrid = new Date(lastDayOfMonth);
        if (lastDayInGrid.getDay() !== 6) {
            lastDayInGrid.setDate(lastDayInGrid.getDate() + (6 - lastDayInGrid.getDay()));
        }

        while (currentDay <= lastDayInGrid) {
            if (currentDay.getDay() === 0) html += '<tr>';

            const dateStr = `${currentDay.getFullYear()}-${String(currentDay.getMonth() + 1).padStart(2, '0')}-${String(currentDay.getDate()).padStart(2, '0')}`;
            const dayPlanData = planData[dateStr];
            const hasPlan = dayPlanData && dayPlanData.plan && Object.values(dayPlanData.plan).some(meal => meal && meal.length > 0);
            const isCurrentMonth = currentDay.getMonth() === month;

            let cellClasses = [];
            let cellAttributes = '';
            let cellContent = '';

            if (isCurrentMonth || hasPlan) {
                cellContent = `<strong class="day-number">${currentDay.getDate()}</strong>`;
                if (hasPlan) {
                    cellClasses.push('day-with-plan', 'clickable-day');
                    cellAttributes = `data-date="${dateStr}"`;
                    const totalCalories = dayPlanData.totals ? Math.round(dayPlanData.totals.calories) : 0;
                    cellContent += `<div class="small text-success fw-bold mt-1">${totalCalories} Kcal</div>`;
                }
                if (!isCurrentMonth && hasPlan) {
                    cellClasses.push('other-month-day');
                }
            } else {
                cellClasses.push('empty-day');
                cellContent = '&nbsp;';
            }

            html += `<td class="${cellClasses.join(' ')}" ${cellAttributes}>${cellContent}</td>`;

            if (currentDay.getDay() === 6) html += '</tr>';
            currentDay.setDate(currentDay.getDate() + 1);
        }

        html += '</tbody></table>';
        return html;
    }
    
    function renderProfileModal(profileName, planData, planType, createdAt = null) {
        const modalHeader = document.getElementById('viewProfileModalHeader');
        const modalBodyEl = document.getElementById('viewProfileModalBody');

        modalHeader.innerHTML = `
            <h5 class="modal-title flex-grow-1"><i class="bi bi-journal-text me-2"></i> ${profileName}</h5>
            <button id="toggle-view-btn" class="btn btn-outline-secondary btn-sm" type="button" style="display: none;">
                <i class="bi bi-calendar3"></i> ดูปฏิทิน
            </button>`;
        
        const planEntries = Object.keys(planData).sort();

        if (planEntries.length === 0) {
            modalBodyEl.innerHTML = '<p class="text-center text-muted p-5">โปรไฟล์นี้ไม่มีข้อมูลแผนอาหาร</p>';
            viewProfileModal.show();
            return;
        }

        modalBodyEl.innerHTML = `
            <div id="detail-view-container">
                <div class="row">
                    <div class="col-12 col-md-4 border-end pe-3">
                        <div class="list-group list-group-flush" id="plan-day-nav"></div>
                    </div>
                    <div class="col-12 col-md-8 mt-3 mt-md-0 ps-md-4">
                        <div id="plan-day-details"></div>
                    </div>
                </div>
            </div>
            <div id="calendar-view-container" style="display: none;"></div>`;
        
        const navContainer = modalBodyEl.querySelector("#plan-day-nav");
        const detailsContainer = modalBodyEl.querySelector("#plan-day-details");
        const calendarContainer = modalBodyEl.querySelector("#calendar-view-container");
        const toggleBtn = modalHeader.querySelector("#toggle-view-btn");

        let navHtml = '';
        let detailsHtml = '';

        const startDate = createdAt ? new Date(createdAt) : null;

        planEntries.forEach((key, index) => {
            const dayData = planData[key];
            const isActive = index === 0;
            let navText;

            if (planType === 'custom') {
                navText = `วันที่ ${index + 1} <span class="small text-muted">(${new Date(key + 'T00:00:00').toLocaleDateString('th-TH', { day: 'numeric', month: 'short', year: '2-digit'})})</span>`;
            } else { // This is for AI Plan
                let formattedDate = '';
                if(startDate) {
                    const currentDate = new Date(startDate);
                    currentDate.setDate(currentDate.getDate() + index);
                    formattedDate = `(${currentDate.toLocaleDateString('th-TH', { day: 'numeric', month: 'short', year: '2-digit'})})`;
                }
                navText = `${key} <span class="small text-muted">${formattedDate}</span>`;
            }
            
            navHtml += `<a href="#" class="list-group-item list-group-item-action ${isActive ? 'active' : ''}" data-target-pane="pane-${index}" data-date-key="${key}">${navText}</a>`;
            detailsHtml += `<div class="day-detail-pane" id="pane-${index}" style="${isActive ? '' : 'display: none;'}">`;
            const mealData = planType === 'custom' ? dayData.plan : dayData;
            
            let mealCount = 0;
            if (mealData) {
                const mealKeys = planType === 'ai' ? Object.keys(mealData) : Object.keys(mealNameMapping);
                mealKeys.forEach(mealKey => {
                    const mealItems = mealData[mealKey];
                    const recipes = Array.isArray(mealItems) ? mealItems : (mealItems ? [mealItems] : []);
                    const mealTitle = mealNameMapping[mealKey] || mealKey;
                    if (recipes.length > 0) {
                         mealCount++;
                         detailsHtml += `<h5 class="mt-3"><strong>${mealTitle}</strong></h5><ul class="list-group list-group-flush">`;
                         recipes.forEach(recipe => {
                             if(recipe && (recipe.name || recipe.recipe_name)) {
                                const recipeName = recipe.name || recipe.recipe_name;
                                detailsHtml += `<li class="list-group-item d-flex align-items-center bg-transparent px-0"><img src="${recipe.image_url || 'https://via.placeholder.com/60'}" alt="${recipeName}" class="me-3" style="width:60px; height:60px; object-fit:cover; border-radius:8px;"><div>${recipeName}<br><small class="text-muted">${recipe.calories || 0} kcal</small></div></li>`;
                             }
                        });
                        detailsHtml += `</ul>`;
                    }
                });
            }

            if (mealCount === 0) {
                detailsHtml += '<div class="text-center text-muted p-4"><em>ไม่มีเมนูสำหรับวันนี้</em></div>';
            }
            detailsHtml += '</div>';
        });

        navContainer.innerHTML = navHtml;
        detailsContainer.innerHTML = detailsHtml;

        if (planType === 'custom') {
            toggleBtn.style.display = 'block';
            const months = {};
            planEntries.forEach(dateStr => { months[dateStr.substring(0, 7)] = true; });
            let calendarHtml = '';
            Object.keys(months).sort().forEach(monthKey => {
                const [year, month] = monthKey.split('-').map(Number);
                const monthName = new Date(year, month - 1, 1).toLocaleDateString('th-TH', { month: 'long', year: 'numeric' });
                calendarHtml += `<h5 class="text-center mb-3 mt-4">${monthName}</h5>`;
                calendarHtml += generateSingleCalendarHtml(year, month - 1, planData);
            });
            calendarContainer.innerHTML = calendarHtml;
        }

        toggleBtn.addEventListener('click', () => {
            const isCalendarVisible = calendarContainer.style.display !== 'none';
            calendarContainer.style.display = isCalendarVisible ? 'none' : 'block';
            modalBodyEl.querySelector("#detail-view-container").style.display = isCalendarVisible ? 'block' : 'none';
            toggleBtn.innerHTML = isCalendarVisible ? '<i class="bi bi-calendar3"></i> ดูปฏิทิน' : '<i class="bi bi-list-ul"></i> กลับไปที่รายการ';
        });

        navContainer.addEventListener('click', (e) => {
            e.preventDefault();
            const clickedLink = e.target.closest('a.list-group-item-action');
            if (!clickedLink) return;
            navContainer.querySelector('a.active')?.classList.remove('active');
            clickedLink.classList.add('active');
            const targetPaneId = clickedLink.dataset.targetPane;
            detailsContainer.querySelectorAll('.day-detail-pane').forEach(pane => pane.style.display = 'none');
            document.getElementById(targetPaneId).style.display = 'block';
        });
        
        calendarContainer.addEventListener('click', (e) => {
            const clickedDay = e.target.closest('.clickable-day');
            if (!clickedDay) return;
            const dateStr = clickedDay.dataset.date;
            const targetNavLink = navContainer.querySelector(`a[data-date-key="${dateStr}"]`);
            if (targetNavLink) {
                targetNavLink.click();
                toggleBtn.click();
            }
        });

        viewProfileModal.show();
    }
    
    // [FIXED] This function now sends the correct data to the new backend script.
    async function confirmApplyPlan() {
        const startDate = document.getElementById('start-date-input').value;
        if (!startDate) {
            alert('กรุณาเลือกวันเริ่มต้น');
            return;
        }
        if (!currentPlanToApply) {
            alert('เกิดข้อผิดพลาด: ไม่พบข้อมูลแผน');
            return;
        }

        const confirmBtn = document.getElementById('confirm-apply-btn');
        confirmBtn.disabled = true;
        confirmBtn.innerHTML = `<span class="spinner-border spinner-border-sm"></span> กำลังนำไปใช้...`;

        try {
            const response = await fetch('process/apply_plan_profile.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    start_date: startDate,
                    plan_id: currentPlanToApply.plan_id, // Send the ID
                    plan_type: currentPlanToApply.plan_type // Send the type
                })
            });
            const result = await response.json();
            if (result.success) {
                // [FIX] Redirect to the dashboard to see the active plan, not the custom planner.
                alert('นำแผนไปใช้สำเร็จ! กำลังไปยังหน้าแดชบอร์ด...');
                window.location.href = 'dashboard.php?plan_activated=true';
            } else {
                throw new Error(result.message || 'เกิดข้อผิดพลาด');
            }
        } catch (error) {
            alert(`เกิดข้อผิดพลาด: ${error.message}`);
            confirmBtn.disabled = false;
            confirmBtn.innerHTML = 'ยืนยันการนำไปใช้';
        }
    }
    
    // ---- Part 5: Attach Event Listeners ----
    document.getElementById('confirm-apply-btn').addEventListener('click', confirmApplyPlan);

    // ---- Part 6: Filtering System ----
    let currentSourceFilter = 'all';
    let currentTagFilter = 'all';
    const sourceButtonsContainer = document.getElementById('source-buttons');
    const tagButtonsContainer = document.getElementById('tag-buttons');
    const tagFilterContainer = document.getElementById('tag-filter-container');
    const allProfileCards = document.querySelectorAll('.row.g-4 > div');

    function populateTagFilters() {
        const tags = new Set();
        document.querySelectorAll('div[data-type="custom"] .card').forEach(card => {
            const cardTags = card.dataset.tags;
            if (cardTags) {
                cardTags.split(',').forEach(tag => {
                    if(tag) tags.add(tag.trim());
                });
            }
        });

        if (tags.size > 0) {
            tagFilterContainer.style.display = 'block';
            const sortedTags = Array.from(tags).sort();
            sortedTags.forEach(tag => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'btn btn-sm btn-outline-primary';
                button.dataset.tag = tag;
                button.textContent = tag;
                tagButtonsContainer.appendChild(button);
            });
        }
    }

    function applyFilters() {
        allProfileCards.forEach(cardContainer => {
            const card = cardContainer.querySelector('.card');
            const cardType = cardContainer.dataset.type;
            const cardTags = card.dataset.tags || '';
            const sourceMatch = (currentSourceFilter === 'all') || (cardType === currentSourceFilter);
            const tagMatch = (currentTagFilter === 'all') || (cardType !== 'custom') || (cardTags.split(',').includes(currentTagFilter));

            if (sourceMatch && tagMatch) {
                cardContainer.style.display = '';
            } else {
                cardContainer.style.display = 'none';
            }
        });
    }

    sourceButtonsContainer.addEventListener('click', (e) => {
        const button = e.target.closest('button');
        if (!button) return;
        
        sourceButtonsContainer.querySelectorAll('button').forEach(btn => {
            btn.classList.remove('active', 'btn-secondary');
            btn.classList.add('btn-outline-secondary');
        });
        button.classList.add('active', 'btn-secondary');
        button.classList.remove('btn-outline-secondary');

        currentSourceFilter = button.dataset.source;
        
        const tagsExist = tagButtonsContainer.querySelectorAll('button').length > 1;
        tagFilterContainer.style.display = (currentSourceFilter === 'ai' || !tagsExist) ? 'none' : 'block';
        
        applyFilters();
    });

    tagButtonsContainer.addEventListener('click', (e) => {
        const button = e.target.closest('button');
        if (!button) return;

        tagButtonsContainer.querySelectorAll('button').forEach(btn => {
            btn.classList.remove('active', 'btn-primary');
            btn.classList.add('btn-outline-primary');
        });
        button.classList.add('active', 'btn-primary');
        button.classList.remove('btn-outline-primary');
        
        currentTagFilter = button.dataset.tag;
        applyFilters();
    });

    populateTagFilters();
    applyFilters(); 
});
</script>

<?php require_once 'includes/footer.php'; ?>
