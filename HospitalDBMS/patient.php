<?php
session_start();
include "dbconnect.php";

// Ensure patient is logged in
if (!isset($_SESSION['pid']) || $_SESSION['role'] != "patient") {
    header("Location: login.php");
    exit();
}

$patient_id = $_SESSION['pid'];

// Fetch patient + person info
$stmt = $conn->prepare("
    SELECT p.PID, p.Name, p.DateofBirth, p.email, p.Phone, pa.BloodGroup, pa.HasInsurance
    FROM person p
    JOIN patient pa ON p.PID = pa.PID
    WHERE p.PID = ?
");
$stmt->bind_param("s", $patient_id);
$stmt->execute();
$result = $stmt->get_result();
if (!$patient = $result->fetch_assoc()) {
    die("Patient not found.");
}
$stmt->close();

// Fetch upcoming appointments
$stmt = $conn->prepare("
    SELECT a.App_ID, a.Date, a.Time, doc.PID AS DoctorID, per.Name AS DoctorName, 
           doc.Specialization, per.Phone AS DoctorPhone, h.Name AS HospitalName,
           CONCAT(h.Plot, ', ', h.Street, ', ', h.Area) AS HospitalAddress
    FROM appointment a
    JOIN doctor doc ON a.Doctor_ID = doc.PID
    JOIN staff s ON doc.PID = s.PID
    JOIN hospital h ON s.hospital_id = h.HospitalID
    JOIN person per ON doc.PID = per.PID
    WHERE a.Patient_ID = ? AND a.Status = 'Upcoming' AND a.Date >= CURDATE()
    ORDER BY a.Date, a.Time
");
$stmt->bind_param("s", $patient_id);
$stmt->execute();
$appointments = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Patient Dashboard</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f5f6fa; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .header h2 { margin: 0; }
        .logout-btn {
            background: #dc3545; color: white; padding: 8px 12px;
            border-radius: 6px; text-decoration: none;
        }
        .logout-btn:hover { background: #c82333; }
        .card {
            background: white; padding: 15px; border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1); margin-bottom: 20px;
        }
        .card h3 { margin-top: 0; }
        .buttons { display: flex; gap: 10px; margin-top: 10px; }
        .buttons a {
            background: #007BFF; color: white; padding: 10px 14px;
            border-radius: 6px; text-decoration: none;
        }
        .buttons a:hover { background: #0056b3; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td {
            border: 1px solid #ddd; padding: 8px; text-align: left;
        }
        th { background: #007BFF; color: white; }
    </style>
</head>
<body>

<div class="header">
    <h2>Welcome, <?php echo htmlspecialchars($patient['Name']); ?></h2>
    <a href="logout.php" class="logout-btn">Logout</a>
</div>

<div class="card">
    <h3>Patient Information</h3>
    <p><b>Patient ID:</b> <?php echo htmlspecialchars($patient['PID']); ?></p>
    <p><b>Date of Birth:</b> <?php echo htmlspecialchars($patient['DateofBirth']); ?></p>
    <p><b>Email:</b> <?php echo htmlspecialchars($patient['email']); ?></p>
    <p><b>Phone:</b> <?php echo htmlspecialchars($patient['Phone']); ?></p>
    <p><b>Blood Group:</b> <?php echo htmlspecialchars($patient['BloodGroup']); ?></p>
    <p><b>Insurance:</b> <?php echo $patient['HasInsurance'] ? "Yes" : "No"; ?></p>

    <div class="buttons">
        <a href="appointment_manage.php">Manage Appointments</a>
        <a href="due_bill.php">View Due Bills</a>
        <a href="modify_patient.php">Update Personal Info</a>
    </div>
</div>

<div class="card">
    <h3>Upcoming Appointments</h3>
    <?php if ($appointments->num_rows > 0): ?>
        <table>
            <tr>
                <th>Date</th>
                <th>Time</th>
                <th>Doctor</th>
                <th>Specialization</th>
                <th>Phone</th>
                <th>Hospital</th>
                <th>Address</th>
            </tr>
            <?php while ($app = $appointments->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($app['Date']); ?></td>
                <td><?php echo htmlspecialchars(substr($app['Time'],0,5)); ?></td>
                <td><?php echo htmlspecialchars($app['DoctorName']); ?></td>
                <td><?php echo htmlspecialchars($app['Specialization']); ?></td>
                <td><?php echo htmlspecialchars($app['DoctorPhone']); ?></td>
                <td><?php echo htmlspecialchars($app['HospitalName']); ?></td>
                <td><?php echo htmlspecialchars($app['HospitalAddress']); ?></td>
            </tr>
            <?php endwhile; ?>
        </table>
    <?php else: ?>
        <p>No upcoming appointments found.</p>
    <?php endif; ?>
</div>

</body>
</html>
