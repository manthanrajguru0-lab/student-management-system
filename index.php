<?php
session_start();
require_once "config/database.php";
$error = "";
// Generate CSRF token if not present
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // CSRF check
    if (
        empty($_POST["csrf_token"]) ||
        !hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"])
    ) {
        $error = "Invalid request. Please try again.";
    } else {
        $username = trim($_POST["username"]);
        $password = $_POST["password"];

        if (empty($username) || empty($password)) {
            $error = "Please enter username and password.";
        } else {
            $stmt = $conn->prepare(
                "SELECT id, username, password FROM admin WHERE username = ?"
            );

            if ($stmt === false) {
                // Log the real error internally; don't leak it to the user
                error_log("DB prepare failed: " . $conn->error);
                $error = "Something went wrong. Please try again later.";
            } else {
                $stmt->bind_param("s", $username);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows == 1) {
                    $admin = $result->fetch_assoc();

                    if (password_verify($password, $admin["password"])) {
                        // Prevent session fixation: rotate the session ID
                        // on privilege change
                        session_regenerate_id(true);

                        $_SESSION["admin_id"] = $admin["id"];
                        $_SESSION["admin_username"] = $admin["username"];

                        // Rotate CSRF token too, since it's now a new session
                        $_SESSION["csrf_token"] = bin2hex(random_bytes(32));

                        $stmt->close();
                        header("Location: dashboard.php");
                        exit();
                    } else {
                        $error = "Invalid username or password.";
                    }
                } else {
                    $error = "Invalid username or password.";
                }

                $stmt->close();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super20 Academy - Admin Login</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="page">
    <div class="login-card">
        <div class="logo">
            S20
        </div>
        <h1>Super20 Academy</h1>
        <p class="tagline">
            Coaching Class Management System
        </p>
        <div class="divider"></div>
        <h2>Admin Login</h2>
        <p class="welcome">
            Welcome back! Please login to continue.
        </p>
        <?php if (!empty($error)): ?>
            <div class="error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        <form method="POST">
            <input
                type="hidden"
                name="csrf_token"
                value="<?php echo htmlspecialchars($_SESSION["csrf_token"]); ?>"
            >
            <div class="input-box">
                <label>Username</label>
                <input
                    type="text"
                    name="username"
                    placeholder="Enter your username"
                    autocomplete="username"
                    required
                >
            </div>
            <div class="input-box">
                <label>Password</label>
                <div class="password-container">
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Enter your password"
                        autocomplete="current-password"
                        required
                    >
                    <button
                        type="button"
                        class="show-password"
                        onclick="togglePassword()"
                    >
                        Show
                    </button>
                </div>
            </div>
            <button type="submit" class="login-btn">
                Login
            </button>
        </form>
        <p class="footer">
            © 2026 Super20 Academy
        </p>
    </div>
</div>
<script>
function togglePassword() {
    const password = document.getElementById("password");
    const button = document.querySelector(".show-password");
    if (password.type === "password") {
        password.type = "text";
        button.innerText = "Hide";
    } else {
        password.type = "password";
        button.innerText = "Show";
    }
}
</script>
</body>
</html>