<?php
include "db.php";
?>

<!DOCTYPE html>
<html lang="pt-PT">
    
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>IPCA — Instituto Politécnico do Cávado e do Ave</title>

<!-- Bootstrap 5 -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- Bootstrap Icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

<!-- Google Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">

<link rel="stylesheet" href="css/style.css">

<!-- Font Awesome-->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">


</head>
<body>

<!-- ══════════ NAVBAR ══════════ -->
<nav class="navbar navbar-expand-lg fixed-top py-2" id="mainNav">
  <div class="container-fluid px-4">

    <a class="navbar-brand d-flex align-items-center gap-2 text-decoration-none" href="#">
      <div class="logo-icon">IP</div>
      <div>
        <div class="brand-name">IPCA</div>
        <div class="brand-sub">Instituto Politécnico</div>
      </div>
    </a>

    <button class="navbar-toggler" type="button"
            data-bs-toggle="collapse" data-bs-target="#navMenu"
            aria-controls="navMenu" aria-expanded="false">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navMenu">
      <ul class="navbar-nav mx-auto gap-1 mb-2 mb-lg-0">
        <li class="nav-item"><a class="nav-link" href="#cursos">Oferta Formativa</a></li>
        <li class="nav-item"><a class="nav-link" href="#sobre">Sobre</a></li>
        <li class="nav-item"><a class="nav-link" href="#noticias">Notícias</a></li>
        <li class="nav-item"><a class="nav-link" href="#contactos">Contactos</a></li>
      </ul>

      <div class="d-flex align-items-center gap-2 mt-2 mt-lg-0">
        <div class="nav-divider d-none d-lg-block"></div>

        <a href="candidatura.php" class="btn-aluno">
          <i class="bi bi-file-earmark-plus me-1"></i>Fazer Candidatura
         </a>
         <a href="consultar_candidatura.php" class="btn-aluno">
          <i class="bi bi-search me-1"></i>Consultar Candidatura
        </a>


        <a href="<?php echo isset($_SESSION['aluno_id']) ? 'dashboard_aluno.php' : 'login_aluno.php'; ?>" class="btn-aluno">
          <i class="bi bi-person me-1"></i>
          <?php echo isset($_SESSION['aluno_id']) ? 'Painel do Aluno' : 'Área do Aluno'; ?>
        </a>

         <?php
          $tipo = $_SESSION['tipo'] ?? '';
          if ($tipo === 'professor') {
            $href_admin  = 'dashboard_professor.php';
            $label_admin = 'Painel do Professor';
            $icon_admin  = 'bi-person-workspace';
          } elseif ($tipo === 'admin' || $tipo === 'funcionario') {
            $href_admin  = 'dashboard_admin.php';
            $label_admin = 'Painel Admin';
            $icon_admin  = 'bi-shield-lock';
          } else {
            $href_admin  = 'login.php';
            $label_admin = 'Administração';
            $icon_admin  = 'bi-shield-lock';
          }
        ?>
        <a href="<?php echo $href_admin; ?>" class="btn-admin">
          <i class="bi <?php echo $icon_admin; ?> me-1"></i>
          <?php echo $label_admin; ?>
        </a>

      </div>
    </div>
  </div>
</nav>


