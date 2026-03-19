<?php
include "db.php";

// ── Proteção
if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'admin') {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: dashboard_admin.php?erro=id_invalido");
    exit();
}

$id = (int)$_GET['id'];

// ── 1. Buscar candidatura
$stmt = $conn->prepare("
    SELECT c.*, cu.Nome AS nome_curso
    FROM candidaturas c
    LEFT JOIN cursos cu ON c.curso_id = cu.ID
    WHERE c.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: dashboard_admin.php?erro=nao_existe");
    exit();
}

$candidatura = $result->fetch_assoc();

// ── 2. Verificar se o aluno já foi criado (evitar duplicados)
$stmt_check = $conn->prepare("SELECT id FROM alunos WHERE nome = ? AND curso_id = ?");
$stmt_check->bind_param("si", $candidatura['nome'], $candidatura['curso_id']);
$stmt_check->execute();
if ($stmt_check->get_result()->num_rows > 0) {
    header("Location: dashboard_admin.php?erro=ja_processada");
    exit();
}

// ── 3. Gerar número de aluno único (5 dígitos)
do {
    $numero_aluno = rand(10000, 99999);
    $chk = $conn->prepare("SELECT id FROM alunos WHERE numero_aluno = ?");
    $chk->bind_param("i", $numero_aluno);
    $chk->execute();
    $chk->store_result();
} while ($chk->num_rows > 0);

// ── 4. Gerar credenciais
$email_institucional = "a" . $numero_aluno . "@alunos.ipca.pt";
$password_plain      = "Ipca" . rand(100, 999) . "!"; // ex: Ipca452!
$password_hashed     = password_hash($password_plain, PASSWORD_BCRYPT);
$foto_aluno          = !empty($candidatura['foto']) ? $candidatura['foto'] : 'default.png';

// ── 5. Inserir na tabela alunos
$stmt_insert = $conn->prepare("
    INSERT INTO alunos (numero_aluno, nome, email, password, curso_id, foto)
    VALUES (?, ?, ?, ?, ?, ?)
");
$stmt_insert->bind_param("isssis",
    $numero_aluno,
    $candidatura['nome'],
    $email_institucional,
    $password_hashed,
    $candidatura['curso_id'],
    $foto_aluno
);

if (!$stmt_insert->execute()) {
    header("Location: dashboard_admin.php?erro=bd");
    exit();
}

// ── 6. Atualizar candidatura: marcar como aprovada + guardar password plain temporariamente
$stmt_update = $conn->prepare("
    UPDATE candidaturas
    SET estado = 'aprovado',
        data_decisao = NOW(),
        password_temp = ?
    WHERE id = ?
");
$stmt_update->bind_param("si", $password_plain, $id);
$stmt_update->execute();

// ── 7. Redirecionar — passa a password no URL para o admin ver no toast
header("Location: dashboard_admin.php?sucesso=aprovado"
    . "&aluno=" . urlencode($candidatura['nome'])
    . "&num="   . $numero_aluno
    . "&pw="    . urlencode($password_plain)
);
exit();
?>