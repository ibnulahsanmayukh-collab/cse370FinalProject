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
if (!$patient = $result->fetch_assoc()) die("Patient not found.");
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Welcome, <?php echo htmlspecialchars($patient['Name']); ?></h2>
        <a href="logout.php" class="btn btn-danger">Logout</a>
    </div>

    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <h4 class="card-title">Patient Information</h4>
            <p><b>Patient ID:</b> <?php echo htmlspecialchars($patient['PID']); ?></p>
            <p><b>Date of Birth:</b> <?php echo htmlspecialchars($patient['DateofBirth']); ?></p>
            <p><b>Email:</b> <?php echo htmlspecialchars($patient['email']); ?></p>
            <p><b>Phone:</b> <?php echo htmlspecialchars($patient['Phone']); ?></p>
            <p><b>Blood Group:</b> <?php echo htmlspecialchars($patient['BloodGroup']); ?></p>
            <p><b>Insurance:</b> <?php echo $patient['HasInsurance'] ? "Yes" : "No"; ?></p>

            <div class="mt-3 d-flex gap-2 flex-wrap">
                <a href="appointment_manage.php" class="btn btn-primary">Manage Appointments</a>
                <a href="due_bill.php" class="btn btn-primary">View Due Bills</a>
                <a href="modify_patient.php" class="btn btn-primary">Update Personal Info</a>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <h4 class="card-title">Upcoming Appointments</h4>
            <?php if ($appointments->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-bordered mt-3">
                        <thead class="table-primary">
                            <tr>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Doctor</th>
                                <th>Specialization</th>
                                <th>Phone</th>
                                <th>Hospital</th>
                                <th>Address</th>
                            </tr>
                        </thead>
                        <tbody>
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
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="mt-3">No upcoming appointments found.</p>
            <?php endif; ?>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
