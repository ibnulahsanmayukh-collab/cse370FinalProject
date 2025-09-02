<?php
session_start();
include "dbconnect.php";

// Ensure admin is logged in
if (!isset($_SESSION['role']) || $_SESSION['role'] != "admin") {
    header("Location: login.php");
    exit();
}

// Helper dates
$prev_month_start = date("Y-m-01", strtotime("first day of last month"));
$prev_month_end = date("Y-m-t", strtotime("last day of last month"));
$this_month_start = date("Y-m-01");
$this_month_end = date("Y-m-t");

// Fetch all hospitals
$hospitals = $conn->query("SELECT HospitalID, Name FROM hospital");

// Totals
$total_patients_prev = 0;
$total_patients_this = 0;
$total_patients_all = 0;
$total_earning_prev = 0;
$total_earning_this = 0;
$total_earning_all = 0;

// Function to calculate earnings for a single appointment
function calc_earning($conn, $appid, $doctorid, $hasInsurance) {
    // Doctor fee
    $stmt = $conn->prepare("SELECT COUNT(*) as degree_count FROM doctordegree WHERE PID=?");
    $stmt->bind_param("s", $doctorid);
    $stmt->execute();
    $degcount = $stmt->get_result()->fetch_assoc()['degree_count'];
    $stmt->close();
    $doctor_fee = $degcount * 200;

    // Diagnosis fee
    $stmt = $conn->prepare("SELECT COUNT(*) as diag_count FROM app_diag WHERE App_ID=?");
    $stmt->bind_param("s", $appid);
    $stmt->execute();
    $diagcount = $stmt->get_result()->fetch_assoc()['diag_count'];
    $stmt->close();
    $diagnosis_fee = $diagcount * 100;

    $total_before_insurance = $doctor_fee + $diagnosis_fee;
    $total_after_insurance = $hasInsurance ? $total_before_insurance * 0.75 : $total_before_insurance;

    return $total_after_insurance;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Hospital Analytics</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f6fa; }
        .header { margin-bottom: 20px; }
        .back-btn { background: #6c757d; color: white; padding: 8px 12px; border-radius: 6px; text-decoration: none; }
        .back-btn:hover { background: #5a6268; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: center; }
        th { background: #007BFF; color: white; }
        tfoot td { font-weight: bold; background: #e9ecef; }
    </style>
</head>
<body>

<div class="header">
    <a href="admin.php" class="back-btn"> Back</a>
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

<?php
while ($h = $hospitals->fetch_assoc()) {
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

        // Fetch all completed appointments for hospital
        $sql = "
            SELECT a.App_ID, a.Patient_ID, d.PID as DoctorID, p.HasInsurance
            FROM appointment a
            JOIN doctor d ON a.Doctor_ID = d.PID
            JOIN staff s ON d.PID = s.PID
            JOIN patient p ON a.Patient_ID = p.PID
            WHERE s.hospital_id = ? AND a.Status='Complete' $date_condition
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $hid);
        $stmt->execute();
        $apps_result = $stmt->get_result();

        $patients[$key] = $apps_result->num_rows;

        while ($app = $apps_result->fetch_assoc()) {
            $earning[$key] += calc_earning($conn, $app['App_ID'], $app['DoctorID'], $app['HasInsurance']);
        }

        $stmt->close();

        // Add to totals
        if ($key == 'prev') { $total_patients_prev += $patients[$key]; $total_earning_prev += $earning[$key]; }
        if ($key == 'this') { $total_patients_this += $patients[$key]; $total_earning_this += $earning[$key]; }
        if ($key == 'all') { $total_patients_all += $patients[$key]; $total_earning_all += $earning[$key]; }
    }

    echo "<tr>
        <td>{$h['HospitalID']}</td>
        <td>{$h['Name']}</td>
        <td>{$patients['prev']}</td>
        <td>{$patients['this']}</td>
        <td>{$patients['all']}</td>
        <td>".number_format($earning['prev'],2)."</td>
        <td>".number_format($earning['this'],2)."</td>
        <td>".number_format($earning['all'],2)."</td>
    </tr>";
}
?>

    <tfoot>
        <tr>
            <td colspan="2">Total for All Hospitals</td>
            <td><?php echo $total_patients_prev; ?></td>
            <td><?php echo $total_patients_this; ?></td>
            <td><?php echo $total_patients_all; ?></td>
            <td><?php echo number_format($total_earning_prev,2); ?></td>
            <td><?php echo number_format($total_earning_this,2); ?></td>
            <td><?php echo number_format($total_earning_all,2); ?></td>
        </tr>
    </tfoot>
</table>

</body>
</html>
