<?php
session_start();
include "dbconnect.php";

// Ensure logged in
if (!isset($_SESSION['pid']) || $_SESSION['role'] != "admin") {
    header("Location: login.php");
    exit();
}

$admin_id = $_SESSION['pid'];

// Get admin details (staff but not doctor)
$stmt = $conn->prepare("SELECT p.Name, p.PID, p.DateofBirth, p.email, p.Phone,
                        h.Name AS HospitalName, h.Plot, h.Street, h.Area, h.email AS HospitalEmail, h.Phone AS HospitalPhone
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm mb-4">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="#">Admin Dashboard</a>
        <div class="d-flex">
            <a href="logout.php" class="btn btn-danger">Logout</a>
        </div>
    </div>
</nav>

<div class="container">

    <!-- Admin & Hospital Info -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    Admin Information
                </div>
                <div class="card-body">
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($admin['Name']); ?></p>
                    <p><strong>Admin ID:</strong> <?php echo $admin['PID']; ?></p>
                    <p><strong>Date of Birth:</strong> <?php echo $admin['DateofBirth']; ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($admin['email']); ?></p>
                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($admin['Phone']); ?></p>
                </div>
            </div>
        </div>

        <div class="col-md-6 mt-3 mt-md-0">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    Hospital Information
                </div>
                <div class="card-body">
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($admin['HospitalName']); ?></p>
                    <p><strong>Address:</strong> <?php echo htmlspecialchars($admin['Plot'] . ", " . $admin['Street'] . ", " . $admin['Area']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($admin['HospitalEmail']); ?></p>
                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($admin['HospitalPhone']); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Admin Actions -->
    <h3 class="mb-3">Management Actions</h3>
    <div class="row g-3">
        <div class="col-md-3 col-sm-6">
            <a href="staff.php" class="text-decoration-none">
                <div class="card shadow-sm text-center h-100">
                    <div class="card-body">
                        <h5 class="card-title">Staff Management</h5>
                        <p class="card-text">Add, edit, or remove hospital staff members.</p>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-3 col-sm-6">
            <a href="inventory.php" class="text-decoration-none">
                <div class="card shadow-sm text-center h-100">
                    <div class="card-body">
                        <h5 class="card-title">Inventory Management</h5>
                        <p class="card-text">Manage medicines, equipment, and supplies.</p>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-3 col-sm-6">
            <a href="analytics.php" class="text-decoration-none">
                <div class="card shadow-sm text-center h-100">
                    <div class="card-body">
                        <h5 class="card-title">Analytics</h5>
                        <p class="card-text">View hospital performance and earnings.</p>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-3 col-sm-6">
            <a href="manage_hospital.php" class="text-decoration-none">
                <div class="card shadow-sm text-center h-100">
                    <div class="card-body">
                        <h5 class="card-title">Manage Hospital</h5>
                        <p class="card-text">Update hospital information and details.</p>
                    </div>
                </div>
            </a>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
