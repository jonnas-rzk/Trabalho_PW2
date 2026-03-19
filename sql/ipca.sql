-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 19-Mar-2026 às 18:40
-- Versão do servidor: 10.4.32-MariaDB
-- versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `ipca`
--

-- --------------------------------------------------------

--
-- Estrutura da tabela `alunos`
--

CREATE TABLE `alunos` (
  `id` int(11) NOT NULL,
  `numero_aluno` int(11) DEFAULT NULL,
  `nome` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `curso_id` int(11) DEFAULT NULL,
  `foto` varchar(255) DEFAULT 'default.png'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `alunos`
--

INSERT INTO `alunos` (`id`, `numero_aluno`, `nome`, `email`, `password`, `curso_id`, `foto`) VALUES
(8, 96343, 'Tobias', 'a96343@alunos.ipca.pt', '$2y$10$Se4vmcZMe0QcVnkljJweBemUo1UrTUimFnFb18T1jIc1Nou1GS8Ka', 1, '69b99fbf54a52.png'),
(9, 61766, 'Jonnas', 'a61766@alunos.ipca.pt', '$2y$10$PJI4N8UPF4J/2LpyNeIR6ut.MogLKYcRiWyKRbfffPSM4jRkFFaPK', 1, '69bc2aef4bc35.jpg');

-- --------------------------------------------------------

--
-- Estrutura da tabela `candidaturas`
--

CREATE TABLE `candidaturas` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) DEFAULT NULL,
  `nif` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `curso_id` int(11) DEFAULT NULL,
  `foto` varchar(255) DEFAULT 'default.png',
  `estado` enum('pendente','aprovado','rejeitado') DEFAULT 'pendente',
  `observacoes` text DEFAULT NULL,
  `data_submissao` datetime DEFAULT NULL,
  `data_decisao` datetime DEFAULT NULL,
  `password_temp` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `candidaturas`
--

INSERT INTO `candidaturas` (`id`, `nome`, `nif`, `email`, `curso_id`, `foto`, `estado`, `observacoes`, `data_submissao`, `data_decisao`, `password_temp`) VALUES
(12, 'Tobias', '54321', 'tobias@gmail.com', 1, '69b98cc4a4c97.jpg', 'aprovado', NULL, '2026-03-17 17:17:56', '2026-03-17 17:20:55', NULL),
(13, 'Jonnas', '12345', 'jonnas@gmail.com', 1, '69bc2aef4bc35.jpg', 'aprovado', NULL, '2026-03-19 16:57:19', '2026-03-19 16:58:02', NULL);

-- --------------------------------------------------------

--
-- Estrutura da tabela `cursos`
--