<!-- ══════════ HERO ══════════ -->
<section class="hero">
  <div class="hero-lines">
    <svg viewBox="0 0 600 800" fill="none" xmlns="http://www.w3.org/2000/svg"
         preserveAspectRatio="xMidYMid slice" style="width:100%;height:100%">
      <line x1="100" y1="0" x2="600" y2="800" stroke="rgba(201,168,76,0.06)" stroke-width="1"/>
      <line x1="250" y1="0" x2="750" y2="800" stroke="rgba(201,168,76,0.03)" stroke-width="1"/>
      <line x1="0" y1="200" x2="600" y2="0" stroke="rgba(42,100,150,0.07)" stroke-width="1"/>
      <circle cx="400" cy="300" r="210" stroke="rgba(201,168,76,0.05)" stroke-width="1" fill="none"/>
      <circle cx="400" cy="300" r="340" stroke="rgba(42,100,150,0.04)" stroke-width="1" fill="none"/>
    </svg>
  </div>

  <div class="container position-relative">
    <div class="row">
      <div class="col-lg-8 col-xl-7">

        <div class="hero-badge mb-4">
          <i class="bi bi-calendar-check"></i>
          Candidaturas Abertas 2025/2026
        </div>

        <h1 class="display-hero mb-3">
          Forma o teu<br>Futuro com<br><em>Excelência</em>
        </h1>

        <p class="hero-sub col-lg-10 mb-5">
          O Instituto Politécnico do Cávado e do Ave oferece formação superior de qualidade,
          aliando rigor académico à inovação e à proximidade com o tecido empresarial.
        </p>

        <div class="hero-ctas d-flex flex-wrap gap-3">
          <a href="#cursos" class="btn-cta-primary">
            Ver Cursos <i class="bi bi-arrow-right"></i>
          </a>
          <a href="#sobre" class="btn-cta-secondary">
            Conhecer o IPCA
          </a>
        </div>

        <div class="stats-strip row row-cols-2 row-cols-sm-4 g-3">
          <div class="col">
            <div class="stat-num">+4.200</div>
            <div class="stat-label">Estudantes</div>
          </div>
          <div class="col">
            <div class="stat-num">38</div>
            <div class="stat-label">Cursos</div>
          </div>
          <div class="col">
            <div class="stat-num">25+</div>
            <div class="stat-label">Anos</div>
          </div>
          <div class="col">
            <div class="stat-num">96%</div>
            <div class="stat-label">Empregabilidade</div>
          </div>
        </div>

      </div>
    </div>
  </div>
</section>




<!-- ══════════ OFERTA FORMATIVA ══════════ -->
<section id="cursos" class="py-5 py-lg-6 position-relative">
  <div class="container py-4">

    <p class="section-label reveal mb-2">Oferta Formativa</p>
    <h2 class="section-title reveal mb-3">Cursos para o Futuro</h2>
    <p class="section-desc reveal col-lg-6 mb-5">
      Escolhe entre licenciaturas, mestrados e CTeSP pensados para as áreas com
      maior procura no mercado de trabalho atual.
    </p>

    <div class="row g-4">

      <div class="col-md-6 col-lg-4 reveal">
        <div class="course-card card border-0 p-4">
          <div class="course-icon mb-3"><i class="fas fa-laptop-code"></i></div>
          <p class="course-area mb-1">Tecnologia</p>
          <p class="course-name mb-2">Engenharia Informática</p>
          <p class="course-desc mb-3">Desenvolvimento de software, sistemas distribuídos, inteligência artificial e segurança digital.</p>
          <div class="d-flex flex-wrap gap-2 mt-auto">
            <span class="course-tag">Licenciatura</span>
            <span class="course-tag">3 anos</span>
            <span class="course-tag">Presencial</span>
          </div>
        </div>
      </div>

      <div class="col-md-6 col-lg-4 reveal">
        <div class="course-card card border-0 p-4">
          <div class="course-icon mb-3"><i class="fa-solid fa-chart-line"></i></div>
          <p class="course-area mb-1">Gestão</p>
          <p class="course-name mb-2">Gestão de Empresas</p>
          <p class="course-desc mb-3">Formação sólida em gestão estratégica, finanças, marketing e empreendedorismo.</p>
          <div class="d-flex flex-wrap gap-2 mt-auto">
            <span class="course-tag">Licenciatura</span>
            <span class="course-tag">3 anos</span>
            <span class="course-tag">Presencial</span>
          </div>
        </div>
      </div>

      <div class="col-md-6 col-lg-4 reveal">
        <div class="course-card card border-0 p-4">
          <div class="course-icon mb-3"><i class="fa-brands fa-figma"></i></div>
          <p class="course-area mb-1">Design</p>
          <p class="course-name mb-2">Design e Marketing</p>
          <p class="course-desc mb-3">Criatividade e estratégia digital, branding, UX/UI e comunicação visual de impacto.</p>
          <div class="d-flex flex-wrap gap-2 mt-auto">
            <span class="course-tag">Licenciatura</span>
            <span class="course-tag">3 anos</span>
            <span class="course-tag">Presencial</span>
          </div>
        </div>
      </div>

      <div class="col-md-6 col-lg-4 reveal">
        <div class="course-card card border-0 p-4">
          <div class="course-icon mb-3"><i class="fa-solid fa-compass-drafting"></i></div>
          <p class="course-area mb-1">Engenharia</p>
          <p class="course-name mb-2">Engenharia Civil</p>
          <p class="course-desc mb-3">Projeto, construção e gestão de infraestruturas com foco em sustentabilidade.</p>
          <div class="d-flex flex-wrap gap-2 mt-auto">
            <span class="course-tag">Licenciatura</span>
            <span class="course-tag">3 anos</span>
            <span class="course-tag">Presencial</span>
          </div>
        </div>
      </div>

      <div class="col-md-6 col-lg-4 reveal">
        <div class="course-card card border-0 p-4">
          <div class="course-icon mb-3"><i class="fa-solid fa-flask"></i></div>
          <p class="course-area mb-1">Ciências</p>
          <p class="course-name mb-2">Biotecnologia</p>
          <p class="course-desc mb-3">Investigação aplicada em biotecnologia industrial, agroalimentar e ambiental.</p>
          <div class="d-flex flex-wrap gap-2 mt-auto">
            <span class="course-tag">Licenciatura</span>
            <span class="course-tag">3 anos</span>
            <span class="course-tag">Presencial</span>
          </div>
        </div>
      </div>

      <div class="col-md-6 col-lg-4 reveal">
        <div class="course-card card border-0 p-4">
          <div class="course-icon mb-3"><i class="fa-solid fa-robot"></i></div>
          <p class="course-area mb-1">Pós-Graduação</p>
          <p class="course-name mb-2">Inteligência Artificial</p>
          <p class="course-desc mb-3">Machine learning, deep learning, visão computacional e ética na IA aplicada a negócio.</p>
          <div class="d-flex flex-wrap gap-2 mt-auto">
            <span class="course-tag">Mestrado</span>
            <span class="course-tag">2 anos</span>
            <span class="course-tag">Híbrido</span>
          </div>
        </div>
      </div>

    </div>
  </div>
