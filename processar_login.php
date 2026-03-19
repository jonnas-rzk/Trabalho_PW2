<?php
include "db.php";

$usuario = trim($_POST['usuario'] ?? '');
$senha   = $_POST['senha'] ?? '';

if (empty($usuario) || empty($senha)) {
    header("Location: login.php?erro=1");
    exit();
}

// ── 1. Tentar login como admin / funcionário (tabela users, pwd em md5)
$stmt = $conn->prepare("SELECT login, pwd, tipo FROM users WHERE login = ?");
$stmt->bind_param("s", $usuario);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();

    // users ainda usa md5 (sistema legado)
    if (md5($senha) === $user['pwd']) {
        $_SESSION['usuario'] = $user['login'];
        $_SESSION['tipo']    = $user['tipo'];
        header("Location: dashboard_admin.php");
        exit();
    }
}

// ── 2. Tentar login como professor (tabela professores, pwd em bcrypt)
$stmt2 = $conn->prepare("SELECT id, nome, email, password FROM professores WHERE email = ?");
$stmt2->bind_param("s", $usuario);
$stmt2->execute();
$result2 = $stmt2->get_result();

if ($result2->num_rows === 1) {
    $prof = $result2->fetch_assoc();

    if (password_verify($senha, $prof['password'])) {
        $_SESSION['professor_id']   = $prof['id'];
        $_SESSION['professor_nome'] = $prof['nome'];
        $_SESSION['tipo']           = 'professor';
        header("Location: dashboard_professor.php");
        exit();
    }
}

// ── Falhou tudo
header("Location: login.php?erro=1");
exit();
?>