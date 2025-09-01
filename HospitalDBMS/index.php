<?php session_start(); ?>
<!DOCTYPE html>
<html>
<head>
    <title>Hospital DBMS</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
  <div class="container-fluid">
    <a class="navbar-brand" href="#">Hospital DBMS</a>
    <div class="collapse navbar-collapse">
      <ul class="navbar-nav ms-auto">
        <?php if (!isset($_SESSION['pid'])): ?>
          <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
          <li class="nav-item"><a class="nav-link" href="register.php">Register</a></li>
        <?php else: ?>
          <li class="nav-item"><span class="nav-link">Logged in as <b><?php echo $_SESSION['pid']; ?></b> (<?php echo $_SESSION['role']; ?>)</span></li>
          <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<div class="container mt-5">
  <div class="text-center">
    <h1 class="mb-4">Welcome to Hospital Management System</h1>

    <?php if (!isset($_SESSION['pid'])): ?>
      <p class="lead">Please login or register to access the system.</p>
      <a href="login.php" class="btn btn-primary btn-lg me-2">Login</a>
      <a href="register.php" class="btn btn-success btn-lg">Register</a>
    <?php else: ?>
      <div class="alert alert-success">
        You are logged in as <b><?php echo $_SESSION['pid']; ?></b> (<?php echo $_SESSION['role']; ?>)
      </div>
      <a href="logout.php" class="btn btn-danger btn-lg">Logout</a>
    <?php endif; ?>
  </div>
</div>

<script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>
