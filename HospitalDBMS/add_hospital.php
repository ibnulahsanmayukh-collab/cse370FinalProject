<?php
session_start();
include "dbconnect.php";

// Ensure only admin can access
if (!isset($_SESSION['pid']) || $_SESSION['role'] != "admin") {
    header("Location: login.php");
    exit();
}

// Generate next HospitalID (H0001, H0002, etc.)
$result = $conn->query("SELECT HospitalID FROM hospital ORDER BY HospitalID DESC LIMIT 1");
if ($row = $result->fetch_assoc()) {
    $last_id = intval(substr($row['HospitalID'], 1));
    $new_id = "H" . str_pad($last_id + 1, 4, "0", STR_PAD_LEFT);
} else {
    $new_id = "H0001"; // first hospital if table is empty
}

$message = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name   = $_POST['name'];
    $plot   = $_POST['plot'];
    $street = $_POST['street'];
    $area   = $_POST['area'];
    $email  = $_POST['email'];
    $phone  = $_POST['phone'];

    $stmt = $conn->prepare("INSERT INTO hospital (HospitalID, Name, Plot, Street, Area, email, Phone) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssss", $new_id, $name, $plot, $street, $area, $email, $phone);

    if ($stmt->execute()) {
        $message = " Hospital added successfully!";
        // Refresh HospitalID for next entry
        $last_id = intval(substr($new_id, 1));
        $new_id = "H" . str_pad($last_id + 1, 4, "0", STR_PAD_LEFT);
    } else {
        $message = " Error: " . $stmt->error;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Add Hospital</title>
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
            background: #28a745;
            color: white;
            border: none;
            padding: 10px;
            border-radius: 6px;
            cursor: pointer;
        }
        button:hover { background: #218838; }
        .message { font-weight: bold; text-align: center; margin-bottom: 15px; }
        .success { color: green; }
        .error { color: red; }
    </style>
</head>
<body>

<div class="header">
    <a href="manage_hospital.php"> Back</a>
    <h2>Add Hospital</h2>
</div>

<?php if (!empty($message)) {
    $class = strpos($message, "") !== false ? "success" : "error";
    echo "<div class='message $class'>$message</div>";
} ?>

<form method="post">
    <label>Hospital ID</label>
    <input type="text" name="hospital_id" value="<?php echo $new_id; ?>" readonly>

    <label>Name</label>
    <input type="text" name="name" required>

    <label>Plot</label>
    <input type="text" name="plot" required>

    <label>Street</label>
    <input type="text" name="street" required>

    <label>Area</label>
    <input type="text" name="area" required>

    <label>Email</label>
    <input type="email" name="email" required>

    <label>Phone</label>
    <input type="text" name="phone" required>

    <button type="submit"> Add Hospital</button>
</form>

</body>
</html>