</section>



<!-- ══════════ SOBRE ══════════ -->
<section id="sobre" class="py-5 py-lg-6" style="background:var(--navy)">
  <div class="container py-4">
    <div class="row align-items-center g-5">

      <div class="col-lg-6">
        <p class="section-label reveal mb-2">Sobre o IPCA</p>
        <h2 class="section-title reveal mb-3">Tradição e Inovação<br>ao Serviço do Saber</h2>
        <p class="section-desc reveal mb-4">
          Fundado há mais de duas décadas, o IPCA afirma-se como referência no ensino
          superior politécnico em Portugal, com campus em Barcelos.
        </p>

        <div class="d-flex flex-column gap-4 reveal">
          <div class="d-flex gap-3">
            <div class="feature-dot mt-1"></div>
            <div>
              <h6 style="color:var(--cream);font-size:15px;font-weight:500;" class="mb-1">Ensino de Qualidade</h6>
              <p class="mb-0" style="font-size:13px;color:var(--muted);">Corpo docente altamente qualificado, com experiência académica e profissional reconhecida.</p>
            </div>
          </div>
          <div class="d-flex gap-3">
            <div class="feature-dot mt-1"></div>
            <div>
              <h6 style="color:var(--cream);font-size:15px;font-weight:500;" class="mb-1">Parcerias Empresariais</h6>
              <p class="mb-0" style="font-size:13px;color:var(--muted);">Protocolos com centenas de empresas da região garantem estágios e inserção no mercado.</p>
            </div>
          </div>
          <div class="d-flex gap-3">
            <div class="feature-dot mt-1"></div>
            <div>
              <h6 style="color:var(--cream);font-size:15px;font-weight:500;" class="mb-1">Internacionalização</h6>
              <p class="mb-0" style="font-size:13px;color:var(--muted);">Programas Erasmus+ e parcerias com universidades em mais de 30 países.</p>
            </div>
          </div>
          <div class="d-flex gap-3">
            <div class="feature-dot mt-1"></div>
            <div>
              <h6 style="color:var(--cream);font-size:15px;font-weight:500;" class="mb-1">Investigação Aplicada</h6>
              <p class="mb-0" style="font-size:13px;color:var(--muted);">Centros de I&D ativos nas áreas de tecnologia, saúde, gestão e artes.</p>
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-6 reveal">
        <div class="position-relative" style="padding-bottom:20px;padding-right:20px;">
          <div class="about-visual-box">
            <div>
              <div style="font-family:'Playfair Display',serif;font-size:20px;font-weight:700;color:var(--cream);">Campus de Barcelos</div>
              <div style="font-size:13px;color:var(--muted);">Minho, Portugal</div>
            </div>
          </div>
          <div class="about-float-badge">
            <div style="font-family:'Playfair Display',serif;font-size:34px;font-weight:900;color:var(--gold);line-height:1;">A+</div>
            <div style="font-size:12px;color:var(--muted);">Avaliação A3ES</div>
          </div>
        </div>
      </div>

    </div>
  </div>
