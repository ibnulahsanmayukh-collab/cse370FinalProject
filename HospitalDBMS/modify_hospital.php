<?php
session_start();
include "dbconnect.php";

// Ensure only admin can access
if (!isset($_SESSION['pid']) || $_SESSION['role'] != "admin") {
    header("Location: login.php");
    exit();
}

// Check if hospital ID is provided
if (!isset($_GET['id'])) {
    header("Location: manage_hospital.php");
    exit();
}

$hospital_id = $_GET['id'];

// Fetch hospital details
$stmt = $conn->prepare("SELECT * FROM hospital WHERE HospitalID = ?");
$stmt->bind_param("s", $hospital_id);
$stmt->execute();
$result = $stmt->get_result();

if (!$hospital = $result->fetch_assoc()) {
    die("Hospital not found.");
}
$stmt->close();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $plot   = $_POST['plot'];
    $street = $_POST['street'];
    $area   = $_POST['area'];
    $email  = $_POST['email'];
    $phone  = $_POST['phone'];

    $stmt = $conn->prepare("UPDATE hospital SET Plot=?, Street=?, Area=?, email=?, Phone=? WHERE HospitalID=?");
    $stmt->bind_param("ssssss", $plot, $street, $area, $email, $phone, $hospital_id);

    if ($stmt->execute()) {
        $stmt->close();
        header("Location: manage_hospital.php?msg=Hospital updated successfully");
        exit();
    } else {
        $error = "Error updating hospital: " . $stmt->error;
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Modify Hospital</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Modify Hospital</h2>
        <a href="manage_hospital.php" class="btn btn-secondary"> Back</a>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="post" class="card p-4 shadow-sm bg-white mx-auto" style="max-width:600px;">
        <div class="mb-3">
            <label class="form-label">Hospital ID</label>
            <input type="text" class="form-control" value="<?php echo htmlspecialchars($hospital['HospitalID']); ?>" readonly>
        </div>

        <div class="mb-3">
            <label class="form-label">Name</label>
            <input type="text" class="form-control" value="<?php echo htmlspecialchars($hospital['Name']); ?>" readonly>
        </div>

        <div class="mb-3">
            <label class="form-label">Plot</label>
            <input type="text" name="plot" class="form-control" value="<?php echo htmlspecialchars($hospital['Plot']); ?>" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Street</label>
            <input type="text" name="street" class="form-control" value="<?php echo htmlspecialchars($hospital['Street']); ?>" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Area</label>
            <input type="text" name="area" class="form-control" value="<?php echo htmlspecialchars($hospital['Area']); ?>" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($hospital['email']); ?>" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Phone</label>
            <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($hospital['Phone']); ?>" required>
        </div>

        <button type="submit" class="btn btn-primary w-100">Update Hospital</button>
    </form>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
