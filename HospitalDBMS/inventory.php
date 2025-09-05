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

// Handle Add Item
if (isset($_POST['save'])) {
    $id = trim($_POST['Item_ID']);
    $name = trim($_POST['Item_name']);
    $qty = intval($_POST['Quantity']);
    $price = intval($_POST['Price']);
    $expiry = $_POST['Expiry_date'];

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("INSERT INTO inventory (Item_ID, Item_name, Quantity, Price, Expiry_date) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssiss", $id, $name, $qty, $price, $expiry);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("INSERT INTO manages (PID, Item_ID) VALUES (?, ?)");
        $stmt->bind_param("ss", $admin_id, $id);
        $stmt->execute();
        $stmt->close();

        $conn->commit();
        $msg = "Item added successfully!";
    } catch (Exception $e) {
        $conn->rollback();
        $msg = "Error: " . $e->getMessage();
    }
}

// Handle Delete Item
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];

    $stmt = $conn->prepare("
        DELETE i FROM inventory i
        JOIN manages m ON i.Item_ID = m.Item_ID
        JOIN staff s ON m.PID = s.PID
        WHERE i.Item_ID = ? AND s.hospital_id = ? AND m.PID = ?
    ");
    $stmt->bind_param("sss", $id, $hospital_id, $admin_id);
    $msg = $stmt->execute() ? "Item deleted successfully!" : "Error: " . $stmt->error;
    $stmt->close();
}

// Fetch Inventory items for this hospital
$stmt = $conn->prepare("
    SELECT i.*
    FROM inventory i
    JOIN manages m ON i.Item_ID = m.Item_ID
    JOIN staff s ON m.PID = s.PID
    WHERE s.hospital_id = ?
");
$stmt->bind_param("s", $hospital_id);
$stmt->execute();
$inventory = $stmt->get_result();
$stmt->close();

$today = date('Y-m-d');
$soon = date('Y-m-d', strtotime('+7 days'));
?>
<!DOCTYPE html>
<html>
<head>
    <title>Inventory Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .status-box {
            width: 20px;
            height: 20px;
            display: inline-block;
            border-radius: 4px;
        }
        .green { background-color: #28a745; }
        .yellow { background-color: #ffc107; }
        .red { background-color: #dc3545; }
        .msg { margin: 10px 0; font-weight: bold; }
    </style>
</head>
<body class="p-4 bg-light">
<div class="container">
    <h1 class="mb-4">Hospital Inventory Management</h1>
    <div class="mb-3">
        <a href="admin.php" class="btn btn-primary me-2">Back to Dashboard</a>
        <a href="logout.php" class="btn btn-primary">Logout</a>
    </div>

    <?php if($msg): ?>
        <div class="alert alert-primary msg"><?php echo $msg; ?></div>
    <?php endif; ?>

    <!-- Add Form -->
    <form method="POST" class="mb-4 row g-2">
        <div class="col-md-2">
            <input type="text" name="Item_ID" class="form-control" placeholder="Item ID" required>
        </div>
        <div class="col-md-3">
            <input type="text" name="Item_name" class="form-control" placeholder="Item Name" maxlength="50" required>
        </div>
        <div class="col-md-2">
            <input type="number" name="Quantity" class="form-control" placeholder="Quantity" required>
        </div>
        <div class="col-md-2">
            <input type="number" name="Price" class="form-control" placeholder="Price" required>
        </div>
        <div class="col-md-3">
            <input type="date" name="Expiry_date" class="form-control" required>
        </div>
        <div class="col-md-2 d-grid">
            <button type="submit" name="save" class="btn btn-success">Add Item</button>
        </div>
    </form>

    <!-- Inventory Table -->
    <table class="table table-bordered table-striped">
        <thead class="table-dark">
            <tr>
                <th>Status</th>
                <th>Item ID</th>
                <th>Name</th>
                <th>Quantity</th>
                <th>Price</th>
                <th>Expiry Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($row = $inventory->fetch_assoc()):
            $statusClass = "green";
            if (strtotime($row['Expiry_date']) < strtotime($today)) {
                $statusClass = "red";
            } elseif (strtotime($row['Expiry_date']) <= strtotime($soon)) {
                $statusClass = "yellow";
            }
        ?>
            <tr>
                <td><span class="status-box <?php echo $statusClass; ?>"></span></td>
                <td><?php echo htmlspecialchars($row['Item_ID']); ?></td>
                <td><?php echo htmlspecialchars($row['Item_name']); ?></td>
                <td><?php echo htmlspecialchars($row['Quantity']); ?></td>
                <td><?php echo htmlspecialchars($row['Price']); ?></td>
                <td><?php echo htmlspecialchars($row['Expiry_date']); ?></td>
                <td>
                    <a href="update_inventory.php?Item_ID=<?php echo urlencode($row['Item_ID']); ?>" 
                       class="btn btn-warning btn-sm">Update</a>
                    <a href="?delete=<?php echo $row['Item_ID']; ?>" 
                       class="btn btn-danger btn-sm"
                       onclick="return confirm('Delete this item?')">Delete</a>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
