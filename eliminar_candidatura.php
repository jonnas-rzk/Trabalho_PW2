<?php
include "db.php";

if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'admin') {
    header("Location: login.php"); exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: dashboard_admin.php"); exit();
}

$id = (int)$_GET['id'];

// Apagar foto física se não for default
$stmt = $conn->prepare("SELECT foto FROM candidaturas WHERE id = ?");
$stmt->execute([$id]);
$row = $stmt->fetch();

if ($row && $row['foto'] !== 'default.png') {
    $path = "uploads/" . $row['foto'];
    if (file_exists($path)) unlink($path);
}

// Eliminar registo
$del = $conn->prepare("DELETE FROM candidaturas WHERE id = ?");
if ($del->execute([$id])) {
    header("Location: dashboard_admin.php?sucesso=eliminado");
} else {
    header("Location: dashboard_admin.php?erro=bd");
}
exit();
?>
