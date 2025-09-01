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

// ✅ Handle form submit only if all fields exist
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['doctor'], $_POST['date'], $_POST['time'])) {
    $doctor_id = $_POST['doctor'];
    $date = $_POST['date'];
    $time = $_POST['time'];

    // Constraint: no same-day or past appointments
    $today = date("Y-m-d");
    if ($date <= $today) {
        $message = "❌ Cannot book appointment today or before.";
    } else {
        // Constraint: no Friday appointments
        if (date('N', strtotime($date)) == 5) {
            $message = "❌ Appointments are not allowed on Fridays.";
        } else {
            $conn->begin_transaction();
            try {
                // Check conflicts: doctor busy?
                $stmt = $conn->prepare("SELECT 1 FROM appointment 
                                        WHERE Doctor_ID=? AND Date=? AND Time=? AND Status='Upcoming'");
                $stmt->bind_param("sss", $doctor_id, $date, $time);
                $stmt->execute();
                $doc_conflict = $stmt->get_result()->num_rows > 0;
                $stmt->close();

                // Check conflicts: patient busy?
                $stmt = $conn->prepare("SELECT 1 FROM appointment 
                                        WHERE Patient_ID=? AND Date=? AND Time=? AND Status='Upcoming'");
                $stmt->bind_param("sss", $pid, $date, $time);
                $stmt->execute();
                $pat_conflict = $stmt->get_result()->num_rows > 0;
                $stmt->close();

                if ($doc_conflict || $pat_conflict) {
                    $message = "❌ Conflict: Either doctor or patient already has an appointment at this time.";
                    $conn->rollback();
                } else {
                    // ✅ Generate App_ID
                    $app_id = "APP" . str_pad(rand(1, 99999999), 8, "0", STR_PAD_LEFT);

                    $stmt = $conn->prepare("INSERT INTO appointment(App_ID, Time, Date, Status, Doctor_ID, Patient_ID) 
                                            VALUES(?, ?, ?, 'Upcoming', ?, ?)");
                    $stmt->bind_param("sssss", $app_id, $time, $date, $doctor_id, $pid);
                    $stmt->execute();
                    $stmt->close();

                    $conn->commit();
                    $message = "✅ Appointment booked successfully!";
                }
            } catch (Exception $e) {
                $conn->rollback();
                $message = "❌ Failed to book: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>New Appointment</title>
    <style>
        body { font-family: Arial, sans-serif; }
        form { margin: 20px 0; }
        label { display: block; margin-top: 10px; }
        select, input[type=date], input[type=time] { padding: 6px; margin-top: 5px; }
        .buttons a { margin-right: 10px; padding: 8px 12px; text-decoration: none; background: #007BFF; color: white; border-radius: 5px; }
        .buttons a:hover { background: #0056b3; }
        .msg { margin: 15px 0; font-weight: bold; }
    </style>
</head>
<body>

<h2>Book New Appointment</h2>

<div class="buttons">
    <a href="appointment_manage.php">Back</a>
    <a href="logout.php">Logout</a>
</div>

<?php if ($message != "") { echo "<p class='msg'>$message</p>"; } ?>

<form method="POST">

    <!-- Select Area -->
    <label for="area">Select Area (Hospital):</label>
    <select name="area" id="area" onchange="this.form.submit()">
        <option value="">-- Select --</option>
        <?php
        $res = $conn->query("SELECT DISTINCT Area, Name FROM hospital");
        while ($row = $res->fetch_assoc()) {
            $selected = (isset($_POST['area']) && $_POST['area']==$row['Area']) ? "selected" : "";
            echo "<option value='{$row['Area']}' $selected>{$row['Area']} ({$row['Name']})</option>";
        }
        ?>
    </select>

    <?php if (!empty($_POST['area'])) { ?>
        <!-- Select Specialization -->
        <label for="specialization">Select Specialization:</label>
        <select name="specialization" onchange="this.form.submit()">
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
    <?php } ?>

    <?php if (!empty($_POST['specialization'])) { ?>
        <!-- Select Shift -->
        <label for="shift">Select Shift:</label>
        <select name="shift" onchange="this.form.submit()">
            <option value="">-- Select --</option>
            <option value="Morning" <?php if(isset($_POST['shift']) && $_POST['shift']=="Morning") echo "selected"; ?>>Morning (9am - 3pm)</option>
            <option value="Evening" <?php if(isset($_POST['shift']) && $_POST['shift']=="Evening") echo "selected"; ?>>Evening (3pm - 9pm)</option>
        </select>
    <?php } ?>

    <?php if (!empty($_POST['shift'])) { ?>
        <!-- Select Doctor -->
        <label for="doctor">Select Doctor:</label>
        <select name="doctor">
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

        <!-- Date -->
        <label for="date">Select Date:</label>
        <input type="date" name="date" min="<?php echo date("Y-m-d", strtotime("+1 day")); ?>">

        <!-- Time (nearest half hour, not free minute entry) -->
        <label for="time">Select Time:</label>
        <select name="time">
            <option value="">-- Select --</option>
            <?php
            if ($_POST['shift']=="Morning") {
                for ($h=9; $h<15; $h++) {
                    foreach (["00","30"] as $m) {
                        $t = sprintf("%02d:%s:00", $h, $m);
                        echo "<option value='$t'>$t</option>";
                    }
                }
            } else {
                for ($h=15; $h<21; $h++) {
                    foreach (["00","30"] as $m) {
                        $t = sprintf("%02d:%s:00", $h, $m);
                        echo "<option value='$t'>$t</option>";
                    }
                }
            }
            ?>
        </select>

        <br><br>
        <input type="submit" value="Book Appointment">
    <?php } ?>

</form>

</body>
</html>
