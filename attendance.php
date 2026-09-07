<?php
session_start();
require_once "../config/database.php";

if (!isset($_SESSION["admin_id"])) {
    header("Location: ../index.php");
    exit();
}

$message = "";
$error = "";

$selected_date = $_POST["attendance_date"] ?? $_GET["date"] ?? date("Y-m-d");
$selected_batch = $_POST["batch_id"] ?? $_GET["batch_id"] ?? "";

/* =========================
   SAVE ATTENDANCE
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["save_attendance"])) {

    $attendance_date = $_POST["attendance_date"];
    $batch_id = intval($_POST["batch_id"]);

    if ($batch_id <= 0) {
        $error = "Please select a batch.";
    } elseif (empty($_POST["status"])) {
        $error = "No students found for this batch.";
    } else {

        $stmt = $conn->prepare("
            INSERT INTO attendance
            (student_id, batch_id, attendance_date, status)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                batch_id = VALUES(batch_id),
                status = VALUES(status)
        ");

        foreach ($_POST["status"] as $student_id => $status) {

            $student_id = intval($student_id);

            if ($status !== "Present" && $status !== "Absent") {
                continue;
            }

            $stmt->bind_param(
                "iiss",
                $student_id,
                $batch_id,
                $attendance_date,
                $status
            );

            $stmt->execute();
        }

        $stmt->close();

        $message = "Attendance saved successfully!";
        $selected_date = $attendance_date;
        $selected_batch = $batch_id;
    }
}


/* =========================
   GET BATCHES
========================= */

$batches = [];

$result = $conn->query("
    SELECT id, batch_name, course
    FROM batches
    WHERE status = 'Active'
    ORDER BY course, batch_name
");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $batches[] = $row;
    }
}


/* =========================
   GET STUDENTS
========================= */

$students = [];

if (!empty($selected_batch)) {

    $stmt = $conn->prepare("
        SELECT
            s.id,
            s.student_code,
            s.full_name,
            s.mobile,
            s.batch,
            COALESCE(a.status, 'Present') AS attendance_status
        FROM students s
        LEFT JOIN attendance a
            ON s.id = a.student_id
            AND a.attendance_date = ?
            AND a.batch_id = ?
        WHERE s.status = 'Active'
        AND s.batch = (
            SELECT batch_name
            FROM batches
            WHERE id = ?
        )
        ORDER BY s.full_name ASC
    ");

    $stmt->bind_param(
        "sii",
        $selected_date,
        $selected_batch,
        $selected_batch
    );

    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }

    $stmt->close();
}


/* =========================
   ATTENDANCE HISTORY
========================= */

$history = [];

$result = $conn->query("
    SELECT
        a.attendance_date,
        s.student_code,
        s.full_name,
        b.batch_name,
        a.status
    FROM attendance a
    INNER JOIN students s
        ON a.student_id = s.id
    INNER JOIN batches b
        ON a.batch_id = b.id
    ORDER BY a.attendance_date DESC, s.full_name ASC
    LIMIT 100
");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $history[] = $row;
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Attendance Management | Super20 Academy</title>

    <link rel="stylesheet" href="../css/style.css">

    <style>

        .page-container {
            width: 95%;
            max-width: 1200px;
            margin: 30px auto;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            gap: 15px;
            flex-wrap: wrap;
        }

        .page-header h1 {
            margin: 0;
        }

        .back-btn {
            text-decoration: none;
            padding: 10px 18px;
            border-radius: 8px;
            background: #333;
            color: white;
        }

        .attendance-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            margin-bottom: 30px;
        }

        .filter-form {
            display: grid;
            grid-template-columns: 1fr 1fr auto;
            gap: 15px;
            align-items: end;
            margin-bottom: 25px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 7px;
        }

        .form-group label {
            font-weight: 600;
        }

        .form-group input,
        .form-group select {
            padding: 11px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 15px;
        }

        .btn {
            border: none;
            padding: 11px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 600;
        }

        .btn-primary {
            background: #2563eb;
            color: white;
        }

        .btn-success {
            background: #16a34a;
            color: white;
        }

        .message {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            background: #dcfce7;
            color: #166534;
        }

        .error {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            background: #fee2e2;
            color: #991b1b;
        }

        .attendance-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .attendance-table th,
        .attendance-table td {
            padding: 13px;
            border-bottom: 1px solid #eee;
            text-align: left;
        }

        .attendance-table th {
            background: #f5f7fb;
        }

        .status-select {
            padding: 8px 12px;
            border-radius: 7px;
            border: 1px solid #ccc;
            font-weight: 600;
        }

        .empty {
            text-align: center;
            padding: 30px;
            color: #777;
        }

        .history-title {
            margin-top: 10px;
            margin-bottom: 15px;
        }

        @media (max-width: 700px) {

            .filter-form {
                grid-template-columns: 1fr;
            }

            .attendance-table {
                font-size: 13px;
            }

            .attendance-table th,
            .attendance-table td {
                padding: 8px;
            }
        }

    </style>

