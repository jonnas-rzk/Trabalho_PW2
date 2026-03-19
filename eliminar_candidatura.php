<?php
include "db.php";

// 1. Verificar se o utilizador tem permissão (apenas admin pode eliminar)
if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// 2. Verificar se o ID foi enviado
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = (int)$_GET['id'];

    // 3. (Opcional) Buscar o nome da foto para apagar o ficheiro da pasta uploads
    $stmt_foto = $conn->prepare("SELECT foto FROM candidaturas WHERE id = ?");
    $stmt_foto->bind_param("i", $id);
    $stmt_foto->execute();
    $res_foto = $stmt_foto->get_result();
    
    if ($row = $res_foto->fetch_assoc()) {
        $caminho_foto = "uploads/" . $row['foto'];
        if ($row['foto'] !== 'default.png' && file_exists($caminho_foto)) {
            unlink($caminho_foto); // Apaga o ficheiro físico
        }
    }

    // 4. Eliminar o registo da base de dados
    $stmt = $conn->prepare("DELETE FROM candidaturas WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        // Sucesso: Redireciona com uma mensagem (podes adicionar o toast depois)
        header("Location: dashboard_admin.php?sucesso=eliminado");
    } else {
        // Erro de BD
        header("Location: dashboard_admin.php?erro=bd");
    }
    $stmt->close();
} else {
    header("Location: dashboard_admin.php");
}
?>