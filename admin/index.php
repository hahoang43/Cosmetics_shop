<?php 
// Đảm bảo đã kết nối Database trước khi gọi truy vấn
require_once '../config/database.php'; 
require_once '../includes/admin_header.php'; 

// --- MỚI: XỬ LÝ BỘ LỌC NĂM ĐỘNG ---
// Nếu trên URL có chọn năm (?year=2025) thì lấy năm đó, ngược lại mặc định là năm hiện tại
$selected_year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
$current_month = date('m');

// Tự động lấy danh sách các năm đang có đơn hàng trong CSDL để nạp vào Dropdown lọc
$stmt_all_years = $conn->query("SELECT DISTINCT YEAR(order_date) as y FROM Orders WHERE order_date IS NOT NULL ORDER BY y DESC");
$years_db = $stmt_all_years->fetchAll(PDO::FETCH_COLUMN);

// Phòng hờ nếu CSDL trống chưa có đơn, hệ thống vẫn tự hiển thị năm hiện tại
if (!in_array((int)date('Y'), $years_db)) {
    $years_db[] = (int)date('Y');
}
sort($years_db);
$years_db = array_reverse($years_db); // Sắp xếp năm mới nhất lên đầu danh sách


// --- 1. TÍNH DOANH THU THÁNG NÀY / HOẶC THÁNG NÀY CỦA NĂM ĐƯỢC CHỌN (status = 2) ---
$stmt_revenue = $conn->prepare("SELECT SUM(total_money) FROM Orders WHERE status = 2 AND MONTH(order_date) = ? AND YEAR(order_date) = ?");
$stmt_revenue->execute([$current_month, $selected_year]);
$revenue = $stmt_revenue->fetchColumn();
$revenue = $revenue ? $revenue : 0; 

// --- 2. ĐÃ SỬA: TÍNH TỔNG DOANH THU CỦA RIÊNG NĂM ĐƯỢC CHỌN LỌC ---
$stmt_year_revenue = $conn->prepare("SELECT SUM(total_money) FROM Orders WHERE status = 2 AND YEAR(order_date) = ?");
$stmt_year_revenue->execute([$selected_year]);
$year_revenue = $stmt_year_revenue->fetchColumn();
$year_revenue = $year_revenue ? $year_revenue : 0;

// --- 3. ĐẾM SỐ ĐƠN HÀNG MỚI (Giữ nguyên toàn hệ thống: Chờ xác nhận status = 0) ---
$stmt_new_orders = $conn->prepare("SELECT COUNT(*) FROM Orders WHERE status = 0");
$stmt_new_orders->execute();
$new_orders = $stmt_new_orders->fetchColumn();

// --- 4. ĐẾM TỔNG SẢN PHẨM ĐANG BÁN (Chưa bị xóa: deleted = 0) ---
$stmt_products = $conn->prepare("SELECT COUNT(*) FROM Product WHERE deleted = 0");
$stmt_products->execute();
$total_products = $stmt_products->fetchColumn();

// --- 5. ĐẾM TỔNG KHÁCH HÀNG (Người dùng bình thường: role = 0) ---
$stmt_users = $conn->prepare("SELECT COUNT(*) FROM User WHERE role = 0 AND deleted = 0");
$stmt_users->execute();
$total_users = $stmt_users->fetchColumn();


// --- 6. BIỂU ĐỒ 1: DOANH THU 7 NGÀY GẦN NHẤT (Chạy theo thời gian thực) ---
$sql_chart_7days = "
    SELECT DATE(order_date) as date, SUM(total_money) as revenue 
    FROM Orders 
    WHERE status = 2 AND order_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY DATE(order_date)
    ORDER BY DATE(order_date) ASC
";
$stmt_7days = $conn->query($sql_chart_7days);
$data_7days = $stmt_7days->fetchAll();

$dates_7days = [];
$revenues_7days = [];
foreach ($data_7days as $row) {
    $dates_7days[] = date('d/m', strtotime($row['date']));
    $revenues_7days[] = (int)$row['revenue'];
}
$json_dates_7days = json_encode($dates_7days);
$json_revenues_7days = json_encode($revenues_7days);


// --- 7. ĐÃ SỬA: BIỂU ĐỒ 2 TRUY VẤN ĐỘNG THEO NĂM ĐƯỢC CHỌN LỌC ($selected_year) ---
$sql_chart_months = "
    SELECT MONTH(order_date) as month, SUM(total_money) as revenue 
    FROM Orders 
    WHERE status = 2 AND YEAR(order_date) = ?
    GROUP BY MONTH(order_date)
    ORDER BY MONTH(order_date) ASC
";
$stmt_months = $conn->prepare($sql_chart_months);
$stmt_months->execute([$selected_year]);
$data_months = $stmt_months->fetchAll();

$months_labels = [];
$months_revenues = [];
for ($m = 1; $m <= 12; $m++) {
    $months_labels[] = "Tháng " . $m;
    $months_revenues[$m] = 0; 
}