</section>



<!-- ══════════ NOTÍCIAS ══════════ -->
<section id="noticias" class="py-5 py-lg-6" style="background:rgba(255,255,255,0.01)">
  <div class="container py-4">

    <p class="section-label reveal mb-2">Atualidade</p>
    <h2 class="section-title reveal mb-3">Notícias &amp; Eventos</h2>
    <p class="section-desc reveal mb-5">Fique a par de tudo o que acontece no IPCA.</p>

    <div class="row g-4">

      <div class="col-lg-6 reveal">
        <a target="_blank" href="https://ipca.pt/noticia/biblioteca-do-ipca-promove-estendal-de-poemas-para-assinalar-o-dia-mundial-da-poesia/" class="news-card news-featured card border-0">
          <div class="news-thumb"></div>
          <div class="p-4 d-flex flex-column flex-grow-1">
            <p class="news-cat mb-2">Destaque</p>
            <p class="news-title mb-2">Biblioteca do IPCA promove “Estendal de Poemas” para assinalar o Dia Mundial da Poesia</p>
            <p class="news-excerpt mb-0">A Biblioteca do IPCA vai dinamizar, de 16 a 20 de março, a iniciativa “Estendal de Poemas”, convidando estudantes, docentes e funcionários a partilhar versos e a celebrar a poesia de forma criativa.</p>
            <p class="news-date mt-3 mb-0"><i class="bi bi-calendar3 me-1"></i>2026-03-13</p>
          </div>
        </a>
      </div>

      <div class="col-lg-6">
        <div class="d-flex flex-column gap-4 h-100">

          <a target="_blank" href="https://ipca.pt/noticia/ipca-oferece-bolsas-de-investigacao-para-as-summer-schools-rd/" class="news-card card border-0 reveal" style="flex-direction:row;">
            <div class="news-thumb2" style="width:130px;height:auto;min-height:120px;border-radius:14px 0 0 14px;font-size:28px;flex-shrink:0;"></div>
            <div class="p-3 d-flex flex-column flex-grow-1">
              <p class="news-cat mb-1">Prémios</p>
              <p class="news-title mb-2" style="font-size:15px;">IPCA oferece Bolsas de Investigação para as Summer Schools R&D</p>
              <p class="news-excerpt mb-0" style="font-size:12px;">O IPCA volta a promover as Summer Schools R&D, uma iniciativa dos seus Centros de Investigação, proporcionando a jovens investigadores a oportunidade de desenvolver atividades de investigação e formação presencial no Campus.</p>
              <p class="news-date mt-auto mb-0"><i class="bi bi-calendar3 me-1"></i>2026-03-12</p>
            </div>
          </a>

          <a target="_blank" href="https://ipca.pt/noticia/tania-graca-no-ipca-para-falar-sobre-sexualidade-e-igualdade-de-genero/" class="news-card card border-0 reveal" style="flex-direction:row;">
            <div class="news-thumb3" style="width:130px;height:auto;min-height:120px;border-radius:14px 0 0 14px;font-size:28px;flex-shrink:0;"></div>
            <div class="p-3 d-flex flex-column flex-grow-1">
              <p class="news-cat mb-1">Psicologia</p>
              <p class="news-title mb-2" style="font-size:15px;">Tânia Graça no IPCA para falar sobre Sexualidade e Igualdade de Género</p>
              <p class="news-excerpt mb-0" style="font-size:12px;">O IPCA recebe, no próximo dia 24 de março, às 14h30, no Auditório Eng.º António Tavares, no Campus, Tânia Graça, uma das vozes mais reconhecidas em Portugal nas áreas da psicologia e da sexologia.</p>
              <p class="news-date mt-auto mb-0"><i class="bi bi-calendar3 me-1"></i>2026-03-10</p>
            </div>
          </a>

          <a target="_blank" href="https://ipca.pt/noticia/ipca-marcou-presenca-no-iv-encontro-intercalar-de-provedores-do-estudante/" class="news-card card border-0 reveal" style="flex-direction:row;">
            <div class="news-thumb4" style="width:130px;height:auto;min-height:120px;border-radius:14px 0 0 14px;font-size:28px;flex-shrink:0;"></div>
            <div class="p-3 d-flex flex-column flex-grow-1">
              <p class="news-cat mb-1">Investigação</p>
              <p class="news-title mb-2" style="font-size:15px;">IPCA marcou presença no IV Encontro Intercalar de Provedores do Estudante</p>
              <p class="news-excerpt mb-0" style="font-size:12px;">A Provedora do Estudante do IPCA, Liliana Ivone da Silva Pereira, marcou presença no IV Encontro Intercalar de Provedores do Estudante (EiPE), que decorreu no passado dia 27 de fevereiro, no Instituto Politécnico de Viseu.</p>
              <p class="news-date mt-auto mb-0"><i class="bi bi-calendar3 me-1"></i>2026-03-10</p>
            </div>
          </a>

        </div>
      </div>

    </div>
  </div>
