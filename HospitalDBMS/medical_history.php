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
    <style>
        body { font-family: Arial, sans-serif; }
        h2 { margin-top: 0; }
        table { border-collapse: collapse; width: 100%; margin-top: 15px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .buttons { margin: 15px 0; }
        .buttons a { margin-right: 10px; padding: 8px 12px; text-decoration: none; background: #007BFF; color: white; border-radius: 5px; }
        .buttons a:hover { background: #0056b3; }
    </style>
</head>
<body>

<h2>Medical History of <?php echo htmlspecialchars($patient['Name']); ?> (<?php echo $patient['PID']; ?>)</h2>

<div class="buttons">
    <a href="attend_appointment.php">Back</a>
</div>

<?php if(empty($history)) { ?>
    <p>No completed appointments found for this patient.</p>
<?php } else { ?>
    <?php foreach($history as $app) { ?>
        <h3>Appointment: <?php echo $app['App_ID']; ?> | Date: <?php echo $app['Date']; ?> | Doctor: <?php echo $app['DoctorName']." (".$app['DoctorID'].")"; ?></h3>

        <table>
            <tr>
                <th>Diagnosis</th>
            </tr>
            <?php foreach($app['Diagnosis'] as $d) { ?>
                <tr>
                    <td><?php echo htmlspecialchars($d['Diagnosis']); ?></td>
                </tr>
            <?php } ?>
        </table>

        <table>
            <tr>
                <th>Prescription</th>
            </tr>
            <?php foreach($app['Prescription'] as $p) { ?>
                <tr>
                    <td><?php echo htmlspecialchars($p['Prescription']); ?></td>
                </tr>
            <?php } ?>
        </table>
        <hr>
    <?php } ?>
<?php } ?>

</body>
</html>
