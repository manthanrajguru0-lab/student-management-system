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
   ADD NOTICE
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["add_notice"])) {

    $title = trim($_POST["title"]);
    $notice_message = trim($_POST["message"]);
    $notice_date = $_POST["notice_date"];
    $status = $_POST["status"];

    if (
        empty($title) ||
        empty($notice_message) ||
        empty($notice_date)
    ) {
        $error = "Please fill all required fields.";
    } else {

        $stmt = $conn->prepare("
            INSERT INTO notices
            (title, message, notice_date, status)
            VALUES (?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "ssss",
            $title,
            $notice_message,
            $notice_date,
            $status
        );

        if ($stmt->execute()) {
            $message = "Notice added successfully!";
        } else {
            $error = "Unable to add notice.";
        }

        $stmt->close();
    }
}


/* =========================
   DELETE NOTICE
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["delete_notice"])) {

    $notice_id = intval($_POST["notice_id"]);

    if ($notice_id > 0) {

        $stmt = $conn->prepare("
            DELETE FROM notices
            WHERE id = ?
        ");

        $stmt->bind_param("i", $notice_id);

        if ($stmt->execute()) {
            $message = "Notice deleted successfully!";
        } else {
            $error = "Unable to delete notice.";
        }

        $stmt->close();
    }
}


/* =========================
   FETCH NOTICES
========================= */

$notices = [];

$result = $conn->query("
    SELECT *
    FROM notices
    ORDER BY notice_date DESC, id DESC
");

if ($result) {

    while ($row = $result->fetch_assoc()) {
        $notices[] = $row;
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
        Notices Management | Super20 Academy
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

        .notice-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            margin-bottom: 30px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 7px;
            margin-bottom: 18px;
        }

        .form-group label {
            font-weight: 600;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            padding: 11px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 15px;
        }

        .form-group textarea {
            min-height: 130px;
            resize: vertical;
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

        .notice-item {
            border: 1px solid #eee;
            border-radius: 12px;
            padding: 20px;
            margin-top: 15px;
            background: #fafafa;
        }

        .notice-title {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .notice-message {
            line-height: 1.6;
            color: #444;
            white-space: pre-wrap;
        }

        .notice-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 15px;
            font-size: 13px;
            color: #777;
        }

        .status-active {
            background: #dcfce7;
            color: #166534;
            padding: 5px 10px;
            border-radius: 20px;
            font-weight: 600;
        }

        .status-inactive {
            background: #fee2e2;
            color: #991b1b;
            padding: 5px 10px;
            border-radius: 20px;
            font-weight: 600;
        }

        .empty {
            text-align: center;
            padding: 30px;
            color: #777;
        }

    </style>

</head>

<body>

<div class="page-container">


    <!-- HEADER -->

    <div class="page-header">

        <div>

            <h1>
                Notices Management
            </h1>

            <p>
                Create and manage academy announcements
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


    <!-- ADD NOTICE -->

    <div class="notice-card">

        <h2>
            Create New Notice
        </h2>

        <br>

        <form method="POST">


            <div class="form-group">

                <label>
                    Notice Title *
                </label>

                <input
                    type="text"
                    name="title"
                    placeholder="e.g. Unit Test Announcement"
                    required
                >

            </div>


            <div class="form-group">

                <label>
                    Notice Message *
                </label>

                <textarea
                    name="message"
                    placeholder="Enter notice details..."
                    required
                ></textarea>

            </div>


            <div class="form-group">

                <label>
                    Notice Date *
                </label>

                <input
                    type="date"
                    name="notice_date"
                    value="<?php echo date('Y-m-d'); ?>"
                    required
                >

            </div>


            <div class="form-group">

                <label>
                    Status
                </label>

                <select name="status">

                    <option value="Active">
                        Active
                    </option>

                    <option value="Inactive">
                        Inactive
                    </option>

                </select>

            </div>


            <button
                type="submit"
                name="add_notice"
                class="btn btn-success"
            >

                + Publish Notice

            </button>

        </form>

    </div>


    <!-- NOTICE LIST -->

    <div class="notice-card">

        <h2>
            All Notices
        </h2>


        <?php if (!empty($notices)): ?>

            <?php foreach ($notices as $notice): ?>

                <div class="notice-item">


                    <div class="notice-title">

                        <?php
                        echo htmlspecialchars(
                            $notice["title"]
                        );
                        ?>

                    </div>


                    <div class="notice-message">

                        <?php
                        echo htmlspecialchars(
                            $notice["message"]
                        );
                        ?>

                    </div>


                    <div class="notice-info">

                        <span>

                            📅

                            <?php
                            echo date(
                                "d M Y",
                                strtotime(
                                    $notice["notice_date"]
                                )
                            );
                            ?>

                        </span>


                        <span>

                            <?php if ($notice["status"] === "Active"): ?>

                                <span class="status-active">
                                    Active
                                </span>

                            <?php else: ?>

                                <span class="status-inactive">
                                    Inactive
                                </span>

                            <?php endif; ?>

                        </span>


                        <form
                            method="POST"
                            onsubmit="return confirm('Delete this notice?');"
                        >

                            <input
                                type="hidden"
                                name="notice_id"
                                value="<?php
                                echo $notice["id"];
                                ?>"
                            >

                            <button
                                type="submit"
                                name="delete_notice"
                                class="btn btn-danger"
                            >

                                Delete

                            </button>

                        </form>

                    </div>

                </div>

            <?php endforeach; ?>

        <?php else: ?>

            <div class="empty">

                No notices available.

            </div>

        <?php endif; ?>

    </div>

</div>

</body>

</html>