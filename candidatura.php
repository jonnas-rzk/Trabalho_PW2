<?php
include "db.php";

$sql_cursos = "SELECT ID, Nome FROM cursos";
$result_cursos = $conn->query($sql_cursos);

?>


<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Candidatura — IPCA</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/candidatura.css">

   
    
</head>
<body>

<nav class="navbar navbar-expand-lg fixed-top py-2" id="mainNav">
  <div class="container-fluid px-4">
    <a class="navbar-brand d-flex align-items-center gap-2 text-decoration-none" href="index.php">
      <div class="logo-icon">IP</div>
      <div><div class="brand-name">IPCA</div><div class="brand-sub">Instituto Politécnico</div></div>
    </a>
  </div>
</nav>

<section class="hero-mini">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-7">
        
        <div class="text-center mb-5 reveal">
            <p class="section-label mb-2">Ingresso 2026</p>
            <h2 class="section-title">Nova Candidatura</h2>
        </div>

        <div class="form-container reveal">
            <form action="guardar_candidatura.php" method="POST" enctype="multipart/form-data">
                
                <div class="mb-4">
                    <label class="form-label">Nome Completo</label>
                    <input type="text" name="nome" class="form-control" placeholder="Seu nome aqui..." required>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-4">
                        <label class="form-label">NIF</label>
                        <input type="text" name="nif" class="form-control" placeholder="Contribuinte" required>
                    </div>
                    <div class="col-md-6 mb-4">
                        <label class="form-label">Email Pessoal</label>
                        <input type="email" name="email" class="form-control" placeholder="exemplo@gmail.com" required>

                    
                    </div>
                    
                </div>

                <div class="mb-4">
                    <label class="form-label">Curso Pretendido</label>
                    <select name="curso" class="form-select" required>
                        <option value="" selected disabled>Selecione um curso...</option>
                        <?php
                        
                        if ($result_cursos->num_rows > 0) {
                            while($row = $result_cursos->fetch_assoc()) {
                                echo "<option value='".$row['ID']."'>".$row['Nome']."</option>";
                            }
                        }
                        ?>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="form-label">Foto de Perfil</label>
                    <input type="file" name="foto" class="form-control" accept="image/*">
                    <div class="mt-1" style="font-size: 11px; color: var(--muted);">Formatos aceites: JPG, PNG. Máx 2MB.</div>
                </div>

                <button type="submit" class="btn-submit">Submeter Candidatura</button>
            </form>
        </div>

      </div>
    </div>
  </div>
</section>

<script>
  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => { if (entry.isIntersecting) entry.target.classList.add('visible'); });
  }, { threshold: 0.1 });
  document.querySelectorAll('.reveal').forEach(el => observer.observe(el));
  
</script>

</body>
</html>