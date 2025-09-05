<?php
session_start();
include "dbconnect.php";

//  Ensure patient login
if (!isset($_SESSION['pid']) || $_SESSION['role'] != "patient") {
    header("Location: login.php");
    exit();
}

$pid = $_SESSION['pid'];

// Cancel appointment if requested
if (isset($_GET['cancel'])) {
    $app_id = $_GET['cancel'];
    $stmt = $conn->prepare("DELETE FROM appointment WHERE App_ID = ? AND Patient_ID = ? AND Status = 'Upcoming'");
    $stmt->bind_param("ss", $app_id, $pid);
    $stmt->execute();
    $stmt->close();
}

// Get upcoming appointments
$sql_upcoming = "SELECT a.App_ID, a.Date, a.Time, d.PID as DoctorID, p.Name as DoctorName, h.Name as HospitalName, h.Area
        FROM appointment a
        JOIN doctor d ON a.Doctor_ID = d.PID
        JOIN staff s ON d.PID = s.PID
        JOIN hospital h ON s.hospital_id = h.HospitalID
        JOIN person p ON d.PID = p.PID
        WHERE a.Patient_ID = ? AND a.Status = 'Upcoming'
        ORDER BY a.Date, a.Time";

$stmt = $conn->prepare($sql_upcoming);
$stmt->bind_param("s", $pid);
$stmt->execute();
$upcoming_result = $stmt->get_result();

// Get past appointments
$sql_past = "SELECT a.App_ID, a.Date, a.Time, a.Status, p.Name as DoctorName
        FROM appointment a
        JOIN doctor d ON a.Doctor_ID = d.PID
        JOIN person p ON d.PID = p.PID
        WHERE a.Patient_ID = ? AND a.Status <> 'Upcoming'
        ORDER BY a.Date DESC, a.Time DESC";

$stmt2 = $conn->prepare($sql_past);
$stmt2->bind_param("s", $pid);
$stmt2->execute();
$past_result = $stmt2->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Appointment Management</title>
    <style>
        body { font-family: Arial, sans-serif; }
        .buttons { margin: 15px 0; }
        .buttons a { margin-right: 10px; padding: 8px 12px; text-decoration: none; background: #007BFF; color: white; border-radius: 5px; }
        .buttons a:hover { background: #0056b3; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: center; }
        th { background-color: #f2f2f2; }
        .view-btn { color: green; font-weight: bold; text-decoration: none; }
        .view-btn:hover { text-decoration: underline; }
        .update-btn, .cancel-btn { font-weight: bold; text-decoration: none; }
        .update-btn { color: orange; }
        .update-btn:hover { text-decoration: underline; }
        .cancel-btn { color: red; }
        .cancel-btn:hover { text-decoration: underline; }
    </style>
</head>
<body>

<h2>Appointment Management</h2>

<div class="buttons">
    <a href="new_appointment.php">New Appointment</a>
    <a href="patient.php">Back</a>
    <a href="logout.php">Logout</a>
</div>

<h3>Upcoming Appointments</h3>
<table>
    <tr>
        <th>Appointment ID</th>
        <th>Date</th>
        <th>Time</th>
        <th>Doctor</th>
        <th>Hospital</th>
        <th>Area</th>
        <th>Actions</th>
    </tr>
    <?php while ($row = $upcoming_result->fetch_assoc()) { ?>
        <tr>
            <td><?php echo $row['App_ID']; ?></td>
            <td><?php echo $row['Date']; ?></td>
            <td><?php echo $row['Time']; ?></td>
            <td><?php echo $row['DoctorName']; ?></td>
            <td><?php echo $row['HospitalName']; ?></td>
            <td><?php echo $row['Area']; ?></td>
            <td>
                <a class="update-btn" href="update_appointment.php?app_id=<?php echo $row['App_ID']; ?>">Update</a> | 
                <a class="cancel-btn" href="?cancel=<?php echo $row['App_ID']; ?>" onclick="return confirm('Cancel this appointment?');">Cancel</a>
            </td>
        </tr>
    <?php } ?>
</table>

<h3>Past Appointments</h3>
<table>
    <tr>
        <th>Appointment ID</th>
        <th>Date</th>
        <th>Time</th>
        <th>Status</th>
        <th>Doctor</th>
        <th>Action</th>
    </tr>
    <?php while ($row = $past_result->fetch_assoc()) { ?>
        <tr>
            <td><?php echo $row['App_ID']; ?></td>
            <td><?php echo $row['Date']; ?></td>
            <td><?php echo $row['Time']; ?></td>
            <td><?php echo $row['Status']; ?></td>
            <td><?php echo $row['DoctorName']; ?></td>
            <td>
                <?php if ($row['Status'] == 'Complete') { ?>
                    <a class="view-btn" href="view_treatment.php?app_id=<?php echo $row['App_ID']; ?>">View Diagnosis & Prescription</a>
                <?php } else { echo "-"; } ?>
            </td>
        </tr>
    <?php } ?>
</table>

</body>
</html>

<?php
$stmt->close();
$stmt2->close();
$conn->close();
?>
