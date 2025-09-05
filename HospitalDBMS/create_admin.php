<?php
session_start();
include "dbconnect.php";

// Ensure logged in as admin
if (!isset($_SESSION['pid']) || $_SESSION['role'] != "admin") {
    header("Location: login.php");
    exit();
}

// Generate next PID (Admins: start with "A")
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

// Fetch all hospitals
$hospitals = $conn->query("SELECT HospitalID, Name FROM hospital");

// Handle form submit
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light p-4">
<div class="container">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Create New Admin</h2>
        <a href="staff.php" class="btn btn-secondary"> Back</a>
    </div>

    <form method="post" class="card p-4 shadow-sm bg-white">
        <div class="mb-3">
            <label class="form-label">Generated PID</label>
            <input type="text" class="form-control" value="<?php echo $new_pid; ?>" disabled>
        </div>

        <div class="mb-3">
            <label class="form-label">Full Name</label>
            <input type="text" class="form-control" name="name" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Date of Birth</label>
            <input type="date" class="form-control" name="dob" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" class="form-control" name="email" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Phone</label>
            <input type="text" class="form-control" name="phone" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" class="form-control" name="password" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Shift</label>
            <select class="form-select" name="shift" required>
                <option value="Morning">Morning</option>
                <option value="Evening">Evening</option>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Hospital</label>
            <select class="form-select" name="hospital_id" required>
                <?php while ($row = $hospitals->fetch_assoc()): ?>
                    <option value="<?php echo $row['HospitalID']; ?>">
                        <?php echo htmlspecialchars($row['Name']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <button type="submit" class="btn btn-success w-100">Create Admin</button>
    </form>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
