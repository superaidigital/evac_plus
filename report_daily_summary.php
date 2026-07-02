<?php
session_start();
require_once 'config/db.php';
include 'includes/header.php';

// ดึงยอดรวมแยกตามวันที่ลงทะเบียน
$sql = "SELECT DATE(registered_at) as reg_date, COUNT(*) as total,
        SUM(CASE WHEN gender='male' THEN 1 ELSE 0 END) as male,
        SUM(CASE WHEN gender='female' THEN 1 ELSE 0 END) as female
        FROM evacuees 
        GROUP BY DATE(registered_at) 
        ORDER BY reg_date DESC LIMIT 7";
$result = $conn->query($sql);
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="container-fluid py-4" style="margin-left:0; width:100%;">
    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white"><h5 class="mb-0 fw-bold">📈 กราฟแสดงยอดผู้เข้าพัก (7 วันล่าสุด)</h5></div>
                <div class="card-body">
                    <canvas id="dailyChart" style="max-height: 300px;"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-warning text-dark"><h5 class="mb-0 fw-bold">ตารางสรุปยอด</h5></div>
                <div class="card-body p-0">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>วันที่</th>
                                <th class="text-end">ชาย</th>
                                <th class="text-end">หญิง</th>
                                <th class="text-end fw-bold">รวม</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $dates = []; $totals = [];
                            while($row = $result->fetch_assoc()): 
                                $dates[] = date('d/m/Y', strtotime($row['reg_date']));
                                $totals[] = $row['total'];
                            ?>
                                <tr>
                                    <td><?php echo date('d/m/Y', strtotime($row['reg_date'])); ?></td>
                                    <td class="text-end"><?php echo $row['male']; ?></td>
                                    <td class="text-end"><?php echo $row['female']; ?></td>
                                    <td class="text-end fw-bold text-primary"><?php echo $row['total']; ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const ctx = document.getElementById('dailyChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_reverse($dates)); ?>,
            datasets: [{
                label: 'จำนวนผู้เข้าพัก (คน)',
                data: <?php echo json_encode(array_reverse($totals)); ?>,
                backgroundColor: '#36b9cc',
                borderRadius: 5
            }]
        },
        options: { responsive: true, maintainAspectRatio: false }
    });
</script>
<?php include 'includes/footer.php'; ?>