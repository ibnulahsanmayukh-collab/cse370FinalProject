<?php
session_start();
include "dbconnect.php";

if (!isset($_SESSION['pid']) || $_SESSION['role'] != "doctor") {
    header("Location: login.php");
    exit;
}

$pid = $_SESSION['pid'];
$message = "";

// ✅ Handle cancel appointment request
if (isset($_GET['cancel'])) {
    $app_id = $_GET['cancel'];
    $stmt = $conn->prepare("UPDATE appointment SET Status='Cancelled' WHERE App_ID=? AND Doctor_ID=? AND Status='Upcoming'");
    $stmt->bind_param("ss", $app_id, $pid);
    if ($stmt->execute()) {
        $message = "✅ Appointment cancelled successfully.";
    } else {
        $message = "❌ Failed to cancel appointment.";
    }
    $stmt->close();
}

// ✅ Get doctor's personal info
$stmt = $conn->prepare("SELECT p.Name, p.DateofBirth, p.email, p.Phone, d.Specialization, GROUP_CONCAT(dd.Degrees SEPARATOR ', ') AS Degrees
                        FROM doctor d
                        JOIN person p ON d.PID=p.PID
                        LEFT JOIN doctordegree dd ON d.PID=dd.PID
                        WHERE d.PID=?");
$stmt->bind_param("s", $pid);
$stmt->execute();
$doctor_info = $stmt->get_result()->fetch_assoc();
$stmt->close();

// ✅ Get upcoming appointments
$stmt = $conn->prepare("SELECT a.App_ID, a.Date, a.Time, pa.PID as PatientID, p.Name as PatientName
                        FROM appointment a
                        JOIN patient pa ON a.Patient_ID=pa.PID
                        JOIN person p ON pa.PID=p.PID
                        WHERE a.Doctor_ID=? AND a.Status='Upcoming'
                        ORDER BY a.Date, a.Time");
$stmt->bind_param("s", $pid);
$stmt->execute();
$apps = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Doctor Dashboard</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <style>
        .action-btn { margin-right: 5px; }
        .msg { margin: 15px 0; font-weight: bold; }
    </style>
</head>
<body class="p-4">
<div class="container">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Welcome Dr. <?php echo htmlspecialchars($doctor_info['Name']); ?></h1>
        <a href="logout.php" class="btn btn-danger">Logout</a>
    </div>

    <?php if($message != ""): ?>
        <div class="msg"><?php echo $message; ?></div>
    <?php endif; ?>

    <h2>Doctor Information</h2>
    <table class="table table-bordered w-50">
        <tr><th>Name</th><td><?php echo htmlspecialchars($doctor_info['Name']); ?></td></tr>
        <tr><th>Specialization</th><td><?php echo htmlspecialchars($doctor_info['Specialization']); ?></td></tr>
        <tr><th>Degrees</th><td><?php echo htmlspecialchars($doctor_info['Degrees']); ?></td></tr>
        <tr><th>Email</th><td><?php echo htmlspecialchars($doctor_info['email']); ?></td></tr>
        <tr><th>Phone</th><td><?php echo htmlspecialchars($doctor_info['Phone']); ?></td></tr>
        <tr><th>Date of Birth</th><td><?php echo htmlspecialchars($doctor_info['DateofBirth']); ?></td></tr>
    </table>

    <h2 class="mt-5">Upcoming Appointments</h2>
    <table class="table table-striped table-bordered">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Date</th>
                <th>Time</th>
                <th>Patient</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($row = $apps->fetch_assoc()): ?>
            <tr>
                <td><?php echo $row['App_ID']; ?></td>
                <td><?php echo $row['Date']; ?></td>
                <td><?php echo $row['Time']; ?></td>
                <td><?php echo htmlspecialchars($row['PatientName']); ?></td>
                <td>
                    <a href="attend_appointment.php?app_id=<?php echo $row['App_ID']; ?>" class="btn btn-success btn-sm action-btn">Attend</a>
                    <a href="reschedule_appointment.php?app_id=<?php echo $row['App_ID']; ?>" class="btn btn-warning btn-sm action-btn">Reschedule</a>
                    <a href="?cancel=<?php echo $row['App_ID']; ?>" onclick="return confirm('Cancel this appointment?');" class="btn btn-danger btn-sm action-btn">Cancel</a>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>

</div>

<script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>
