<?php
session_start();
include "dbconnect.php";

// ✅ Ensure logged in
if (!isset($_SESSION['pid']) || $_SESSION['role'] != "admin") {
    header("Location: login.php");
    exit();
}

$admin_id = $_SESSION['pid'];

// ✅ Get admin details (must be staff but not doctor)
$stmt = $conn->prepare("SELECT p.Name, p.PID, h.Name AS HospitalName, h.Plot, h.Street, h.Area
                        FROM staff s
                        JOIN person p ON s.PID = p.PID
                        JOIN hospital h ON s.hospital_id = h.HospitalID
                        WHERE p.PID=? AND NOT EXISTS (SELECT 1 FROM doctor d WHERE d.PID = p.PID)");
$stmt->bind_param("s", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();
$stmt->close();

if (!$admin) {
    die("Unauthorized: This user is not an admin.");
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; margin: 0; }
        .header {
            display: flex;
            justify-content: flex-end;
            padding: 10px 20px;
            background: #f8f9fa;
            margin: -20px -20px 20px -20px;
        }
        .header a {
            background: #dc3545;
            color: white;
            text-decoration: none;
            padding: 8px 12px;
            border-radius: 6px;
        }
        .header a:hover { background: #a71d2a; }

        h2 { margin-bottom: 10px; }
        .info { margin-bottom: 20px; }

        .buttons {
            display: flex;
            flex-direction: column;
            max-width: 250px;
        }
        .buttons a {
            margin: 6px 0;
            padding: 12px;
            background: #007BFF;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            text-align: center;
        }
        .buttons a:hover { background: #0056b3; }
    </style>
</head>
<body>

<div class="header">
    <a href="logout.php">Logout</a>
</div>

<h2>Admin Dashboard</h2>

<div class="info">
    <h3>Hospital Information</h3>
    <p><strong><?php echo htmlspecialchars($admin['HospitalName']); ?></strong></p>
    <p><?php echo htmlspecialchars($admin['Plot'] . ", " . $admin['Street'] . ", " . $admin['Area']); ?></p>

    <h3>Admin Information</h3>
    <p><?php echo htmlspecialchars($admin['Name']); ?> (<?php echo $admin['PID']; ?>)</p>
</div>

<div class="buttons">
    <a href="staff.php">Staff Management</a>
    <a href="inventory.php">Inventory Management</a>
    <a href="analytics.php">Analytics</a>
    <a href="manage_hospital.php">Manage Hospital</a>
</div>

</body>
</html>
