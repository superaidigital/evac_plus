<?php
session_start();
require_once 'config/db.php';
include 'includes/header.php';

$sql = "SELECT vulnerable_group, gender, age FROM evacuees";
$result = $conn->query($sql);
$stats = ['total'=>0, 'elderly'=>0, 'child'=>0, 'disabled'=>0, 'bedridden'=>0, 'male'=>0, 'female'=>0];

while($row = $result->fetch_assoc()) {
    $stats['total']++;
    if($row['gender'] == 'male') $stats['male']++; else $stats['female']++;
    if($row['vulnerable_group']) {
        foreach(explode(',', $row['vulnerable_group']) as $g) if(isset($stats[$g])) $stats[$g]++;
    }
}
?>
<style>
    .main-content { margin-left: 0 !important; padding: 20px !important; width: 100% !important; }
    .footer { left: 0 !important; width: 100% !important; }
    #vertical-menu-btn { display: none !important; }
    .stat-card { border-radius: 10px; border:none; transition: 0.3s; }
    .stat-card:hover { transform: translateY(-5px); }
</style>

<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <h4 class="mb-4 text-primary fw-bold">สรุปข้อมูลกลุ่มเปราะบาง</h4>
            
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card stat-card bg-primary text-white p-3">
                        <h3><?php echo $stats['total']; ?></h3>
                        <small>ทั้งหมด</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card bg-warning text-dark p-3">
                        <h3><?php echo $stats['elderly']; ?></h3>
                        <small>ผู้สูงอายุ</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card bg-info text-white p-3">
                        <h3><?php echo $stats['child']; ?></h3>
                        <small>เด็กเล็ก</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card bg-danger text-white p-3">
                        <h3><?php echo $stats['bedridden']; ?></h3>
                        <small>ติดเตียง</small>
                    </div>
                </div>
            </div>
            
            <div class="text-center">
                <a href="evacuee_list.php" class="btn btn-outline-secondary">ดูรายชื่อทั้งหมด</a>
            </div>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>