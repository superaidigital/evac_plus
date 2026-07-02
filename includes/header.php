<?php
/**
 * includes/header.php
 * ส่วนหัวของหน้าเว็บ (Navbar & Sidebar Structure)
 * ปรับปรุง: แก้ไขปุ่มย่อเมนูให้ทำงานชัวร์ 100% (CSS Fix & JS Logic)
 */

// เริ่ม Session หากยังไม่มี
if (session_status() == PHP_SESSION_NONE) { session_start(); }

// เชื่อมต่อฐานข้อมูลและโหลดฟังก์ชัน
require_once __DIR__ . '/../config/db.php'; 

$current_page = basename($_SERVER['PHP_SELF']);
$role = $_SESSION['role'] ?? 'guest';
$is_admin = ($role === 'admin');
$my_shelter_id = $_SESSION['shelter_id'] ?? '';

// ตรวจสอบสถานะการ Login (ถ้าไม่มี Session user_id ให้เด้งไป login)
if (!isset($_SESSION['user_id']) && $current_page != 'login.php' && $current_page != 'login_db.php') {
    header("Location: login.php");
    exit();
}

// ดึงชื่อศูนย์ที่รับผิดชอบมาแสดง
$user_shelter_name = '';
if ($my_shelter_id && isset($conn)) {
    $stmt_sh = $conn->prepare("SELECT name FROM shelters WHERE id = ?");
    if ($stmt_sh) {
        $stmt_sh->bind_param("i", $my_shelter_id);
        $stmt_sh->execute();
        $res_sh = $stmt_sh->get_result();
        if ($row_sh = $res_sh->fetch_assoc()) {
            $user_shelter_name = $row_sh['name'];
        }
        $stmt_sh->close();
    }
}
$user_name = $_SESSION['username'] ?? 'Guest';
$user_role = $_SESSION['role'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบบริหารจัดการศูนย์พักพิงอัจฉริยะ</title>
    
    <!-- Google Fonts: Prompt -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 & FontAwesome 6 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    
    <!-- Plugins CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css">
    <link rel="stylesheet" href="https://earthchie.github.io/jquery.Thailand.js/jquery.Thailand.js/dist/jquery.Thailand.min.css">

    <style>
        :root {
            --sidebar-width: 270px;
            --nav-bg: #0f172a;
            --nav-hover: #1e293b;
            --active-gold: #fbbf24;
            --text-main: #f1f5f9;
            --text-muted: #94a3b8;
            --body-bg: #f8fafc;
            --header-height: 70px;
            --primary-color: #0d6efd;
        }

        body {
            font-family: 'Prompt', sans-serif;
            background-color: var(--body-bg);
            color: #334155;
            overflow-x: hidden;
            margin: 0;
            transition: margin-left 0.3s ease;
        }

        .wrapper { 
            display: block; 
            width: 100%; 
        }

        /* Sidebar Styling */
        #sidebar {
            width: var(--sidebar-width);
            min-width: var(--sidebar-width);
            max-width: var(--sidebar-width);
            background-color: var(--nav-bg);
            color: var(--text-main);
            transition: margin-left 0.3s ease-in-out;
            position: fixed;
            top: 0; left: 0;
            height: 100vh;
            z-index: 1050;
            box-shadow: 4px 0 20px rgba(0,0,0,0.15);
            overflow-y: auto;
        }

        /* Content Styling */
        #content {
            width: auto;
            min-height: 100vh;
            transition: margin-left 0.3s ease-in-out;
            margin-left: var(--sidebar-width); /* Default state: Content pushed right */
            position: relative;
        }

        /* --- COLLAPSED STATE (Desktop) --- */
        /* เมื่อ Body มี class 'sidebar-collapsed' */
        
        /* 1. ดัน Sidebar ออกไปทางซ้ายจนมองไม่เห็น */
        body.sidebar-collapsed #sidebar { 
            margin-left: -270px !important; 
        }

        /* 2. ดึง Content กลับมาชิดซ้ายเต็มจอ */
        body.sidebar-collapsed #content {
            margin-left: 0 !important;
        }
        
        /* --- MOBILE STATE (< 992px) --- */
        @media (max-width: 991.98px) {
            /* Default Mobile: Sidebar ซ่อนอยู่ซ้ายสุด */
            #sidebar { margin-left: -270px; }
            
            /* Default Mobile: Content เต็มจอ */
            #content { margin-left: 0; }
            
            /* Mobile Active: แสดง Sidebar (เลื่อนกลับมาที่ 0) */
            #sidebar.show-mobile { margin-left: 0 !important; }
        }

        /* Mobile Overlay */
        .sidebar-overlay {
            display: none;
            position: fixed;
            width: 100vw; height: 100vh;
            background: rgba(15, 23, 42, 0.5);
            z-index: 1040;
            backdrop-filter: blur(2px);
            opacity: 0;
            transition: all 0.3s;
            top: 0; left: 0;
        }
        .sidebar-overlay.active { display: block; opacity: 1; }

        /* Components */
        .sidebar-header {
            padding: 25px 20px;
            background: rgba(0,0,0,0.2);
            text-align: center;
            border-bottom: 3px solid var(--active-gold);
        }

        .user-panel {
            padding: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            display: flex; align-items: center; gap: 12px;
        }

        .user-avatar {
            width: 42px; height: 42px;
            background: var(--active-gold);
            color: var(--nav-bg);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-weight: bold; font-size: 1.2rem;
        }

        .menu-label {
            font-size: 0.7rem; text-transform: uppercase;
            color: var(--text-muted); padding: 20px 25px 8px;
            font-weight: 600; letter-spacing: 1px;
        }

        ul.components { padding: 0; list-style: none; }
        ul.components li a {
            padding: 12px 25px; font-size: 0.95rem;
            display: flex; align-items: center;
            color: var(--text-muted); text-decoration: none;
            transition: all 0.2s; border-left: 4px solid transparent;
        }

        ul.components li a:hover, ul.components li a.active {
            color: #fff; background-color: var(--nav-hover);
            border-left-color: var(--active-gold);
        }

        ul.components li a i { width: 24px; text-align: center; margin-right: 12px; font-size: 1.1rem; }
        ul.components li a.active i { color: var(--active-gold); }

        .dropdown-toggle::after { display: none !important; }
        .dropdown-icon { margin-left: auto; font-size: 0.75rem; transition: transform 0.3s; opacity: 0.5; }
        a[aria-expanded="true"] .dropdown-icon { transform: rotate(180deg); opacity: 1; color: var(--active-gold); }

        ul.collapse { background-color: rgba(0,0,0,0.1); list-style: none; padding: 0; }
        ul.collapse li a { padding-left: 60px; font-size: 0.88rem; border-left: none; }
        ul.collapse li a:hover { border-left: none; background-color: rgba(255,255,255,0.05); }

        /* Top Navbar */
        .top-navbar {
            height: var(--header-height);
            background: #fff; border-bottom: 1px solid #e2e8f0;
            padding: 0 25px; display: flex; align-items: center;
            justify-content: space-between; position: sticky; top: 0; z-index: 900;
            width: 100%;
        }

        #sidebarCollapse {
            width: 40px; height: 40px;
            display: flex; align-items: center; justify-content: center;
            border-radius: 10px; background: #f1f5f9; color: #475569;
            cursor: pointer; transition: all 0.2s; border: 1px solid #e2e8f0;
        }
        #sidebarCollapse:hover { background: #fff; color: var(--active-gold); border-color: var(--active-gold); }
    </style>
