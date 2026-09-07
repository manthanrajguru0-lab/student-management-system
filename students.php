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
   DELETE STUDENT
========================= */

if (isset($_POST["delete_student"])) {

    $id = intval($_POST["student_id"]);

    $stmt = $conn->prepare("DELETE FROM students WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        $message = "Student deleted successfully.";
    } else {
        $error = "Unable to delete student.";
    }

    $stmt->close();
}


/* =========================
   ADD / UPDATE STUDENT
========================= */

if (isset($_POST["save_student"])) {

    $id = intval($_POST["student_id"]);

    $student_code = trim($_POST["student_code"]);
    $full_name = trim($_POST["full_name"]);
    $mobile = trim($_POST["mobile"]);
    $parent_name = trim($_POST["parent_name"]);
    $parent_contact = trim($_POST["parent_contact"]);
    $address = trim($_POST["address"]);
    $course = trim($_POST["course"]);
    $batch = trim($_POST["batch"]);
    $joining_date = $_POST["joining_date"];
    $total_fees = floatval($_POST["total_fees"]);
    $status = $_POST["status"];

    if (
        empty($student_code) ||
        empty($full_name) ||
        empty($course)
    ) {
        $error = "Please fill all required fields.";
    } else {

        if ($id > 0) {

            /* UPDATE */

            $stmt = $conn->prepare("
                UPDATE students SET
                    student_code = ?,
                    full_name = ?,
                    mobile = ?,
                    parent_name = ?,
                    parent_contact = ?,
                    address = ?,
                    course = ?,
                    batch = ?,
                    joining_date = ?,
                    total_fees = ?,
                    status = ?
                WHERE id = ?
            ");

            $stmt->bind_param(
                "sssssssssdsi",
                $student_code,
                $full_name,
                $mobile,
                $parent_name,
                $parent_contact,
                $address,
                $course,
                $batch,
                $joining_date,
                $total_fees,
                $status,
                $id
            );

            if ($stmt->execute()) {
                $message = "Student updated successfully.";
            } else {
                $error = "Unable to update student. Student code may already exist.";
            }

            $stmt->close();

        } else {

            /* INSERT */

            $stmt = $conn->prepare("
                INSERT INTO students
                (
                    student_code,
                    full_name,
                    mobile,
                    parent_name,
                    parent_contact,
                    address,
                    course,
                    batch,
                    joining_date,
                    total_fees,
                    status
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->bind_param(
                "sssssssssds",
                $student_code,
                $full_name,
                $mobile,
                $parent_name,
                $parent_contact,
                $address,
                $course,
                $batch,
                $joining_date,
                $total_fees,
                $status
            );

            if ($stmt->execute()) {
                $message = "Student added successfully.";
            } else {
                $error = "Unable to add student. Student code may already exist.";
            }

            $stmt->close();
        }
    }
}


/* =========================
   EDIT STUDENT
========================= */

$edit_student = null;

if (isset($_GET["edit"])) {

    $edit_id = intval($_GET["edit"]);

    $stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $edit_student = $result->fetch_assoc();
    }

    $stmt->close();
}


/* =========================
   SEARCH STUDENTS
========================= */

$search = "";

if (isset($_GET["search"])) {
    $search = trim($_GET["search"]);
}

if ($search != "") {

    $search_value = "%" . $search . "%";

    $stmt = $conn->prepare("
        SELECT *
        FROM students
        WHERE student_code LIKE ?
        OR full_name LIKE ?
        OR mobile LIKE ?
        OR course LIKE ?
        OR batch LIKE ?
        ORDER BY id DESC
    ");

    $stmt->bind_param(
        "sssss",
        $search_value,
        $search_value,
        $search_value,
        $search_value,
        $search_value
    );

    $stmt->execute();

    $students = $stmt->get_result();

    $stmt->close();

} else {

    $students = $conn->query("
        SELECT *
        FROM students
        ORDER BY id DESC
    ");
}


/* =========================
   GET BATCHES
========================= */

$batches = $conn->query("
    SELECT id, batch_name, course
    FROM batches
    WHERE status = 'Active'
    ORDER BY batch_name ASC
");

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Students | Super20 Academy</title>

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
            max-width: 1250px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .top-section {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
            margin-bottom: 25px;
        }

        .top-section h2 {
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

        .form-group.full {
            grid-column: span 3;
        }

        .form-group label {
            font-weight: bold;
            margin-bottom: 7px;
            color: #333;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 11px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            outline: none;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #667eea;
        }

        .form-group textarea {
            min-height: 80px;
            resize: vertical;
        }

        .btn {
            border: none;
            padding: 12px 20px;
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

        .search-section {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .search-section input {
            flex: 1;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
        }

        .table-section {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 950px;
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

        .status-active {
            background: #dff5e3;
            color: #16802d;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .status-inactive {
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

        @media (max-width: 850px) {

            .form-grid {
                grid-template-columns: 1fr;
            }

            .form-group.full {
                grid-column: span 1;
            }

            .page-header {
                padding: 15px;
            }

            .page-header h1 {
                font-size: 19px;
            }
        }

    </style>

</head>

<body>


<!-- HEADER -->

<div class="page-header">

    <h1>👨‍🎓 Student Management</h1>

    <a href="../dashboard.php">← Dashboard</a>

</div>


<div class="container">


    <!-- MESSAGES -->

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


    <!-- ADD / EDIT STUDENT -->

    <div class="top-section">

        <h2>
            <?php echo $edit_student ? "✏️ Edit Student" : "➕ Add New Student"; ?>
        </h2>


        <form method="POST">

            <input
                type="hidden"
                name="student_id"
                value="<?php echo $edit_student ? $edit_student["id"] : 0; ?>"
            >


            <div class="form-grid">


                <div class="form-group">

                    <label>Student Code *</label>

                    <input
                        type="text"
                        name="student_code"
                        placeholder="Example: S20-001"
                        required
                        value="<?php echo $edit_student ? htmlspecialchars($edit_student["student_code"]) : ""; ?>"
                    >

                </div>


                <div class="form-group">

                    <label>Full Name *</label>

                    <input
                        type="text"
                        name="full_name"
                        placeholder="Enter student name"
                        required
                        value="<?php echo $edit_student ? htmlspecialchars($edit_student["full_name"]) : ""; ?>"
                    >

                </div>


                <div class="form-group">

                    <label>Mobile</label>

                    <input
                        type="text"
                        name="mobile"
                        placeholder="Student mobile"
                        value="<?php echo $edit_student ? htmlspecialchars($edit_student["mobile"]) : ""; ?>"
                    >

                </div>


                <div class="form-group">

                    <label>Parent Name</label>

                    <input
                        type="text"
                        name="parent_name"
                        placeholder="Parent / Guardian name"
                        value="<?php echo $edit_student ? htmlspecialchars($edit_student["parent_name"]) : ""; ?>"
                    >

                </div>


                <div class="form-group">

                    <label>Parent Contact</label>

                    <input
                        type="text"
                        name="parent_contact"
                        placeholder="Parent contact number"
                        value="<?php echo $edit_student ? htmlspecialchars($edit_student["parent_contact"]) : ""; ?>"
                    >

                </div>


                <div class="form-group">

                    <label>Course *</label>

                    <input
                        type="text"
                        name="course"
                        placeholder="Example: BCA / Science / Commerce"
                        required
                        value="<?php echo $edit_student ? htmlspecialchars($edit_student["course"]) : ""; ?>"
                    >

                </div>


                <div class="form-group">

                    <label>Batch</label>

                    <select name="batch">

                        <option value="">Select Batch</option>

                        <?php if ($batches && $batches->num_rows > 0): ?>

                            <?php while ($batch_row = $batches->fetch_assoc()): ?>

                                <option
                                    value="<?php echo htmlspecialchars($batch_row["batch_name"]); ?>"
                                    <?php
                                    if (
                                        $edit_student &&
                                        $edit_student["batch"] == $batch_row["batch_name"]
                                    ) {
                                        echo "selected";
                                    }
                                    ?>
                                >
                                    <?php echo htmlspecialchars($batch_row["batch_name"]); ?>
                                </option>

                            <?php endwhile; ?>

                        <?php endif; ?>

                    </select>

                </div>


                <div class="form-group">

                    <label>Joining Date</label>

                    <input
                        type="date"
                        name="joining_date"
                        value="<?php echo $edit_student ? $edit_student["joining_date"] : ""; ?>"
                    >

                </div>


                <div class="form-group">

                    <label>Total Fees</label>

                    <input
                        type="number"
                        step="0.01"
                        name="total_fees"
                        placeholder="0.00"
                        value="<?php echo $edit_student ? $edit_student["total_fees"] : "0"; ?>"
                    >

                </div>


                <div class="form-group">

                    <label>Status</label>

                    <select name="status">

                        <option
                            value="Active"
                            <?php
                            if (!$edit_student || $edit_student["status"] == "Active") {
                                echo "selected";
                            }
                            ?>
                        >
                            Active
                        </option>

                        <option
                            value="Inactive"
                            <?php
                            if ($edit_student && $edit_student["status"] == "Inactive") {
                                echo "selected";
                            }
                            ?>
                        >
                            Inactive
                        </option>

                    </select>

                </div>


                <div class="form-group full">

                    <label>Address</label>

                    <textarea
                        name="address"
                        placeholder="Enter student address"
                    ><?php echo $edit_student ? htmlspecialchars($edit_student["address"]) : ""; ?></textarea>

                </div>


                <div>

                    <button
                        type="submit"
                        name="save_student"
                        class="btn btn-primary"
                    >
                        <?php echo $edit_student ? "Update Student" : "Add Student"; ?>
                    </button>


                    <?php if ($edit_student): ?>

                        <a
                            href="students.php"
                            class="btn btn-secondary"
                        >
                            Cancel
                        </a>

                    <?php endif; ?>

                </div>

            </div>

        </form>

    </div>


    <!-- SEARCH -->

    <div class="table-section">

        <h2>📋 Student Records</h2>

        <br>


        <form method="GET" class="search-section">

            <input
                type="text"
                name="search"
                placeholder="Search by name, code, mobile, course or batch..."
                value="<?php echo htmlspecialchars($search); ?>"
            >

            <button
                type="submit"
                class="btn btn-primary"
            >
                🔍 Search
            </button>

            <a
                href="students.php"
                class="btn btn-secondary"
            >
                Reset
            </a>

        </form>


        <!-- STUDENT TABLE -->

        <table>

            <thead>

                <tr>

                    <th>#</th>

                    <th>Student Code</th>

                    <th>Name</th>

                    <th>Mobile</th>

                    <th>Parent</th>

                    <th>Course</th>

                    <th>Batch</th>

                    <th>Fees</th>

                    <th>Status</th>

                    <th>Actions</th>

                </tr>

            </thead>


            <tbody>

                <?php if ($students && $students->num_rows > 0): ?>

                    <?php $count = 1; ?>

                    <?php while ($student = $students->fetch_assoc()): ?>

                        <tr>

                            <td>
                                <?php echo $count++; ?>
                            </td>

                            <td>
                                <strong>
                                    <?php echo htmlspecialchars($student["student_code"]); ?>
                                </strong>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($student["full_name"]); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($student["mobile"]); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($student["parent_name"]); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($student["course"]); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($student["batch"]); ?>
                            </td>

                            <td>
                                ₹<?php echo number_format($student["total_fees"], 2); ?>
                            </td>

                            <td>

                                <?php if ($student["status"] == "Active"): ?>

                                    <span class="status-active">
                                        Active
                                    </span>

                                <?php else: ?>

                                    <span class="status-inactive">
                                        Inactive
                                    </span>

                                <?php endif; ?>

                            </td>


                            <td>

                                <div class="actions">

                                    <a
                                        href="students.php?edit=<?php echo $student["id"]; ?>"
                                        class="btn btn-edit"
                                    >
                                        Edit
                                    </a>


                                    <form
                                        method="POST"
                                        class="delete-form"
                                        onsubmit="return confirm('Are you sure you want to delete this student?');"
                                    >

                                        <input
                                            type="hidden"
                                            name="student_id"
                                            value="<?php echo $student["id"]; ?>"
                                        >

                                        <button
                                            type="submit"
                                            name="delete_student"
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
                            colspan="10"
                            style="text-align:center; padding:30px;"
                        >
                            No students found.
                        </td>

                    </tr>

                <?php endif; ?>

            </tbody>

        </table>

    </div>

</div>


</body>

</html>