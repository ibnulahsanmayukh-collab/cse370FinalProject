<?php
include "dbconnect.php";

if (isset($_POST['register'])) {

    $name = trim($_POST['name']);
    $dob = $_POST['dob'];
    $password = trim($_POST['password']);
    $blood = $_POST['blood'];
    $insurance = isset($_POST['insurance']) ? 1 : 0;
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    // --- Generate next serial PID ---
    $result = $conn->query("SELECT MAX(CAST(SUBSTRING(PID, 2) AS UNSIGNED)) AS max_pid FROM person WHERE PID LIKE 'P%'");
    $row = $result->fetch_assoc();
    $next_num = $row['max_pid'] ? $row['max_pid'] + 1 : 1;
    $pid = "P" . str_pad($next_num, 9, '0', STR_PAD_LEFT); // P000000001, P000000002, etc.

    $conn->begin_transaction();

    try {
        // Insert into person
        $stmt1 = $conn->prepare("INSERT INTO person (PID, Name, DateofBirth, password, email, Phone) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt1->bind_param("ssssss", $pid, $name, $dob, $password, $email, $phone);
        $stmt1->execute();

        // Insert into patient
        $stmt2 = $conn->prepare("INSERT INTO patient (PID, BloodGroup, HasInsurance) VALUES (?, ?, ?)");
        $stmt2->bind_param("ssi", $pid, $blood, $insurance);
        $stmt2->execute();

        $conn->commit();

        $success = "Registration successful! Your Patient ID is <b>$pid</b>.";

    } catch (Exception $e) {
        $conn->rollback();
        $error = "Registration failed: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Patient Registration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
  <div class="row justify-content-center">
    <div class="col-md-6">
      <div class="card shadow-lg">
        <div class="card-body">
          <h3 class="card-title text-center mb-4">Patient Registration</h3>

          <?php if (!empty($success)) : ?>
              <div class="alert alert-success">
                  <?php echo $success; ?>
                  <a href="login.php" class="btn btn-sm btn-primary ms-2">Login</a>
              </div>
          <?php elseif (!empty($error)) : ?>
              <div class="alert alert-danger"><?php echo $error; ?></div>
          <?php endif; ?>

          <form method="post">
            <div class="mb-3">
              <label for="name" class="form-label">Full Name</label>
              <input type="text" class="form-control" id="name" name="name" required>
            </div>

            <div class="mb-3">
              <label for="dob" class="form-label">Date of Birth</label>
              <input type="date" class="form-control" id="dob" name="dob" required>
            </div>

            <div class="mb-3">
              <label for="email" class="form-label">Email</label>
              <input type="email" class="form-control" id="email" name="email" required>
            </div>

            <div class="mb-3">
              <label for="phone" class="form-label">Phone Number</label>
              <input type="tel" class="form-control" id="phone" name="phone" maxlength="14" required>
            </div>

            <div class="mb-3">
              <label for="blood" class="form-label">Blood Group</label>
              <input type="text" class="form-control" id="blood" name="blood" maxlength="3" required>
            </div>

            <div class="form-check mb-3">
              <input type="checkbox" class="form-check-input" id="insurance" name="insurance">
              <label class="form-check-label" for="insurance">Has Insurance</label>
            </div>

            <div class="mb-3">
              <label for="password" class="form-label">Password</label>
              <input type="password" class="form-control" id="password" name="password" required>
            </div>

            <button type="submit" name="register" class="btn btn-success w-100">Register</button>
          </form>

          <div class="text-center mt-3">
            <a href="index.php" class="btn btn-link">Back</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
