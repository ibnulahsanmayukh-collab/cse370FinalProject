<?php
session_start();
include "dbconnect.php";

// Ensure patient login
if (!isset($_SESSION['pid']) || $_SESSION['role'] != "patient") {
    header("Location: login.php");
    exit();
}

$pid = $_SESSION['pid'];
$message = "";

// Get appointment ID from GET
if (!isset($_GET['app_id'])) {
    header("Location: appointment_manage.php");
    exit();
}

$app_id = $_GET['app_id'];

// Fetch existing appointment
$stmt = $conn->prepare("SELECT a.App_ID, a.Date, a.Time, a.Doctor_ID, a.Status, s.shift, h.Area, d.Specialization
                        FROM appointment a
                        JOIN staff s ON a.Doctor_ID = s.PID
                        JOIN hospital h ON s.hospital_id = h.HospitalID
                        JOIN doctor d ON a.Doctor_ID = d.PID
                        WHERE a.App_ID=? AND a.Patient_ID=?");
$stmt->bind_param("ss", $app_id, $pid);
$stmt->execute();
$appointment = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$appointment) {
    die("Appointment not found.");
}

$today = date("Y-m-d");
if ($appointment['Date'] <= $today) {
    die("Cannot update appointment scheduled for today or past.");
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
            $stmt->bind_param("ssss", $appointment['Doctor_ID'], $new_date, $new_time, $app_id);
            $stmt->execute();
            $doc_conflict = $stmt->get_result()->num_rows > 0;
            $stmt->close();

            // Check patient conflict
            $stmt = $conn->prepare("SELECT 1 FROM appointment 
                                    WHERE Patient_ID=? AND Date=? AND Time=? AND Status='Upcoming' AND App_ID<>?");
            $stmt->bind_param("ssss", $pid, $new_date, $new_time, $app_id);
            $stmt->execute();
            $pat_conflict = $stmt->get_result()->num_rows > 0;
            $stmt->close();

            if ($doc_conflict || $pat_conflict) {
                $message = "Conflict: Either doctor or patient already has an appointment at this time.";
                $conn->rollback();
            } else {
                // Update appointment
                $stmt = $conn->prepare("UPDATE appointment SET Date=?, Time=? WHERE App_ID=? AND Patient_ID=? AND Status='Upcoming'");
                $stmt->bind_param("ssss", $new_date, $new_time, $app_id, $pid);
                $stmt->execute();
                $stmt->close();

                $conn->commit();
                $message = "Appointment updated successfully!";
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
    <title>Update Appointment</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Update Appointment</h2>
        <div>
            <a href="appointment_manage.php" class="btn btn-secondary me-2">Back</a>
            <a href="logout.php" class="btn btn-danger">Logout</a>
        </div>
    </div>

    <?php if ($message != ""): ?>
        <div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <p><strong>Doctor:</strong> <?php echo htmlspecialchars($appointment['Doctor_ID']); ?> (<?php echo htmlspecialchars($appointment['Specialization']); ?>)</p>
            <p><strong>Hospital Area:</strong> <?php echo htmlspecialchars($appointment['Area']); ?> | <strong>Shift:</strong> <?php echo htmlspecialchars($appointment['shift']); ?></p>

            <form method="POST">
                <div class="mb-3">
                    <label for="date" class="form-label">Select New Date:</label>
                    <input type="date" class="form-control" name="date" value="<?php echo htmlspecialchars($appointment['Date']); ?>" min="<?php echo date("Y-m-d", strtotime("+1 day")); ?>" required>
                </div>

                <div class="mb-3">
                    <label for="time" class="form-label">Select New Time:</label>
                    <select class="form-select" name="time" required>
                        <option value="">-- Select --</option>
                        <?php
                        $shift = $appointment['shift'];
                        $hours = ($shift=="Morning") ? range(9,14) : range(15,20);
                        foreach ($hours as $h) {
                            foreach (["00","30"] as $m) {
                                $t = sprintf("%02d:%s:00", $h, $m);
                                $selected = ($t==$appointment['Time']) ? "selected" : "";
                                echo "<option value='$t' $selected>$t</option>";
                            }
                        }
                        ?>
                    </select>
                </div>

                <button type="submit" class="btn btn-success">Update Appointment</button>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
