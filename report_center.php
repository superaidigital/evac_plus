<?php
session_start();
require_once 'config/db.php';
include 'includes/header.php';
?>

<style>
    .main-content { margin-left: 0 !important; width: 100% !important; padding: 30px; background-color: #f4f6f9; }
    .report-card { 
        border: none; 
        border-radius: 15px; 
        box-shadow: 0 4px 6px rgba(0,0,0,0.05); 
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        height: 100%;
        cursor: pointer;
    }
    .report-card:hover { 
        transform: translateY(-5px); 
        box-shadow: 0 10px 15px rgba(0,0,0,0.1); 
    }
    .report-icon {
        width: 60px; height: 60px;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 24px; margin-bottom: 15px;
    }
    .text-primary-custom { color: #4e73df; }
    .bg-light-primary { background-color: #eaf1fb; color: #4e73df; }
    .bg-light-success { background-color: #e6f8f0; color: #1cc88a; }
    .bg-light-warning { background-color: #fff8e1; color: #f6c23e; }
    .bg-light-info { background-color: #e3f2fd; color: #36b9cc; }
    .bg-light-danger { background-color: #fdeaea; color: #e74a3b; }
</style>

<div class="main-content">
    <div class="container-fluid">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold text-dark mb-1"><i class="fas fa-chart-line me-2"></i>ศูนย์รวมรายงาน (Report Center)</h3>
                <p class="text-muted">เลือกประเภทรายงานที่ต้องการตรวจสอบ</p>
            </div>
            <a href="index.php" class="btn btn-outline-secondary"><i class="fas fa-home me-1"></i> กลับหน้าหลัก</a>
        </div>

        <div class="row g-4">
            
            <!-- 1. รายงานรายชื่อผู้เข้าพักแต่ละวัน -->
            <div class="col-md-4 col-lg-3">
                <div class="card report-card" onclick="window.location.href='report_daily_evacuees.php'">
                    <div class="card-body text-center p-4">
                        <div class="report-icon bg-light-primary mx-auto">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <h5 class="card-title fw-bold mb-2">ผู้เข้าพักรายวัน</h5>
                        <p class="card-text text-muted small">แสดงรายชื่อผู้เข้าพักแยกตามวันที่เลือก (Check-in Daily)</p>
                    </div>
                </div>
            </div>

            <!-- 2. รายงานผู้ประสบภัย (Unique) -->
            <div class="col-md-4 col-lg-3">
                <div class="card report-card" onclick="window.location.href='report_unique_evacuees.php'">
                    <div class="card-body text-center p-4">
                        <div class="report-icon bg-light-success mx-auto">
                            <i class="fas fa-users"></i>
                        </div>
                        <h5 class="card-title fw-bold mb-2">ทะเบียนรายบุคคล</h5>
                        <p class="card-text text-muted small">รายชื่อผู้ประสบภัยทั้งหมดแบบไม่ซ้ำ (Unique List)</p>
                    </div>
                </div>
            </div>

            <!-- 3. รายงานยอดแต่ละวัน -->
            <div class="col-md-4 col-lg-3">
                <div class="card report-card" onclick="window.location.href='report_daily_summary.php'">
                    <div class="card-body text-center p-4">
                        <div class="report-icon bg-light-warning mx-auto">
                            <i class="fas fa-chart-bar"></i>
                        </div>
                        <h5 class="card-title fw-bold mb-2">สรุปยอดรายวัน</h5>
                        <p class="card-text text-muted small">กราฟและตารางสรุปจำนวนผู้เข้าพักในแต่ละวัน</p>
                    </div>
                </div>
            </div>

            <!-- 5. รายงานจำนวนศูนย์ (ข้ามข้อ 4 ตามที่แจ้ง) -->
            <div class="col-md-4 col-lg-3">
                <div class="card report-card" onclick="window.location.href='report_shelter_stats.php'">
                    <div class="card-body text-center p-4">
                        <div class="report-icon bg-light-info mx-auto">
                            <i class="fas fa-campground"></i>
                        </div>
                        <h5 class="card-title fw-bold mb-2">สถานะศูนย์พักพิง</h5>
                        <p class="card-text text-muted small">จำนวนและสถานะความหนาแน่นของแต่ละศูนย์</p>
                    </div>
                </div>
            </div>

            <!-- 6. รายงานการรับบริจาค -->
            <div class="col-md-4 col-lg-3">
                <div class="card report-card" onclick="window.location.href='report_donation_overview.php'">
                    <div class="card-body text-center p-4">
                        <div class="report-icon bg-light-danger mx-auto">
                            <i class="fas fa-hand-holding-heart"></i>
                        </div>
                        <h5 class="card-title fw-bold mb-2">ภาพรวมการบริจาค</h5>
                        <p class="card-text text-muted small">สรุปยอดสิ่งของบริจาคและการเบิกจ่าย</p>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>