-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Tempo de geração: 18/11/2025 às 06:29
-- Versão do servidor: 9.1.0
-- Versão do PHP: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `ibisa`
--
CREATE DATABASE IF NOT EXISTS `ibisa` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
USE `ibisa`;

-- --------------------------------------------------------

--
-- Estrutura para tabela `categoria`
--

DROP TABLE IF EXISTS `categoria`;
CREATE TABLE IF NOT EXISTS `categoria` (
  `id_categoria` int NOT NULL AUTO_INCREMENT,
  `nome_categoria` varchar(100) NOT NULL,
  PRIMARY KEY (`id_categoria`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Despejando dados para a tabela `categoria`
--

INSERT INTO `categoria` (`id_categoria`, `nome_categoria`) VALUES
(2, 'Brinco'),
(3, 'Corrente'),
(4, 'Anel'),
(5, 'Pulseira'),
(6, 'Pingente');

-- --------------------------------------------------------

--
-- Estrutura para tabela `enderecos`
--

DROP TABLE IF EXISTS `enderecos`;
CREATE TABLE IF NOT EXISTS `enderecos` (
  `id_endereco` int NOT NULL AUTO_INCREMENT,
  `id_usuario` int NOT NULL,
  `tipo_endereco` varchar(50) DEFAULT 'Casa',
  `cep` varchar(10) NOT NULL,
  `rua` varchar(250) NOT NULL,
  `numerocasa` varchar(10) NOT NULL,
  `bairro` varchar(200) NOT NULL,
  `cidade` varchar(100) NOT NULL,
  `uf` char(2) NOT NULL,
  PRIMARY KEY (`id_endereco`),
  KEY `fk_endereco_usuario` (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `estoque`
--

DROP TABLE IF EXISTS `estoque`;
CREATE TABLE IF NOT EXISTS `estoque` (
  `id_movimentacao` int NOT NULL AUTO_INCREMENT,
  `id_produto` int NOT NULL,
  `data` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `tipo_movimentacao` enum('Entrada','Saída') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `quantidade` int NOT NULL,
  `motivo` varchar(255) NOT NULL,
  `valor_custo_unitario` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`id_movimentacao`),
  KEY `fk_mov_produto` (`id_produto`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `forma_pagamento`
--

DROP TABLE IF EXISTS `forma_pagamento`;
CREATE TABLE IF NOT EXISTS `forma_pagamento` (
  `id_forma_pagamento` int NOT NULL AUTO_INCREMENT,
  `nome_forma_pagamento` varchar(100) NOT NULL,
  PRIMARY KEY (`id_forma_pagamento`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Despejando dados para a tabela `forma_pagamento`
--

INSERT INTO `forma_pagamento` (`id_forma_pagamento`, `nome_forma_pagamento`) VALUES
(1, 'Pix'),
(2, 'Cartão de Crédito'),
(3, 'Boleto Bancário');

-- --------------------------------------------------------

--
-- Estrutura para tabela `itens_venda`
--

DROP TABLE IF EXISTS `itens_venda`;
CREATE TABLE IF NOT EXISTS `itens_venda` (
  `id_item_venda` int NOT NULL AUTO_INCREMENT,
  `id_venda` int NOT NULL,
  `id_produto` int NOT NULL,
  `quantidade` int NOT NULL,
  `preco_unitario` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id_item_venda`),
  KEY `fk_item_venda` (`id_venda`),
  KEY `fk_item_produto` (`id_produto`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Despejando dados para a tabela `itens_venda`
--

INSERT INTO `itens_venda` (`id_item_venda`, `id_venda`, `id_produto`, `quantidade`, `preco_unitario`) VALUES
(1, 3, 35, 1, 99.00),
(2, 4, 18, 1, 120.00),
(3, 5, 18, 1, 120.00),
(4, 6, 23, 5, 115.00),
(5, 7, 18, 1, 120.00),
(6, 7, 35, 1, 99.00),
(7, 8, 24, 1, 110.00),
(8, 9, 26, 1, 85.00),
(9, 9, 27, 1, 110.00);

-- --------------------------------------------------------

--
-- Estrutura para tabela `mensagens_contato`
--

DROP TABLE IF EXISTS `mensagens_contato`;
CREATE TABLE IF NOT EXISTS `mensagens_contato` (
  `id_mensagem` int NOT NULL AUTO_INCREMENT,
  `id_usuario` int DEFAULT NULL,
  `nome` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `email` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `assunto` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `mensagem` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `data_envio` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('Pendente','Lida') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'Pendente',
  PRIMARY KEY (`id_mensagem`),
  KEY `fk_mensagem_usuario` (`id_usuario`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Despejando dados para a tabela `mensagens_contato`
--

INSERT INTO `mensagens_contato` (`id_mensagem`, `id_usuario`, `nome`, `email`, `assunto`, `mensagem`, `data_envio`, `status`) VALUES
(1, NULL, 'Otávio Francisco Farina', 'o@gmail.com', 'Estou com problemas de login', 'Nas últimas vezes que tentei efetuar o login no sistema tive dificuldades', '2025-11-15 16:23:34', 'Lida'),
(2, NULL, 'Otávio Francisco Farina', 'o@gmail.com', 'Pomba', 'Teste123', '2025-11-15 16:40:45', 'Lida'),
(3, NULL, 'Otávio Francisco Farina', 'o@gmail.com', 'Olá', 'Boa noite!!', '2025-11-15 21:57:29', 'Lida'),
(4, NULL, 'Otávio Francisco Farina', 'o@gmail.com', 'Teste', '1234', '2025-11-17 05:19:15', 'Lida'),
(5, NULL, 'Otávio Francisco Farina', 'o@gmail.com', 'Diego bomba', 'MASCOSAMASMSMDASMDMSADSADOSMDSADADMSADOMDOMDSAMDAMDosamdao', '2025-11-17 16:06:26', 'Pendente');

-- --------------------------------------------------------

--
-- Estrutura para tabela `produto`
--

DROP TABLE IF EXISTS `produto`;
CREATE TABLE IF NOT EXISTS `produto` (
  `id_produto` int NOT NULL AUTO_INCREMENT,
  `nome_produto` varchar(200) NOT NULL,
  `id_categoria` int NOT NULL,
  `preco` decimal(10,2) NOT NULL,
  `estoque` int NOT NULL DEFAULT '0',
  `imagem` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id_produto`),
  KEY `fk_produto_categoria` (`id_categoria`)
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Despejando dados para a tabela `produto`
--

INSERT INTO `produto` (`id_produto`, `nome_produto`, `id_categoria`, `preco`, `estoque`, `imagem`) VALUES
(18, 'Anel', 4, 110.00, 13, 'produto_690186de4dece.jpg'),
(19, 'Brinco de Pérola', 2, 70.00, 6, 'produto_69018721e3679.jpg'),
(21, 'Pingente de Coração', 6, 65.00, 13, 'produto_690187527d1bc.jpg'),
(22, 'Pulseira preta', 5, 40.00, 12, 'produto_69018767132be.jpg'),
(23, 'Anel de Ouro', 4, 115.00, 34, 'produto_69165c2a0b964.jpg'),
(24, 'Anel com Pedra', 4, 110.00, 29, 'produto_69165c4a3838c.jpg'),
(25, 'Anel de Esmeralda', 4, 160.00, 40, 'produto_69165d335759d.jpg'),
(26, 'Brico Gado', 2, 85.00, 11, 'produto_691668ad4ffe2.jpg'),
(27, 'Brinco Pedra Azul', 2, 110.00, 14, 'produto_691669065d829.jpg'),
(28, 'Brinco Ponta  de Luz', 2, 50.00, 50, 'produto_6916693f6b24c.jpg'),
(29, 'Corrente de Ouro', 3, 95.00, 2, 'produto_691669ab4c034.jpg'),
(30, 'Corrente de Prata', 3, 100.00, 1, 'produto_691669cb8896a.jpg'),
(31, 'Colar de Pedras', 3, 110.00, 4, 'produto_691669e273d1d.jpg'),
(32, 'Colar de Pérolas', 3, 90.00, 5, 'produto_69166a2ff1a79.jpg'),
(33, 'Pingente de Nossa Senhora', 6, 60.00, 10, 'produto_69166c63f0280.jpg'),
(34, 'Pingente Meia Lua', 6, 45.00, 16, 'produto_69166c748845c.jpg'),
(35, 'Pingente de Esmeralda', 6, 99.00, 13, 'produto_69166c8a125eb.jpg'),
(36, 'Pulseira de Prata', 5, 76.00, 12, 'produto_69166d026fca3.jpg'),
(38, 'Pulseira de Ouro', 5, 78.00, 19, 'produto_69166d70a4960.jpg'),
(39, 'Pulseira de Coração', 5, 100.00, 20, 'produto_69166da7189e1.jpg');

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id_usuario` int NOT NULL AUTO_INCREMENT,
  `tipo` int NOT NULL DEFAULT '1',
  `conta_status` enum('Ativa','Bloqueada') NOT NULL DEFAULT 'Ativa' COMMENT 'Controla se a conta do usuário está ativa ou bloqueada pelo admin',
  `nome_usuario` varchar(200) NOT NULL,
  `email` varchar(200) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `telefone` varchar(50) NOT NULL,
  `cpf` varchar(100) NOT NULL,
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `cpf` (`cpf`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`id_usuario`, `tipo`, `conta_status`, `nome_usuario`, `email`, `senha`, `telefone`, `cpf`) VALUES
(1, 1, 'Ativa', 'Otávio Francisco Farina', 'o@gmail.com', '$2y$10$MSVP/qOhQ3za3QTkOjGiLOM1RO8yUcM5Efj2JQoXKsuMZTBDX2wT2', '19994485049', '52645913857'),
(2, 2, 'Ativa', 'Lívia Cordeiro de Moraes', 'li@gmail.com', '$2y$10$/lp9dvA3oHmCR/xYF6oIYufntnSfqn/9O.WAMrJHw9ox5Ojb693ra', '19971585843', '57753526880'),
(3, 1, 'Ativa', 'Gabriel Vinicius da Silva Batista', 'b@gmail.com', '$2y$10$rsOoqlmVikoAEDLQLlNmYO1.EG5bpHRwgxfAXmthX/0YOiFQQaP/S', '19988380293', '44444444444'),
(4, 1, 'Ativa', 'Diego Figueiredo', 'didi@gmail.com', '$2y$10$GbondVob18G8Qo7IA4nRZ.ZDQjsZAMaSqe96hQyMmjXVePht7k1LW', '1991483000', '38835108829');

-- --------------------------------------------------------

--
-- Estrutura para tabela `vendas`
--

DROP TABLE IF EXISTS `vendas`;
CREATE TABLE IF NOT EXISTS `vendas` (
  `id_venda` int NOT NULL AUTO_INCREMENT,
  `data` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `valor_total` decimal(10,2) NOT NULL,
  `id_forma_pagamento` int NOT NULL,
  `id_usuario` int NOT NULL,
  `status` enum('Aguardando Confirmação','Pagamento Aprovado','Em Preparação','Em Trânsito','Saiu para Entrega','Entregue','Cancelado') NOT NULL DEFAULT 'Aguardando Confirmação',
  PRIMARY KEY (`id_venda`),
  KEY `fk_venda_usuario` (`id_usuario`),
  KEY `fk_venda_formapag` (`id_forma_pagamento`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Despejando dados para a tabela `vendas`
--

INSERT INTO `vendas` (`id_venda`, `data`, `valor_total`, `id_forma_pagamento`, `id_usuario`, `status`) VALUES
(3, '2025-11-13 22:57:18', 99.00, 1, 1, 'Cancelado'),
(4, '2025-11-13 23:01:49', 120.00, 2, 1, 'Saiu para Entrega'),
(5, '2025-11-13 23:02:21', 120.00, 2, 1, 'Em Preparação'),
(6, '2025-11-13 23:07:06', 575.00, 1, 1, 'Pagamento Aprovado'),
(7, '2025-11-14 13:00:14', 219.00, 3, 1, 'Entregue'),
(8, '2025-11-15 21:57:01', 110.00, 3, 1, 'Pagamento Aprovado'),
(9, '2025-11-17 05:19:42', 195.00, 3, 1, 'Saiu para Entrega');

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `enderecos`
--
ALTER TABLE `enderecos`
  ADD CONSTRAINT `fk_endereco_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restrições para tabelas `estoque`
--
ALTER TABLE `estoque`
  ADD CONSTRAINT `fk_mov_produto` FOREIGN KEY (`id_produto`) REFERENCES `produto` (`id_produto`) ON DELETE CASCADE;

--
-- Restrições para tabelas `itens_venda`
--
ALTER TABLE `itens_venda`
  ADD CONSTRAINT `fk_item_produto` FOREIGN KEY (`id_produto`) REFERENCES `produto` (`id_produto`),
  ADD CONSTRAINT `fk_item_venda` FOREIGN KEY (`id_venda`) REFERENCES `vendas` (`id_venda`);

--
-- Restrições para tabelas `mensagens_contato`
--
ALTER TABLE `mensagens_contato`
  ADD CONSTRAINT `fk_mensagem_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Restrições para tabelas `produto`
--
ALTER TABLE `produto`
  ADD CONSTRAINT `fk_produto_categoria` FOREIGN KEY (`id_categoria`) REFERENCES `categoria` (`id_categoria`);

--
-- Restrições para tabelas `vendas`
--
ALTER TABLE `vendas`
  ADD CONSTRAINT `fk_venda_formapag` FOREIGN KEY (`id_forma_pagamento`) REFERENCES `forma_pagamento` (`id_forma_pagamento`),
  ADD CONSTRAINT `fk_venda_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
