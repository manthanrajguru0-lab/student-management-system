<?php
session_start();

if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit();
}

$admin_username = $_SESSION["admin_username"];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Super20 Academy - Dashboard</title>

    <link rel="stylesheet" href="css/style.css">

    <style>
        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: #f4f6fb;
        }

        .dashboard {
            min-height: 100vh;
        }

        .topbar {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 18px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .topbar h1 {
            color: white;
            margin: 0;
            font-size: 24px;
        }

        .admin-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .logout {
            text-decoration: none;
            color: white;
            background: rgba(255,255,255,0.2);
            padding: 9px 15px;
            border-radius: 7px;
        }

        .content {
            padding: 30px;
        }

        .welcome {
            background: white;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }

        .welcome h2 {
            margin-top: 0;
        }

        .modules {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
        }

        .module {
            background: white;
            padding: 25px;
            border-radius: 12px;
            text-decoration: none;
            color: #333;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            transition: 0.3s;
        }

        .module:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }

        .module-icon {
            font-size: 32px;
            margin-bottom: 12px;
        }

        .module h3 {
            margin: 5px 0;
        }

        .module p {
            color: #777;
            font-size: 13px;
        }

        @media (max-width: 900px) {
            .modules {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 600px) {
            .modules {
                grid-template-columns: 1fr;
            }

            .topbar {
                flex-direction: column;
                gap: 15px;
            }
        }
    </style>
</head>

<body>

<div class="dashboard">

    <div class="topbar">

        <h1>Super20 Academy</h1>

        <div class="admin-info">
            <span>Admin: <?php echo htmlspecialchars($admin_username); ?></span>

            <a href="logout.php" class="logout">
                Logout
            </a>
        </div>

    </div>


    <div class="content">

        <div class="welcome">

            <h2>Welcome to Admin Dashboard 👋</h2>

            <p>
                Coaching Class Management System
            </p>

        </div>


        <div class="modules">

            <a href="pages/students.php" class="module">
                <div class="module-icon">👨‍🎓</div>
                <h3>Students</h3>
                <p>Manage student records</p>
            </a>

            <a href="pages/batches.php" class="module">
                <div class="module-icon">👥</div>
                <h3>Batches</h3>
                <p>Manage class batches</p>
            </a>

            <a href="pages/attendance.php" class="module">
                <div class="module-icon">✅</div>
                <h3>Attendance</h3>
                <p>Manage attendance</p>
            </a>

            <a href="pages/fees.php" class="module">
                <div class="module-icon">💰</div>
                <h3>Fees</h3>
                <p>Manage student fees</p>
            </a>

            <a href="pages/tests.php" class="module">
                <div class="module-icon">📝</div>
                <h3>Tests</h3>
                <p>Manage tests and exams</p>
            </a>

            <a href="pages/marks.php" class="module">
                <div class="module-icon">📊</div>
                <h3>Marks</h3>
                <p>Manage test marks</p>
            </a>

            <a href="pages/timetable.php" class="module">
                <div class="module-icon">🕐</div>
                <h3>Timetable</h3>
                <p>Manage class timetable</p>
            </a>

            <a href="pages/notices.php" class="module">
                <div class="module-icon">📢</div>
                <h3>Notices</h3>
                <p>Manage announcements</p>
            </a>
<a href="pages/reports.php" class="dashboard-card">
    
    <div class="card-icon">
        📊
    </div>

    <div class="card-content">
        <h3>Reports</h3>
        <p>View and generate academy reports</p>
    </div>

</a>
        </div>

    </div>

</div>

</body>
</html>