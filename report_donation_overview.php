<?php
require_once 'includes/header.php';

// ดึงข้อมูลสินค้าคงเหลือ (Inventory)
$sql = "SELECT i.*, s.name as shelter_name 
        FROM inventory i 
        LEFT JOIN shelters s ON i.shelter_id = s.id 
        ORDER BY i.quantity ASC"; // เรียงตามของน้อยไปมาก
$result = $conn->query($sql);
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0 fw-bold text-danger">ภาพรวมสิ่งของบริจาคและคงคลัง</h4>
        <button class="btn btn-sm btn-outline-secondary" onclick="window.history.back()">กลับ</button>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>รายการสิ่งของ</th>
                            <th>หมวดหมู่</th>
                            <th>ศูนย์พักพิง</th>
                            <th class="text-end">คงเหลือ</th>
                            <th>สถานะ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td class="fw-bold"><?php echo $row['item_name']; ?></td>
                                <td><span class="badge bg-light text-dark border"><?php echo $row['category']; ?></span></td>
                                <td><?php echo $row['shelter_name']; ?></td>
                                <td class="text-end fw-bold"><?php echo number_format($row['quantity']) . ' ' . $row['unit']; ?></td>
                                <td>
                                    <?php 
                                    if($row['quantity'] == 0) echo '<span class="badge bg-danger">หมด</span>';
                                    elseif($row['quantity'] < 50) echo '<span class="badge bg-warning text-dark">ใกล้หมด</span>';
                                    else echo '<span class="badge bg-success">ปกติ</span>';
                                    ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>