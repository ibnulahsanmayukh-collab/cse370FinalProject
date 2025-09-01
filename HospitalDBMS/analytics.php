<?php
session_start();
include "dbconnect.php";

// Ensure admin is logged in
if (!isset($_SESSION['role']) || $_SESSION['role'] != "admin") {
    header("Location: login.php");
    exit();
}

// Helper functions for month/year
$prev_month_start = date("Y-m-01", strtotime("first day of last month"));
$prev_month_end = date("Y-m-t", strtotime("last day of last month"));
$this_month_start = date("Y-m-01");
$this_month_end = date("Y-m-t");

// Fetch all hospitals
$hospitals_result = $conn->query("SELECT HospitalID, Name FROM hospital");
$hospitals = [];
while ($h = $hospitals_result->fetch_assoc()) {
    $hospitals[] = $h;
}

// Initialize totals
$total_patients_prev = 0;
$total_patients_this = 0;
$total_patients_all = 0;
$total_earning_prev = 0;
$total_earning_this = 0;
$total_earning_all = 0;

// Prepare hospital analytics data
$analytics = [];

foreach ($hospitals as $h) {
    $hid = $h['HospitalID'];

    $periods = [
        'prev' => [$prev_month_start, $prev_month_end],
        'this' => [$this_month_start, $this_month_end],
        'all'  => [null, null]
    ];

    $patients = ['prev'=>0, 'this'=>0, 'all'=>0];
    $earning = ['prev'=>0, 'this'=>0, 'all'=>0];

    foreach ($periods as $key => $dates) {
        $date_condition = ($dates[0] && $dates[1]) ? "AND a.Date BETWEEN '{$dates[0]}' AND '{$dates[1]}'" : "";

        $sql = "
            SELECT a.App_ID, doc.PID as DoctorID
            FROM appointment a
            JOIN doctor doc ON a.Doctor_ID = doc.PID
            JOIN staff s ON doc.PID = s.PID
            WHERE s.hospital_id = ? AND a.Status='Complete' $date_condition
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $hid);
        $stmt->execute();
        $apps_result = $stmt->get_result();

        $patients[$key] = $apps_result->num_rows;

        while ($app = $apps_result->fetch_assoc()) {
            $appid = $app['App_ID'];
            $doctorid = $app['DoctorID'];

            // Check if bill is paid
            $bill_stmt = $conn->prepare("SELECT COUNT(*) as paidcount FROM bill WHERE App_ID=? AND Status='Paid'");
            $bill_stmt->bind_param("s", $appid);
            $bill_stmt->execute();
            $paidcount = $bill_stmt->get_result()->fetch_assoc()['paidcount'];
            $bill_stmt->close();

            if ($paidcount > 0) {
                // Count diagnoses
                $diag_stmt = $conn->prepare("SELECT COUNT(*) as diagcount FROM app_diag WHERE App_ID=?");
                $diag_stmt->bind_param("s", $appid);
                $diag_stmt->execute();
                $diagcount = $diag_stmt->get_result()->fetch_assoc()['diagcount'];
                $diag_stmt->close();

                // Count doctor degrees
                $deg_stmt = $conn->prepare("SELECT COUNT(*) as degcount FROM doctordegree WHERE PID=?");
                $deg_stmt->bind_param("s", $doctorid);
                $deg_stmt->execute();
                $degcount = $deg_stmt->get_result()->fetch_assoc()['degcount'];
                $deg_stmt->close();

                $earning[$key] += ($diagcount*100 + $degcount*200);
            }
        }

        $stmt->close();

        // Add to totals
        if ($key == 'prev') { $total_patients_prev += $patients[$key]; $total_earning_prev += $earning[$key]; }
        if ($key == 'this') { $total_patients_this += $patients[$key]; $total_earning_this += $earning[$key]; }
        if ($key == 'all') { $total_patients_all += $patients[$key]; $total_earning_all += $earning[$key]; }
    }

    $analytics[] = [
        'HospitalID' => $h['HospitalID'],
        'HospitalName' => $h['Name'],
        'patients' => $patients,
        'earning' => $earning
    ];
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Hospital Analytics</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f6fa; }
        .header { margin-bottom: 20px; }
        .back-btn {
            background: #6c757d; color: white; padding: 8px 12px;
            border-radius: 6px; text-decoration: none;
        }
        .back-btn:hover { background: #5a6268; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: center; }
        th { background: #007BFF; color: white; }
        tfoot td { font-weight: bold; background: #e9ecef; }
    </style>
</head>
<body>

<div class="header">
    <a href="admin.php" class="back-btn">← Back</a>
    <h2>Hospital Analytics</h2>
</div>

<table>
    <tr>
        <th>Hospital ID</th>
        <th>Hospital Name</th>
        <th>Patients Served (Prev Month)</th>
        <th>Patients Served (This Month)</th>
        <th>Patients Served (All Time)</th>
        <th>Total Earning (Prev Month)</th>
        <th>Total Earning (This Month)</th>
        <th>Total Earning (All Time)</th>
    </tr>

    <?php foreach ($analytics as $row): ?>
    <tr>
        <td><?= $row['HospitalID']; ?></td>
        <td><?= $row['HospitalName']; ?></td>
        <td><?= $row['patients']['prev']; ?></td>
        <td><?= $row['patients']['this']; ?></td>
        <td><?= $row['patients']['all']; ?></td>
        <td><?= $row['earning']['prev']; ?></td>
        <td><?= $row['earning']['this']; ?></td>
        <td><?= $row['earning']['all']; ?></td>
    </tr>
    <?php endforeach; ?>

    <tfoot>
        <tr>
            <td colspan="2">Total for All Hospitals</td>
            <td><?= $total_patients_prev; ?></td>
            <td><?= $total_patients_this; ?></td>
            <td><?= $total_patients_all; ?></td>
            <td><?= $total_earning_prev; ?></td>
            <td><?= $total_earning_this; ?></td>
            <td><?= $total_earning_all; ?></td>
        </tr>
    </tfoot>
</table>

</body>
</html>