</head>
<body class="<?php echo (isset($_COOKIE['sidebar_state']) && $_COOKIE['sidebar_state'] == 'collapsed') ? 'sidebar-collapsed' : ''; ?>">

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="wrapper">
    <!-- Sidebar -->
    <nav id="sidebar">
        <div class="sidebar-header">
            <div class="mb-2"><i class="fas fa-shield-alt fa-2x text-warning"></i></div>
            <div class="fw-bold text-white h6 mb-0">ระบบบริหารศูนย์พักพิงฯ</div>
            <div class="text-white small" style="font-size: 0.6rem;">SMART DISASTER MANAGEMENT</div>
        </div>

        <?php if(isset($_SESSION['user_id'])): ?>
            <div class="user-panel">
                <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1)); ?></div>
                <div class="overflow-hidden">
                    <div class="text-white small fw-bold text-truncate"><?php echo $_SESSION['username'] ?? 'User'; ?></div>
                    <div class="text-warning text-truncate" style="font-size: 0.7rem;">
                        <i class="fas fa-map-marker-alt me-1"></i><?php echo $user_shelter_name ?: 'ส่วนกลาง'; ?>
                    </div>
                </div>
            </div>

            <ul class="components">
                <li>
                    <a href="index.php" class="<?php echo $current_page == 'index.php' ? 'active' : ''; ?>">
                        <i class="fas fa-th-large"></i> <span>หน้าหลักภาพรวม</span>
                    </a>
                </li>

                <li class="menu-label">War Room & สถานการณ์</li>
                <li><a href="monitor_dashboard.php" target="_blank"><i class="fas fa-desktop text-danger"></i> <span>War Room (Live)</span></a></li>
                <li><a href="gis_dashboard.php" class="<?php echo $current_page == 'gis_dashboard.php' ? 'active' : ''; ?>"><i class="fas fa-map-location-dot text-info"></i> <span>แผนที่ GIS</span></a></li>

                <li class="menu-label">งานทะเบียน & ผู้อพยพ</li>
                <li>
                    <a href="#evacueeSubmenu" data-bs-toggle="collapse" class="dropdown-toggle <?php echo (strpos($current_page, 'evacuee') !== false) ? 'active' : ''; ?>">
                        <i class="fas fa-users"></i> <span>ทะเบียนผู้ประสบภัย</span>
                        <i class="fas fa-chevron-down dropdown-icon"></i>
                    </a>
                    <ul class="collapse <?php echo (strpos($current_page, 'evacuee') !== false) ? 'show' : ''; ?>" id="evacueeSubmenu">
                        <li><a href="evacuee_list.php" class="<?php echo $current_page == 'evacuee_list.php' ? 'active' : ''; ?>">บัญชีรายชื่อ</a></li>
                        <li><a href="qr_scanner.php" class="<?php echo $current_page == 'qr_scanner.php' ? 'active' : ''; ?>">สแกน QR Code</a></li>
                    </ul>
                </li>

                <li class="menu-label">ทรัพยากร & คลัง</li>
                <li>
                    <a href="#logisticsSubmenu" data-bs-toggle="collapse" class="dropdown-toggle <?php echo (strpos($current_page, 'inventory') !== false) ? 'active' : ''; ?>">
                        <i class="fas fa-boxes-stacked"></i> <span>จัดการพัสดุ</span>
                        <i class="fas fa-chevron-down dropdown-icon"></i>
                    </a>
                    <ul class="collapse <?php echo (strpos($current_page, 'inventory') !== false) ? 'show' : ''; ?>" id="logisticsSubmenu">
                        <li><a href="inventory_dashboard.php" class="<?php echo $current_page == 'inventory_dashboard.php' ? 'active' : ''; ?>">แดชบอร์ดคลัง</a></li>
                        <li><a href="distribution_manager.php" class="<?php echo $current_page == 'distribution_manager.php' ? 'active' : ''; ?>">บันทึกการแจกจ่าย</a></li>
                    </ul>
                </li>
                
                <li class="menu-label">รายงาน & สถิติ</li>
                <li>
                    <a href="report_center.php" class="<?php echo (strpos($current_page, 'report_') !== false) ? 'active' : ''; ?>">
                        <i class="fas fa-chart-line"></i> <span>ศูนย์รวมรายงาน</span>
                    </a>
                </li>

                <?php if($is_admin || $role === 'staff'): ?>
                <li class="menu-label">บริหารจัดการ</li>
                <li>
                    <a href="incident_manager.php" class="<?php echo $current_page == 'incident_manager.php' ? 'active' : ''; ?>">
                        <i class="fas fa-house-tsunami"></i> <span>จัดการเหตุการณ์</span>
                    </a>
                </li>
                <li>
                    <a href="shelter_list.php" class="<?php echo $current_page == 'shelter_list.php' ? 'active' : ''; ?>">
                        <i class="fas fa-campground"></i> <span>ข้อมูลศูนย์พักพิง</span>
                    </a>
                </li>
                <?php endif; ?>

                <?php if($is_admin): ?>
                <li>
                    <a href="user_manager.php" class="<?php echo $current_page == 'user_manager.php' ? 'active' : ''; ?>">
                        <i class="fas fa-users-cog"></i> <span>จัดการผู้ใช้งาน</span>
                    </a>
                </li>
                <?php endif; ?>

                <li class="mt-4 px-3">
                    <a href="#" onclick="confirmLogout(event)" class="btn btn-danger btn-sm w-100 text-white border-0 shadow-sm py-2">
                        <i class="fas fa-sign-out-alt me-2"></i> ออกจากระบบ
                    </a>
                </li>
            </ul>
        <?php endif; ?>
    </nav>

    <!-- Page Content Area -->
    <div id="content">
        <nav class="top-navbar shadow-sm">
            <div class="d-flex align-items-center">
                <!-- Toggle Button with Explicit onclick -->
                <div id="sidebarCollapse" onclick="toggleSidebar()" style="cursor: pointer;">
                    <i class="fas fa-bars"></i>
                </div>
                <div class="ms-3 d-none d-sm-block fw-bold text-dark">
                    <?php 
                        if($current_page == 'index.php') echo 'ภาพรวมสถานการณ์';
                        elseif($current_page == 'evacuee_list.php') echo 'ทะเบียนรายชื่อผู้ประสบภัย';
                        elseif(strpos($current_page, 'report_') !== false) echo 'ระบบรายงานและการวิเคราะห์';
                        elseif($current_page == 'incident_manager.php') echo 'จัดการเหตุการณ์ภัยพิบัติ';
                        elseif($current_page == 'shelter_list.php') echo 'บริหารจัดการศูนย์พักพิง';
                        else echo 'ระบบบริหารจัดการศูนย์พักพิง';
                    ?>
                </div>
            </div>

            <div class="d-flex align-items-center gap-3">
                <a href="family_finder.php" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold">
                    <i class="fas fa-search me-1"></i> สำหรับประชาชน
                </a>
                <div class="vr d-none d-md-block" style="height: 20px;"></div>
                
                <div class="dropdown">
                    <button class="btn btn-light rounded-pill d-flex align-items-center gap-2 px-3 dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="border: 1px solid #e2e8f0;">
                        <div class="user-avatar" style="width: 28px; height: 28px; font-size: 0.8rem;">
                            <?php echo strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1)); ?>
                        </div>
                        <span class="d-none d-md-block small fw-bold"><?php echo $_SESSION['username'] ?? 'User'; ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2">
                        <li><h6 class="dropdown-header text-muted">บัญชีผู้ใช้</h6></li>
                        <li><a class="dropdown-item" href="#"><i class="fas fa-user-circle me-2"></i> โปรไฟล์</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="#" onclick="confirmLogout(event)"><i class="fas fa-sign-out-alt me-2"></i> ออกจากระบบ</a></li>
                    </ul>
                </div>
            </div>
        </nav>

        <div class="p-3 p-md-4">
            <!-- Content Starts Here -->
            
