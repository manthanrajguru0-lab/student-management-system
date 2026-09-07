<?php
session_start();
require_once "../config/database.php";

if (!isset($_SESSION["admin_id"])) {
    header("Location: ../index.php");
    exit();
}

$report_type = $_GET["report"] ?? "overview";


// =========================
// OVERVIEW COUNTS
// =========================

$student_count = 0;
$batch_count = 0;
$attendance_count = 0;
$fee_total = 0;
$test_count = 0;

$result = $conn->query("SELECT COUNT(*) AS total FROM students");
if ($result) {
    $student_count = $result->fetch_assoc()["total"];
}

$result = $conn->query("SELECT COUNT(*) AS total FROM batches");
if ($result) {
    $batch_count = $result->fetch_assoc()["total"];
}

$result = $conn->query("SELECT COUNT(*) AS total FROM attendance");
if ($result) {
    $attendance_count = $result->fetch_assoc()["total"];
}

$result = $conn->query("SELECT COALESCE(SUM(amount),0) AS total FROM fees");
if ($result) {
    $fee_total = $result->fetch_assoc()["total"];
}

$result = $conn->query("SELECT COUNT(*) AS total FROM tests");
if ($result) {
    $test_count = $result->fetch_assoc()["total"];
}


// =========================
// STUDENT REPORT
// =========================

$students = [];

if ($report_type === "students") {

    $result = $conn->query("
        SELECT
            student_code,
            full_name,
            mobile,
            course,
            batch,
            joining_date,
            total_fees,
            status
        FROM students
        ORDER BY id DESC
    ");

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $students[] = $row;
        }
    }
}


// =========================
// FEES REPORT
// =========================

$fees = [];

if ($report_type === "fees") {

    $result = $conn->query("
        SELECT
            f.receipt_no,
            s.full_name,
            s.student_code,
            f.amount,
            f.payment_date,
            f.payment_mode,
            f.remarks
        FROM fees f
        LEFT JOIN students s
            ON f.student_id = s.id
        ORDER BY f.payment_date DESC, f.id DESC
    ");

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $fees[] = $row;
        }
    }
}


// =========================
// ATTENDANCE REPORT
// =========================

$attendance = [];

if ($report_type === "attendance") {

    $result = $conn->query("
        SELECT
            a.attendance_date,
            s.student_code,
            s.full_name,
            s.batch,
            a.status
        FROM attendance a
        LEFT JOIN students s
            ON a.student_id = s.id
        ORDER BY a.attendance_date DESC, a.id DESC
    ");

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $attendance[] = $row;
        }
    }
}


// =========================
// TEST / MARKS REPORT
// =========================

$marks = [];

