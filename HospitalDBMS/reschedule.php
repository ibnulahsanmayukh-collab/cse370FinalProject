<?php
session_start();
include "dbconnect.php";

if (!isset($_SESSION['pid']) || $_SESSION['role'] != "patient") {
    header("Location: login.php");
    exit;
}

$pid = $_SESSION['pid'];
$msg = "";

// --- Cancel appointment ---
if (isset($_GET['cancel'])) {
    $appid = $_GET['cancel'];
    $conn->query("DELETE FROM appointment WHERE App_ID='$appid' AND Patient_ID='$pid'");
    $msg = "Appointment cancelled.";
}

// --- Fetch specializations ---
$specs = $conn->query("SELECT DISTINCT Specialization FROM doctor");

// --- Handle rescheduling ---
if (isset($_POST['reschedule'])) {
    $appid = $_POST['appid'];
    $date = $_POST['date'];
    $time = $_POST['time'];
    $doctor = $_POST['doctor'];

    // Validate time window
    $t = strtotime($time);
    if ($t < strtotime("09:00") || $t > strtotime("21:00")) {
        $msg = "Appointments allowed only between 9AM and 9PM.";
    }
    // Validate 24h advance
    elseif (strtotime("$date $time") < time() + 86400) {
        $msg = "Appointments must be booked at least 24h in advance.";
    }
    else {
        // Check 10 min rule
        $check = $conn->query("
            SELECT * FROM appointment 
            WHERE Doctor_ID='$doctor' AND Date='$date'
            AND ABS(TIMESTAMPDIFF(MINUTE, Time, '$time')) < 10
            AND App_ID != '$appid'
        ");
        if ($check->num_rows > 0) {
            $msg = "This slot is too close to another appointment for that doctor.";
        } else {
            // Reschedule (update)
            $conn->query("UPDATE appointment 
                          SET Date='$date', Time='$time', Doctor_ID='$doctor' 
                          WHERE App_ID='$appid' AND Patient_ID='$pid'");
            $msg = "Appointment rescheduled successfully!";
        }
    }
}

// --- Get patient’s appointments ---
$apps = $conn->query("SELECT a.App_ID, a.Date, a.Time, d.Specialization, p.Name AS DoctorName
                      FROM appointment a
                      JOIN doctor d ON a.Doctor_ID = d.PID
                      JOIN person p ON d.PID = p.PID
                      WHERE a.Patient_ID='$pid'");
?>
<!DOCTYPE html>
<html>
<head><title>Reschedule or Cancel Appointment</title></head>
<body>
<h1>Manage Appointments for <?php echo $pid; ?></h1>
<a href="patient.php">Back</a> | <a href="logout.php">Logout</a>
<hr>

<?php if ($msg) echo "<p style='color:blue'>$msg</p>"; ?>

<h2>My Appointments</h2>
<table border="1">
<tr><th>ID</th><th>Date</th><th>Time</th><th>Specialization</th><th>Doctor</th><th>Action</th></tr>
<?php while ($row = $apps->fetch_assoc()): ?>
<tr>
    <td><?php echo $row['App_ID']; ?></td>
    <td><?php echo $row['Date']; ?></td>
    <td><?php echo $row['Time']; ?></td>
    <td><?php echo $row['Specialization']; ?></td>
    <td><?php echo $row['DoctorName']; ?></td>
    <td>
        <a href="reschedule.php?edit=<?php echo $row['App_ID']; ?>">Reschedule</a> |
        <a href="reschedule.php?cancel=<?php echo $row['App_ID']; ?>" onclick="return confirm('Cancel this appointment?');">Cancel</a>
    </td>
</tr>
<?php endwhile; ?>
</table>

<?php
// --- Show reschedule form if edit is set ---
if (isset($_GET['edit'])):
    $appid = $_GET['edit'];
    $app = $conn->query("SELECT * FROM appointment WHERE App_ID='$appid' AND Patient_ID='$pid'")->fetch_assoc();
    $spec = $conn->query("SELECT Specialization FROM doctor WHERE PID='{$app['Doctor_ID']}'")->fetch_assoc()['Specialization'];
    $docs = $conn->query("SELECT d.PID, p.Name, s.shift 
                          FROM doctor d
                          JOIN staff s ON d.PID=s.PID
                          JOIN person p ON d.PID=p.PID
                          WHERE d.Specialization='$spec'");
?>
<hr>
<h2>Reschedule Appointment</h2>
<form method="post">
    <input type="hidden" name="appid" value="<?php echo $appid; ?>">
    Date: <input type="date" name="date" value="<?php echo $app['Date']; ?>" required><br>
    Time: <input type="time" name="time" value="<?php echo $app['Time']; ?>" required><br>
    Select Doctor:
    <select name="doctor" required>
        <?php while ($d = $docs->fetch_assoc()): ?>
            <option value="<?php echo $d['PID']; ?>" <?php if ($d['PID'] == $app['Doctor_ID']) echo "selected"; ?>>
                <?php echo $d['Name']." (".$d['shift'].")"; ?>
            </option>
        <?php endwhile; ?>
    </select><br>
    <button type="submit" name="reschedule">Update Appointment</button>
</form>
<?php endif; ?>

</body>
</html>
