<?php
session_start();
include "dbconnect.php";

if (!isset($_GET['pid'])) {
    die("Doctor ID not specified.");
}

$pid = $_GET['pid'];

// Fetch current doctor info
$sql = "SELECT p.Name, p.email, p.Phone, p.password, s.shift, d.Specialization
        FROM person p
        JOIN staff s ON p.PID = s.PID
        JOIN doctor d ON s.PID = d.PID
        WHERE p.PID=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $pid);
$stmt->execute();
$result = $stmt->get_result();
$doctor = $result->fetch_assoc();

// Fetch degrees
$degree_sql = "SELECT Degrees FROM doctordegree WHERE PID=?";
$deg_stmt = $conn->prepare($degree_sql);
$deg_stmt->bind_param("s", $pid);
$deg_stmt->execute();
$deg_result = $deg_stmt->get_result();
$degrees = [];
while ($row = $deg_result->fetch_assoc()) {
    $degrees[] = $row['Degrees'];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $password = $_POST['password'];
    $shift = $_POST['shift'];
    $new_degrees = $_POST['degrees']; // array

    // Validate shift
    if($shift !== "Morning" && $shift !== "Evening"){
        die("Invalid shift selected.");
    }

    // Update person
    $update_person = $conn->prepare("UPDATE person SET email=?, Phone=?, password=? WHERE PID=?");
    $update_person->bind_param("ssss", $email, $phone, $password, $pid);
    $update_person->execute();

    // Update staff shift
    $update_staff = $conn->prepare("UPDATE staff SET shift=? WHERE PID=?");
    $update_staff->bind_param("ss", $shift, $pid);
    $update_staff->execute();

    // Update degrees: remove old and insert new
    $conn->query("DELETE FROM doctordegree WHERE PID='$pid'");
    $insert_degree = $conn->prepare("INSERT INTO doctordegree (PID, Degrees) VALUES (?, ?)");
    foreach($new_degrees as $deg){
        $insert_degree->bind_param("ss", $pid, $deg);
        $insert_degree->execute();
    }

    echo "<p style='color:green;'>Doctor info updated successfully!</p>";
    // Refresh doctor info
    header("Refresh:0");
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Modify Doctor</title>
     <a href="staff.php">⬅ Back</a>
</head>
<body>

    <h2>Modify Doctor Info</h2>
    <div style="display:flex; gap:50px;">
        <!-- Previous info -->
        <div>
            <h3>Current Info</h3>
            <p><strong>Name:</strong> <?= $doctor['Name'] ?></p>
            <p><strong>Email:</strong> <?= $doctor['email'] ?></p>
            <p><strong>Phone:</strong> <?= $doctor['Phone'] ?></p>
            <p><strong>Shift:</strong> <?= $doctor['shift'] ?></p>
            <p><strong>Specialization:</strong> <?= $doctor['Specialization'] ?></p>
            <p><strong>Degrees:</strong> <?= implode(", ", $degrees) ?></p>
        </div>

        <!-- Form to modify -->
        <form method="post">
            <label>Email:</label><br>
            <input type="email" name="email" value="<?= $doctor['email'] ?>" required><br><br>

            <label>Phone:</label><br>
            <input type="text" name="phone" value="<?= $doctor['Phone'] ?>" required><br><br>

            <label>Password:</label><br>
            <input type="text" name="password" value="<?= $doctor['password'] ?>" required><br><br>

            <label>Shift:</label><br>
            <select name="shift" required>
                <option value="Morning" <?= $doctor['shift']=="Morning"?"selected":"" ?>>Morning</option>
                <option value="Evening" <?= $doctor['shift']=="Evening"?"selected":"" ?>>Evening</option>
            </select><br><br>

            <label>Degrees (separate by comma):</label><br>
            <input type="text" name="degrees[]" value="<?= implode(",", $degrees) ?>"><br><br>

            <button type="submit">Update Doctor</button>
        </form>
    </div>
</body>
</html>
