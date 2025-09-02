<?php
session_start();
include "dbconnect.php";

// Ensure logged in as admin
if (!isset($_SESSION['pid']) || $_SESSION['role'] != "admin") {
    header("Location: login.php");
    exit();
}

$admin_id = $_SESSION['pid'];

// Get admin's hospital
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

// Get staff of this hospital
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Staff Management</h2>
        <a href="admin.php" class="btn btn-secondary">Back</a>
    </div>

    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <h5 class="card-title">Hospital Information</h5>
            <p><strong><?php echo htmlspecialchars($hospital['Name']); ?></strong></p>
            <p><?php echo htmlspecialchars($hospital['Plot'] . ", " . $hospital['Street'] . ", " . $hospital['Area']); ?></p>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h5 class="card-title mb-3">Staff Members</h5>
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>PID</th>
                            <th>Name</th>
                            <th>Shift</th>
                            <th>Role</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $staff_result->fetch_assoc()) { ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['PID']); ?></td>
                                <td><?php echo htmlspecialchars($row['Name']); ?></td>
                                <td><?php echo htmlspecialchars($row['shift']); ?></td>
                                <td><?php echo htmlspecialchars($row['Role']); ?></td>
                                <td>
                                    <?php if ($row['Role'] === "Doctor") { ?>
                                        <a href="modify_doctor.php?pid=<?php echo urlencode($row['PID']); ?>" class="btn btn-primary btn-sm">Modify Doctor</a>
                                    <?php } else { ?>
                                        <a href="modify_admin.php?pid=<?php echo urlencode($row['PID']); ?>" class="btn btn-info btn-sm">Modify Admin</a>
                                    <?php } ?>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="d-flex gap-3">
        <a href="create_admin.php" class="btn btn-success">Create Admin</a>
        <a href="create_doctor.php" class="btn btn-success">Create Doctor</a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
