<?php
include "db.php";

$email = trim($_POST['email'] ?? '');
$senha = $_POST['senha']       ?? '';

if (empty($email) || empty($senha)) {
    header("Location: login_aluno.php?erro=1");
    exit();
}

$stmt = $conn->prepare("SELECT id, nome, password FROM alunos WHERE email = ?");
$stmt->execute([$email]);
$aluno = $stmt->fetch();

if ($aluno && password_verify($senha, $aluno['password'])) {
    $_SESSION['aluno_id']   = $aluno['id'];
    $_SESSION['aluno_nome'] = $aluno['nome'];

    // Verificar primeiro acesso (password_temp ainda existe)
    $chk = $conn->prepare("SELECT password_temp FROM candidaturas WHERE nome = ? AND password_temp IS NOT NULL LIMIT 1");
    $chk->execute([$aluno['nome']]);
    $primeiro_acesso = (bool)$chk->fetch();

    header("Location: " . ($primeiro_acesso ? "nova_password.php" : "dashboard_aluno.php"));
    exit();
}

header("Location: login_aluno.php?erro=1");
exit();
?>
