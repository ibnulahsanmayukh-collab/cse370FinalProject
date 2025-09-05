<?php
session_start();
include "dbconnect.php";

// Ensure doctor login
if (!isset($_SESSION['pid']) || $_SESSION['role'] != "doctor") {
    header("Location: login.php");
    exit;
}

$pid = $_SESSION['pid'];
$message = "";

// Validate app_id from GET
if (!isset($_GET['app_id'])) {
    header("Location: doctor.php");
    exit;
}
$app_id = $_GET['app_id'];

// Fetch appointment + patient info
$stmt = $conn->prepare("
    SELECT a.App_ID, p.Name, p.DateofBirth, pa.BloodGroup, pa.HasInsurance, pa.PID as PatientID
    FROM appointment a
    JOIN patient pa ON a.Patient_ID = pa.PID
    JOIN person p ON pa.PID = p.PID
    WHERE a.App_ID = ? AND a.Doctor_ID = ?");
$stmt->bind_param("ss", $app_id, $pid);
$stmt->execute();
$appointment = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$appointment) {
    header("Location: doctor.php");
    exit;
}

// Handle submission
if ($_SERVER['REQUEST_METHOD'] === "POST") {

    $diagnoses = [];
    $prescriptions = [];

    // Collect diagnoses
    for ($i = 1; $i <= 5; $i++) {
        $field = "diagnosis$i";
        if (!empty($_POST[$field])) {
            $diagnoses[] = substr(trim($_POST[$field]), 0, 250);
        }
    }

    // Collect prescriptions
    for ($i = 1; $i <= 8; $i++) {
        $field = "prescription$i";
        if (!empty($_POST[$field])) {
            $prescriptions[] = substr(trim($_POST[$field]), 0, 100);
        }
    }

    // Begin transaction
    $conn->begin_transaction();
    try {
        // Insert diagnoses
        $stmt = $conn->prepare("INSERT INTO app_diag (App_ID, Diagnosis) VALUES (?, ?)");
        foreach ($diagnoses as $diag) {
            $stmt->bind_param("ss", $app_id, $diag);
            $stmt->execute();
        }
        $stmt->close();

        // Insert prescriptions
        $stmt = $conn->prepare("INSERT INTO app_presc (App_ID, Prescription) VALUES (?, ?)");
        foreach ($prescriptions as $presc) {
            $stmt->bind_param("ss", $app_id, $presc);
            $stmt->execute();
        }
        $stmt->close();

        // Mark appointment complete
        $stmt = $conn->prepare("UPDATE appointment SET Status='Complete' WHERE App_ID=?");
        $stmt->bind_param("s", $app_id);
        $stmt->execute();
        $stmt->close();

        // Generate bill
        $bill_id = "BILL" . substr(md5(uniqid()), 0, 6);
        $stmt = $conn->prepare("INSERT INTO bill (Bill_ID, Status, App_ID) VALUES (?, 'Unpaid', ?)");
        $stmt->bind_param("ss", $bill_id, $app_id);
        $stmt->execute();
        $stmt->close();

        $conn->commit();
        header("Location: doctor.php");
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        $message = "Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Attend Appointment</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light p-4">
<div class="container">

    <h2 class="mb-4">Attend Appointment</h2>
    <a href="doctor.php" class="btn btn-secondary mb-3">Back</a>

    <?php if ($message): ?>
        <div class="alert alert-danger"><?php echo $message; ?></div>
    <?php endif; ?>

    <h4>Patient Information</h4>
    <table class="table table-bordered w-50 mb-4">
        <tr><th>Name</th><td><?php echo htmlspecialchars($appointment['Name']); ?></td></tr>
        <tr><th>Date of Birth</th><td><?php echo htmlspecialchars($appointment['DateofBirth']); ?></td></tr>
        <tr><th>Blood Group</th><td><?php echo htmlspecialchars($appointment['BloodGroup']); ?></td></tr>
        <tr>
            <th>Medical History</th>
            <td>
                <a href="medical_history.php?patient_id=<?php echo $appointment['PatientID']; ?>" class="btn btn-info btn-sm">View</a>
            </td>
        </tr>
    </table>

    <form method="post">
        <h4>Diagnosis (up to 5)</h4>
        <?php for ($i=1; $i<=5; $i++): ?>
            <div class="mb-2">
                <label class="form-label" for="diagnosis<?php echo $i; ?>">Diagnosis <?php echo $i; ?></label>
                <input type="text" maxlength="250" class="form-control" name="diagnosis<?php echo $i; ?>" id="diagnosis<?php echo $i; ?>">
            </div>
        <?php endfor; ?>

        <h4 class="mt-4">Prescriptions (up to 8)</h4>
        <?php for ($i=1; $i<=8; $i++): ?>
            <div class="mb-2">
                <label class="form-label" for="prescription<?php echo $i; ?>">Prescription <?php echo $i; ?></label>
                <input type="text" maxlength="100" class="form-control" name="prescription<?php echo $i; ?>" id="prescription<?php echo $i; ?>">
            </div>
        <?php endfor; ?>

        <div class="mt-3">
            <button type="submit" class="btn btn-success">Complete Appointment</button>
        </div>
    </form>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