</head>

<body>

<div class="page-container">

    <div class="page-header">

        <div>
            <h1>Attendance Management</h1>
            <p>Manage student attendance</p>
        </div>

        <a href="../dashboard.php" class="back-btn">
            ← Dashboard
        </a>

    </div>


    <div class="attendance-card">

        <?php if (!empty($message)): ?>

            <div class="message">
                <?php echo htmlspecialchars($message); ?>
            </div>

        <?php endif; ?>


        <?php if (!empty($error)): ?>

            <div class="error">
                <?php echo htmlspecialchars($error); ?>
            </div>

        <?php endif; ?>


        <!-- FILTER -->

        <form method="GET" class="filter-form">

            <div class="form-group">

                <label>Attendance Date</label>

                <input
                    type="date"
                    name="date"
                    value="<?php echo htmlspecialchars($selected_date); ?>"
                    required
                >

            </div>


            <div class="form-group">

                <label>Select Batch</label>

                <select name="batch_id" required>

                    <option value="">-- Select Batch --</option>

                    <?php foreach ($batches as $batch): ?>

                        <option
                            value="<?php echo $batch["id"]; ?>"
                            <?php echo ($selected_batch == $batch["id"]) ? "selected" : ""; ?>
                        >

                            <?php
                            echo htmlspecialchars(
                                $batch["batch_name"] . " - " . $batch["course"]
                            );
                            ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <button type="submit" class="btn btn-primary">
                Load Students
            </button>

        </form>


        <?php if (!empty($selected_batch)): ?>

            <form method="POST">

                <input
                    type="hidden"
                    name="attendance_date"
                    value="<?php echo htmlspecialchars($selected_date); ?>"
                >

                <input
                    type="hidden"
                    name="batch_id"
                    value="<?php echo htmlspecialchars($selected_batch); ?>"
                >


                <?php if (!empty($students)): ?>

                    <table class="attendance-table">

                        <thead>

                            <tr>
                                <th>#</th>
                                <th>Student Code</th>
                                <th>Student Name</th>
                                <th>Mobile</th>
                                <th>Attendance</th>
                            </tr>

                        </thead>

                        <tbody>

                            <?php $count = 1; ?>

                            <?php foreach ($students as $student): ?>

                                <tr>

                                    <td>
                                        <?php echo $count++; ?>
                                    </td>

                                    <td>
                                        <?php echo htmlspecialchars($student["student_code"]); ?>
                                    </td>

                                    <td>
                                        <strong>
                                            <?php echo htmlspecialchars($student["full_name"]); ?>
                                        </strong>
                                    </td>

                                    <td>
                                        <?php echo htmlspecialchars($student["mobile"]); ?>
                                    </td>

                                    <td>

                                        <select
                                            name="status[<?php echo $student["id"]; ?>]"
                                            class="status-select"
                                        >

                                            <option
                                                value="Present"
                                                <?php echo ($student["attendance_status"] === "Present") ? "selected" : ""; ?>
                                            >
                                                Present
                                            </option>

                                            <option
                                                value="Absent"
                                                <?php echo ($student["attendance_status"] === "Absent") ? "selected" : ""; ?>
                                            >
                                                Absent
                                            </option>

                                        </select>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>


                    <br>

                    <button
                        type="submit"
                        name="save_attendance"
                        class="btn btn-success"
                    >
                        Save Attendance
                    </button>


                <?php else: ?>

                    <div class="empty">
                        No active students found for this batch.
                    </div>

                <?php endif; ?>

            </form>

        <?php endif; ?>

    </div>


    <!-- ATTENDANCE HISTORY -->

    <div class="attendance-card">

        <h2 class="history-title">
            Recent Attendance History
        </h2>

        <?php if (!empty($history)): ?>

            <table class="attendance-table">

                <thead>

                    <tr>
                        <th>Date</th>
                        <th>Student Code</th>
                        <th>Student Name</th>
                        <th>Batch</th>
                        <th>Status</th>
                    </tr>

                </thead>

                <tbody>

                    <?php foreach ($history as $record): ?>

                        <tr>

                            <td>
                                <?php
                                echo date(
                                    "d-m-Y",
                                    strtotime($record["attendance_date"])
                                );
                                ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($record["student_code"]); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($record["full_name"]); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($record["batch_name"]); ?>
                            </td>

                            <td>

                                <?php if ($record["status"] === "Present"): ?>

                                    <strong style="color: green;">
                                        Present
                                    </strong>

                                <?php else: ?>

                                    <strong style="color: red;">
                                        Absent
                                    </strong>

                                <?php endif; ?>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        <?php else: ?>

            <div class="empty">
                No attendance records available.
            </div>

        <?php endif; ?>

    </div>

</div>

</body>
</html>