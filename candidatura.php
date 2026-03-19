<?php
include "db.php";

// Buscar cursos disponíveis
$result_cursos = $conn->query("SELECT ID, Nome FROM cursos ORDER BY Nome ASC");
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

<!-- ── NAVBAR ── -->
<nav class="navbar navbar-expand-lg fixed-top py-2">
  <div class="container-fluid px-4">
    <a class="navbar-brand d-flex align-items-center gap-2 text-decoration-none" href="index.php">
      <div class="logo-icon">IP</div>
      <div>
        <div class="brand-name">IPCA</div>
        <div class="brand-sub">Instituto Politécnico</div>
      </div>
    </a>
    <a href="consultar_candidatura.php"
       style="color:var(--muted);text-decoration:none;font-size:13px;display:flex;align-items:center;gap:6px;">
      <i class="bi bi-search"></i> Consultar candidatura
    </a>
  </div>
</nav>

<!-- ── CONTEÚDO ── -->
<section class="hero-mini">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-7 col-xl-6">

        <!-- Cabeçalho -->
        <div class="text-center mb-5 reveal">
          <p class="section-label mb-2">Ingresso 2026</p>
          <h2 class="section-title mb-2">Nova Candidatura</h2>
          <p style="font-size:14px;color:var(--muted);margin-top:8px;line-height:1.6;">
            Preenche o formulário para iniciares o processo de candidatura ao IPCA.
          </p>
        </div>

        <!-- Indicador de passos -->
        <div class="step-indicator reveal">
          <div class="step-item">
            <div class="step-num active">1</div>
            <div class="step-text active">Dados</div>
          </div>
          <div class="step-connector"></div>
          <div class="step-item">
            <div class="step-num todo">2</div>
            <div class="step-text">Análise</div>
          </div>
          <div class="step-connector"></div>
          <div class="step-item">
            <div class="step-num todo">3</div>
            <div class="step-text">Decisão</div>
          </div>
          <div class="step-connector"></div>
          <div class="step-item">
            <div class="step-num todo">4</div>
            <div class="step-text">Acesso</div>
          </div>
        </div>

        <!-- Card do formulário -->
        <div class="form-container reveal">

          <form action="guardar_candidatura.php" method="POST" enctype="multipart/form-data" novalidate>

            <!-- Identificação -->
            <div class="field-group-title">
              <i class="bi bi-person-vcard"></i> Identificação
            </div>

            <div class="mb-4">
              <label class="form-label">Nome Completo</label>
              <input type="text" name="nome" class="form-control"
                     placeholder="O teu nome completo" required>
            </div>

            <div class="row g-3 mb-2">
              <div class="col-md-5">
                <label class="form-label">NIF</label>
                <input type="text" name="nif" class="form-control"
                       placeholder="123456789" maxlength="9" required>
              </div>
              <div class="col-md-7">
                <label class="form-label">Email Pessoal</label>
                <input type="email" name="email" class="form-control"
                       placeholder="exemplo@gmail.com" required>
              </div>
            </div>
            <div class="form-text mb-4">
              <i class="bi bi-info-circle"></i>
              O email é usado para consultar o estado da candidatura.
            </div>

            <hr class="form-divider">

            <!-- Curso -->
            <div class="field-group-title">
              <i class="bi bi-book"></i> Curso Pretendido
            </div>

            <div class="mb-4">
              <label class="form-label">Seleciona o Curso</label>
              <select name="curso" class="form-select" required>
                <option value="" disabled selected>Seleciona um curso...</option>
                <?php foreach ($result_cursos as $row): ?>
                  <option value="<?php echo $row['ID']; ?>">
                    <?php echo htmlspecialchars($row['Nome']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <hr class="form-divider">

            <!-- Foto -->
            <div class="field-group-title">
              <i class="bi bi-camera"></i> Foto de Perfil
            </div>

            <div class="mb-5">
              <label class="form-label">Fotografia <span style="color:var(--muted);font-size:10px;text-transform:none;letter-spacing:0;">(opcional)</span></label>
              <input type="file" name="foto" class="form-control"
                     accept="image/jpeg,image/png,image/webp">
              <div class="form-text">
                <i class="bi bi-image"></i>
                JPG, PNG ou WebP · Máx. 2MB
              </div>
            </div>

            <!-- Botão -->
            <button type="submit" class="btn-submit">
              <i class="bi bi-send"></i>
              Submeter Candidatura
            </button>

          </form>

        </div><!-- /form-container -->

        <!-- Voltar -->
        <div class="text-center">
          <a href="index.php" class="link-voltar">
            <i class="bi bi-arrow-left"></i> Voltar ao site
          </a>
        </div>

      </div>
    </div>
  </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry, i) => {
      if (entry.isIntersecting) {
        setTimeout(() => entry.target.classList.add('visible'), i * 120);
      }
    });
  }, { threshold: 0.1 });

  document.querySelectorAll('.reveal').forEach(el => observer.observe(el));
</script>
</body>
</html>
