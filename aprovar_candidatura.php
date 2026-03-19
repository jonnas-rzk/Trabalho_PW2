<?php
include "db.php";

if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'admin') {
    header("Location: login.php"); exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: dashboard_admin.php?erro=id_invalido"); exit();
}

$id = (int)$_GET['id'];

// 1. Buscar candidatura
$stmt = $conn->prepare("SELECT c.*, cu.Nome AS nome_curso FROM candidaturas c LEFT JOIN cursos cu ON c.curso_id = cu.ID WHERE c.id = ?");
$stmt->execute([$id]);
$candidatura = $stmt->fetch();

if (!$candidatura) {
    header("Location: dashboard_admin.php?erro=nao_existe"); exit();
}

// 2. Evitar duplicado
$chk = $conn->prepare("SELECT id FROM alunos WHERE nome = ? AND curso_id = ?");
$chk->execute([$candidatura['nome'], $candidatura['curso_id']]);
if ($chk->fetch()) {
    header("Location: dashboard_admin.php?erro=ja_processada"); exit();
}

// 3. Gerar nº aluno único
do {
    $numero_aluno = rand(10000, 99999);
    $c = $conn->prepare("SELECT id FROM alunos WHERE numero_aluno = ?");
    $c->execute([$numero_aluno]);
} while ($c->fetch());

// 4. Credenciais
$email_institucional = "a{$numero_aluno}@alunos.ipca.pt";
$password_plain      = "Ipca" . rand(100, 999) . "!";
$password_hashed     = password_hash($password_plain, PASSWORD_BCRYPT);
$foto_aluno          = !empty($candidatura['foto']) ? $candidatura['foto'] : 'default.png';

// 5. Inserir aluno
$ins = $conn->prepare("INSERT INTO alunos (numero_aluno, nome, email, password, curso_id, foto) VALUES (?, ?, ?, ?, ?, ?)");
if (!$ins->execute([$numero_aluno, $candidatura['nome'], $email_institucional, $password_hashed, $candidatura['curso_id'], $foto_aluno])) {
    header("Location: dashboard_admin.php?erro=bd"); exit();
}

// 6. Atualizar candidatura
$upd = $conn->prepare("UPDATE candidaturas SET estado='aprovado', data_decisao=NOW(), password_temp=? WHERE id=?");
$upd->execute([$password_plain, $id]);

// 7. Redirecionar com toast
header("Location: dashboard_admin.php?sucesso=aprovado&aluno=" . urlencode($candidatura['nome']) . "&num={$numero_aluno}&pw=" . urlencode($password_plain));
exit();
?>
