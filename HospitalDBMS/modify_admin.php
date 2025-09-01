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
$stmt = $conn->prepare("SELECT p.email, p.Phone, p.password, s.shift FROM person p JOIN staff s ON p.PID = s.PID WHERE p.PID = ?");
$stmt->bind_param("s", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
if (!$row = $result->fetch_assoc()) {
    die("Admin not found.");
}
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

    $message = " Admin info updated successfully!";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Modify Admin Info</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; margin: 0; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .header a {
            background: #6c757d;
            color: white;
            padding: 8px 12px;
            border-radius: 6px;
            text-decoration: none;
        }
        .header a:hover { background: #5a6268; }
        form { max-width: 500px; margin: auto; display: flex; flex-direction: column; gap: 12px; }
        label { font-weight: bold; }
        input, select {
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 6px;
            width: 100%;
        }
        button {
            background: #007BFF;
            color: white;
            border: none;
            padding: 10px;
            border-radius: 6px;
            cursor: pointer;
        }
        button:hover { background: #0056b3; }
        .message { color: green; font-weight: bold; margin-bottom: 10px; text-align: center; }
    </style>
</head>
<body>

<div class="header">
    <h2>Modify Admin Info</h2>
    <a href="staff.php">⬅ Back</a>
</div>

<?php if (!empty($message)) echo "<div class='message'>$message</div>"; ?>

<form method="post">
    <label>Email</label>
    <input type="email" name="email" value="<?php echo htmlspecialchars($current_email); ?>" required>

    <label>Phone</label>
    <input type="text" name="phone" value="<?php echo htmlspecialchars($current_phone); ?>" required>

    <label>Password</label>
    <input type="password" name="password" value="<?php echo htmlspecialchars($current_password); ?>" required>

    <label>Shift</label>
    <select name="shift" required>
        <option value="Morning" <?php if($current_shift=="Morning") echo "selected"; ?>>Morning</option>
        <option value="Evening" <?php if($current_shift=="Evening") echo "selected"; ?>>Evening</option>
    </select>

    <button type="submit"> Update Info</button>
</form>

</body>
</html>
