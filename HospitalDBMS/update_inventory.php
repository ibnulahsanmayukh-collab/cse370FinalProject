<?php
session_start();
include "dbconnect.php";

// Ensure admin login
if (!isset($_SESSION['pid']) || $_SESSION['role'] !== "admin") {
    header("Location: login.php");
    exit;
}

$admin_id = $_SESSION['pid'];
$msg = "";

// Get hospital ID of the admin
$stmt = $conn->prepare("SELECT hospital_id FROM staff WHERE PID = ?");
$stmt->bind_param("s", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$hospital_id = $result->fetch_assoc()['hospital_id'] ?? null;
$stmt->close();

if (!$hospital_id) {
    die("Hospital not found for this admin.");
}

// Get Item ID
if (!isset($_GET['Item_ID'])) {
    die("No item selected.");
}
$item_id = $_GET['Item_ID'];

// Fetch item details
$stmt = $conn->prepare("
    SELECT i.* 
    FROM inventory i
    JOIN manages m ON i.Item_ID = m.Item_ID
    JOIN staff s ON m.PID = s.PID
    WHERE i.Item_ID = ? AND s.hospital_id = ?
");
$stmt->bind_param("ss", $item_id, $hospital_id);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$item) {
    die("Item not found or you don't have permission.");
}

// Handle update
if (isset($_POST['update'])) {
    $name = trim($_POST['Item_name']);
    $qty = intval($_POST['Quantity']);
    $price = intval($_POST['Price']);
    $expiry = $_POST['Expiry_date'];

    $stmt = $conn->prepare("UPDATE inventory SET Item_name=?, Quantity=?, Price=?, Expiry_date=? WHERE Item_ID=?");
    $stmt->bind_param("siiss", $name, $qty, $price, $expiry, $item_id);
    if ($stmt->execute()) {
        $msg = "Item updated successfully!";
        // Refresh updated data
        $stmt->close();
        $stmt = $conn->prepare("SELECT * FROM inventory WHERE Item_ID=?");
        $stmt->bind_param("s", $item_id);
        $stmt->execute();
        $item = $stmt->get_result()->fetch_assoc();
    } else {
        $msg = "Error updating: " . $stmt->error;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Update Item</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4 bg-light">
<div class="container">
    <h1 class="mb-4">Update Inventory Item</h1>
    <div class="mb-3">
        <a href="inventory.php" class="btn btn-primary">Back to Inventory</a>
    </div>

    <?php if($msg): ?>
        <div class="alert alert-info"><?php echo $msg; ?></div>
    <?php endif; ?>

    <form method="POST" class="row g-3">
        <div class="col-md-4">
            <label class="form-label">Item ID</label>
            <input type="text" class="form-control" value="<?php echo htmlspecialchars($item['Item_ID']); ?>" disabled>
        </div>
        <div class="col-md-6">
            <label class="form-label">Item Name</label>
            <input type="text" name="Item_name" class="form-control" value="<?php echo htmlspecialchars($item['Item_name']); ?>" required>
        </div>
        <div class="col-md-3">
            <label class="form-label">Quantity</label>
            <input type="number" name="Quantity" class="form-control" value="<?php echo htmlspecialchars($item['Quantity']); ?>" required>
        </div>
        <div class="col-md-3">
            <label class="form-label">Price</label>
            <input type="number" name="Price" class="form-control" value="<?php echo htmlspecialchars($item['Price']); ?>" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">Expiry Date</label>
            <input type="date" name="Expiry_date" class="form-control" value="<?php echo htmlspecialchars($item['Expiry_date']); ?>" required>
        </div>
        <div class="col-md-12 d-grid">
            <button type="submit" name="update" class="btn btn-success">Update Item</button>
        </div>
    </form>
</div>
</body>
</html>
