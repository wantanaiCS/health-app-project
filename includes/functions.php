<?php

/**
 * ฟังก์ชันสำหรับคำนวณแคลอรี่ที่แนะนำต่อวันตามเป้าหมาย
 * @param array|null $profile ข้อมูลโปรไฟล์ของผู้ใช้
 * @return int แคลอรี่ที่แนะนำ (ปัดเศษ) หรือ 0 หากข้อมูลไม่ครบ
 */
function getRecommendedCalories($profile) {
    if (!isset($profile['bmr'], $profile['activity_level'], $profile['goal'])) {
        return 0; 
    }

    $tdee = $profile['bmr'] * $profile['activity_level'];
    $recommended_calories = $tdee; // ค่าพื้นฐานคือรักษาน้ำหนัก

    switch ($profile['goal']) {
        case 'เพิ่มน้ำหนัก':
            $recommended_calories = $tdee + 400;
            break;
        case 'ลดน้ำหนัก':
            $recommended_calories = $tdee - 500;
            break;
        case 'รักษาน้ำหนัก':
        default:
            $recommended_calories = $tdee;
            break;
    }
    
    return round($recommended_calories);
}

/**
 * ฟังก์ชันสำหรับแปลงวันที่ YYYY-mm เป็น เดือนและปี พ.ศ. ภาษาไทย
 * @param string $date_str วันที่ในรูปแบบ 'Y-m'
 * @return string เดือนและปีภาษาไทย
 */
function get_thai_month_year($date_str) {
    $thai_months = [
        '01' => 'มกราคม', '02' => 'กุมภาพันธ์', '03' => 'มีนาคม',
        '04' => 'เมษายน', '05' => 'พฤษภาคม', '06' => 'มิถุนายน',
        '07' => 'กรกฎาคม', '08' => 'สิงหาคม', '09' => 'กันยายน',
        '10' => 'ตุลาคม', '11' => 'พฤศจิกายน', '12' => 'ธันวาคม'
    ];
    $timestamp = strtotime($date_str);
    $month_num = date('m', $timestamp);
    $year_buddhist = date('Y', $timestamp) + 543;
    return $thai_months[$month_num] . ' พ.ศ. ' . $year_buddhist;
}

/**
 * ฟังก์ชันสำหรับแปลงวันที่ YYYY-MM-DD เป็น วัน เดือนย่อ ปี พ.ศ. ภาษาไทย
 * @param string $date_str วันที่ในรูปแบบ 'Y-m-d'
 * @return string วันที่และเดือนย่อปีเต็มภาษาไทย
 */
function format_thai_date($date_str) {
    if (empty($date_str)) {
        return '';
    }
    $date = new DateTime($date_str);
    $thai_months = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
    return $date->format('j ') . $thai_months[$date->format('n') - 1] . ' ' . ($date->format('Y') + 543);
}

?>