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
   ADD FEE PAYMENT
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["add_fee"])) {

    $student_id = intval($_POST["student_id"]);
    $amount = floatval($_POST["amount"]);
    $payment_date = $_POST["payment_date"];
    $payment_mode = trim($_POST["payment_mode"]);
    $receipt_no = trim($_POST["receipt_no"]);
    $remarks = trim($_POST["remarks"]);

    if ($student_id <= 0 || $amount <= 0 || empty($payment_date)) {
        $error = "Please enter all required fee details.";
    } else {

        $stmt = $conn->prepare("
            INSERT INTO fees
            (student_id, amount, payment_date, payment_mode, receipt_no, remarks)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "idssss",
            $student_id,
            $amount,
            $payment_date,
            $payment_mode,
            $receipt_no,
            $remarks
        );

        if ($stmt->execute()) {
            $message = "Fee payment added successfully!";
        } else {
            $error = "Unable to add fee payment.";
        }

        $stmt->close();
    }
}


/* =========================
   DELETE FEE
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["delete_fee"])) {

    $fee_id = intval($_POST["fee_id"]);

    if ($fee_id > 0) {

        $stmt = $conn->prepare("DELETE FROM fees WHERE id = ?");
        $stmt->bind_param("i", $fee_id);

        if ($stmt->execute()) {
            $message = "Fee record deleted successfully!";
        } else {
            $error = "Unable to delete fee record.";
        }

        $stmt->close();
    }
}


/* =========================
   GET STUDENTS
========================= */

$students = [];

$result = $conn->query("
    SELECT id, student_code, full_name, batch, total_fees
    FROM students
    WHERE status = 'Active'
    ORDER BY full_name ASC
");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
}


/* =========================
   GET FEE RECORDS
========================= */

$fees = [];

$result = $conn->query("
    SELECT
        f.id,
        f.student_id,
        f.amount,
        f.payment_date,
        f.payment_mode,
        f.receipt_no,
        f.remarks,
        s.student_code,
        s.full_name,
        s.batch
    FROM fees f
    INNER JOIN students s
        ON f.student_id = s.id
    ORDER BY f.payment_date DESC, f.id DESC
");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $fees[] = $row;
    }
}


/* =========================
   TOTAL COLLECTION
========================= */

$total_collection = 0;

