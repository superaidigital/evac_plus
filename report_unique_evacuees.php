<?php
session_start();
require_once 'config/db.php';
include 'includes/header.php';

// ดึงข้อมูล Group By เลขบัตร เพื่อไม่ให้ซ้ำ
$sql = "SELECT * FROM evacuees GROUP BY id_card ORDER BY first_name ASC";
$result = $conn->query($sql);
$total = $result->num_rows;
?>

<div class="container-fluid py-4" style="margin-left:0; width:100%;">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold text-success"><i class="fas fa-id-card me-2"></i>ทะเบียนผู้ประสบภัย (รายชื่อไม่ซ้ำ)</h5>
            <span class="badge bg-success fs-6">รวมทั้งสิ้น <?php echo number_format($total); ?> คน</span>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover" id="uniqueTable">
                    <thead>
                        <tr>
                            <th>ชื่อ-นามสกุล</th>
                            <th>เลขบัตรประชาชน</th>
                            <th>อายุ</th>
                            <th>เพศ</th>
                            <th>ที่อยู่ภูมิลำเนา</th>
                            <th>สถานะล่าสุด</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['prefix'].$row['first_name'].' '.$row['last_name']; ?></td>
                                <td><?php echo $row['id_card']; ?></td>
                                <td><?php echo $row['age']; ?></td>
                                <td><?php echo $row['gender']=='male'?'ชาย':'หญิง'; ?></td>
                                <td><small><?php echo $row['address']; ?></small></td>
                                <td>
                                    <?php 
                                        if($row['status']=='active') echo '<span class="text-success fw-bold">พักอยู่</span>';
                                        elseif($row['status']=='returned') echo '<span class="text-muted">กลับบ้าน</span>';
                                        else echo '<span class="text-danger">ส่ง รพ.</span>';
                                    ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination example or Export button can be added here -->
            <div class="mt-4 text-center">
                <button class="btn btn-outline-success" onclick="window.print()">พิมพ์รายชื่อ</button>
            </div>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>