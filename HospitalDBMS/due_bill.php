<?php
session_start();
include "dbconnect.php";

// ✅ Ensure patient login
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
            JOIN doctordegree dd ON d.PID = dd.PID
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
        $total_after_insurance = $hasInsurance ? $total_before_insurance * 0.75 : $total_before_insurance;

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
    <style>
        body { font-family: Arial, sans-serif; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: center; }
        th { background-color: #f2f2f2; }
        .btn { padding: 5px 10px; text-decoration: none; background: #28a745; color: white; border-radius: 5px; }
        .btn:hover { background: #218838; }
        .buttons { margin: 15px 0; }
        .buttons a { margin-right: 10px; padding: 8px 12px; text-decoration: none; background: #007BFF; color: white; border-radius: 5px; }
        .buttons a:hover { background: #0056b3; }
        .message { margin: 10px 0; padding: 10px; background: #d4edda; color: #155724; border-radius: 5px; }
    </style>
</head>
<body>

<h2>Due Bills</h2>

<div class="buttons">
    <a href="patient.php">Back</a>
    <a href="logout.php">Logout</a>
</div>

<?php if($message) echo "<div class='message'>{$message}</div>"; ?>

<table>
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
    <?php foreach ($bill_data as $bill) { ?>
        <tr>
            <td><?php echo $bill['Bill_ID']; ?></td>
            <td><?php echo $bill['App_ID']; ?></td>
            <td><?php echo $bill['Date']; ?></td>
            <td><?php echo number_format($bill['DoctorFee'], 2); ?></td>
            <td><?php echo number_format($bill['DiagnosisFee'], 2); ?></td>
            <td><?php echo number_format($bill['TotalBeforeInsurance'], 2); ?></td>
            <td><?php echo number_format($bill['TotalAfterInsurance'], 2); ?></td>
            <td><?php echo $bill['Status']; ?></td>
            <td>
                <?php if (strtolower($bill['Status']) == 'unpaid') { ?>
                    <form method="POST" style="margin:0;">
                        <input type="hidden" name="bill_id" value="<?php echo $bill['Bill_ID']; ?>">
                        <button class="btn" type="submit" name="pay_bill">Pay Bill</button>
                    </form>
                <?php } else { echo "-"; } ?>
            </td>
        </tr>
    <?php } ?>
</table>

</body>
</html>
