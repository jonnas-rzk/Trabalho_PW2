<?php
include "db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome  = $_POST['nome'];
    $nif   = $_POST['nif'];
    $email = $_POST['email'];
    $curso = $_POST['curso'];
    
    // --- LÓGICA DA FOTO ---
    $foto_nome = "default.png"; // Fallback
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === 0) {
        $extensao = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        $novo_nome = uniqid() . "." . $extensao; // Nome único para não sobrescrever
        $diretorio = "uploads/"; // Certifica-te que esta pasta existe!
        
        if (move_uploaded_file($_FILES['foto']['tmp_name'], $diretorio . $novo_nome)) {
            $foto_nome = $novo_nome;
        }
    }

    // SQL atualizado (ajusta os nomes das colunas conforme a tua tabela)
    $sql = "INSERT INTO candidaturas (nome, nif, email, curso_id, foto, data_submissao) 
            VALUES (?, ?, ?, ?, ?, NOW())";

    $stmt = $conn->prepare($sql);
    // "sssis" -> string, string, string, int, string (foto)
    $stmt->bind_param("sssis", $nome, $nif, $email, $curso, $foto_nome); 

    if ($stmt->execute()) {
        header("Location: index.php?success=1");
        exit();
    } else {
        echo "Erro: " . $stmt->error;
    }
    $stmt->close();
}