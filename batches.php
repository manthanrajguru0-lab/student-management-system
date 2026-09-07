<?php
session_start();

if (!isset($_SESSION["admin_id"])) {
    header("Location: ../index.php");
    exit();
}

require_once "../config/database.php";

$message = "";
$error = "";

/* =========================
   DELETE BATCH
========================= */

if (isset($_POST["delete_batch"])) {

    $id = intval($_POST["batch_id"]);

    $stmt = $conn->prepare("DELETE FROM batches WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        $message = "Batch deleted successfully.";
    } else {
        $error = "Unable to delete batch.";
    }

    $stmt->close();
}


/* =========================
   ADD / UPDATE BATCH
========================= */

if (isset($_POST["save_batch"])) {

    $id = intval($_POST["batch_id"]);

    $batch_name = trim($_POST["batch_name"]);
    $course = trim($_POST["course"]);
    $start_time = $_POST["start_time"];
    $end_time = $_POST["end_time"];
    $start_date = $_POST["start_date"];
    $status = $_POST["status"];

    if (empty($batch_name) || empty($course)) {

        $error = "Batch name and course are required.";

    } else {

        if ($id > 0) {

            /* UPDATE */

            $stmt = $conn->prepare("
                UPDATE batches SET
                    batch_name = ?,
                    course = ?,
                    start_time = ?,
                    end_time = ?,
                    start_date = ?,
                    status = ?
                WHERE id = ?
            ");

            $stmt->bind_param(
                "ssssssi",
                $batch_name,
                $course,
                $start_time,
                $end_time,
                $start_date,
                $status,
                $id
            );

            if ($stmt->execute()) {
                $message = "Batch updated successfully.";
            } else {
                $error = "Unable to update batch.";
            }

            $stmt->close();

        } else {

            /* INSERT */

            $stmt = $conn->prepare("
                INSERT INTO batches
                (
                    batch_name,
                    course,
                    start_time,
                    end_time,
                    start_date,
                    status
                )
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            $stmt->bind_param(
                "ssssss",
                $batch_name,
                $course,
                $start_time,
                $end_time,
                $start_date,
                $status
            );

            if ($stmt->execute()) {
                $message = "Batch added successfully.";
            } else {
                $error = "Unable to add batch.";
            }

            $stmt->close();
        }
    }
}


/* =========================
   EDIT BATCH
========================= */

$edit_batch = null;

if (isset($_GET["edit"])) {

    $edit_id = intval($_GET["edit"]);

    $stmt = $conn->prepare("SELECT * FROM batches WHERE id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $edit_batch = $result->fetch_assoc();
    }

    $stmt->close();
}


/* =========================
   SEARCH BATCH
========================= */

$search = "";

if (isset($_GET["search"])) {
    $search = trim($_GET["search"]);
}

if ($search != "") {

    $search_value = "%" . $search . "%";

    $stmt = $conn->prepare("
        SELECT *
        FROM batches
        WHERE batch_name LIKE ?
        OR course LIKE ?
        ORDER BY id DESC
    ");

    $stmt->bind_param(
        "ss",
        $search_value,
        $search_value
    );

    $stmt->execute();

    $batches = $stmt->get_result();

    $stmt->close();

} else {

    $batches = $conn->query("
        SELECT *
        FROM batches
        ORDER BY id DESC
    ");
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Batches | Super20 Academy</title>

    <link rel="stylesheet" href="../css/style.css">

    <style>

        body {
            background: #f4f6fb;
        }

        .page-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 18px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-header h1 {
            color: white;
            font-size: 24px;
        }

        .page-header a {
            color: white;
            text-decoration: none;
            background: rgba(255,255,255,0.18);
            padding: 10px 16px;
            border-radius: 8px;
        }

        .container {
            max-width: 1100px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .section {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
            margin-bottom: 25px;
        }

        .section h2 {
            margin-bottom: 20px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-weight: bold;
            margin-bottom: 7px;
        }

        .form-group input,
        .form-group select {
            padding: 11px;
            border: 1px solid #ddd;
            border-radius: 8px;
            outline: none;
        }

        .form-group input:focus,
        .form-group select:focus {
            border-color: #667eea;
        }

        .btn {
            border: none;
            padding: 11px 18px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .btn-secondary {
            background: #777;
            color: white;
        }

        .btn-edit {
            background: #f0ad4e;
            color: white;
            padding: 7px 12px;
            font-size: 13px;
        }

        .btn-delete {
            background: #dc3545;
            color: white;
            padding: 7px 12px;
            font-size: 13px;
        }

        .message {
            background: #dff5e3;
            color: #176b2c;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .error {
            background: #ffe1e1;
            color: #b00000;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .search {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .search input {
            flex: 1;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #667eea;
            color: white;
            padding: 13px;
            text-align: left;
        }

        td {
            padding: 12px;
            border-bottom: 1px solid #eee;
        }

        tr:hover {
            background: #f8f9ff;
        }

        .active {
            background: #dff5e3;
            color: #16802d;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .inactive {
            background: #ffe1e1;
            color: #b00000;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .actions {
            display: flex;
            gap: 6px;
        }

        .delete-form {
            display: inline;
        }

        @media (max-width: 800px) {

            .form-grid {
                grid-template-columns: 1fr;
            }

            .page-header {
                padding: 15px;
            }

        }

    </style>

</head>

<body>


<div class="page-header">

    <h1>👥 Batch Management</h1>

    <a href="../dashboard.php">← Dashboard</a>

</div>


<div class="container">


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


    <!-- ADD / EDIT -->

    <div class="section">

        <h2>
            <?php echo $edit_batch ? "✏️ Edit Batch" : "➕ Add New Batch"; ?>
        </h2>

        <form method="POST">

            <input
                type="hidden"
                name="batch_id"
                value="<?php echo $edit_batch ? $edit_batch["id"] : 0; ?>"
            >

            <div class="form-grid">


                <div class="form-group">

                    <label>Batch Name *</label>

                    <input
                        type="text"
                        name="batch_name"
                        placeholder="Example: 10th Batch 1"
                        required
                        value="<?php echo $edit_batch ? htmlspecialchars($edit_batch["batch_name"]) : ""; ?>"
                    >

                </div>


                <div class="form-group">

                    <label>Course / Stream *</label>

                    <select name="course" required>

                        <option value="">Select Course</option>

                        <option
                            value="10th"
                            <?php
                            if ($edit_batch && $edit_batch["course"] == "10th") {
                                echo "selected";
                            }
                            ?>
                        >
                            10th
                        </option>

                        <option
                            value="11th Science"
                            <?php
                            if ($edit_batch && $edit_batch["course"] == "11th Science") {
                                echo "selected";
                            }
                            ?>
                        >
                            11th Science
                        </option>

                        <option
                            value="11th Commerce"
                            <?php
                            if ($edit_batch && $edit_batch["course"] == "11th Commerce") {
                                echo "selected";
                            }
                            ?>
                        >
                            11th Commerce
                        </option>

                        <option
                            value="12th Science"
                            <?php
                            if ($edit_batch && $edit_batch["course"] == "12th Science") {
                                echo "selected";
                            }
                            ?>
                        >
                            12th Science
                        </option>

                        <option
                            value="12th Commerce"
                            <?php
                            if ($edit_batch && $edit_batch["course"] == "12th Commerce") {
                                echo "selected";
                            }
                            ?>
                        >
                            12th Commerce
                        </option>

                    </select>

                </div>


                <div class="form-group">

                    <label>Status</label>

                    <select name="status">

                        <option
                            value="Active"
                            <?php
                            if (!$edit_batch || $edit_batch["status"] == "Active") {
                                echo "selected";
                            }
                            ?>
                        >
                            Active
                        </option>

                        <option
                            value="Inactive"
                            <?php
                            if ($edit_batch && $edit_batch["status"] == "Inactive") {
                                echo "selected";
                            }
                            ?>
                        >
                            Inactive
                        </option>

                    </select>

                </div>


                <div class="form-group">

                    <label>Start Time</label>

                    <input
                        type="time"
                        name="start_time"
                        value="<?php echo $edit_batch ? $edit_batch["start_time"] : ""; ?>"
                    >

                </div>


                <div class="form-group">

                    <label>End Time</label>

                    <input
                        type="time"
                        name="end_time"
                        value="<?php echo $edit_batch ? $edit_batch["end_time"] : ""; ?>"
                    >

                </div>


                <div class="form-group">

                    <label>Start Date</label>

                    <input
                        type="date"
                        name="start_date"
                        value="<?php echo $edit_batch ? $edit_batch["start_date"] : ""; ?>"
                    >

                </div>


                <div>

                    <button
                        type="submit"
                        name="save_batch"
                        class="btn btn-primary"
                    >
                        <?php echo $edit_batch ? "Update Batch" : "Add Batch"; ?>
                    </button>


                    <?php if ($edit_batch): ?>

                        <a
                            href="batches.php"
                            class="btn btn-secondary"
                        >
                            Cancel
                        </a>

                    <?php endif; ?>

                </div>

            </div>

        </form>

    </div>


    <!-- BATCH LIST -->

    <div class="section">

        <h2>📋 Batch Records</h2>

        <br>


        <form method="GET" class="search">

            <input
                type="text"
                name="search"
                placeholder="Search batch or course..."
                value="<?php echo htmlspecialchars($search); ?>"
            >

            <button
                type="submit"
                class="btn btn-primary"
            >
                🔍 Search
            </button>

            <a
                href="batches.php"
                class="btn btn-secondary"
            >
                Reset
            </a>

        </form>


        <div class="table-wrapper">

            <table>

                <thead>

                    <tr>

                        <th>#</th>
                        <th>Batch Name</th>
                        <th>Course / Stream</th>
                        <th>Start Time</th>
                        <th>End Time</th>
                        <th>Start Date</th>
                        <th>Status</th>
                        <th>Actions</th>

                    </tr>

                </thead>


                <tbody>

                <?php if ($batches && $batches->num_rows > 0): ?>

                    <?php $count = 1; ?>

                    <?php while ($batch = $batches->fetch_assoc()): ?>

                        <tr>

                            <td>
                                <?php echo $count++; ?>
                            </td>

                            <td>
                                <strong>
                                    <?php echo htmlspecialchars($batch["batch_name"]); ?>
                                </strong>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($batch["course"]); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($batch["start_time"]); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($batch["end_time"]); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($batch["start_date"]); ?>
                            </td>

                            <td>

                                <?php if ($batch["status"] == "Active"): ?>

                                    <span class="active">
                                        Active
                                    </span>

                                <?php else: ?>

                                    <span class="inactive">
                                        Inactive
                                    </span>

                                <?php endif; ?>

                            </td>

                            <td>

                                <div class="actions">

                                    <a
                                        href="batches.php?edit=<?php echo $batch["id"]; ?>"
                                        class="btn btn-edit"
                                    >
                                        Edit
                                    </a>


                                    <form
                                        method="POST"
                                        class="delete-form"
                                        onsubmit="return confirm('Are you sure you want to delete this batch?');"
                                    >

                                        <input
                                            type="hidden"
                                            name="batch_id"
                                            value="<?php echo $batch["id"]; ?>"
                                        >

                                        <button
                                            type="submit"
                                            name="delete_batch"
                                            class="btn btn-delete"
                                        >
                                            Delete
                                        </button>

                                    </form>

                                </div>

                            </td>

                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>

                        <td
                            colspan="8"
                            style="text-align:center; padding:30px;"
                        >
                            No batches found.
                        </td>

                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>


</body>

</html>