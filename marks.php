<?php
session_start();
require_once "../config/database.php";

if (!isset($_SESSION["admin_id"])) {
    header("Location: ../index.php");
    exit();
}

$message = "";
$error = "";

$selected_test = $_POST["test_id"] ?? $_GET["test_id"] ?? "";

/* =========================
   SAVE MARKS
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["save_marks"])) {

    $test_id = intval($_POST["test_id"]);

    if ($test_id <= 0) {
        $error = "Please select a test.";
    } elseif (empty($_POST["marks"])) {
        $error = "No student marks were entered.";
    } else {

        // Get total marks of selected test
        $stmt = $conn->prepare("
            SELECT total_marks
            FROM tests
            WHERE id = ?
        ");

        $stmt->bind_param("i", $test_id);
        $stmt->execute();

        $result = $stmt->get_result();
        $test_data = $result->fetch_assoc();

        $stmt->close();

        if (!$test_data) {

            $error = "Invalid test selected.";

        } else {

            $total_marks = intval($test_data["total_marks"]);

            $stmt = $conn->prepare("
                INSERT INTO marks
                (test_id, student_id, marks_obtained)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    marks_obtained = VALUES(marks_obtained)
            ");

            foreach ($_POST["marks"] as $student_id => $marks_obtained) {

                $student_id = intval($student_id);

                if ($marks_obtained === "") {
                    continue;
                }

                $marks_obtained = intval($marks_obtained);

                // Prevent marks greater than total marks
                if ($marks_obtained < 0) {
                    $marks_obtained = 0;
                }

                if ($marks_obtained > $total_marks) {
                    $marks_obtained = $total_marks;
                }

                $stmt->bind_param(
                    "iii",
                    $test_id,
                    $student_id,
                    $marks_obtained
                );

                $stmt->execute();
            }

            $stmt->close();

            $message = "Marks saved successfully!";
            $selected_test = $test_id;
        }
    }
}


/* =========================
   GET TESTS
========================= */

$tests = [];

$result = $conn->query("
    SELECT
        t.id,
        t.test_name,
        t.subject,
        t.test_date,
        t.total_marks,
        b.batch_name,
        b.course
    FROM tests t
    INNER JOIN batches b
        ON t.batch_id = b.id
    ORDER BY t.test_date DESC, t.id DESC
");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $tests[] = $row;
    }
}


/* =========================
   GET SELECTED TEST
========================= */

$selected_test_data = null;

