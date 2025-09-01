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
        table { border-collapse: collapse; width: 100%; margin-top: 15px; }
        th, td { border: 1px solid #ccc; padding: 10px; text-align: left; }
        th { background: #f8f9fa; }
        .actions a {
            display: inline-block;
            margin-right: 6px;
            padding: 6px 10px;
            background: #007BFF;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 14px;
        }
        .actions a:hover { background: #0056b3; }
        .add-btn {
            display: inline-block;
            margin-top: 15px;
            padding: 10px 14px;
            background: #28a745;
            color: white;
            text-decoration: none;
            border-radius: 6px;
        }
        .add-btn:hover { background: #218838; }
    </style>
</head>
<body>

<div class="header">
    <a href="admin.php">⬅ Back</a>
    <h2>Manage Hospitals</h2>
</div>

<table>
    <tr>
        <th>Hospital ID</th>
        <th>Name</th>
        <th>Address</th>
        <th>Email</th>
        <th>Phone</th>
        <th>Doctors</th>
        <th>Actions</th>
    </tr>
    <?php while ($row = $result->fetch_assoc()) { ?>
        <tr>
            <td><?php echo htmlspecialchars($row['HospitalID']); ?></td>
            <td><?php echo htmlspecialchars($row['Name']); ?></td>
            <td><?php echo htmlspecialchars($row['Plot'] . ", " . $row['Street'] . ", " . $row['Area']); ?></td>
            <td><?php echo htmlspecialchars($row['email']); ?></td>
            <td><?php echo htmlspecialchars($row['Phone']); ?></td>
            <td><?php echo htmlspecialchars($row['DoctorCount']); ?></td>
            <td class="actions">
                <a href="modify_hospital.php?id=<?php echo urlencode($row['HospitalID']); ?>">Modify</a>
            </td>
        </tr>
    <?php } ?>
</table>

<a href="add_hospital.php" class="add-btn">Add Hospital</a>

</body>
</html>
