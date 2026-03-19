<?php
include "db.php";

$usuario = trim($_POST['usuario'] ?? '');
$senha   = $_POST['senha'] ?? '';

if (empty($usuario) || empty($senha)) {
    header("Location: login.php?erro=1");
    exit();
}

// ── 1. Admin / funcionário (tabela users, md5)
$stmt = $conn->prepare("SELECT login, pwd, tipo FROM users WHERE login = ?");
$stmt->execute([$usuario]);
$user = $stmt->fetch();

if ($user && md5($senha) === $user['pwd']) {
    $_SESSION['usuario'] = $user['login'];
    $_SESSION['tipo']    = $user['tipo'];
    header("Location: dashboard_admin.php");
    exit();
}

// ── 2. Professor (tabela professores, bcrypt)
$stmt2 = $conn->prepare("SELECT id, nome, email, password FROM professores WHERE email = ?");
$stmt2->execute([$usuario]);
$prof = $stmt2->fetch();

if ($prof && password_verify($senha, $prof['password'])) {
    $_SESSION['professor_id']   = $prof['id'];
    $_SESSION['professor_nome'] = $prof['nome'];
    $_SESSION['tipo']           = 'professor';
    header("Location: dashboard_professor.php");
    exit();
}

header("Location: login.php?erro=1");
exit();
?>
