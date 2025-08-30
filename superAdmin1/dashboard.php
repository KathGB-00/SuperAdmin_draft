<?php
require __DIR__ . '/vendor/autoload.php';

use Google\Cloud\Firestore\FirestoreClient;

// 🔑 Firestore connection
$db = new FirestoreClient([
    'projectId'   => 'safeotw1',   
    'keyFilePath' => __DIR__ . '/service_account.json' 
]);

// --- Count companies ---
$companies = $db->collection('companies')->documents();
$companyCount = 0;
foreach ($companies as $doc) {
    if ($doc->exists()) {
        $companyCount++;
    }
}

// --- Count traffic signs ---
$signs = $db->collection('trafficSigns')->documents();
$signCount = 0;
foreach ($signs as $doc) {
    if ($doc->exists()) {
        $signCount++;
    }
}

// --- Count users ---
$users = $db->collection('users')->documents();
$userCount = 0;
foreach ($users as $doc) {
    if ($doc->exists()) {
        $userCount++;
    }
}

// --- Count students ---
$students = $db->collection('students')->documents();
$studentCount = 0;
foreach ($students as $doc) {
    if ($doc->exists()) {
        $studentCount++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <style>
        body {background: url('bg2.png') no-repeat center center fixed; background-size: cover; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;color: #1A1A1A;}
        .sidebar {position: fixed;top: 0; left: 0;height: 100%;width: 90px;background: #1A1A1A; padding-top: 20px; display: flex; flex-direction: column; align-items: center; box-shadow: 2px 0 8px rgba(0,0,0,0.2);}
        .logo-circle {width: 60px; height: 60px;border-radius: 50%; object-fit: cover; border: 3px solid #a8180aff; margin-bottom: 40px; box-shadow: 0 4px 6px rgba(0,0,0,0.3);}
        .sidebar .nav-link {color: #be281aff; font-size: 1.8rem; margin: 18px 0; transition: 0.3s; display: flex; justify-content: center; width: 100%;}
        .sidebar .nav-link:hover {color: #FFC107;transform: scale(1.2);}
        .sidebar .nav-link.active {color: #FFC107;transform: scale(1.2);}
        .main-content {margin-left: 90px;padding: 80px; padding-top: 30px;}
        .dashboard-title {text-align: center; font-weight: bold; padding-bottom: 30px; margin-bottom: 30px; font-size: 2.5rem;}
        .dashboard-title::after {content: ""; display: block;width: 120px;height: 5px;background: #a8180aff;margin: 12px auto 0 auto;border-radius: 10px;}
        
        /* ✅ Fixed alignment */
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 40px;
            justify-items: center;
        }
        .dashboard-card {display: flex;align-items: center;justify-content: space-between;padding: 50px 35px;border-radius: 22px;width: 100%;max-width: 400px;height: 200px;background: linear-gradient(170deg, #a80a1dff, #f8df55ff);color: #1A1A1A;box-shadow: 0 8px 18px rgba(0,0,0,0.25);transition: transform 0.3s, box-shadow 0.3s;cursor: pointer;text-decoration: none;}
        .dashboard-card:hover {transform: translateY(-6px);box-shadow: 0 12px 28px rgba(0,0,0,0.3);}
        .card-icon {font-size: 7rem;color: #f8df55ff;}
        .card-content {display: flex;flex-direction: column;align-items: center;text-align: center;}
        .card-number {font-size: 5rem;font-weight: bold;margin: 0 0 15px 0;color: #1A1A1A;}
        .card-name {font-size: 1.7rem;font-weight: 600;margin: 0;color: #f2e5a0ff;}
        .traffic-card {background: linear-gradient(135deg, #f8df55ff, #a8180aff)}
        .user-card {background: linear-gradient(180deg, #a8180aff, #f8df55ff)}
        .student-card {background: linear-gradient(220deg, #f8df55ff, #a8180aff)}
        
        /* 🔻 Logout button style */
        .logout-btn {
            margin-top: auto;
            margin-bottom: 20px;
            font-size: 1.8rem;
            color: #be281aff;
            transition: 0.3s;
        }
        .logout-btn:hover {
            color: #FFC107;
            transform: scale(1.2);
        }
    </style>
</head>
<body>

<div class="sidebar">
    <img src="logo1.png" alt="Logo" class="logo-circle">
    <a href="dashboard.php" class="nav-link active" title="Dashboard"><i class="bi bi-house"></i></a>
    <a href="companies.php" class="nav-link" title="Companies"><i class="bi bi-buildings"></i></a>
    <a href="traffic_signs.php" class="nav-link" title="Traffic Signs"><i class="bi bi-signpost-split"></i></a>
    <a href="users.php" class="nav-link" title="Users"><i class="bi bi-people"></i></a>
    <a href="students.php" class="nav-link" title="Students"><i class="bi bi-mortarboard"></i></a>
    
    <!-- 🔻 Logout button -->
    <a href="login.php" class="nav-link logout-btn" title="Logout"><i class="bi bi-box-arrow-right"></i></a>
</div>

<div class="main-content">

    <h2 class="dashboard-title">Dashboard</h2>

    <div class="dashboard-cards">
        <!-- Companies Card -->
        <a href="companies.php" class="dashboard-card">
            <div class="card-content">
                <p class="card-number" id="companyCount"><?= $companyCount ?></p>
                <p class="card-name">Companies</p>
            </div>
            <div class="card-icon"><i class="bi bi-buildings"></i></div>
        </a>

        <!-- Traffic Signs Card -->
        <a href="traffic_signs.php" class="dashboard-card traffic-card">
            <div class="card-content">
                <p class="card-number" id="signCount"><?= $signCount ?></p>
                <p class="card-name">Traffic Signs</p>
            </div>
            <div class="card-icon"><i class="bi bi-signpost-split"></i></div>
        </a>

        <!-- Users Card -->
        <a href="users.php" class="dashboard-card user-card">
            <div class="card-content">
                <p class="card-number" id="userCount"><?= $userCount ?></p>
                <p class="card-name">Users</p>
            </div>
            <div class="card-icon"><i class="bi bi-people"></i></div>
        </a>

        <!-- Students Card -->
        <a href="students.php" class="dashboard-card student-card">
            <div class="card-content">
                <p class="card-number" id="studentCount"><?= $studentCount ?></p>
                <p class="card-name">Students</p>
            </div>
            <div class="card-icon"><i class="bi bi-mortarboard"></i></div>
        </a>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Animate numbers safely
    function animateValue(id, start, end, duration) {
        let obj = document.getElementById(id);
        if (start === end) {
            obj.textContent = end;
            return;
        }
        let range = end - start;
        let current = start;
        let increment = end > start ? 1 : -1;
        let stepTime = Math.abs(Math.floor(duration / range));
        let timer = setInterval(function() {
            current += increment;
            obj.textContent = current;
            if (current == end) clearInterval(timer);
        }, stepTime);
    }
    animateValue("companyCount", 0, <?= $companyCount ?>, 1500);
    animateValue("signCount", 0, <?= $signCount ?>, 1500);
    animateValue("userCount", 0, <?= $userCount ?>, 1500);
    animateValue("studentCount", 0, <?= $studentCount ?>, 1500);
</script>
</body>
</html>
