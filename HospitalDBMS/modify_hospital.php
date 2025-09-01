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
        input {
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
        .error { color: red; text-align: center; margin-bottom: 10px; }
    </style>
</head>
<body>

<div class="header">
    <a href="manage_hospital.php">⬅ Back</a>
    <h2>Modify Hospital</h2>
</div>

<?php if (!empty($error)) echo "<div class='error'>$error</div>"; ?>

<form method="post">
    <label>Hospital ID</label>
    <input type="text" value="<?php echo htmlspecialchars($hospital['HospitalID']); ?>" readonly>

    <label>Name</label>
    <input type="text" value="<?php echo htmlspecialchars($hospital['Name']); ?>" readonly>

    <label>Plot</label>
    <input type="text" name="plot" value="<?php echo htmlspecialchars($hospital['Plot']); ?>" required>

    <label>Street</label>
    <input type="text" name="street" value="<?php echo htmlspecialchars($hospital['Street']); ?>" required>

    <label>Area</label>
    <input type="text" name="area" value="<?php echo htmlspecialchars($hospital['Area']); ?>" required>

    <label>Email</label>
    <input type="email" name="email" value="<?php echo htmlspecialchars($hospital['email']); ?>" required>

    <label>Phone</label>
    <input type="text" name="phone" value="<?php echo htmlspecialchars($hospital['Phone']); ?>" required>

    <button type="submit">Change</button>
</form>

</body>
</html>
