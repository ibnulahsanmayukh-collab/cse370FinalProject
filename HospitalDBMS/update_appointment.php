<?php
session_start();
include "dbconnect.php";

// ✅ Ensure patient login
if (!isset($_SESSION['pid']) || $_SESSION['role'] != "patient") {
    header("Location: login.php");
    exit();
}

$pid = $_SESSION['pid'];
$message = "";

// ✅ Get appointment ID from GET
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

// ✅ Handle form submit
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['date'], $_POST['time'])) {
    $new_date = $_POST['date'];
    $new_time = $_POST['time'];

    // Constraint: no same-day or past appointments
    if ($new_date <= $today) {
        $message = "❌ Cannot set appointment for today or past.";
    } elseif (date('N', strtotime($new_date)) == 5) { // No Friday
        $message = "❌ Appointments are not allowed on Fridays.";
    } else {
        $conn->begin_transaction();
        try {
            // Check conflicts: doctor busy?
            $stmt = $conn->prepare("SELECT 1 FROM appointment 
                                    WHERE Doctor_ID=? AND Date=? AND Time=? AND Status='Upcoming' AND App_ID<>?");
            $stmt->bind_param("ssss", $appointment['Doctor_ID'], $new_date, $new_time, $app_id);
            $stmt->execute();
            $doc_conflict = $stmt->get_result()->num_rows > 0;
            $stmt->close();

            // Check conflicts: patient busy?
            $stmt = $conn->prepare("SELECT 1 FROM appointment 
                                    WHERE Patient_ID=? AND Date=? AND Time=? AND Status='Upcoming' AND App_ID<>?");
            $stmt->bind_param("ssss", $pid, $new_date, $new_time, $app_id);
            $stmt->execute();
            $pat_conflict = $stmt->get_result()->num_rows > 0;
            $stmt->close();

            if ($doc_conflict || $pat_conflict) {
                $message = "❌ Conflict: Either doctor or patient already has an appointment at this time.";
                $conn->rollback();
            } else {
                // ✅ Update appointment
                $stmt = $conn->prepare("UPDATE appointment SET Date=?, Time=? WHERE App_ID=? AND Patient_ID=? AND Status='Upcoming'");
                $stmt->bind_param("ssss", $new_date, $new_time, $app_id, $pid);
                $stmt->execute();
                $stmt->close();

                $conn->commit();
                $message = "✅ Appointment updated successfully!";
            }
        } catch (Exception $e) {
            $conn->rollback();
            $message = "❌ Failed to update: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Update Appointment</title>
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

<h2>Update Appointment</h2>

<div class="buttons">
    <a href="appointment_manage.php">Back</a>
    <a href="logout.php">Logout</a>
</div>

<?php if ($message != "") { echo "<p class='msg'>$message</p>"; } ?>

<form method="POST">
    <p>Doctor: <?php echo $appointment['Doctor_ID']; ?> (<?php echo $appointment['Specialization']; ?>)</p>
    <p>Hospital Area: <?php echo $appointment['Area']; ?> | Shift: <?php echo $appointment['shift']; ?></p>

    <label for="date">Select New Date:</label>
    <input type="date" name="date" value="<?php echo $appointment['Date']; ?>" min="<?php echo date("Y-m-d", strtotime("+1 day")); ?>">

    <label for="time">Select New Time:</label>
    <select name="time">
        <option value="">-- Select --</option>
        <?php
        $shift = $appointment['shift'];
        if ($shift=="Morning") {
            for ($h=9; $h<15; $h++) foreach (["00","30"] as $m) {
                $t = sprintf("%02d:%s:00", $h, $m);
                $selected = ($t==$appointment['Time']) ? "selected" : "";
                echo "<option value='$t' $selected>$t</option>";
            }
        } else {
            for ($h=15; $h<21; $h++) foreach (["00","30"] as $m) {
                $t = sprintf("%02d:%s:00", $h, $m);
                $selected = ($t==$appointment['Time']) ? "selected" : "";
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
