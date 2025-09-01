<?php
session_start();
include "dbconnect.php";

if (!isset($_SESSION['pid']) || $_SESSION['role'] != "admin") {
    header("Location: login.php");
    exit;
}

$msg = "";

// Add Item
if (isset($_POST['save'])) {
    $id = trim($_POST['Item_ID']);
    $name = trim($_POST['Item_name']);
    $qty = intval($_POST['Quantity']);
    $expiry = $_POST['Expiry_date'];

    $stmt = $conn->prepare("INSERT INTO inventory (Item_ID, Item_name, Quantity, Expiry_date) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssis", $id, $name, $qty, $expiry);

    $msg = $stmt->execute() ? "Item added successfully!" : "Error: " . $stmt->error;
}

// Update Item
if (isset($_POST['update'])) {
    $id = trim($_POST['Item_ID']);
    $name = trim($_POST['Item_name']);
    $qty = intval($_POST['Quantity']);
    $expiry = $_POST['Expiry_date'];

    $stmt = $conn->prepare("UPDATE inventory SET Item_name=?, Quantity=?, Expiry_date=? WHERE Item_ID=?");
    $stmt->bind_param("siss", $name, $qty, $expiry, $id);

    $msg = $stmt->execute() ? "Item updated successfully!" : "Error: " . $stmt->error;
}

// Delete Item
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM inventory WHERE Item_ID=?");
    $stmt->bind_param("s", $id);

    $msg = $stmt->execute() ? "Item deleted successfully!" : "Error: " . $stmt->error;
}

// Fetch Inventory
$result = $conn->query("SELECT * FROM inventory ORDER BY Expiry_date ASC");
$today = date('Y-m-d');
$soon = date('Y-m-d', strtotime('+7 days'));
?>
<!DOCTYPE html>
<html>
<head>
    <title>Inventory Management</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <style>
        .expired { background-color: #ffcccc; }
        .soon { background-color: #fff3cd; }
        .msg { margin: 10px 0; font-weight: bold; }
    </style>
</head>
<body class="p-4">

<div class="container">
    <h1 class="mb-4">Hospital Inventory Management</h1>
    <div class="mb-3">
        <a href="admin.php" class="btn btn-secondary">Back to Dashboard</a>
        <a href="logout.php" class="btn btn-danger">Logout</a>
    </div>

    <?php if($msg): ?>
        <div class="alert alert-success msg"><?php echo $msg; ?></div>
    <?php endif; ?>

    <!-- Add / Update Form -->
    <form method="POST" class="mb-4 row g-2">
        <div class="col-md-2">
            <input type="text" name="Item_ID" class="form-control" placeholder="Item ID (8 chars)" maxlength="8" required>
        </div>
        <div class="col-md-3">
            <input type="text" name="Item_name" class="form-control" placeholder="Item Name" maxlength="50" required>
        </div>
        <div class="col-md-2">
            <input type="number" name="Quantity" class="form-control" placeholder="Quantity" required>
        </div>
        <div class="col-md-3">
            <input type="date" name="Expiry_date" class="form-control" required>
        </div>
        <div class="col-md-2 d-grid">
            <button type="submit" name="save" class="btn btn-primary mb-2">Add Item</button>
            <button type="submit" name="update" class="btn btn-warning">Update Item</button>
        </div>
    </form>

    <!-- Inventory Table -->
    <table class="table table-bordered table-striped">
        <thead class="table-dark">
            <tr>
                <th>Item ID</th>
                <th>Name</th>
                <th>Quantity</th>
                <th>Expiry Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($row = $result->fetch_assoc()): 
            $class = "";
            if ($row['Expiry_date'] < $today) $class = "expired";
            elseif ($row['Expiry_date'] <= $soon) $class = "soon";
        ?>
            <tr class="<?php echo $class; ?>">
                <td><?php echo $row['Item_ID']; ?></td>
                <td><?php echo $row['Item_name']; ?></td>
                <td><?php echo $row['Quantity']; ?></td>
                <td><?php echo $row['Expiry_date']; ?></td>
                <td>
                    <a href="?delete=<?php echo $row['Item_ID']; ?>" 
                       class="btn btn-sm btn-danger"
                       onclick="return confirm('Delete this item?')">Delete</a>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>

<script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>
