<?php
session_start();
include "dbconnect.php";

// Ensure logged in as admin
if (!isset($_SESSION['pid']) || $_SESSION['role'] != "admin") {
    header("Location: login.php");
    exit();
}

$admin_id = $_SESSION['pid'];

// Fetch current admin info
$stmt = $conn->prepare("
    SELECT p.name, p.email, p.Phone, p.password, s.shift 
    FROM person p 
    JOIN staff s ON p.PID = s.PID 
    WHERE p.PID = ?");
$stmt->bind_param("s", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
if (!$row = $result->fetch_assoc()) {
    die("Admin not found.");
}
$current_name = $row['name'];
$current_email = $row['email'];
$current_phone = $row['Phone'];
$current_password = $row['password'];
$current_shift = $row['shift'];
$stmt->close();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $password = $_POST['password'];
    $shift = $_POST['shift'];

    // Update person
    $stmt = $conn->prepare("UPDATE person SET email=?, Phone=?, password=? WHERE PID=?");
    $stmt->bind_param("ssss", $email, $phone, $password, $admin_id);
    $stmt->execute();
    $stmt->close();

    // Update staff shift
    $stmt = $conn->prepare("UPDATE staff SET shift=? WHERE PID=?");
    $stmt->bind_param("ss", $shift, $admin_id);
    $stmt->execute();
    $stmt->close();

    $message = "Admin info updated successfully!";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Modify Admin Info</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 20px; background-color: #f8f9fa; }
        .card { padding: 20px; border-radius: 10px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .header a { text-decoration: none; }
        .message { color: green; font-weight: bold; margin-bottom: 10px; text-align: center; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h2>Modify Admin Info</h2>
        <a href="staff.php" class="btn btn-secondary">Back</a>
    </div>

    <?php if (!empty($message)) echo "<div class='message'>$message</div>"; ?>

    <div class="row g-4">
        <!-- Left: Current Info -->
        <div class="col-md-5">
            <div class="card bg-light">
                <h5>Current Info</h5>
                <p><strong>Name:</strong> <?php echo htmlspecialchars($current_name); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($current_email); ?></p>
                <p><strong>Phone:</strong> <?php echo htmlspecialchars($current_phone); ?></p>
                <p><strong>Shift:</strong> <?php echo htmlspecialchars($current_shift); ?></p>
            </div>
        </div>

        <!-- Right: Form to Modify -->
        <div class="col-md-7">
            <div class="card">
                <form method="post">
                    <div class="mb-3">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($current_email); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label>Phone</label>
                        <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($current_phone); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label>Password</label>
                        <input type="password" name="password" class="form-control" value="<?php echo htmlspecialchars($current_password); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label>Shift</label>
                        <select name="shift" class="form-select" required>
                            <option value="Morning" <?php if($current_shift=="Morning") echo "selected"; ?>>Morning</option>
                            <option value="Evening" <?php if($current_shift=="Evening") echo "selected"; ?>>Evening</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary">Update Info</button>
                </form>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
