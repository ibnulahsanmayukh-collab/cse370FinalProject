<?php
session_start();
include "dbconnect.php";

// Ensure doctor login
if (!isset($_SESSION['pid']) || $_SESSION['role'] != "doctor") {
    header("Location: login.php");
    exit();
}

$doctor_id = $_SESSION['pid'];
$message = "";

// Get appointment ID
if (!isset($_GET['app_id'])) {
    header("Location: attend_appointment.php");
    exit();
}
$app_id = $_GET['app_id'];

// Fetch existing appointment (must belong to this doctor)
$stmt = $conn->prepare("SELECT a.App_ID, a.Date, a.Time, a.Status, 
                               p.Name AS PatientName, p.PID AS PatientID,
                               d.PID AS DoctorID, per.Name AS DoctorName, d.Specialization,
                               s.shift, h.Area
                        FROM appointment a
                        JOIN patient pa ON a.Patient_ID = pa.PID
                        JOIN person p ON pa.PID = p.PID
                        JOIN doctor d ON a.Doctor_ID = d.PID
                        JOIN staff s ON d.PID = s.PID
                        JOIN hospital h ON s.hospital_id = h.HospitalID
                        WHERE a.App_ID=? AND a.Doctor_ID=?");
$stmt->bind_param("ss", $app_id, $doctor_id);
$stmt->execute();
$appointment = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$appointment) {
    die("Appointment not found or unauthorized.");
}

$today = date("Y-m-d");
if ($appointment['Date'] <= $today) {
    die("Cannot reschedule appointment scheduled for today or past.");
}

// Handle form submit
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['date'], $_POST['time'])) {
    $new_date = $_POST['date'];
    $new_time = $_POST['time'];

    if ($new_date <= $today) {
        $message = "Cannot set appointment for today or past.";
    } elseif (date('N', strtotime($new_date)) == 5) { // No Friday
        $message = "Appointments are not allowed on Fridays.";
    } else {
        $conn->begin_transaction();
        try {
            // Check doctor conflict
            $stmt = $conn->prepare("SELECT 1 FROM appointment 
                                    WHERE Doctor_ID=? AND Date=? AND Time=? AND Status='Upcoming' AND App_ID<>?");
            $stmt->bind_param("ssss", $appointment['DoctorID'], $new_date, $new_time, $app_id);
            $stmt->execute();
            $doc_conflict = $stmt->get_result()->num_rows > 0;
            $stmt->close();

            // Check patient conflict
            $stmt = $conn->prepare("SELECT 1 FROM appointment 
                                    WHERE Patient_ID=? AND Date=? AND Time=? AND Status='Upcoming' AND App_ID<>?");
            $stmt->bind_param("ssss", $appointment['PatientID'], $new_date, $new_time, $app_id);
            $stmt->execute();
            $pat_conflict = $stmt->get_result()->num_rows > 0;
            $stmt->close();

            if ($doc_conflict || $pat_conflict) {
                $message = "Conflict: Either doctor or patient already has an appointment at this time.";
                $conn->rollback();
            } else {
                // Update appointment
                $stmt = $conn->prepare("UPDATE appointment SET Date=?, Time=? WHERE App_ID=? AND Doctor_ID=? AND Status='Upcoming'");
                $stmt->bind_param("ssss", $new_date, $new_time, $app_id, $doctor_id);
                $stmt->execute();
                $stmt->close();

                $conn->commit();
                $message = "Appointment updated successfully.";
            }
        } catch (Exception $e) {
            $conn->rollback();
            $message = "Failed to update: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reschedule Appointment</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-7">
            <div class="card shadow">
                <div class="card-body">
                    <h3 class="card-title mb-4 text-center">Reschedule Appointment</h3>

                    <?php if ($message != ""): ?>
                        <div class="alert alert-info"><?php echo $message; ?></div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <h5>Doctor Details</h5>
                        <p><?php echo htmlspecialchars($appointment['DoctorName']); ?> (<?php echo $appointment['DoctorID']; ?>) - <?php echo $appointment['Specialization']; ?></p>
                    </div>

                    <div class="mb-3">
                        <h5>Patient Details</h5>
                        <p><?php echo htmlspecialchars($appointment['PatientName']); ?> (<?php echo $appointment['PatientID']; ?>)</p>
                    </div>

                    <div class="mb-3">
                        <h5>Appointment Info</h5>
                        <p>Hospital Area: <?php echo $appointment['Area']; ?> | Shift: <?php echo $appointment['shift']; ?></p>
                    </div>

                    <form method="POST">
                        <div class="mb-3">
                            <label for="date" class="form-label">Select New Date:</label>
                            <input type="date" name="date" class="form-control" value="<?php echo $appointment['Date']; ?>" min="<?php echo date("Y-m-d", strtotime("+1 day")); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="time" class="form-label">Select New Time:</label>
                            <select name="time" class="form-select" required>
                                <option value="">-- Select --</option>
                                <?php
                                $shift = $appointment['shift'];
                                if ($shift == "Morning") {
                                    for ($h=9; $h<15; $h++) foreach (["00","30"] as $m) {
                                        $t = sprintf("%02d:%s:00", $h, $m);
                                        $selected = ($t == $appointment['Time']) ? "selected" : "";
                                        echo "<option value='$t' $selected>$t</option>";
                                    }
                                } else {
                                    for ($h=15; $h<21; $h++) foreach (["00","30"] as $m) {
                                        $t = sprintf("%02d:%s:00", $h, $m);
                                        $selected = ($t == $appointment['Time']) ? "selected" : "";
                                        echo "<option value='$t' $selected>$t</option>";
                                    }
                                }
                                ?>
                            </select>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="attend_appointment.php" class="btn btn-secondary">Back</a>
                            <button type="submit" class="btn btn-primary">Update Appointment</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
