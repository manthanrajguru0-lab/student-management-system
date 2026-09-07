<?php
session_start();
require_once "../config/database.php";

if (!isset($_SESSION["admin_id"])) {
    header("Location: ../index.php");
    exit();
}

$message = "";
$error = "";

/* =========================
   ADD TEST
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["add_test"])) {

    $batch_id = intval($_POST["batch_id"]);
    $test_name = trim($_POST["test_name"]);
    $subject = trim($_POST["subject"]);
    $test_date = $_POST["test_date"];
    $total_marks = intval($_POST["total_marks"]);

    if (
        $batch_id <= 0 ||
        empty($test_name) ||
        empty($subject) ||
        empty($test_date) ||
        $total_marks <= 0
    ) {
        $error = "Please fill all required fields.";
    } else {

        $stmt = $conn->prepare("
            INSERT INTO tests
            (batch_id, test_name, subject, test_date, total_marks)
            VALUES (?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "isssi",
            $batch_id,
            $test_name,
            $subject,
            $test_date,
            $total_marks
        );

        if ($stmt->execute()) {
            $message = "Test created successfully!";
        } else {
            $error = "Unable to create test.";
        }

        $stmt->close();
    }
}


/* =========================
   DELETE TEST
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["delete_test"])) {

    $test_id = intval($_POST["test_id"]);

    if ($test_id > 0) {

        $stmt = $conn->prepare("DELETE FROM tests WHERE id = ?");
        $stmt->bind_param("i", $test_id);

        if ($stmt->execute()) {
            $message = "Test deleted successfully!";
        } else {
            $error = "Unable to delete test.";
        }

        $stmt->close();
    }
}


/* =========================
   GET ACTIVE BATCHES
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

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Tests Management | Super20 Academy</title>

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

        .test-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            margin-bottom: 30px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
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

        .full-width {
            grid-column: 1 / -1;
        }

        .btn {
            border: none;
            padding: 11px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 600;
        }

        .btn-success {
            background: #16a34a;
            color: white;
        }

        .btn-danger {
            background: #dc2626;
            color: white;
            padding: 8px 13px;
            font-size: 13px;
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

        .test-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .test-table th,
        .test-table td {
            padding: 13px;
            border-bottom: 1px solid #eee;
            text-align: left;
        }

        .test-table th {
            background: #f5f7fb;
        }

        .empty {
            text-align: center;
            padding: 30px;
            color: #777;
        }

        .subject {
            font-weight: 600;
        }

        .marks {
            font-weight: bold;
        }

        @media (max-width: 700px) {

            .form-grid {
                grid-template-columns: 1fr;
            }

            .full-width {
                grid-column: auto;
            }

            .test-table {
                font-size: 13px;
            }

            .test-table th,
            .test-table td {
                padding: 8px;
            }
        }

    </style>

</head>

<body>

<div class="page-container">

    <div class="page-header">

        <div>
            <h1>Tests Management</h1>
            <p>Create and manage tests for batches</p>
        </div>

        <a href="../dashboard.php" class="back-btn">
            ← Dashboard
        </a>

    </div>


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


    <!-- CREATE TEST -->

    <div class="test-card">

        <h2>Create New Test</h2>

        <br>

        <form method="POST">

            <div class="form-grid">

                <div class="form-group">

                    <label>Select Batch *</label>

                    <select name="batch_id" required>

                        <option value="">
                            -- Select Batch --
                        </option>

                        <?php foreach ($batches as $batch): ?>

                            <option value="<?php echo $batch["id"]; ?>">

                                <?php
                                echo htmlspecialchars(
                                    $batch["batch_name"]
                                    . " - "
                                    . $batch["course"]
                                );
                                ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <div class="form-group">

                    <label>Test Name *</label>

                    <input
                        type="text"
                        name="test_name"
                        placeholder="e.g. Unit Test 1"
                        required
                    >

                </div>


                <div class="form-group">

                    <label>Subject *</label>

                    <input
                        type="text"
                        name="subject"
                        placeholder="e.g. Mathematics"
                        required
                    >

                </div>


                <div class="form-group">

                    <label>Test Date *</label>

                    <input
                        type="date"
                        name="test_date"
                        value="<?php echo date("Y-m-d"); ?>"
                        required
                    >

                </div>


                <div class="form-group">

                    <label>Total Marks *</label>

                    <input
                        type="number"
                        name="total_marks"
                        min="1"
                        placeholder="e.g. 100"
                        required
                    >

                </div>


                <div class="full-width">

                    <button
                        type="submit"
                        name="add_test"
                        class="btn btn-success"
                    >
                        + Create Test
                    </button>

                </div>

            </div>

        </form>

    </div>


    <!-- TEST LIST -->

    <div class="test-card">

        <h2>Test List</h2>

        <?php if (!empty($tests)): ?>

            <div style="overflow-x:auto;">

                <table class="test-table">

                    <thead>

                        <tr>
                            <th>#</th>
                            <th>Test Name</th>
                            <th>Subject</th>
                            <th>Batch</th>
                            <th>Date</th>
                            <th>Total Marks</th>
                            <th>Action</th>
                        </tr>

                    </thead>

                    <tbody>

                        <?php $count = 1; ?>

                        <?php foreach ($tests as $test): ?>

                            <tr>

                                <td>
                                    <?php echo $count++; ?>
                                </td>

                                <td>
                                    <strong>
                                        <?php
                                        echo htmlspecialchars(
                                            $test["test_name"]
                                        );
                                        ?>
                                    </strong>
                                </td>

                                <td class="subject">
                                    <?php
                                    echo htmlspecialchars(
                                        $test["subject"]
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $test["batch_name"]
                                    );
                                    ?>
                                    <br>
                                    <small>
                                        <?php
                                        echo htmlspecialchars(
                                            $test["course"]
                                        );
                                        ?>
                                    </small>
                                </td>

                                <td>
                                    <?php
                                    echo date(
                                        "d-m-Y",
                                        strtotime($test["test_date"])
                                    );
                                    ?>
                                </td>

                                <td class="marks">
                                    <?php
                                    echo htmlspecialchars(
                                        $test["total_marks"]
                                    );
                                    ?>
                                </td>

                                <td>

                                    <form method="POST"
                                          onsubmit="return confirm('Delete this test?');">

                                        <input
                                            type="hidden"
                                            name="test_id"
                                            value="<?php echo $test["id"]; ?>"
                                        >

                                        <button
                                            type="submit"
                                            name="delete_test"
                                            class="btn btn-danger"
                                        >
                                            Delete
                                        </button>

                                    </form>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php else: ?>

            <div class="empty">
                No tests created yet.
            </div>

        <?php endif; ?>

    </div>

</div>

</body>
</html>