<?php
session_start();
include "dbconnect.php";

// ✅ Ensure doctor login
if (!isset($_SESSION['pid']) || $_SESSION['role'] != "doctor") {
    header("Location: login.php");
    exit();
}

$doctor_id = $_SESSION['pid'];
$message = "";

//  Get appointment ID
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
                        JOIN person per ON d.PID = per.PID
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

//  Handle form submit
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
                //  Update appointment
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
    <style>
        body { font-family: Arial, sans-serif; }
        form { margin: 20px 0; }
        label { display: block; margin-top: 10px; }
        input[type=date], select { padding: 6px; margin-top: 5px; }
        .buttons a { margin-right: 10px; padding: 8px 12px; text-decoration: none; background: #007BFF; color: white; border-radius: 5px; }
        .buttons a:hover { background: #0056b3; }
        .msg { margin: 15px 0; font-weight: bold; }
    </style>
</head>
<body>

<h2>Reschedule Appointment</h2>

<div class="buttons">
    <a href="attend_appointment.php">Back</a>
    <a href="logout.php">Logout</a>
</div>

<?php if ($message != "") { echo "<p class='msg'>$message</p>"; } ?>

<form method="POST">
    <h3>Doctor Details</h3>
    <p><?php echo htmlspecialchars($appointment['DoctorName']); ?> (<?php echo $appointment['DoctorID']; ?>) - <?php echo $appointment['Specialization']; ?></p>

    <h3>Patient Details</h3>
    <p><?php echo htmlspecialchars($appointment['PatientName']); ?> (<?php echo $appointment['PatientID']; ?>)</p>

    <h3>Appointment Info</h3>
    <p>Hospital Area: <?php echo $appointment['Area']; ?> | Shift: <?php echo $appointment['shift']; ?></p>

    <label for="date">Select New Date:</label>
    <input type="date" name="date" value="<?php echo $appointment['Date']; ?>" min="<?php echo date("Y-m-d", strtotime("+1 day")); ?>">

    <label for="time">Select New Time:</label>
    <select name="time">
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

    <br><br>
    <input type="submit" value="Update Appointment">
</form>

</body>
</html>
