<?php
session_start();
include "dbconnect.php";

if (!isset($_SESSION['pid']) || $_SESSION['role'] != "admin") {
    header("Location: login.php");
    exit;
}

$msg = "";

// Handle create staff
if (isset($_POST['create_staff'])) {
    $pid = trim($_POST['PID']);
    $name = trim($_POST['Name']);
    $dob = $_POST['DateofBirth'];
    $email = trim($_POST['email']);
    $phone = trim($_POST['Phone']);
    $password = trim($_POST['password']);
    $role = $_POST['role']; // admin or doctor
    $hospital_id = trim($_POST['hospital_id']);
    $shift = trim($_POST['shift']);
    $specialization = trim($_POST['specialization']);
    $degrees = $_POST['degrees']; // array of degrees

    // Check PID uniqueness
    $check = $conn->prepare("SELECT PID FROM person WHERE PID=?");
    $check->bind_param("s", $pid);
    $check->execute();
    $check->store_result();
    
    if ($check->num_rows > 0) {
        $msg = "PID already exists!";
    } else {
        // Insert into person
        $stmt = $conn->prepare("INSERT INTO person (PID, Name, DateofBirth, email, Phone, password) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $pid, $name, $dob, $email, $phone, $password);
        $stmt->execute();

        // Insert into staff
        $stmt2 = $conn->prepare("INSERT INTO staff (PID, shift, hospital_id) VALUES (?, ?, ?)");
        $stmt2->bind_param("sss", $pid, $shift, $hospital_id);
        $stmt2->execute();

        if ($role == "doctor") {
            // Insert into doctor
            $stmt3 = $conn->prepare("INSERT INTO doctor (PID, Specialization) VALUES (?, ?)");
            $stmt3->bind_param("ss", $pid, $specialization);
            $stmt3->execute();

            // Insert degrees
            foreach($degrees as $deg) {
                if (!empty($deg)) {
                    $stmt4 = $conn->prepare("INSERT INTO doctordegree (PID, Degrees) VALUES (?, ?)");
                    $stmt4->bind_param("ss", $pid, $deg);
                    $stmt4->execute();
                }
            }
        }

        $msg = ucfirst($role) . " created successfully!";
    }
}

// Handle delete staff
if (isset($_GET['delete'])) {
    $pid = $_GET['delete'];
    $conn->query("DELETE FROM staff WHERE PID='$pid'");
    $msg = "Staff deleted successfully!";
}

// Fetch staff list
$staff_list = $conn->query("
    SELECT s.PID, p.Name AS StaffName, s.shift, s.hospital_id, 
           IF(d.PID IS NULL, 'Admin', 'Doctor') AS Role, d.Specialization
    FROM staff s
    JOIN person p ON s.PID = p.PID
    LEFT JOIN doctor d ON s.PID = d.PID
    ORDER BY p.Name ASC
");

// Fetch hospitals for dropdown
$hospitals = $conn->query("SELECT HospitalID, Name FROM hospital");

?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Staff</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
</head>
<body class="p-4">
<div class="container">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Staff Management</h1>
        <a href="admin.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>

    <?php if($msg): ?>
        <div class="alert alert-info"><?php echo $msg; ?></div>
    <?php endif; ?>

    <!-- Create Staff Form -->
    <h2>Create Staff</h2>
    <form method="POST" class="row g-3 mb-5">
        <div class="col-md-2">
            <input type="text" name="PID" class="form-control" placeholder="PID" required>
        </div>
        <div class="col-md-3">
            <input type="text" name="Name" class="form-control" placeholder="Name" required>
        </div>
        <div class="col-md-2">
            <input type="date" name="DateofBirth" class="form-control" required>
        </div>
        <div class="col-md-3">
            <input type="email" name="email" class="form-control" placeholder="Email">
        </div>
        <div class="col-md-2">
            <input type="text" name="Phone" class="form-control" placeholder="Phone">
        </div>
        <div class="col-md-2">
            <input type="password" name="password" class="form-control" placeholder="Password" required>
        </div>
        <div class="col-md-2">
            <select name="role" class="form-select" required>
                <option value="">Role</option>
                <option value="admin">Admin</option>
                <option value="doctor">Doctor</option>
            </select>
        </div>
        <div class="col-md-2">
            <select name="hospital_id" class="form-select" required>
                <option value="">Hospital</option>
                <?php while($h = $hospitals->fetch_assoc()): ?>
                    <option value="<?php echo $h['HospitalID']; ?>"><?php echo $h['Name']; ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="col-md-2">
            <input type="text" name="shift" class="form-control" placeholder="Shift" required>
        </div>
        <div class="col-md-3">
            <input type="text" name="specialization" class="form-control" placeholder="Specialization (Doctor)">
        </div>
        <div class="col-md-5">
            <input type="text" name="degrees[]" class="form-control mb-2" placeholder="Degree 1 (Doctor)">
            <input type="text" name="degrees[]" class="form-control mb-2" placeholder="Degree 2">
            <input type="text" name="degrees[]" class="form-control mb-2" placeholder="Degree 3">
        </div>
        <div class="col-md-2 d-grid">
            <button type="submit" name="create_staff" class="btn btn-success">Create Staff</button>
        </div>
    </form>

    <!-- Staff Table -->
    <h2>Existing Staff</h2>
    <table class="table table-striped table-bordered">
        <thead class="table-dark">
            <tr>
                <th>PID</th>
                <th>Name</th>
                <th>Shift</th>
                <th>Hospital</th>
                <th>Role</th>
                <th>Specialization</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php while($s = $staff_list->fetch_assoc()): ?>
            <tr>
                <td><?php echo $s['PID']; ?></td>
                <td><?php echo $s['StaffName']; ?></td>
                <td><?php echo $s['shift']; ?></td>
                <td><?php echo $s['hospital_id']; ?></td>
                <td><?php echo $s['Role']; ?></td>
                <td><?php echo $s['Specialization']; ?></td>
                <td>
                    <a href="edit_staff.php?pid=<?php echo $s['PID']; ?>" class="btn btn-sm btn-primary">Edit</a>
                    <a href="?delete=<?php echo $s['PID']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this staff?')">Delete</a>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>

</div>

<script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>
