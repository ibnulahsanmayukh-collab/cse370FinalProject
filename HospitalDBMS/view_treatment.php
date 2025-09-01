<?php
session_start();
include "dbconnect.php";

if (!isset($_SESSION['pid']) || $_SESSION['role'] != "patient") {
    header("Location: login.php");
    exit();
}

$patient_id = $_SESSION['pid'];

if (!isset($_GET['app_id'])) {
    header("Location: patient.php");
    exit();
}

$app_id = $_GET['app_id'];

// ✅ Get appointment + patient + doctor info
$stmt = $conn->prepare("SELECT a.App_ID, a.Date, p.Name AS PatientName, pat.BloodGroup, dtr.PID AS DoctorID, d.Name AS DoctorName
                        FROM appointment a
                        JOIN patient pat ON a.Patient_ID=pat.PID
                        JOIN person p ON pat.PID=p.PID
                        JOIN doctor dtr ON a.Doctor_ID=dtr.PID
                        JOIN person d ON dtr.PID=d.PID
                        WHERE a.App_ID=? AND a.Patient_ID=?");
$stmt->bind_param("ss", $app_id, $patient_id);
$stmt->execute();
$appointment = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$appointment) {
    header("Location: patient.php");
    exit();
}

// ✅ Get diagnoses
$stmt = $conn->prepare("SELECT Diagnosis FROM app_diag WHERE App_ID=?");
$stmt->bind_param("s", $app_id);
$stmt->execute();
$diag_result = $stmt->get_result();
$diagnoses = [];
while($row = $diag_result->fetch_assoc()) {
    $diagnoses[] = $row['Diagnosis'];
}
$stmt->close();

// ✅ Get prescriptions
$stmt = $conn->prepare("SELECT Prescription FROM app_presc WHERE App_ID=?");
$stmt->bind_param("s", $app_id);
$stmt->execute();
$presc_result = $stmt->get_result();
$prescriptions = [];
while($row = $presc_result->fetch_assoc()) {
    $prescriptions[] = $row['Prescription'];
}
$stmt->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>View Treatment</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
</head>
<body class="p-4">
<div class="container">
    <a href="patient.php" class="btn btn-secondary mb-3">Back</a>

    <h2>Appointment Details</h2>
    <table class="table table-bordered w-75">
        <tr><th>Appointment ID</th><td><?php echo htmlspecialchars($appointment['App_ID']); ?></td></tr>
        <tr><th>Date</th><td><?php echo htmlspecialchars($appointment['Date']); ?></td></tr>
        <tr><th>Patient Name</th><td><?php echo htmlspecialchars($appointment['PatientName']); ?></td></tr>
        <tr><th>Blood Group</th><td><?php echo htmlspecialchars($appointment['BloodGroup']); ?></td></tr>
        <tr><th>Doctor Name</th><td><?php echo htmlspecialchars($appointment['DoctorName']); ?></td></tr>
    </table>

    <h4>Diagnoses</h4>
    <?php if(count($diagnoses) > 0): ?>
        <ul>
            <?php foreach($diagnoses as $diag): ?>
                <li><?php echo htmlspecialchars($diag); ?></li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>No diagnoses recorded yet.</p>
    <?php endif; ?>

    <h4>Prescriptions</h4>
    <?php if(count($prescriptions) > 0): ?>
        <ul>
            <?php foreach($prescriptions as $presc): ?>
                <li><?php echo htmlspecialchars($presc); ?></li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>No prescriptions recorded yet.</p>
    <?php endif; ?>
</div>

<script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>