</section>



<!-- ══════════ CTA BAND ══════════ -->
<section class="py-5" style="background:var(--navy)">
  <div class="container">
    <div class="cta-band p-4 p-md-5 reveal">
      <div class="row align-items-center gy-4">
        <div class="col-lg-7">
          <h3 class="mb-2">Pronto para começar<br>a tua jornada?</h3>
          <p class="mb-0" style="color:var(--muted);font-size:15px;">Submete a tua candidatura ou acede à tua área pessoal.</p>
        </div>
        <div class="col-lg-5 d-flex flex-wrap gap-3 justify-content-lg-end">
          <a href="login_aluno.php" class="btn-cta-secondary">
            <i class="bi bi-person me-1"></i>Área do Aluno
          </a>
          <a href="#cursos" class="btn-cta-primary">
            Ver Cursos <i class="bi bi-arrow-right"></i>
          </a>
        </div>
      </div>
    </div>
  </div>
</section>



<!-- ══════════ FOOTER ══════════ -->
<footer id="contactos" class="pt-5 pb-3">
  <div class="container">
    <div class="row g-4 mb-5">

      <div class="col-lg-4 col-md-6">
        <div class="d-flex align-items-center gap-2 mb-3">
          <div class="logo-icon">IP</div>
          <div>
            <div class="brand-name">IPCA</div>
            <div class="brand-sub">Instituto Politécnico</div>
          </div>
        </div>
        <p style="font-size:13px;color:var(--muted);line-height:1.7;max-width:260px;">
          Formação superior de qualidade para os desafios do futuro. Barcelos, Minho, Portugal.
        </p>
        <div class="d-flex gap-3 mt-3">
          <a target="_blank" href="https://www.facebook.com/IPCA.Politecnico/?locale=pt_PT" style="color:var(--muted);font-size:18px;"><i class="bi bi-facebook"></i></a>
          <a target="_blank" href="https://www.instagram.com/ipca.politecnico/" style="color:var(--muted);font-size:18px;"><i class="bi bi-instagram"></i></a>
          <a target="_blank" href="https://www.linkedin.com/school/politecnico-do-cavado-e-do-ave/posts/?feedView=all" style="color:var(--muted);font-size:18px;"><i class="bi bi-linkedin"></i></a>
          <a target="_blank" href="https://www.youtube.com/@ipca.barcelos" style="color:var(--muted);font-size:18px;"><i class="bi bi-youtube"></i></a>
        </div>
      </div>

      <div class="col-lg-2 col-md-6 col-6">
        <h5 class="mb-3">Cursos</h5>
        <a href="#" class="d-block mb-2">Licenciaturas</a>
        <a href="#" class="d-block mb-2">Mestrados</a>
        <a href="#" class="d-block mb-2">CTeSP</a>
        <a href="#" class="d-block mb-2">Pós-Graduações</a>
      </div>

      <div class="col-lg-2 col-md-6 col-6">
        <h5 class="mb-3">Instituição</h5>
        <a href="#" class="d-block mb-2">Sobre o IPCA</a>
        <a href="#" class="d-block mb-2">Campus</a>
        <a href="#" class="d-block mb-2">Investigação</a>
        <a href="#" class="d-block mb-2">Parceiros</a>
      </div>

      <div class="col-lg-2 col-md-6 col-6">
        <h5 class="mb-3">Acesso</h5>
        <a href="login_aluno.php" class="d-block mb-2">Área do Aluno</a>
        <a href="index.php" class="d-block mb-2">Área Admin</a>
        <a href="#" class="d-block mb-2">Candidaturas</a>
      </div>

      <div class="col-lg-2 col-md-6 col-6">
        <h5 class="mb-3">Contactos</h5>
        <p style="font-size:13px;color:var(--muted);" class="mb-2"><i class="bi bi-geo-alt me-1"></i>Barcelos, Portugal</p>
        <p style="font-size:13px;color:var(--muted);" class="mb-2"><i class="bi bi-telephone me-1"></i>+351 253 802 190</p>
        <p style="font-size:13px;color:var(--muted);" class="mb-0"><i class="bi bi-envelope me-1"></i>geral@ipca.pt</p>
      </div>

    </div>

    <div class="footer-bottom py-3 d-flex flex-wrap justify-content-between gap-2">
      <span style="font-size:12px;color:var(--muted);">© 2026 IPCA — Instituto Politécnico do Cávado e do Ave</span>
      <span style="font-size:12px;color:var(--muted);">Barcelos, Portugal</span>
    </div>
  </div>
