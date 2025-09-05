<?php
session_start();
include "dbconnect.php";

if (isset($_POST['login'])) {
    $pid = $_POST['pid'];
    $password = $_POST['password'];

    $sql = "SELECT PID, password FROM person WHERE PID=? AND password=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $pid, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $_SESSION['pid'] = $pid;

        // Detect role
        if ($conn->query("SELECT 1 FROM doctor WHERE PID='$pid'")->num_rows > 0) {
            $_SESSION['role'] = "doctor";
            header("Location: doctor.php");
        } elseif ($conn->query("SELECT 1 FROM patient WHERE PID='$pid'")->num_rows > 0) {
            $_SESSION['role'] = "patient";
            header("Location: patient.php");
        } else {
            $_SESSION['role'] = "admin";
            header("Location: admin.php");
        }
        exit;
    } else {
        $error = "Invalid PID or password.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
  <div class="row justify-content-center">
    <div class="col-md-4">
      <div class="card shadow-lg">
        <div class="card-body">
          <h3 class="card-title text-center mb-4">Login</h3>

          <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
          <?php endif; ?>

          <form method="post">
            <div class="mb-3">
              <label for="pid" class="form-label">PID</label>
              <input type="text" class="form-control" id="pid" name="pid" required>
            </div>
            <div class="mb-3">
              <label for="password" class="form-label">Password</label>
              <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <button type="submit" name="login" class="btn btn-primary w-100">Login</button>
          </form>

          <div class="text-center mt-3">
            <a href="index.php" class="btn btn-primary">Back</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
