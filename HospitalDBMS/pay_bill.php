<?php
session_start();
include "dbconnect.php";

if (!isset($_SESSION['pid']) || $_SESSION['role'] != "patient") {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['bill_id'])) {
    die("Bill ID missing.");
}

$bill_id = $_GET['bill_id'];
$pid = $_SESSION['pid'];

$conn->begin_transaction();
try {
    // Check if bill belongs to this patient and is unpaid
    $stmt = $conn->prepare("SELECT b.Status, a.Patient_ID FROM bill b
                            JOIN appointment a ON b.App_ID = a.App_ID
                            WHERE b.Bill_ID=?");
    $stmt->bind_param("s", $bill_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) throw new Exception("Bill not found.");
    if ($row['Patient_ID'] !== $pid) throw new Exception("Unauthorized.");
    if (strtolower($row['Status']) != 'unpaid') throw new Exception("Bill is already paid.");

    // Update bill status
    $stmt = $conn->prepare("UPDATE bill SET Status='Paid' WHERE Bill_ID=?");
    $stmt->bind_param("s", $bill_id);
    $stmt->execute();
    $stmt->close();

    $conn->commit();
    echo "<script>alert('Bill successfully paid!'); window.location.href='due_bill.php';</script>";
} catch (Exception $e) {
    $conn->rollback();
    die("Payment failed: " . $e->getMessage());
}
?>
