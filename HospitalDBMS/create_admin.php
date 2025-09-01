<?php
session_start();
include "dbconnect.php";

// ✅ Ensure logged in as admin
if (!isset($_SESSION['pid']) || $_SESSION['role'] != "admin") {
    header("Location: login.php");
    exit();
}

// ✅ Generate next PID (for Admins: start with "A")
function generateNextAdminPID($conn) {
    $result = $conn->query("SELECT PID FROM staff WHERE PID LIKE 'A%' ORDER BY PID DESC LIMIT 1");
    if ($row = $result->fetch_assoc()) {
        $lastId = intval(substr($row['PID'], 1));
        $newId = $lastId + 1;
    } else {
        $newId = 1;
    }
    return "A" . str_pad($newId, 9, "0", STR_PAD_LEFT);
}

$new_pid = generateNextAdminPID($conn);

// ✅ Fetch all hospitals
$hospitals = $conn->query("SELECT HospitalID, Name FROM hospital");

// ✅ Handle form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $dob = $_POST['dob'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $password = $_POST['password'];
    $shift = $_POST['shift'];
    $hospital_id = $_POST['hospital_id'];

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

    header("Location: staff.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Create Admin</title>
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
            background: #28a745;
            color: white;
            border: none;
            padding: 10px;
            border-radius: 6px;
            cursor: pointer;
        }
        button:hover { background: #218838; }
    </style>
</head>
<body>

<div class="header">
    <h2>Create New Admin</h2>
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
    </select>

    <label>Hospital</label>
    <select name="hospital_id" required>
        <?php while ($row = $hospitals->fetch_assoc()) { ?>
            <option value="<?php echo $row['HospitalID']; ?>">
                <?php echo htmlspecialchars($row['Name']); ?>
            </option>
        <?php } ?>
    </select>

    <button type="submit">✅ Create Admin</button>
</form>

</body>
</html>