</footer>



<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
  // ESTE SCRIPT TEM DE ESTAR FORA DO IF PARA O SITE APARECER SEMPRE
  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry, i) => {
      if (entry.isIntersecting) {
        setTimeout(() => entry.target.classList.add('visible'), i * 70);
      }
    });
  }, { threshold: 0.08, rootMargin: '0px 0px -40px 0px' });

  document.querySelectorAll('.reveal').forEach(el => observer.observe(el));

  // Navbar shrink on scroll
  const nav = document.getElementById('mainNav');
  window.addEventListener('scroll', () => {
    nav.classList.toggle('py-1', window.scrollY > 60);
    nav.classList.toggle('py-2', window.scrollY <= 60);
  });
</script>

<?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
  <div id="success-alert" class="container mt-4" style="position: fixed; top: 80px; left: 50%; transform: translateX(-50%); z-index: 9999; max-width: 500px;">
      <div class="alert" style="background: rgba(20, 40, 70, 0.9); border: 1px solid var(--gold); color: var(--cream); backdrop-filter: blur(10px); border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.5);">
          <div class="d-flex align-items-center">
              <i class="bi bi-check-circle-fill me-3" style="color: var(--gold); font-size: 1.5rem;"></i>
              <div>
                  <h6 class="mb-0" style="color: var(--gold); font-weight: 700;">Candidatura Submetida!</h6>
                  <small style="opacity: 0.8;">A sua inscrição foi registada com sucesso.</small>
              </div>
          </div>
      </div>
  </div>
  
  <script>
    // Apenas este script de fechar o alerta fica dentro do IF
    setTimeout(function() {
        const alert = document.getElementById('success-alert');
        if (alert) {
            alert.style.transition = "all 0.8s ease";
            alert.style.opacity = "0";
            alert.style.transform = "translateX(-50%) translateY(-20px)";
            setTimeout(() => alert.remove(), 800);
        }
    }, 5000);
  </script>
<?php endif; ?>

</body>
</html>