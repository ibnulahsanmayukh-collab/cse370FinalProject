<?php
session_start();
include "dbconnect.php";

if (!isset($_SESSION['pid']) || $_SESSION['role'] != "patient") {
    header("Location: login.php");
    exit();
}

$pid = $_SESSION['pid'];
$message = "";

// Handle bill payment
if (isset($_POST['pay_bill'])) {
    $bill_id = $_POST['bill_id'];
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("UPDATE bill SET Status='Paid' WHERE Bill_ID=? AND Status='Unpaid'");
        $stmt->bind_param("s", $bill_id);
        $stmt->execute();
        if ($stmt->affected_rows > 0) {
            $message = "Bill $bill_id successfully paid.";
        } else {
            $message = "Bill $bill_id was already paid or not found.";
        }
        $stmt->close();
        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        die("Failed to pay bill: " . $e->getMessage());
    }
}

// Fetch bills
$conn->begin_transaction();
try {
    $sql = "SELECT b.Bill_ID, b.Status, a.App_ID, a.Date, p.HasInsurance, d.PID as DoctorID
            FROM bill b
            JOIN appointment a ON b.App_ID = a.App_ID
            JOIN doctor d ON a.Doctor_ID = d.PID
            JOIN patient p ON a.Patient_ID = p.PID
            WHERE a.Patient_ID = ?
            GROUP BY b.Bill_ID";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $pid);
    $stmt->execute();
    $bills = $stmt->get_result();
    $stmt->close();

    $bill_data = [];
    while ($row = $bills->fetch_assoc()) {
        $app_id = $row['App_ID'];
        $hasInsurance = $row['HasInsurance'];

        // Count doctor degrees
        $stmt = $conn->prepare("SELECT COUNT(*) as degree_count FROM doctordegree WHERE PID=?");
        $stmt->bind_param("s", $row['DoctorID']);
        $stmt->execute();
        $doctor_fee = $stmt->get_result()->fetch_assoc()['degree_count'] * 200;
        $stmt->close();

        // Count diagnostics
        $stmt = $conn->prepare("SELECT COUNT(*) as diag_count FROM app_diag WHERE App_ID=?");
        $stmt->bind_param("s", $app_id);
        $stmt->execute();
        $diag_count = $stmt->get_result()->fetch_assoc()['diag_count'];
        $stmt->close();
        $diagnosis_fee = $diag_count * 100;

        $total_before_insurance = $doctor_fee + $diagnosis_fee;
        $total_after_insurance = $hasInsurance ? $total_before_insurance * 0.75 : null; // null if no insurance

        $row['DoctorFee'] = $doctor_fee;
        $row['DiagnosisFee'] = $diagnosis_fee;
        $row['TotalBeforeInsurance'] = $total_before_insurance;
        $row['TotalAfterInsurance'] = $total_after_insurance;

        $bill_data[] = $row;
    }

    $conn->commit();
} catch (Exception $e) {
    $conn->rollback();
    die("Failed to fetch bills: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Due Bills</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light p-4">

<div class="container">

    <h2>Due Bills</h2>

    <div class="mb-3">
        <a href="patient.php" class="btn btn-primary me-2">Back</a>
        <a href="logout.php" class="btn btn-primary">Logout</a>
    </div>

    <?php if($message): ?>
        <div class="alert alert-primary"><?php echo $message; ?></div>
    <?php endif; ?>

    <table class="table table-bordered table-striped">
        <thead class="table-dark">
            <tr>
                <th>Bill ID</th>
                <th>Appointment ID</th>
                <th>Appointment Date</th>
                <th>Doctor Fee</th>
                <th>Diagnosis Fee</th>
                <th>Total Before Insurance</th>
                <th>Total After Insurance</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($bill_data as $bill): ?>
            <tr>
                <td><?php echo $bill['Bill_ID']; ?></td>
                <td><?php echo $bill['App_ID']; ?></td>
                <td><?php echo $bill['Date']; ?></td>
                <td><?php echo number_format($bill['DoctorFee'], 2); ?></td>
                <td><?php echo number_format($bill['DiagnosisFee'], 2); ?></td>
                <td><?php echo number_format($bill['TotalBeforeInsurance'], 2); ?></td>
                <td>
                    <?php 
                        echo $bill['TotalAfterInsurance'] !== null 
                             ? number_format($bill['TotalAfterInsurance'], 2) 
                             : "N/A"; 
                    ?>
                </td>
                <td><?php echo $bill['Status']; ?></td>
                <td>
                    <?php if (strtolower($bill['Status']) == 'unpaid'): ?>
                        <form method="POST" style="margin:0;">
                            <input type="hidden" name="bill_id" value="<?php echo $bill['Bill_ID']; ?>">
                            <button class="btn btn-primary btn-sm" type="submit" name="pay_bill">Pay Bill</button>
                        </form>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
