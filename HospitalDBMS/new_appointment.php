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

// Handle form submit
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['doctor'], $_POST['date'], $_POST['time'])) {
    $doctor_id = $_POST['doctor'];
    $date = $_POST['date'];
    $time = $_POST['time'];

    $today = date("Y-m-d");
    if ($date <= $today) {
        $message = "Cannot book appointment today or before.";
    } elseif (date('N', strtotime($date)) == 5) {
        $message = "Appointments are not allowed on Fridays.";
    } else {
        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("SELECT 1 FROM appointment WHERE Doctor_ID=? AND Date=? AND Time=? AND Status='Upcoming'");
            $stmt->bind_param("sss", $doctor_id, $date, $time);
            $stmt->execute();
            $doc_conflict = $stmt->get_result()->num_rows > 0;
            $stmt->close();

            $stmt = $conn->prepare("SELECT 1 FROM appointment WHERE Patient_ID=? AND Date=? AND Time=? AND Status='Upcoming'");
            $stmt->bind_param("sss", $pid, $date, $time);
            $stmt->execute();
            $pat_conflict = $stmt->get_result()->num_rows > 0;
            $stmt->close();

            if ($doc_conflict || $pat_conflict) {
                $message = "Conflict: Either doctor or patient already has an appointment at this time.";
                $conn->rollback();
            } else {
                $app_id = "APP" . str_pad(rand(1, 99999999), 8, "0", STR_PAD_LEFT);
                $stmt = $conn->prepare("INSERT INTO appointment(App_ID, Time, Date, Status, Doctor_ID, Patient_ID) 
                                        VALUES(?, ?, ?, 'Upcoming', ?, ?)");
                $stmt->bind_param("sssss", $app_id, $time, $date, $doctor_id, $pid);
                $stmt->execute();
                $stmt->close();
                $conn->commit();
                $message = "Appointment booked successfully!";
            }
        } catch (Exception $e) {
            $conn->rollback();
            $message = "Failed to book: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Book Appointment</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Book New Appointment</h2>
        <div>
            <a href="appointment_manage.php" class="btn btn-secondary">Back</a>
            <a href="logout.php" class="btn btn-danger">Logout</a>
        </div>
    </div>

    <?php if ($message != ""): ?>
        <div class="alert alert-info"><?php echo $message; ?></div>
    <?php endif; ?>

    <form method="POST" class="card p-4 shadow-sm">

        <!-- Select Area -->
        <div class="mb-3">
            <label class="form-label">Select Area (Hospital):</label>
            <select name="area" class="form-select" onchange="this.form.submit()">
                <option value="">-- Select --</option>
                <?php
                $res = $conn->query("SELECT DISTINCT Area, Name FROM hospital");
                while ($row = $res->fetch_assoc()) {
                    $selected = (isset($_POST['area']) && $_POST['area']==$row['Area']) ? "selected" : "";
                    echo "<option value='{$row['Area']}' $selected>{$row['Area']} ({$row['Name']})</option>";
                }
                ?>
            </select>
        </div>

        <?php if (!empty($_POST['area'])): ?>
            <div class="mb-3">
                <label class="form-label">Select Specialization:</label>
                <select name="specialization" class="form-select" onchange="this.form.submit()">
                    <option value="">-- Select --</option>
                    <?php
                    $stmt = $conn->prepare("SELECT DISTINCT d.Specialization 
                                            FROM doctor d 
                                            JOIN staff s ON d.PID=s.PID 
                                            JOIN hospital h ON s.hospital_id=h.HospitalID 
                                            WHERE h.Area=?");
                    $stmt->bind_param("s", $_POST['area']);
                    $stmt->execute();
                    $spRes = $stmt->get_result();
                    while ($row = $spRes->fetch_assoc()) {
                        $selected = (isset($_POST['specialization']) && $_POST['specialization']==$row['Specialization']) ? "selected" : "";
                        echo "<option value='{$row['Specialization']}' $selected>{$row['Specialization']}</option>";
                    }
                    $stmt->close();
                    ?>
                </select>
            </div>
        <?php endif; ?>

        <?php if (!empty($_POST['specialization'])): ?>
            <div class="mb-3">
                <label class="form-label">Select Shift:</label>
                <select name="shift" class="form-select" onchange="this.form.submit()">
                    <option value="">-- Select --</option>
                    <option value="Morning" <?php if(isset($_POST['shift']) && $_POST['shift']=="Morning") echo "selected"; ?>>Morning (9am - 3pm)</option>
                    <option value="Evening" <?php if(isset($_POST['shift']) && $_POST['shift']=="Evening") echo "selected"; ?>>Evening (3pm - 9pm)</option>
                </select>
            </div>
        <?php endif; ?>

        <?php if (!empty($_POST['shift'])): ?>
            <div class="mb-3">
                <label class="form-label">Select Doctor:</label>
                <select name="doctor" class="form-select" required>
                    <option value="">-- Select --</option>
                    <?php
                    $stmt = $conn->prepare("SELECT d.PID, p.Name, h.Name as HospitalName 
                                            FROM doctor d
                                            JOIN person p ON d.PID=p.PID
                                            JOIN staff s ON d.PID=s.PID
                                            JOIN hospital h ON s.hospital_id=h.HospitalID
                                            WHERE d.Specialization=? AND h.Area=? AND s.shift=?");
                    $stmt->bind_param("sss", $_POST['specialization'], $_POST['area'], $_POST['shift']);
                    $stmt->execute();
                    $docRes = $stmt->get_result();
                    while ($row = $docRes->fetch_assoc()) {
                        echo "<option value='{$row['PID']}'>{$row['Name']} ({$row['HospitalName']})</option>";
                    }
                    $stmt->close();
                    ?>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Select Date:</label>
                <input type="date" name="date" class="form-control" min="<?php echo date("Y-m-d", strtotime("+1 day")); ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Select Time:</label>
                <select name="time" class="form-select" required>
                    <option value="">-- Select --</option>
                    <?php
                    $shift_hours = ($_POST['shift']=="Morning") ? range(9,14) : range(15,20);
                    foreach($shift_hours as $h){
                        foreach(["00","30"] as $m){
                            $t = sprintf("%02d:%s:00", $h, $m);
                            echo "<option value='$t'>$t</option>";
                        }
                    }
                    ?>
                </select>
            </div>

            <button type="submit" class="btn btn-primary w-100">Book Appointment</button>
        <?php endif; ?>

    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
