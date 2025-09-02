<?php
session_start();
include "dbconnect.php";

// Ensure only admin can access
if (!isset($_SESSION['pid']) || $_SESSION['role'] != "admin") {
    header("Location: login.php");
    exit();
}

// Fetch hospitals with doctor count
$query = "
    SELECT h.HospitalID, h.Name, h.Plot, h.Street, h.Area, h.email, h.Phone,
           COUNT(DISTINCT d.PID) AS DoctorCount
    FROM hospital h
    LEFT JOIN staff s ON h.HospitalID = s.hospital_id
    LEFT JOIN doctor d ON s.PID = d.PID
    GROUP BY h.HospitalID, h.Name, h.Plot, h.Street, h.Area, h.email, h.Phone
";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Hospitals</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light p-4">

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Manage Hospitals</h2>
        <a href="admin.php" class="btn btn-secondary"> Back</a>
    </div>

    <table class="table table-bordered table-striped">
        <thead class="table-dark">
            <tr>
                <th>Hospital ID</th>
                <th>Name</th>
                <th>Address</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Doctors</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['HospitalID']); ?></td>
                <td><?php echo htmlspecialchars($row['Name']); ?></td>
                <td><?php echo htmlspecialchars($row['Plot'] . ", " . $row['Street'] . ", " . $row['Area']); ?></td>
                <td><?php echo htmlspecialchars($row['email']); ?></td>
                <td><?php echo htmlspecialchars($row['Phone']); ?></td>
                <td><?php echo htmlspecialchars($row['DoctorCount']); ?></td>
                <td>
                    <a href="modify_hospital.php?id=<?php echo urlencode($row['HospitalID']); ?>" class="btn btn-primary btn-sm">Modify</a>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>

    <a href="add_hospital.php" class="btn btn-success">Add Hospital</a>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
