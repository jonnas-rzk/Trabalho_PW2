<?php
include "db.php";

$email = trim($_POST['email'] ?? '');
$senha = $_POST['senha']       ?? '';

if (empty($email) || empty($senha)) {
    header("Location: login_aluno.php?erro=1");
    exit();
}

// ── Buscar aluno pelo email
$stmt = $conn->prepare("SELECT id, nome, password FROM alunos WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $aluno = $result->fetch_assoc();

    if (password_verify($senha, $aluno['password'])) {

        // ── Login válido — guardar sessão
        $_SESSION['aluno_id']   = $aluno['id'];
        $_SESSION['aluno_nome'] = $aluno['nome'];

        // ── Verificar se é o primeiro acesso (password_temp ainda existe)
        $stmt_check = $conn->prepare("
            SELECT password_temp
            FROM candidaturas
            WHERE nome = ? AND password_temp IS NOT NULL
            LIMIT 1
        ");
        $stmt_check->bind_param("s", $aluno['nome']);
        $stmt_check->execute();
        $primeiro_acesso = $stmt_check->get_result()->num_rows > 0;

        if ($primeiro_acesso) {
            // Primeiro login → forçar criação de nova password
            header("Location: nova_password.php");
        } else {
            // Login normal → ir direto ao dashboard
            header("Location: dashboard_aluno.php");
        }
        exit();
    }
}

header("Location: login_aluno.php?erro=1");
exit();
?>