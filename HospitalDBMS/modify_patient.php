<?php
session_start();
include "dbconnect.php";

// Ensure patient is logged in
if (!isset($_SESSION['pid']) || $_SESSION['role'] != "patient") {
    header("Location: login.php");
    exit();
}

$patient_id = $_SESSION['pid'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);

    $stmt = $conn->prepare("UPDATE person SET email = ?, Phone = ? WHERE PID = ?");
    $stmt->bind_param("sss", $email, $phone, $patient_id);
    $stmt->execute();
    $stmt->close();

    header("Location: patient.php");
    exit();
}

// Fetch current info
$stmt = $conn->prepare("SELECT email, Phone FROM person WHERE PID = ?");
$stmt->bind_param("s", $patient_id);
$stmt->execute();
$result = $stmt->get_result();
$patient = $result->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Modify Personal Info</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f5f6fa; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .back-btn {
            background: #6c757d; color: white; padding: 8px 12px;
            border-radius: 6px; text-decoration: none;
        }
        .back-btn:hover { background: #5a6268; }
        .card {
            background: white; padding: 20px; border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1); max-width: 400px;
            margin: auto;
        }
        h3 { margin-top: 0; }
        label { display: block; margin-top: 10px; }
        input[type="text"], input[type="email"] {
            width: 100%; padding: 8px; margin-top: 5px;
            border: 1px solid #ccc; border-radius: 4px;
        }
        button {
            margin-top: 15px; padding: 10px 15px;
            background: #007BFF; color: white; border: none;
            border-radius: 6px; cursor: pointer;
        }
        button:hover { background: #0056b3; }
    </style>
</head>
<body>

<div class="header">
    <a href="patient.php" class="back-btn">← Back</a>
</div>

<div class="card">
    <h3>Update Contact Information</h3>
    <form method="POST">
        <label>Email:</label>
        <input type="email" name="email" value="<?php echo htmlspecialchars($patient['email']); ?>" required>

        <label>Phone:</label>
        <input type="text" name="phone" value="<?php echo htmlspecialchars($patient['Phone']); ?>" required>

        <button type="submit">Save</button>
    </form>
</div>

</body>
</html>