if ($report_type === "marks") {

    $result = $conn->query("
        SELECT
            t.test_name,
            t.subject,
            t.test_date,
            t.total_marks,
            s.student_code,
            s.full_name,
            m.marks_obtained
        FROM marks m

        INNER JOIN tests t
            ON m.test_id = t.id

        INNER JOIN students s
            ON m.student_id = s.id

        ORDER BY t.test_date DESC, s.full_name ASC
    ");

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $marks[] = $row;
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>
    Reports | Super20 Academy
</title>

<link rel="stylesheet"
      href="../css/style.css">

<style>

.page-container {
    width: 95%;
    max-width: 1250px;
    margin: 30px auto;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 25px;
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

.report-menu {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 25px;
}

.report-menu a {
    text-decoration: none;
    padding: 10px 16px;
    border-radius: 8px;
    background: white;
    color: #333;
    border: 1px solid #ddd;
    font-weight: 600;
}

.report-menu a:hover {
    background: #f1f5f9;
}

.report-card {
    background: white;
    padding: 25px;
    border-radius: 15px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.08);
    margin-bottom: 25px;
}

.stats {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 15px;
}

.stat-box {
    padding: 20px;
    border-radius: 12px;
    background: #f8fafc;
    border: 1px solid #eee;
}

.stat-box h3 {
    margin: 0 0 8px;
    font-size: 14px;
    color: #666;
}

.stat-box p {
    margin: 0;
    font-size: 25px;
    font-weight: 700;
}

.table-wrapper {
    overflow-x: auto;
}

.report-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}

.report-table th,
.report-table td {
    padding: 12px;
    border-bottom: 1px solid #eee;
    text-align: left;
    white-space: nowrap;
}

.report-table th {
    background: #f5f7fb;
}

.amount {
    font-weight: 700;
}

.present {
    color: #15803d;
    font-weight: 700;
}

.absent {
    color: #dc2626;
    font-weight: 700;
}

.print-btn {
    border: none;
    padding: 10px 18px;
    border-radius: 8px;
    cursor: pointer;
    background: #2563eb;
    color: white;
    font-weight: 600;
}

.empty {
    text-align: center;
    padding: 30px;
    color: #777;
}

@media (max-width: 900px) {

    .stats {
        grid-template-columns: repeat(2, 1fr);
    }

}

@media (max-width: 500px) {

    .stats {
        grid-template-columns: 1fr;
    }

}


/* PRINT */

@media print {

    .page-header a,
    .report-menu,
    .print-btn {
        display: none;
    }

    .report-card {
        box-shadow: none;
        border: none;
    }

}

</style>

</head>


<body>


<div class="page-container">


    <!-- HEADER -->

    <div class="page-header">

        <div>

            <h1>
                Reports
            </h1>

            <p>
                Super20 Academy Management Reports
            </p>

        </div>

        <a href="../dashboard.php"
           class="back-btn">

            ← Dashboard

        </a>

    </div>


    <!-- REPORT MENU -->

    <div class="report-menu">

        <a href="reports.php?report=overview">
            📊 Overview
        </a>

        <a href="reports.php?report=students">
            👨‍🎓 Students
        </a>

        <a href="reports.php?report=attendance">
            📅 Attendance
        </a>

        <a href="reports.php?report=fees">
            💰 Fees
        </a>

        <a href="reports.php?report=marks">
            📝 Marks
        </a>

    </div>


    <!-- OVERVIEW -->

    <?php if ($report_type === "overview"): ?>

        <div class="report-card">

            <h2>
                Academy Overview
            </h2>

            <br>

            <div class="stats">


                <div class="stat-box">

                    <h3>
                        Total Students
                    </h3>

                    <p>
                        <?php echo $student_count; ?>
                    </p>

                </div>


                <div class="stat-box">

                    <h3>
                        Total Batches
                    </h3>

                    <p>
                        <?php echo $batch_count; ?>
                    </p>

                </div>


                <div class="stat-box">

                    <h3>
                        Attendance Records
                    </h3>

                    <p>
                        <?php echo $attendance_count; ?>
                    </p>

                </div>


                <div class="stat-box">

                    <h3>
                        Fee Collection
                    </h3>

                    <p>
                        ₹<?php echo number_format($fee_total, 2); ?>
                    </p>

                </div>


                <div class="stat-box">

                    <h3>
                        Total Tests
                    </h3>

                    <p>
                        <?php echo $test_count; ?>
                    </p>

                </div>


            </div>

        </div>

    <?php endif; ?>


    <!-- STUDENT REPORT -->

    <?php if ($report_type === "students"): ?>

        <div class="report-card">

            <h2>
                Student Report
            </h2>

            <br>

            <button
                class="print-btn"
                onclick="window.print()"
            >
                🖨 Print Report
            </button>


            <?php if (!empty($students)): ?>

                <div class="table-wrapper">

                    <table class="report-table">

                        <thead>

                        <tr>

                            <th>Code</th>
                            <th>Name</th>
                            <th>Mobile</th>
                            <th>Course</th>
                            <th>Batch</th>
                            <th>Joining Date</th>
                            <th>Total Fees</th>
                            <th>Status</th>

                        </tr>

                        </thead>

                        <tbody>

                        <?php foreach ($students as $student): ?>

                            <tr>

                                <td>
                                    <?php echo htmlspecialchars($student["student_code"]); ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($student["full_name"]); ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($student["mobile"]); ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($student["course"]); ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($student["batch"]); ?>
                                </td>

                                <td>
                                    <?php
                                    echo !empty($student["joining_date"])
                                        ? date("d M Y", strtotime($student["joining_date"]))
                                        : "-";
                                    ?>
                                </td>

                                <td class="amount">
                                    ₹<?php echo number_format($student["total_fees"], 2); ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($student["status"]); ?>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php else: ?>

                <div class="empty">
                    No student records found.
                </div>

            <?php endif; ?>

        </div>

    <?php endif; ?>


    <!-- ATTENDANCE REPORT -->

    <?php if ($report_type === "attendance"): ?>

        <div class="report-card">

            <h2>
                Attendance Report
            </h2>

            <br>

            <button
                class="print-btn"
                onclick="window.print()"
            >
                🖨 Print Report
            </button>


            <?php if (!empty($attendance)): ?>

                <div class="table-wrapper">

                    <table class="report-table">

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

                        <?php foreach ($attendance as $item): ?>

                            <tr>

                                <td>
                                    <?php
                                    echo date(
                                        "d M Y",
                                        strtotime($item["attendance_date"])
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($item["student_code"]); ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($item["full_name"]); ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($item["batch"]); ?>
                                </td>

                                <td>

                                    <?php if ($item["status"] === "Present"): ?>

                                        <span class="present">
                                            Present
                                        </span>

                                    <?php else: ?>

                                        <span class="absent">
                                            Absent
                                        </span>

                                    <?php endif; ?>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php else: ?>

                <div class="empty">
                    No attendance records found.
                </div>

            <?php endif; ?>

        </div>

    <?php endif; ?>


    <!-- FEES REPORT -->

    <?php if ($report_type === "fees"): ?>

        <div class="report-card">

            <h2>
                Fees Report
            </h2>

            <br>

            <button
                class="print-btn"
                onclick="window.print()"
            >
                🖨 Print Report
            </button>


            <h3 style="margin-top:20px;">

                Total Collection:
                ₹<?php echo number_format($fee_total, 2); ?>

            </h3>


            <?php if (!empty($fees)): ?>

                <div class="table-wrapper">

                    <table class="report-table">

                        <thead>

                        <tr>

                            <th>Receipt</th>
                            <th>Student Code</th>
                            <th>Student Name</th>
                            <th>Amount</th>
                            <th>Date</th>
                            <th>Mode</th>
                            <th>Remarks</th>

                        </tr>

                        </thead>

                        <tbody>

                        <?php foreach ($fees as $fee): ?>

                            <tr>

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $fee["receipt_no"] ?: "-"
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $fee["student_code"] ?: "-"
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $fee["full_name"] ?: "-"
                                    );
                                    ?>
                                </td>

                                <td class="amount">
                                    ₹<?php
                                    echo number_format(
                                        $fee["amount"],
                                        2
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    echo date(
                                        "d M Y",
                                        strtotime(
                                            $fee["payment_date"]
                                        )
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $fee["payment_mode"]
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $fee["remarks"] ?: "-"
                                    );
                                    ?>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php else: ?>

                <div class="empty">
                    No fee records found.
                </div>

            <?php endif; ?>

        </div>

    <?php endif; ?>


    <!-- MARKS REPORT -->

    <?php if ($report_type === "marks"): ?>

        <div class="report-card">

            <h2>
                Test Marks Report
            </h2>

            <br>

            <button
                class="print-btn"
                onclick="window.print()"
            >
                🖨 Print Report
            </button>


            <?php if (!empty($marks)): ?>

                <div class="table-wrapper">

                    <table class="report-table">

                        <thead>

                        <tr>

                            <th>Test</th>
                            <th>Subject</th>
                            <th>Date</th>
                            <th>Student Code</th>
                            <th>Student Name</th>
                            <th>Marks</th>
                            <th>Total</th>
                            <th>Percentage</th>

                        </tr>

                        </thead>

                        <tbody>

                        <?php foreach ($marks as $mark): ?>

                            <?php

                            $percentage = 0;

                            if ($mark["total_marks"] > 0) {

                                $percentage =
                                    ($mark["marks_obtained"]
                                    / $mark["total_marks"])
                                    * 100;

                            }

                            ?>

                            <tr>

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $mark["test_name"]
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $mark["subject"]
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    echo date(
                                        "d M Y",
                                        strtotime(
                                            $mark["test_date"]
                                        )
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $mark["student_code"]
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $mark["full_name"]
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    echo $mark["marks_obtained"];
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    echo $mark["total_marks"];
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    echo number_format(
                                        $percentage,
                                        2
                                    );
                                    ?>%
                                </td>

                            </tr>

                        <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php else: ?>

                <div class="empty">
                    No marks records found.
                </div>

            <?php endif; ?>

        </div>

    <?php endif; ?>


</div>

</body>

</html>