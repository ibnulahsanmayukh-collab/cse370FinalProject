<?php
session_start();
include "dbconnect.php";

// Ensure patient is logged in
if (!isset($_SESSION['pid']) || $_SESSION['role'] != "patient") {
    header("Location: login.php");
    exit();
}

$patient_id = $_SESSION['pid'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);

    $stmt = $conn->prepare("UPDATE person SET email = ?, Phone = ? WHERE PID = ?");
    $stmt->bind_param("sss", $email, $phone, $patient_id);
    $stmt->execute();
    $stmt->close();

    header("Location: patient.php");
    exit();
}

// Fetch current info
$stmt = $conn->prepare("SELECT email, Phone FROM person WHERE PID = ?");
$stmt->bind_param("s", $patient_id);
$stmt->execute();
$result = $stmt->get_result();
$patient = $result->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Modify Personal Info</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Update Contact Information</h2>
        <a href="patient.php" class="btn btn-secondary"> Back</a>
    </div>

    <div class="card p-4 shadow-sm mx-auto" style="max-width: 500px;">
        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($patient['email']); ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Phone</label>
                <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($patient['Phone']); ?>" required>
            </div>

            <button type="submit" class="btn btn-primary w-100">Save</button>
        </form>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
