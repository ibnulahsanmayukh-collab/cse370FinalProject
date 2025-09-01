<?php
session_start();
include "dbconnect.php";

// ✅ Ensure logged in as admin
if (!isset($_SESSION['pid']) || $_SESSION['role'] != "admin") {
    header("Location: login.php");
    exit();
}

$admin_id = $_SESSION['pid'];

// ✅ Get admin's hospital
$stmt = $conn->prepare("SELECT h.HospitalID, h.Name, h.Plot, h.Street, h.Area
                        FROM staff s
                        JOIN hospital h ON s.hospital_id = h.HospitalID
                        WHERE s.PID = ?");
$stmt->bind_param("s", $admin_id);
$stmt->execute();
$hospital_result = $stmt->get_result();
$hospital = $hospital_result->fetch_assoc();
$stmt->close();

if (!$hospital) {
    die("Unauthorized: Admin hospital not found.");
}

$hospital_id = $hospital['HospitalID'];

// ✅ Get staff of this hospital
$query = "SELECT s.PID, p.Name, s.shift, 
                 CASE WHEN d.PID IS NOT NULL THEN 'Doctor' ELSE 'Admin' END AS Role
          FROM staff s
          JOIN person p ON s.PID = p.PID
          LEFT JOIN doctor d ON s.PID = d.PID
          WHERE s.hospital_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $hospital_id);
$stmt->execute();
$staff_result = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Staff Management</title>
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
        table {
            border-collapse: collapse;
            width: 100%;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        th { background: #f2f2f2; }
        .btn {
            padding: 6px 10px;
            text-decoration: none;
            border-radius: 5px;
            color: white;
        }
        .modify-admin { background: #17a2b8; }
        .modify-admin:hover { background: #117a8b; }
        .modify-doctor { background: #007bff; }
        .modify-doctor:hover { background: #0056b3; }
        .actions { display: flex; gap: 10px; }
        .create-buttons {
            margin-top: 20px;
            display: flex;
            gap: 15px;
        }
        .create-buttons a {
            background: #28a745;
            padding: 10px 15px;
            border-radius: 6px;
            text-decoration: none;
            color: white;
        }
        .create-buttons a:hover { background: #218838; }
    </style>
</head>
<body>

<div class="header">
    <h2>Staff Management</h2>
    <a href="admin.php">⬅ Back</a>
</div>

<h3>Hospital Information</h3>
<p><strong><?php echo htmlspecialchars($hospital['Name']); ?></strong></p>
<p><?php echo htmlspecialchars($hospital['Plot'] . ", " . $hospital['Street'] . ", " . $hospital['Area']); ?></p>

<h3>Staff Members</h3>
<table>
    <tr>
        <th>PID</th>
        <th>Name</th>
        <th>Shift</th>
        <th>Role</th>
        <th>Action</th>
    </tr>
    <?php while ($row = $staff_result->fetch_assoc()) { ?>
        <tr>
            <td><?php echo htmlspecialchars($row['PID']); ?></td>
            <td><?php echo htmlspecialchars($row['Name']); ?></td>
            <td><?php echo htmlspecialchars($row['shift']); ?></td>
            <td><?php echo htmlspecialchars($row['Role']); ?></td>
            <td>
                <?php if ($row['Role'] === "Doctor") { ?>
                    <a href="modify_doctor.php?pid=<?php echo urlencode($row['PID']); ?>" class="btn modify-doctor">Modify Doctor</a>
                <?php } else { ?>
                    <a href="modify_admin.php?pid=<?php echo urlencode($row['PID']); ?>" class="btn modify-admin">Modify Admin</a>
                <?php } ?>
            </td>
        </tr>
    <?php } ?>
</table>

<div class="create-buttons">
    <a href="create_admin.php">➕ Create Admin</a>
    <a href="create_doctor.php">➕ Create Doctor</a>
</div>

</body>
</html>