<!-- JavaScript Libraries -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    /**
     * Function to toggle sidebar visibility/collapse state
     * Handles both desktop (collapse) and mobile (show/hide) modes
     */
    function toggleSidebar() {
        const body = document.body;
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        const windowWidth = window.innerWidth;
        
        // Debugging to console to ensure function is called
        console.log("Toggle Sidebar Clicked. Window Width:", windowWidth);

        if (windowWidth < 992) {
            // Mobile Mode: Toggle visibility
            if(sidebar) sidebar.classList.toggle('show-mobile');
            if(overlay) overlay.classList.toggle('active');
        } else {
            // Desktop Mode: Toggle collapse state on BODY
            body.classList.toggle('sidebar-collapsed');
            
            // Save state to cookie
            const isCollapsed = body.classList.contains('sidebar-collapsed');
            document.cookie = "sidebar_state=" + (isCollapsed ? "collapsed" : "expanded") + "; path=/; max-age=31536000";
        }
    }

    // Initialize Event Listeners when DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
        const overlay = document.getElementById('sidebarOverlay');
        
        // Handle Overlay Click (Close menu on mobile)
        if(overlay) {
            overlay.addEventListener('click', function() {
                const sidebar = document.getElementById('sidebar');
                if(sidebar) sidebar.classList.remove('show-mobile');
                this.classList.remove('active');
            });
        }
    });

    // Logout Confirmation Function
    function confirmLogout(e) {
        e.preventDefault();
        Swal.fire({
            title: 'ยืนยันการออกจากระบบ?',
            text: "คุณต้องการออกจากระบบใช่หรือไม่",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'ออกจากระบบ',
            cancelButtonText: 'ยกเลิก',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'logout.php';
            }
        });
    }
</script>