CREATE TABLE `cursos` (
  `ID` int(11) NOT NULL,
  `Nome` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `cursos`
--

INSERT INTO `cursos` (`ID`, `Nome`) VALUES
(1, 'Desenvolvimento Web e Multimédia'),
(6, 'Audiovisual Digital');

-- --------------------------------------------------------

--
-- Estrutura da tabela `disciplinas`
--

CREATE TABLE `disciplinas` (
  `ID` int(11) NOT NULL,
  `Nome_disc` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `disciplinas`
--

INSERT INTO `disciplinas` (`ID`, `Nome_disc`) VALUES
(1, 'Matemática'),
(2, 'Programação WEB I'),
(3, 'Linguagens de Programação'),
(4, 'Português');

-- --------------------------------------------------------

--
-- Estrutura da tabela `grupos`
--

CREATE TABLE `grupos` (
  `ID` int(11) NOT NULL,
  `GRUPO` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `grupos`
--

INSERT INTO `grupos` (`ID`, `GRUPO`) VALUES
(1, 'ADMIN'),
(2, 'ALUNO');

-- --------------------------------------------------------

--
-- Estrutura da tabela `pautas`
--

CREATE TABLE `pautas` (
  `id` int(11) NOT NULL,
  `aluno_id` int(11) NOT NULL,
  `disciplina_id` int(11) NOT NULL,
  `nota` decimal(4,1) DEFAULT NULL,
  `epoca` enum('normal','recurso','especial') DEFAULT 'normal',
  `data_lancamento` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `pautas`
--

INSERT INTO `pautas` (`id`, `aluno_id`, `disciplina_id`, `nota`, `epoca`, `data_lancamento`) VALUES
(1, 8, 2, 9.9, 'normal', '2026-03-19 16:57:48'),
(14, 8, 2, 0.0, 'recurso', '2026-03-19 16:56:27'),
(20, 8, 2, 12.0, 'especial', '2026-03-19 16:56:00'),
(30, 9, 2, 17.0, 'normal', '2026-03-19 17:00:04');

-- --------------------------------------------------------

--
-- Estrutura da tabela `plano_estudos`
--

CREATE TABLE `plano_estudos` (
  `CURSOS` int(11) NOT NULL,
  `DISCIPLINA` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `plano_estudos`
--

INSERT INTO `plano_estudos` (`CURSOS`, `DISCIPLINA`) VALUES
(1, 1),
(1, 2),
(1, 3),
(1, 4),
(5, 3),
(5, 1),
(5, 4),
(6, 3),
(6, 1),
(6, 4),
(6, 2);

-- --------------------------------------------------------

--
-- Estrutura da tabela `professores`
--

CREATE TABLE `professores` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `foto` varchar(255) DEFAULT 'default.png'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `professores`
--

INSERT INTO `professores` (`id`, `nome`, `email`, `password`, `foto`) VALUES
(2, 'António Machado', 'antonio@ipca.pt', '$2y$10$mY8BnTol4Qda9pJI3gIr8uiAuRMsv9aSkUfrIBCds3hPpXvcZDJCO', 'default.png');

-- --------------------------------------------------------

--
-- Estrutura da tabela `prof_disciplinas`
--

CREATE TABLE `prof_disciplinas` (
  `professor_id` int(11) NOT NULL,
  `disciplina_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `prof_disciplinas`
--

INSERT INTO `prof_disciplinas` (`professor_id`, `disciplina_id`) VALUES
(2, 2);

-- --------------------------------------------------------

--
-- Estrutura da tabela `users`
--

CREATE TABLE `users` (
  `login` varchar(20) NOT NULL,
  `pwd` varchar(250) NOT NULL,
  `tipo` enum('admin','funcionario') DEFAULT 'funcionario',
  `grupo` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `users`
--

INSERT INTO `users` (`login`, `pwd`, `tipo`, `grupo`) VALUES
('gestor1', '202cb962ac59075b964b07152d234b70', 'admin', 1);

--
-- Índices para tabelas despejadas
--

--
-- Índices para tabela `alunos`
--
ALTER TABLE `alunos`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `candidaturas`
--
ALTER TABLE `candidaturas`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `cursos`
--
ALTER TABLE `cursos`
  ADD PRIMARY KEY (`ID`);

--
-- Índices para tabela `disciplinas`
--
ALTER TABLE `disciplinas`
  ADD PRIMARY KEY (`ID`);

--
-- Índices para tabela `grupos`
--
ALTER TABLE `grupos`
  ADD PRIMARY KEY (`ID`);

--
-- Índices para tabela `pautas`
--
ALTER TABLE `pautas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_aluno_disc_epoca` (`aluno_id`,`disciplina_id`,`epoca`);

--
-- Índices para tabela `professores`
--
ALTER TABLE `professores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_email` (`email`);

--
-- Índices para tabela `prof_disciplinas`
--
ALTER TABLE `prof_disciplinas`
  ADD PRIMARY KEY (`professor_id`,`disciplina_id`);

--
-- Índices para tabela `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`login`);

--
-- AUTO_INCREMENT de tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `alunos`
--
ALTER TABLE `alunos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de tabela `candidaturas`
--
ALTER TABLE `candidaturas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de tabela `cursos`
--
ALTER TABLE `cursos`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de tabela `disciplinas`
--
ALTER TABLE `disciplinas`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `grupos`
--
ALTER TABLE `grupos`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `pautas`
--
ALTER TABLE `pautas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT de tabela `professores`
--
ALTER TABLE `professores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
