<?php
$base_path = '/health_app'; // << เปลี่ยนให้ตรงกับชื่อโปรเจกต์ของคุณ
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'FitMealWeek'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $base_path; ?>/assets/css/style.css?v=<?php echo filemtime($_SERVER['DOCUMENT_ROOT'] . $base_path . '/assets/css/style.css'); ?>">
    <link rel="stylesheet" href="<?php echo $base_path; ?>/css/calendar.css">

        <!-- Libraries Stylesheet -->
    <link href="<?php echo $base_path; ?>/assets/lib/animate/animate.min.css" rel="stylesheet">
    <link href="<?php echo $base_path; ?>/assets/lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">
    <link href="<?php echo $base_path; ?>/assets/lib/tempusdominus/css/tempusdominus-bootstrap-4.min.css" rel="stylesheet">

    <!-- FullCalendar CDN -->
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.css' rel='stylesheet' />
    
</head>
<body>  
<!-- Navbar & Hero Start -->
<nav class="navbar navbar-expand-lg navbar-dark px-4 px-lg-5 py-3 py-lg-0 sticky-top" style="background-color: var(--text-color); position: sticky; top: 0; z-index: 1030;">  
  <div class="container-fluid d-flex align-items-center justify-content-between">
    <a class="navbar-brand d-flex align-items-center" href="/health_app/dashboard.php">
        <img src="/health_app/assets/images/logo.png" alt="FitMealWeek Logo" class="navbar-logo me-2">
        <h1 class="m-0 d-flex align-items-center fs-4 fs-md-1">
            <span style="color: #B7D971;">Fit</span>
            <span style="color: #2FAAA8;" class="mx-1">Meal</span>
            <span style="color: #B7D971;">Week</span>
        </h1>
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
       <span class="fa fa-bars"></span>
    </button>
    <div class="collapse navbar-collapse justify-content-end align-items-center" id="navbarCollapse">
      <ul class="navbar-nav nav-gap py-0">
            <li class="nav-item">
          <a class="nav-link <?php if(basename($_SERVER['PHP_SELF']) == 'dashboard.php') echo 'active'; ?>" href="/health_app/dashboard.php">หน้าแรก</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php if(basename($_SERVER['PHP_SELF']) == 'weekly_plan_dashboard.php') echo 'active'; ?>" href="/health_app/weekly_plan_dashboard.php">แผนจาก AI</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php if(basename($_SERVER['PHP_SELF']) == 'custom_plan.php') echo 'active'; ?>" href="/health_app/custom_plan.php">กำหนดแผนเอง</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php if(basename($_SERVER['PHP_SELF']) == 'recipes.php') echo 'active'; ?>" href="/health_app/recipes.php">คลังสูตรอาหาร</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php if(basename($_SERVER['PHP_SELF']) == 'my_plans.php') echo 'active'; ?>" href="/health_app/my_plans.php">ประวัติของฉัน</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php if(basename($_SERVER['PHP_SELF']) == 'about.php') echo 'active'; ?>" href="/health_app/about.php">เกี่ยวกับเรา</a>
        </li>
        <?php if (isset($_SESSION['user_id'])): ?>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownMenuLink" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($_SESSION['username']); ?>
          </a>
          <ul class="dropdown-menu m-0" aria-labelledby="navbarDropdownMenuLink">
            <li><a class="dropdown-item" href="profile.php">แก้ไขข้อมูลสุขภาพ</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="logout.php">ออกจากระบบ</a></li>
          </ul>
        </li>
       <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

