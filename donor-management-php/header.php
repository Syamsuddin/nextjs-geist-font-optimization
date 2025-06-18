<?php
session_start();
require_once 'role_check.php';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Manajemen Donatur</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand" href="donation_input.php">DonaturApp</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" 
      aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item">
          <a class="nav-link" href="donation_input.php">Input Donasi</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="donor_recap.php">Rekap Donatur</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="donation_recap.php">Rekap Donasi</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="chart.php">Grafik Donasi</a>
        </li>
        <?php if (is_admin()): ?>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="adminMenu" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            Admin
          </a>
          <ul class="dropdown-menu" aria-labelledby="adminMenu">
            <li><a class="dropdown-item" href="backup_restore.php">Backup & Restore</a></li>
            <li><a class="dropdown-item" href="user_management.php">Manajemen User</a></li>
          </ul>
        </li>
        <?php endif; ?>
      </ul>
      <span class="navbar-text me-3">
        <?= isset($_SESSION['nama']) ? 'Halo, ' . htmlspecialchars($_SESSION['nama']) : '' ?>
      </span>
      <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
    </div>
  </div>
</nav>
<div class="container mt-4">
