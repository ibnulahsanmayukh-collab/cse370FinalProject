<?php
session_start();
include "dbconnect.php";

// ✅ Ensure doctor login
if (!isset($_SESSION['pid']) || $_SESSION['role'] != "doctor") {
    header("Location: login.php");
    exit();
}

// Get patient id from query
if (!isset($_GET['patient_id'])) {
    die("Patient ID is required.");
}
$patient_id = $_GET['patient_id'];

// Fetch patient info
$stmt = $conn->prepare("SELECT p.Name, p.PID FROM patient pa JOIN person p ON pa.PID=p.PID WHERE pa.PID=?");
$stmt->bind_param("s", $patient_id);
$stmt->execute();
$patient = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$patient) {
    die("Patient not found.");
}

// Fetch all completed appointments with diagnosis & prescription
$conn->begin_transaction();
try {
    $sql = "SELECT a.App_ID, a.Date, d.PID as DoctorID, per.Name as DoctorName
            FROM appointment a
            JOIN doctor d ON a.Doctor_ID=d.PID
            JOIN staff s ON d.PID=s.PID
            JOIN person per ON d.PID=per.PID
            WHERE a.Patient_ID=? AND a.Status='Complete'
            ORDER BY a.Date DESC, a.Time DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $patient_id);
    $stmt->execute();
    $appointments = $stmt->get_result();
    $stmt->close();

    $history = [];
    while ($app = $appointments->fetch_assoc()) {
        $app_id = $app['App_ID'];

        // Fetch all diagnosis for this appointment
        $stmt = $conn->prepare("SELECT Diagnosis FROM app_diag WHERE App_ID=?");
        $stmt->bind_param("s", $app_id);
        $stmt->execute();
        $diagnosis = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // Fetch all prescriptions for this appointment
        $stmt = $conn->prepare("SELECT Prescription FROM app_presc WHERE App_ID=?");
        $stmt->bind_param("s", $app_id);
        $stmt->execute();
        $prescriptions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $history[] = [
            'App_ID' => $app_id,
            'Date' => $app['Date'],
            'DoctorID' => $app['DoctorID'],
            'DoctorName' => $app['DoctorName'],
            'Diagnosis' => $diagnosis,
            'Prescription' => $prescriptions
        ];
    }

    $conn->commit();
} catch (Exception $e) {
    $conn->rollback();
    die("Failed to fetch medical history: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Medical History</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Medical History of <?php echo htmlspecialchars($patient['Name']); ?> (<?php echo $patient['PID']; ?>)</h2>
        <a href="attend_appointment.php" class="btn btn-secondary">Back</a>
    </div>

    <?php if(empty($history)) { ?>
        <div class="alert alert-info">No completed appointments found for this patient.</div>
    <?php } else { ?>
        <?php foreach($history as $app) { ?>
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-primary text-white">
                    Appointment: <?php echo $app['App_ID']; ?> | Date: <?php echo $app['Date']; ?> | Doctor: <?php echo $app['DoctorName']." (".$app['DoctorID'].")"; ?>
                </div>
                <div class="card-body">

                    <h5>Diagnosis</h5>
                    <?php if(empty($app['Diagnosis'])): ?>
                        <p class="text-muted">No diagnosis recorded.</p>
                    <?php else: ?>
                        <ul>
                            <?php foreach($app['Diagnosis'] as $d): ?>
                                <li><?php echo htmlspecialchars($d['Diagnosis']); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>

                    <h5>Prescriptions</h5>
                    <?php if(empty($app['Prescription'])): ?>
                        <p class="text-muted">No prescriptions recorded.</p>
                    <?php else: ?>
                        <ul>
                            <?php foreach($app['Prescription'] as $p): ?>
                                <li><?php echo htmlspecialchars($p['Prescription']); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>

                </div>
            </div>
        <?php } ?>
    <?php } ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
