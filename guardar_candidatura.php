<?php
include "db.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: candidatura.php"); exit();
}

$nome  = trim($_POST['nome']  ?? '');
$nif   = trim($_POST['nif']   ?? '');
$email = trim($_POST['email'] ?? '');
$curso = (int)($_POST['curso'] ?? 0);

if (empty($nome) || empty($nif) || empty($email) || $curso <= 0) {
    header("Location: candidatura.php?erro=campos"); exit();
}

// Foto
$foto_nome = 'default.png';
if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
    $allowed = ['image/jpeg', 'image/png', 'image/webp'];
    if (in_array($_FILES['foto']['type'], $allowed) && $_FILES['foto']['size'] <= 2 * 1024 * 1024) {
        $ext      = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . strtolower($ext);
        if (move_uploaded_file($_FILES['foto']['tmp_name'], 'uploads/' . $filename)) {
            $foto_nome = $filename;
        }
    }
}

$stmt = $conn->prepare("INSERT INTO candidaturas (nome, nif, email, curso_id, foto, data_submissao) VALUES (?, ?, ?, ?, ?, NOW())");
if ($stmt->execute([$nome, $nif, $email, $curso, $foto_nome])) {
    header("Location: consultar_candidatura.php?submetida=1");
} else {
    header("Location: candidatura.php?erro=bd");
}
exit();
?>
