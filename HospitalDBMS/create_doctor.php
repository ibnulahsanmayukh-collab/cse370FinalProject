<?php
session_start();
include "dbconnect.php";

//  Ensure logged in as admin
if (!isset($_SESSION['pid']) || $_SESSION['role'] != "admin") {
    header("Location: login.php");
    exit();
}

$admin_id = $_SESSION['pid'];

//  Get admin’s hospital
$stmt = $conn->prepare("SELECT hospital_id FROM staff WHERE PID=?");
$stmt->bind_param("s", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
if (!$row = $result->fetch_assoc()) {
    die("Admin not found in staff table.");
}
$hospital_id = $row['hospital_id'];
$stmt->close();

//  Generate next Doctor PID
function generateNextDoctorPID($conn) {
    $result = $conn->query("SELECT PID FROM staff WHERE PID LIKE 'D%' ORDER BY PID DESC LIMIT 1");
    if ($row = $result->fetch_assoc()) {
        $lastId = intval(substr($row['PID'], 1));
        $newId = $lastId + 1;
    } else {
        $newId = 1;
    }
    return "D" . str_pad($newId, 9, "0", STR_PAD_LEFT);
}

$new_pid = generateNextDoctorPID($conn);

//  Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $dob = $_POST['dob'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $password = $_POST['password'];
    $shift = $_POST['shift'];
    $specialization = $_POST['specialization'];
    $degree = $_POST['degree'];

    // Insert into person
    $stmt = $conn->prepare("INSERT INTO person (PID, Name, DateofBirth, email, Phone, password) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $new_pid, $name, $dob, $email, $phone, $password);
    $stmt->execute();
    $stmt->close();

    // Insert into staff
    $stmt = $conn->prepare("INSERT INTO staff (PID, shift, hospital_id) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $new_pid, $shift, $hospital_id);
    $stmt->execute();
    $stmt->close();

    // Insert into doctor
    $stmt = $conn->prepare("INSERT INTO doctor (PID, Specialization) VALUES (?, ?)");
    $stmt->bind_param("ss", $new_pid, $specialization);
    $stmt->execute();
    $stmt->close();

    // Insert into doctordegree
    $stmt = $conn->prepare("INSERT INTO doctordegree (PID, Degrees) VALUES (?, ?)");
    $stmt->bind_param("ss", $new_pid, $degree);
    $stmt->execute();
    $stmt->close();

    header("Location: staff.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Create Doctor</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; margin: 0; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .header a {
            background: #6c757d;
            color: white;
            padding: 8px 12px;
            border-radius: 6px;
            text-decoration: none;
        }
        .header a:hover { background: #5a6268; }
        form { max-width: 500px; margin: auto; display: flex; flex-direction: column; gap: 12px; }
        label { font-weight: bold; }
        input, select {
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 6px;
            width: 100%;
        }
        button {
            background: #007BFF;
            color: white;
            border: none;
            padding: 10px;
            border-radius: 6px;
            cursor: pointer;
        }
        button:hover { background: #0056b3; }
    </style>
</head>
<body>

<div class="header">
    <h2>Create New Doctor</h2>
    <a href="staff.php">⬅ Back</a>
</div>

<form method="post">
    <label>Generated PID</label>
    <input type="text" value="<?php echo $new_pid; ?>" disabled>

    <label>Full Name</label>
    <input type="text" name="name" required>

    <label>Date of Birth</label>
    <input type="date" name="dob" required>

    <label>Email</label>
    <input type="email" name="email" required>

    <label>Phone</label>
    <input type="text" name="phone" required>

    <label>Password</label>
    <input type="password" name="password" required>

    <label>Shift</label>
    <select name="shift" required>
        <option value="Morning">Morning</option>
        <option value="Evening">Evening</option>
        <option value="Night">Night</option>
    </select>

    <label>Specialization</label>
    <input type="text" name="specialization" required>

    <label>Degree(s)</label>
    <input type="text" name="degree" placeholder="e.g. MBBS, FCPS" required>

    <button type="submit"> Create Doctor</button>
</form>

</body>
</html>
