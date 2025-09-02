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
    $degrees_input = $_POST['degrees']; // comma-separated

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
    $deg_array = array_map('trim', explode(',', $degrees_input));
    foreach($deg_array as $deg){
        if(!empty($deg)){
            $insert_degree->bind_param("ss", $pid, $deg);
            $insert_degree->execute();
        }
    }

    $message = "Doctor info updated successfully!";
    // Refresh doctor info
    header("Refresh:0");
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Modify Doctor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Modify Doctor Info</h2>
        <a href="staff.php" class="btn btn-secondary"> Back</a>
    </div>

    <?php if(!empty($message)): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Current Info -->
        <div class="col-md-5">
            <div class="card p-3 shadow-sm bg-white">
                <h4>Current Info</h4>
                <p><strong>Name:</strong> <?= htmlspecialchars($doctor['Name']) ?></p>
                <p><strong>Email:</strong> <?= htmlspecialchars($doctor['email']) ?></p>
                <p><strong>Phone:</strong> <?= htmlspecialchars($doctor['Phone']) ?></p>
                <p><strong>Shift:</strong> <?= htmlspecialchars($doctor['shift']) ?></p>
                <p><strong>Specialization:</strong> <?= htmlspecialchars($doctor['Specialization']) ?></p>
                <p><strong>Degrees:</strong> <?= htmlspecialchars(implode(", ", $degrees)) ?></p>
            </div>
        </div>

        <!-- Modify Form -->
        <div class="col-md-7">
            <div class="card p-4 shadow-sm bg-white">
                <form method="post">
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($doctor['email']) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($doctor['Phone']) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="text" name="password" class="form-control" value="<?= htmlspecialchars($doctor['password']) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Shift</label>
                        <select name="shift" class="form-select" required>
                            <option value="Morning" <?= $doctor['shift']=="Morning"?"selected":"" ?>>Morning</option>
                            <option value="Evening" <?= $doctor['shift']=="Evening"?"selected":"" ?>>Evening</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Degrees (comma separated)</label>
                        <input type="text" name="degrees" class="form-control" value="<?= htmlspecialchars(implode(", ", $degrees)) ?>">
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Update Doctor</button>
                </form>
            </div>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