foreach ($data_months as $row) {
    $m_index = (int)$row['month'];
    $months_revenues[$m_index] = (int)$row['revenue'];
}
$json_months_labels = json_encode($months_labels);
$json_months_revenues = json_encode(array_values($months_revenues));
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; border-bottom: 2px solid #eee; padding-bottom: 10px;">
    <h1 class="page-title" style="margin: 0; border: none;">Tổng quan hệ thống</h1>
    
    <div style="background: #fff; padding: 6px 12px; border-radius: 6px; border: 1px solid #ddd; display: flex; align-items: center; gap: 10px;">
        <form action="" method="GET" style="display: flex; align-items: center; gap: 8px; margin: 0;">
            <label style="font-weight: bold; font-size: 14px; color: #333;"><i class="fa-solid fa-calendar-days" style="color:#D4A373;"></i> Xem dữ liệu năm:</label>
            <select name="year" onchange="this.form.submit()" style="padding: 6px 12px; border: 1px solid #ccc; border-radius: 4px; outline: none; cursor: pointer; font-weight: bold; color: #2c3e50; background: #fff;">
                <?php foreach ($years_db as $y): ?>
                    <option value="<?= $y ?>" <?= $selected_year === (int)$y ? 'selected' : '' ?>>Năm <?= $y ?></option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>
</div>

<div class="dashboard-cards">
    
    <div class="stat-card">
        <div class="stat-icon" style="background: #3498db;">
            <i class="fa-solid fa-money-bill-wave"></i>
        </div>
        <div class="stat-info">
            <h4>DOANH THU THÁNG <?= $current_month ?>/<?= $selected_year ?></h4>
            <span style="color: #2c3e50;"><?= number_format($revenue, 0, ',', '.') ?>đ</span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background: #27ae60;">
            <i class="fa-solid fa-coins"></i>
        </div>
        <div class="stat-info">
            <h4>DOANH THU CẢ NĂM <?= $selected_year ?></h4>
            <span style="color: #27ae60; font-weight: bold;"><?= number_format($year_revenue, 0, ',', '.') ?>đ</span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background: #e67e22;">
            <i class="fa-solid fa-box"></i>
        </div>
        <div class="stat-info">
            <h4>ĐƠN HÀNG MỚI</h4>
            <span <?= $new_orders > 0 ? 'style="color: #e74c3c;"' : '' ?>><?= $new_orders ?></span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background: #2ecc71;">
            <i class="fa-solid fa-tags"></i>
        </div>
        <div class="stat-info">
            <h4>TỔNG SẢN PHẨM</h4>
            <span><?= $total_products ?></span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background: #9b59b6;">
            <i class="fa-solid fa-users"></i>
        </div>
        <div class="stat-info">
            <h4>KHÁCH HÀNG</h4>
            <span><?= $total_users ?></span>
        </div>
    </div>

</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(450px, 1fr)); gap: 20px; margin-top: 30px; margin-bottom: 30px;">
    
    <div style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
        <h3 style="margin: 0 0 15px 0; color: #2c3e50; font-size: 15px; display: flex; align-items: center; gap: 8px;">
            <i class="fa-solid fa-chart-line" style="color: #3498db;"></i> Biến động doanh thu 7 ngày gần đây
        </h3>
        <div style="position: relative; height: 300px; width: 100%;">
            <canvas id="chart7Days"></canvas>
        </div>
    </div>

    <div style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
        <h3 style="margin: 0 0 15px 0; color: #2c3e50; font-size: 15px; display: flex; align-items: center; gap: 8px;">
            <i class="fa-solid fa-chart-bar" style="color: #2ecc71;"></i> Tổng hợp doanh thu 12 tháng năm <?= $selected_year ?>
        </h3>
        <div style="position: relative; height: 300px; width: 100%;">
            <canvas id="chartMonthly"></canvas>
        </div>
    </div>

</div>

<script>
// CONFIG BIỂU ĐỒ 1: 7 NGÀY GẦN NHẤT
const ctx7 = document.getElementById('chart7Days').getContext('2d');
new Chart(ctx7, {
    type: 'line',
    data: {
        labels: <?php echo $json_dates_7days; ?>,
        datasets: [{
            label: 'Doanh thu (VNĐ)',
            data: <?php echo $json_revenues_7days; ?>,
            borderColor: '#3498db',
            backgroundColor: 'rgba(52, 152, 219, 0.1)',
            borderWidth: 3,
            tension: 0.2,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: { beginAtZero: true, ticks: { callback: v => new Intl.NumberFormat('vi-VN').format(v) + 'đ' } }
        }
    }
});

// CONFIG BIỂU ĐỒ 2: ĐỦ 12 THÁNG TRONG NĂM ĐƯỢC LỌC
const ctxMonth = document.getElementById('chartMonthly').getContext('2d');
new Chart(ctxMonth, {
    type: 'bar',
    data: {
        labels: <?php echo $json_months_labels; ?>,
        datasets: [{
            label: 'Doanh thu tháng (VNĐ)',
            data: <?php echo $json_months_revenues; ?>,
            backgroundColor: '#D4A373',
            hoverBackgroundColor: '#c2905f',
            borderRadius: 4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: { beginAtZero: true, ticks: { callback: v => new Intl.NumberFormat('vi-VN').format(v) + 'đ' } },
            x: { grid: { display: false } }
        }
    }
});
</script>

<?php require_once '../includes/admin_footer.php'; ?>