$result = $conn->query("
    SELECT COALESCE(SUM(amount), 0) AS total
    FROM fees
");

if ($result) {
    $row = $result->fetch_assoc();
    $total_collection = $row["total"];
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Fees Management | Super20 Academy</title>

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

        .fee-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            margin-bottom: 30px;
        }

        .summary {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f5f7fb;
            padding: 18px 22px;
            border-radius: 10px;
            margin-bottom: 25px;
        }

        .summary h3 {
            margin: 0;
        }

        .total {
            font-size: 24px;
            font-weight: bold;
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
        .form-group select,
        .form-group textarea {
            padding: 11px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 15px;
        }

        .form-group textarea {
            min-height: 80px;
            resize: vertical;
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

        .fee-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .fee-table th,
        .fee-table td {
            padding: 13px;
            border-bottom: 1px solid #eee;
            text-align: left;
        }

        .fee-table th {
            background: #f5f7fb;
        }

        .amount {
            font-weight: bold;
        }

        .empty {
            text-align: center;
            padding: 30px;
            color: #777;
        }

        @media (max-width: 700px) {

            .form-grid {
                grid-template-columns: 1fr;
            }

            .full-width {
                grid-column: auto;
            }

            .fee-table {
                font-size: 13px;
            }

            .fee-table th,
            .fee-table td {
                padding: 8px;
            }

            .summary {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }
        }

    </style>

</head>

<body>

<div class="page-container">

    <div class="page-header">

        <div>
            <h1>Fees Management</h1>
            <p>Manage student fee payments</p>
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


    <!-- SUMMARY -->

    <div class="fee-card">

        <div class="summary">

            <h3>Total Fee Collection</h3>

            <div class="total">
                ₹<?php echo number_format($total_collection, 2); ?>
            </div>

        </div>


        <!-- ADD PAYMENT -->

        <h2>Add Fee Payment</h2>

        <br>

        <form method="POST">

            <div class="form-grid">

                <div class="form-group">

                    <label>Student *</label>

                    <select name="student_id" required>

                        <option value="">
                            -- Select Student --
                        </option>

                        <?php foreach ($students as $student): ?>

                            <option value="<?php echo $student["id"]; ?>">

                                <?php
                                echo htmlspecialchars(
                                    $student["student_code"]
                                    . " - "
                                    . $student["full_name"]
                                    . " (" 
                                    . $student["batch"]
                                    . ")"
                                );
                                ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <div class="form-group">

                    <label>Amount *</label>

                    <input
                        type="number"
                        name="amount"
                        min="1"
                        step="0.01"
                        placeholder="Enter amount"
                        required
                    >

                </div>


                <div class="form-group">

                    <label>Payment Date *</label>

                    <input
                        type="date"
                        name="payment_date"
                        value="<?php echo date("Y-m-d"); ?>"
                        required
                    >

                </div>


                <div class="form-group">

                    <label>Payment Mode</label>

                    <select name="payment_mode">

                        <option value="Cash">
                            Cash
                        </option>

                        <option value="UPI">
                            UPI
                        </option>

                        <option value="Card">
                            Card
                        </option>

                        <option value="Bank Transfer">
                            Bank Transfer
                        </option>

                    </select>

                </div>


                <div class="form-group">

                    <label>Receipt Number</label>

                    <input
                        type="text"
                        name="receipt_no"
                        placeholder="e.g. S20-001"
                    >

                </div>


                <div class="form-group">

                    <label>Remarks</label>

                    <input
                        type="text"
                        name="remarks"
                        placeholder="Optional remarks"
                    >

                </div>


                <div class="full-width">

                    <button
                        type="submit"
                        name="add_fee"
                        class="btn btn-success"
                    >
                        + Add Payment
                    </button>

                </div>

            </div>

        </form>

    </div>


    <!-- FEE HISTORY -->

    <div class="fee-card">

        <h2>Fee Payment History</h2>

        <?php if (!empty($fees)): ?>

            <div style="overflow-x:auto;">

                <table class="fee-table">

                    <thead>

                        <tr>
                            <th>Date</th>
                            <th>Student</th>
                            <th>Batch</th>
                            <th>Amount</th>
                            <th>Mode</th>
                            <th>Receipt</th>
                            <th>Remarks</th>
                            <th>Action</th>
                        </tr>

                    </thead>

                    <tbody>

                        <?php foreach ($fees as $fee): ?>

                            <tr>

                                <td>
                                    <?php
                                    echo date(
                                        "d-m-Y",
                                        strtotime($fee["payment_date"])
                                    );
                                    ?>
                                </td>

                                <td>

                                    <strong>
                                        <?php
                                        echo htmlspecialchars(
                                            $fee["full_name"]
                                        );
                                        ?>
                                    </strong>

                                    <br>

                                    <small>
                                        <?php
                                        echo htmlspecialchars(
                                            $fee["student_code"]
                                        );
                                        ?>
                                    </small>

                                </td>

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $fee["batch"]
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
                                    echo htmlspecialchars(
                                        $fee["payment_mode"]
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $fee["receipt_no"]
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $fee["remarks"]
                                    );
                                    ?>
                                </td>

                                <td>

                                    <form method="POST"
                                          onsubmit="return confirm('Delete this fee record?');">

                                        <input
                                            type="hidden"
                                            name="fee_id"
                                            value="<?php echo $fee["id"]; ?>"
                                        >

                                        <button
                                            type="submit"
                                            name="delete_fee"
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
                No fee payments recorded yet.
            </div>

        <?php endif; ?>

    </div>

</div>

</body>
</html>