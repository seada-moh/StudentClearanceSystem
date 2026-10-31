<?php
require '../config/db.php';
if (session_status() == PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') { header("Location: ../index.php"); exit(); }

// 1. UPDATED TABLES MAP (Dormmate removed, since they are now Students)
$tables_map =[
    'student' => 'students', 
    'proctor' => 'proctors', 
    'librarian' => 'librarians', 
    'cafeteria' => 'cafeterias', 
    'student_service' => 'student_services', 
    'dept_head' => 'dept_heads', 
    'registrar' => 'registrars'
];

// 2. FETCH LIVE DYNAMIC DATA FOR CHART (Active vs Soft-Deleted)
$chart_labels = [];
$active_data = [];
$deleted_data = [];

foreach ($tables_map as $role_key => $table_name) {
    // Add beautiful names to the X-axis of the chart
    $display_name = ucwords(str_replace('_', ' ', $role_key));
    if($role_key == 'student_service') $display_name = 'Student Service';
    $chart_labels[] = $display_name;
    
    // Count currently ACTIVE users (is_deleted = 0)
    $active_count = $conn->query("SELECT COUNT(*) as c FROM $table_name WHERE is_deleted = 0")->fetch_assoc()['c'];
    $active_data[] = (int)$active_count;
    
    // Count SOFT DELETED users sitting in the Recycle Bin (is_deleted = 1)
    $deleted_count = $conn->query("SELECT COUNT(*) as c FROM $table_name WHERE is_deleted = 1")->fetch_assoc()['c'];
    $deleted_data[] = (int)$deleted_count;
}

include '../includes/header.php';
?>

<h2><i class="fas fa-chart-bar"></i> System Statistics Dashboard</h2>
<p style="color: #7f8c8d; margin-bottom: 20px;">
    Live overview of all registered and deleted users within the university system.
</p>

<!-- THE LIVE BAR CHART CONTAINER -->
<div class="chart-container" style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); height: 500px; width: 100%;">
    <canvas id="userChart"></canvas>
</div>

<!-- TOP SUMMARY CARDS (Extra visual touch for the Admin) -->
<div style="display: flex; gap: 20px; margin-top: 30px;">
    <?php 
        $total_active = array_sum($active_data);
        $total_deleted = array_sum($deleted_data);
    ?>
    <div style="flex: 1; background: linear-gradient(135deg, #2ecc71, #27ae60); padding: 30px; border-radius: 12px; color: white; box-shadow: 0 10px 20px rgba(46, 204, 113, 0.4); text-align: center;">
        <i class="fas fa-users" style="font-size: 40px; margin-bottom: 10px; opacity: 0.8;"></i>
        <h3 style="margin: 0; font-size: 18px; text-transform: uppercase; letter-spacing: 1px;">Total Active Users</h3>
        <p style="margin: 5px 0 0 0; font-size: 35px; font-weight: 900;"><?= $total_active ?></p>
    </div>
    
    <div style="flex: 1; background: linear-gradient(135deg, #e74c3c, #c0392b); padding: 30px; border-radius: 12px; color: white; box-shadow: 0 10px 20px rgba(231, 76, 60, 0.4); text-align: center;">
        <i class="fas fa-trash-alt" style="font-size: 40px; margin-bottom: 10px; opacity: 0.8;"></i>
        <h3 style="margin: 0; font-size: 18px; text-transform: uppercase; letter-spacing: 1px;">Users in Recycle Bin</h3>
        <p style="margin: 5px 0 0 0; font-size: 35px; font-weight: 900;"><?= $total_deleted ?></p>
    </div>
</div>

<!-- INITIALIZE CHART.JS -->
<script>
const ctx = document.getElementById('userChart').getContext('2d');

// Gradient colors for the Green (Active) bars
let greenGradient = ctx.createLinearGradient(0, 0, 0, 400);
greenGradient.addColorStop(0, 'rgba(46, 204, 113, 1)');
greenGradient.addColorStop(1, 'rgba(39, 174, 96, 0.8)');

// Gradient colors for the Red (Deleted) bars
let redGradient = ctx.createLinearGradient(0, 0, 0, 400);
redGradient.addColorStop(0, 'rgba(231, 76, 60, 1)');
redGradient.addColorStop(1, 'rgba(192, 57, 43, 0.8)');

new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= json_encode($chart_labels) ?>,
        datasets:[
            {
                label: 'Currently Active Users',
                data: <?= json_encode($active_data) ?>,
                backgroundColor: greenGradient,
                borderColor: '#27ae60',
                borderWidth: 1,
                borderRadius: 4, // Curved tops on the bars
                barPercentage: 0.6
            },
            {
                label: 'Deleted Users (Recycle Bin)',
                data: <?= json_encode($deleted_data) ?>,
                backgroundColor: redGradient,
                borderColor: '#c0392b',
                borderWidth: 1,
                borderRadius: 4,
                barPercentage: 0.6
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            title: { 
                display: true, 
                text: 'Live System User Distribution & Recycle Bin Status', 
                font: { size: 18, family: 'Segoe UI' },
                color: '#2c3e50',
                padding: { bottom: 20 }
            },
            legend: {
                position: 'bottom',
                labels: { font: { size: 14 }, usePointStyle: true, padding: 20 }
            },
            tooltip: {
                backgroundColor: 'rgba(44, 62, 80, 0.9)',
                titleFont: { size: 15 },
                bodyFont: { size: 14 },
                padding: 15,
                cornerRadius: 8
            }
        },
        scales: { 
            y: { 
                beginAtZero: true, 
                ticks: { stepSize: 1, font: { size: 14 } },
                grid: { color: 'rgba(189, 195, 199, 0.3)' } // Subtle horizontal lines
            },
            x: {
                ticks: { font: { size: 13, weight: 'bold' }, color: '#34495e' },
                grid: { display: false } // Hides vertical lines for a cleaner look
            }
        }
    }
});
</script>

<?php include '../includes/footer.php'; ?>