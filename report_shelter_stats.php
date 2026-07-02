<?php
require_once 'includes/header.php';

// ดึงข้อมูลศูนย์และจำนวนคนปัจจุบัน (Active only)
$sql = "SELECT s.name, s.capacity, s.location,
        (SELECT COUNT(*) FROM evacuees WHERE shelter_id = s.id AND status IN ('active','hospitalized')) as current_count
        FROM shelters s
        ORDER BY current_count DESC";
$result = $conn->query($sql);
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0 fw-bold text-info">สถานะความหนาแน่นศูนย์พักพิง</h4>
        <button class="btn btn-sm btn-outline-secondary" onclick="window.history.back()">กลับ</button>
    </div>

    <div class="row g-4">
        <?php while($row = $result->fetch_assoc()): 
            $percent = ($row['capacity'] > 0) ? ($row['current_count'] / $row['capacity']) * 100 : 0;
            $color = 'success';
            if($percent > 80) $color = 'danger';
            else if($percent > 50) $color = 'warning';
        ?>
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm border-top border-4 border-<?php echo $color; ?>">
                <div class="card-body">
                    <h5 class="card-title fw-bold text-dark"><?php echo $row['name']; ?></h5>
                    <p class="text-muted small mb-3"><i class="fas fa-map-marker-alt me-1"></i> <?php echo $row['location']; ?></p>
                    
                    <div class="d-flex justify-content-between align-items-end mb-1">
                        <span class="fw-bold text-<?php echo $color; ?>"><?php echo $row['current_count']; ?> คน</span>
                        <small class="text-muted">รองรับได้ <?php echo number_format($row['capacity']); ?></small>
                    </div>
                    
                    <div class="progress" style="height: 10px;">
                        <div class="progress-bar bg-<?php echo $color; ?>" role="progressbar" style="width: <?php echo $percent; ?>%"></div>
                    </div>
                    <small class="text-muted mt-2 d-block text-end"><?php echo number_format($percent, 1); ?>% เต็ม</small>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
</div>
<?php include 'includes/footer.php'; ?>