if (!empty($selected_test)) {

    $stmt = $conn->prepare("
        SELECT
            t.id,
            t.test_name,
            t.subject,
            t.test_date,
            t.total_marks,
            t.batch_id,
            b.batch_name,
            b.course
        FROM tests t
        INNER JOIN batches b
            ON t.batch_id = b.id
        WHERE t.id = ?
    ");

    $stmt->bind_param("i", $selected_test);
    $stmt->execute();

    $result = $stmt->get_result();

    $selected_test_data = $result->fetch_assoc();

    $stmt->close();
}


/* =========================
   GET STUDENTS + MARKS
========================= */

$students = [];

if ($selected_test_data) {

    /*
       Students are matched using the batch_name
       stored in students.batch.
    */

    $stmt = $conn->prepare("
        SELECT
            s.id,
            s.student_code,
            s.full_name,
            s.batch,
            m.marks_obtained
        FROM students s
        LEFT JOIN marks m
            ON s.id = m.student_id
            AND m.test_id = ?
        WHERE s.status = 'Active'
        AND s.batch = ?
        ORDER BY s.full_name ASC
    ");

    $stmt->bind_param(
        "is",
        $selected_test,
        $selected_test_data["batch_name"]
    );

    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }

    $stmt->close();
}


/* =========================
   MARKS HISTORY
========================= */

$marks_history = [];

$result = $conn->query("
    SELECT
        m.id,
        m.marks_obtained,
        s.student_code,
        s.full_name,
        t.test_name,
        t.subject,
        t.total_marks,
        t.test_date,
        b.batch_name
    FROM marks m

    INNER JOIN students s
        ON m.student_id = s.id

    INNER JOIN tests t
        ON m.test_id = t.id

    INNER JOIN batches b
        ON t.batch_id = b.id

    ORDER BY t.test_date DESC, s.full_name ASC

    LIMIT 100
");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $marks_history[] = $row;
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
        Marks Management | Super20 Academy
    </title>

    <link rel="stylesheet"
          href="../css/style.css">

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

        .marks-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            margin-bottom: 30px;
        }

        .filter-form {
            display: grid;
            grid-template-columns: 1fr auto;
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

        .test-info {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 25px;
        }

        .info-box {
            background: #f5f7fb;
            padding: 15px;
            border-radius: 10px;
        }

        .info-box span {
            display: block;
            font-size: 13px;
            color: #777;
            margin-bottom: 5px;
        }

        .info-box strong {
            font-size: 16px;
        }

        .marks-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .marks-table th,
        .marks-table td {
            padding: 13px;
            border-bottom: 1px solid #eee;
            text-align: left;
        }

        .marks-table th {
            background: #f5f7fb;
        }

        .marks-input {
            width: 100px;
            padding: 9px;
            border: 1px solid #ccc;
            border-radius: 7px;
            font-size: 15px;
        }

        .percentage {
            font-weight: bold;
        }

        .empty {
            text-align: center;
            padding: 30px;
            color: #777;
        }

        .history-title {
            margin-top: 0;
        }

        @media (max-width: 800px) {

            .test-info {
                grid-template-columns: 1fr 1fr;
            }

        }

        @media (max-width: 600px) {

            .filter-form {
                grid-template-columns: 1fr;
            }

            .test-info {
                grid-template-columns: 1fr;
            }

            .marks-table {
                font-size: 13px;
            }

            .marks-table th,
            .marks-table td {
                padding: 8px;
            }

            .marks-input {
                width: 70px;
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
                Marks Management
            </h1>

            <p>
                Enter and manage student test marks
            </p>

        </div>

        <a href="../dashboard.php"
           class="back-btn">

            ← Dashboard

        </a>

    </div>


    <!-- MESSAGES -->

    <?php if (!empty($message)): ?>

        <div class="message">

            <?php
            echo htmlspecialchars($message);
            ?>

        </div>

    <?php endif; ?>


    <?php if (!empty($error)): ?>

        <div class="error">

            <?php
            echo htmlspecialchars($error);
            ?>

        </div>

    <?php endif; ?>


    <!-- SELECT TEST -->

    <div class="marks-card">

        <h2>
            Select Test
        </h2>

        <br>

        <form method="GET"
              class="filter-form">

            <div class="form-group">

                <label>
                    Test
                </label>

                <select name="test_id"
                        required>

                    <option value="">
                        -- Select Test --
                    </option>

                    <?php foreach ($tests as $test): ?>

                        <option
                            value="<?php echo $test["id"]; ?>"
                            <?php
                            echo (
                                $selected_test == $test["id"]
                            )
                            ? "selected"
                            : "";
                            ?>
                        >

                            <?php

                            echo htmlspecialchars(
                                $test["test_name"]
                                . " - "
                                . $test["subject"]
                                . " | "
                                . $test["batch_name"]
                                . " | "
                                . date(
                                    "d-m-Y",
                                    strtotime(
                                        $test["test_date"]
                                    )
                                )
                            );

                            ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <button
                type="submit"
                class="btn btn-primary"
            >

                Load Students

            </button>

        </form>

    </div>


    <!-- MARKS ENTRY -->

    <?php if ($selected_test_data): ?>

        <div class="marks-card">

            <h2>
                Enter Marks
            </h2>

            <br>


            <!-- TEST INFORMATION -->

            <div class="test-info">

                <div class="info-box">

                    <span>
                        Test
                    </span>

                    <strong>
                        <?php
                        echo htmlspecialchars(
                            $selected_test_data["test_name"]
                        );
                        ?>
                    </strong>

                </div>


                <div class="info-box">

                    <span>
                        Subject
                    </span>

                    <strong>
                        <?php
                        echo htmlspecialchars(
                            $selected_test_data["subject"]
                        );
                        ?>
                    </strong>

                </div>


                <div class="info-box">

                    <span>
                        Batch
                    </span>

                    <strong>
                        <?php
                        echo htmlspecialchars(
                            $selected_test_data["batch_name"]
                        );
                        ?>
                    </strong>

                </div>


                <div class="info-box">

                    <span>
                        Total Marks
                    </span>

                    <strong>
                        <?php
                        echo htmlspecialchars(
                            $selected_test_data["total_marks"]
                        );
                        ?>
                    </strong>

                </div>

            </div>


            <?php if (!empty($students)): ?>

                <form method="POST">

                    <input
                        type="hidden"
                        name="test_id"
                        value="<?php
                        echo $selected_test_data["id"];
                        ?>"
                    >


                    <div style="overflow-x:auto;">

                        <table class="marks-table">

                            <thead>

                                <tr>

                                    <th>
                                        #
                                    </th>

                                    <th>
                                        Student Code
                                    </th>

                                    <th>
                                        Student Name
                                    </th>

                                    <th>
                                        Marks
                                    </th>

                                    <th>
                                        Percentage
                                    </th>

                                </tr>

                            </thead>


                            <tbody>

                            <?php $count = 1; ?>

                            <?php foreach ($students as $student): ?>

                                <?php

                                $marks = $student["marks_obtained"];

                                $percentage = "";

                                if (
                                    $marks !== null &&
                                    $selected_test_data["total_marks"] > 0
                                ) {

                                    $percentage = round(
                                        (
                                            $marks /
                                            $selected_test_data[
                                                "total_marks"
                                            ]
                                        ) * 100,
                                        2
                                    );

                                }

                                ?>

                                <tr>

                                    <td>
                                        <?php
                                        echo $count++;
                                        ?>
                                    </td>

                                    <td>
                                        <?php
                                        echo htmlspecialchars(
                                            $student[
                                                "student_code"
                                            ]
                                        );
                                        ?>
                                    </td>

                                    <td>

                                        <strong>

                                            <?php
                                            echo htmlspecialchars(
                                                $student[
                                                    "full_name"
                                                ]
                                            );
                                            ?>

                                        </strong>

                                    </td>

                                    <td>

                                        <input
                                            type="number"
                                            name="marks[<?php
                                            echo $student["id"];
                                            ?>]"
                                            class="marks-input"
                                            min="0"
                                            max="<?php
                                            echo $selected_test_data[
                                                "total_marks"
                                            ];
                                            ?>"
                                            value="<?php
                                            echo $marks !== null
                                                ? htmlspecialchars(
                                                    $marks
                                                )
                                                : "";
                                            ?>"
                                            placeholder="Marks"
                                        >

                                        /
                                        <?php
                                        echo $selected_test_data[
                                            "total_marks"
                                        ];
                                        ?>

                                    </td>

                                    <td class="percentage">

                                        <?php

                                        if ($percentage !== "") {
                                            echo $percentage . "%";
                                        } else {
                                            echo "-";
                                        }

                                        ?>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>


                    <br>

                    <button
                        type="submit"
                        name="save_marks"
                        class="btn btn-success"
                    >

                        Save Marks

                    </button>

                </form>

            <?php else: ?>

                <div class="empty">

                    No active students found for this test's batch.

                    <br><br>

                    Make sure the student's
                    <strong>batch</strong>
                    matches the batch name used in the test.

                </div>

            <?php endif; ?>

        </div>

    <?php endif; ?>


    <!-- MARKS HISTORY -->

    <div class="marks-card">

        <h2 class="history-title">
            Marks History
        </h2>


        <?php if (!empty($marks_history)): ?>

            <div style="overflow-x:auto;">

                <table class="marks-table">

                    <thead>

                        <tr>

                            <th>
                                Date
                            </th>

                            <th>
                                Student
                            </th>

                            <th>
                                Test
                            </th>

                            <th>
                                Subject
                            </th>

                            <th>
                                Batch
                            </th>

                            <th>
                                Marks
                            </th>

                            <th>
                                Percentage
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php foreach ($marks_history as $record): ?>

                        <?php

                        $percentage = 0;

                        if ($record["total_marks"] > 0) {

                            $percentage = round(
                                (
                                    $record["marks_obtained"]
                                    /
                                    $record["total_marks"]
                                ) * 100,
                                2
                            );

                        }

                        ?>

                        <tr>

                            <td>

                                <?php
                                echo date(
                                    "d-m-Y",
                                    strtotime(
                                        $record["test_date"]
                                    )
                                );
                                ?>

                            </td>


                            <td>

                                <strong>

                                    <?php
                                    echo htmlspecialchars(
                                        $record["full_name"]
                                    );
                                    ?>

                                </strong>

                                <br>

                                <small>

                                    <?php
                                    echo htmlspecialchars(
                                        $record["student_code"]
                                    );
                                    ?>

                                </small>

                            </td>


                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $record["test_name"]
                                );
                                ?>

                            </td>


                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $record["subject"]
                                );
                                ?>

                            </td>


                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $record["batch_name"]
                                );
                                ?>

                            </td>


                            <td>

                                <strong>

                                    <?php
                                    echo htmlspecialchars(
                                        $record[
                                            "marks_obtained"
                                        ]
                                    );
                                    ?>

                                </strong>

                                /

                                <?php
                                echo htmlspecialchars(
                                    $record["total_marks"]
                                );
                                ?>

                            </td>


                            <td class="percentage">

                                <?php
                                echo $percentage . "%";
                                ?>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php else: ?>

            <div class="empty">

                No marks have been entered yet.

            </div>

        <?php endif; ?>

    </div>

</div>

</body>
</html>