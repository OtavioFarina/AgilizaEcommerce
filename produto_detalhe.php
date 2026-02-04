<?php
require_once 'session_config.php';
require_once 'conexao.php';
require_once 'functions.php';

// 1. Verifica se o ID foi passado
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$id_produto = intval($_GET['id']);
$produto = null;
$categorias = [];

try {
    // 2. Busca o produto detalhado
    $stmt = $pdo->prepare("
        SELECT p.*, c.nome_categoria 
        FROM produto p
        JOIN categoria c ON p.id_categoria = c.id_categoria
        WHERE p.id_produto = :id
    ");
    $stmt->execute([':id' => $id_produto]);
    $produto = $stmt->fetch(PDO::FETCH_ASSOC);

    // 3. Busca as categorias para o MENU (igual na index)
    $stmt_cat = $pdo->query("SELECT id_categoria, nome_categoria FROM categoria ORDER BY nome_categoria ASC");
    $categorias = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $erro = "Erro ao carregar dados.";
}

// Se não achou o produto, volta
if (!$produto) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($produto['nome_produto']); ?> - Ibisa Acessórios</title>
    <link rel="icon" href="img/background/favicon.jpg" type="img/jpg">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>

    <style>
        /* --- CSS GERAL (Copiado da Index) --- */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Montserrat', sans-serif;
            background-color: #fdfdfd;
            color: #333;
            line-height: 1.6;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        main {
            flex: 1;
        }

        a {
            text-decoration: none;
            color: inherit;
            transition: color 0.3s ease;
        }

        ul {
            list-style: none;
        }

        img {
            max-width: 100%;
            display: block;
        }

        /* HEADER */
        header {
            width: 100%;
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #ffffff;
            border-bottom: 1px solid #eee;
            position: sticky;
            top: 0;
            z-index: 1000;
            transition: all 0.3s ease;
        }

        header.sticky {
            background-color: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(5px);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .logo {
            font-size: 1.8rem;
            font-weight: 700;
            letter-spacing: 1px;
            color: #b8860b;
        }

        nav ul {
            display: flex;
            gap: 25px;
        }

        nav a {
            font-weight: 500;
            font-size: 1rem;
        }

        nav a:hover {
            color: #b8860b;
        }

        .nav-icons {
            display: flex;
            gap: 15px;
            font-weight: 500;
            align-items: center;
            flex-shrink: 0;
            white-space: nowrap;
        }

        .nav-icons .admin-link {
            color: #b8860b;
            font-weight: 700;
            border: 1px solid #b8860b;
            padding: 5px 10px;
            border-radius: 5px;
            transition: all 0.3s ease;
        }

        .nav-icons .admin-link:hover {
            background-color: #b8860b;
            color: #fff;
        }

        /* --- ESTILO ESPECÍFICO DESTA PÁGINA (Detalhe do Produto) --- */
        .product-detail-container {
            max-width: 1100px;
            margin: 40px auto;
            padding: 20px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 50px;
            align-items: start;
        }

        .product-image-box {
            background-color: #fff;
            border: 1px solid #eee;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }

        .product-image-box img {
            width: 100%;
            height: auto;
            object-fit: cover;
        }

        .product-info-box {
            padding: 10px;
        }

        .breadcrumb {
            font-size: 0.9rem;
            color: #888;
            margin-bottom: 15px;
        }

        .breadcrumb a:hover {
            text-decoration: underline;
            color: #b8860b;
        }

        .product-title {
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 10px;
            line-height: 1.2;
        }

        .product-price {
            font-size: 1.8rem;
            color: #b8860b;
            font-weight: 700;
            margin-bottom: 20px;
        }

        .product-description {
            font-size: 1rem;
            color: #555;
            line-height: 1.8;
            margin-bottom: 30px;
        }

        .stock-status {
            display: inline-block;
            margin-bottom: 20px;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .in-stock {
            color: #28a745;
        }

        .out-stock {
            color: #dc3545;
        }

        .btn-buy-large {
            display: block;
            width: 100%;
            padding: 18px;
            background-color: #b8860b;
            color: white;
            text-align: center;
            font-size: 1.2rem;
            font-weight: 700;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s, transform 0.2s;
            text-transform: uppercase;
        }

        .btn-buy-large:hover {
            background-color: #a0740a;
            transform: translateY(-2px);
        }

        /* --- CSS DO FOOTER (Copiado da Index) --- */
        .site-footer {
            background-color: #222;
            color: #ccc;
            padding-top: 60px;
            font-size: 0.9rem;
            line-height: 1.7;
            margin-top: auto;
        }

        .footer-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .footer-main {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 30px;
            border-bottom: 1px solid #444;
            padding-bottom: 40px;
            margin-bottom: 30px;
        }

        .footer-col h4 {
            font-size: 1rem;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 20px;
            text-transform: uppercase;
        }

        .footer-col ul li {
            margin-bottom: 10px;
        }

        .footer-col ul li a:hover {
            color: #b8860b;
            text-decoration: underline;
        }

        .footer-col p {
            margin-bottom: 10px;
        }

        .footer-col .icon-text {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .footer-col .icon-text i {
            font-size: 1.3rem;
            color: #ffffff;
        }

        .footer-socials {
            display: flex;
            gap: 15px;
        }

        .footer-socials a {
            font-size: 1.8rem;
            color: #ffffff;
        }

        .footer-socials a:hover {
            color: #b8860b;
        }

        .footer-bottom {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 30px;
            padding-bottom: 40px;
            border-bottom: 1px solid #444;
            margin-bottom: 20px;
        }

        .footer-payment h4,
        .footer-security h4 {
            font-size: 1rem;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 20px;
            text-transform: uppercase;
        }

        .payment,
        .security-seals {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
        }

        .payment img {
            max-height: 28px;
            margin-right: 8px;
            margin-bottom: 8px;
            filter: grayscale(1) brightness(1.5);
            display: inline-block;
        }

        .security-seals img {
            max-height: 40px;
            margin-right: 8px;
            display: inline-block;
        }

        .footer-sub {
            text-align: center;
            font-size: 0.8rem;
            color: #888;
            padding-bottom: 30px;
        }

        .footer-sub p {
            margin-bottom: 5px;
        }

        .footer-powered {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 15px;
            margin-top: 20px;
        }

        .footer-powered span {
            font-size: 0.8rem;
            color: #aaa;
        }

        .footer-powered img {
            max-height: 30px;
            filter: grayscale(1) brightness(1.5);
        }

        @media (max-width: 768px) {
            .footer-main {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            }

            .footer-bottom {
                flex-direction: column;
            }

            .product-detail-container {
                grid-template-columns: 1fr;
                gap: 30px;
            }
        }

        /* --- CSS DO CARRINHO & MODAIS (Copiado da Index) --- */
        .cart-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1999;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }

        .cart-sidebar {
            position: fixed;
            top: 0;
            right: 0;
            width: 100%;
            max-width: 400px;
            height: 100%;
            background-color: #ffffff;
            z-index: 2000;
            box-shadow: -5px 0 15px rgba(0, 0, 0, 0.15);
            transform: translateX(100%);
            transition: transform 0.3s ease-in-out;
            display: flex;
            flex-direction: column;
        }

        body.cart-open .cart-overlay {
            opacity: 1;
            visibility: visible;
        }

        body.cart-open .cart-sidebar {
            transform: translateX(0);
        }

        .cart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            border-bottom: 1px solid #eee;
        }

        .cart-header h3 {
            font-size: 1.2rem;
            font-weight: 700;
        }

        .cart-close-btn {
            font-size: 1.8rem;
            cursor: pointer;
            color: #888;
        }

        .cart-close-btn:hover {
            color: #333;
        }

        .cart-body {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
        }

        .cart-item {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }

        .cart-item img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 4px;
            border: 1px solid #eee;
        }

        .cart-item-info {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .cart-item-info h4 {
            font-size: 0.95rem;
            font-weight: 500;
            margin-bottom: 5px;
        }

        .cart-item-info .price {
            font-size: 1rem;
            font-weight: 700;
            color: #b8860b;
            margin-bottom: 10px;
        }

        .cart-item-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .quantity-control {
            display: flex;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .quantity-control button {
            border: none;
            background: #f5f5f5;
            padding: 0 10px;
            cursor: pointer;
            font-size: 1.2rem;
        }

        .quantity-control button:hover {
            background: #e0e0e0;
        }

        .quantity-control span {
            padding: 5px 12px;
            font-weight: 500;
        }

        .remove-item-btn {
            font-size: 0.9rem;
            color: #dc3545;
            cursor: pointer;
        }

        .remove-item-btn:hover {
            text-decoration: underline;
        }

        .cart-footer {
            padding: 20px;
            border-top: 1px solid #eee;
            box-shadow: 0 -5px 10px rgba(0, 0, 0, 0.05);
        }

        .cart-total {
            display: flex;
            justify-content: space-between;
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 20px;
        }

        .btn-checkout {
            display: block;
            width: 100%;
            padding: 15px;
            text-align: center;
            background-color: #b8860b;
            color: white;
            border: none;
            border-radius: 5px;
            font-weight: 700;
            font-size: 1.1rem;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .btn-checkout:hover {
            background-color: #a0740a;
        }

        .cart-empty-message {
            text-align: center;
            padding: 40px 0;
            color: #888;
        }

        /* MODAIS */
        .modal-info {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 2000;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }

        .modal-info.is-active {
            opacity: 1;
            visibility: visible;
        }

        .modal-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            cursor: pointer;
        }

        .modal-container {
            position: relative;
            background-color: #ffffff;
            border-radius: 8px;
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            transform: scale(0.9);
            transition: transform 0.3s ease;
        }

        .modal-info.is-active .modal-container {
            transform: scale(1);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 25px;
            border-bottom: 1px solid #eee;
        }

        .modal-header h2 {
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 0;
            color: #333;
        }

        .modal-close-btn {
            font-size: 1.8rem;
            color: #888;
            cursor: pointer;
            transition: color 0.2s ease;
        }

        .modal-close-btn:hover {
            color: #333;
        }

        .modal-body {
            padding: 25px;
            overflow-y: auto;
            font-size: 0.95rem;
            line-height: 1.7;
            color: #555;
        }

        .modal-body p {
            margin-bottom: 15px;
        }

        .modal-body p:last-child {
            margin-bottom: 0;
        }

        /* --- CSS DO MENU RESPONSIVO (HAMBÚRGUER) --- */

        /* 1. Estilo Padrão (Desktop) - O botão fica escondido */
        .menu-toggle {
            display: none;
            font-size: 1.8rem;
            cursor: pointer;
            color: #333;
        }

        /* 2. Regras para Telas Menores (Tablets e Celulares) */
        @media (max-width: 860px) {

            /* Ajuste do Header para o botão caber bem */
            header {
                padding: 15px 20px;
                /* Um pouco menos de padding */
                flex-wrap: wrap;
                /* Permite que itens quebrem linha se necessário */
            }

            /* Mostrar o botão hambúrguer */
            .menu-toggle {
                display: block;
                order: 3;
                /* Força o botão a ficar na direita, se quiser */
                /* Ou ajuste a ordem conforme preferir: Logo(1), Icones(2), Menu(3) */
            }

            /* Mudar a ordem dos ícones para ficarem sempre visíveis */
            .nav-icons {
                margin-left: auto;
                /* Empurra para a direita */
                margin-right: 15px;
                /* Espaço entre ícones e hambúrguer */
            }

            /* O MENU (NAV) EM SI - O Dropdown */
            .navbar {
                position: absolute;
                top: 100%;
                /* Logo abaixo do header */
                left: 0;
                width: 100%;
                background-color: #ffffff;
                border-top: 1px solid #eee;
                box-shadow: 0 5px 10px rgba(0, 0, 0, 0.05);

                /* Efeito de esconder/mostrar suave */
                max-height: 0;
                overflow: hidden;
                transition: max-height 0.4s ease-in-out;
                z-index: 999;
            }

            /* Classe que o JS vai adicionar para MOSTRAR o menu */
            .navbar.active {
                max-height: 500px;
                /* Altura suficiente para os links */
            }

            /* Estilo dos Links no Mobile */
            .navbar ul {
                display: flex;
                flex-direction: column;
                /* Links um embaixo do outro */
                align-items: center;
                padding: 20px 0;
                gap: 20px;
            }

            .navbar ul li {
                width: 100%;
                text-align: center;
            }

            .navbar ul li a {
                display: block;
                width: 100%;
                padding: 10px 0;
                font-size: 1.1rem;
            }

            .navbar ul li a:hover {
                background-color: #f9f9f9;
                color: #b8860b;
            }
        }
    </style>
</head>

<body>
    <header id="main-header">
        <div class="logo">IBISA</div>

        <div class="menu-toggle" id="mobile-menu">
            <i class='bx bx-menu'></i>
        </div>

        <nav class="navbar">
            <ul>
                <li><a href="index.php">Início</a></li>
                <li><a href="index.php#destaques">Destaques</a></li>

                <?php foreach ($categorias as $categoria): ?>
                    <li>
                        <a href="index.php#<?php echo criarAncora($categoria['nome_categoria']); ?>">
                            <?php echo htmlspecialchars($categoria['nome_categoria']); ?>
                        </a>
                    </li>
                <?php endforeach; ?>

                <li><a href="#sobre">Sobre</a></li>
            </ul>
        </nav>

        <div class="nav-icons">
            <?php if (isset($_SESSION['usuario_logado']) && $_SESSION['usuario_logado'] === true): ?>
                <a href="perfil.php">Olá,
                    <strong><?php echo htmlspecialchars(explode(' ', $_SESSION['usuario_nome'])[0]); ?></strong></a>
                <?php if (isset($_SESSION['usuario_tipo']) && $_SESSION['usuario_tipo'] == 2): ?>
                    <a href="adm/painel_admin.php" class="admin-link">Painel ADM</a>
                <?php endif; ?>
                <a href="logout.php">Sair</a>
            <?php else: ?>
                <a href="conta.php">Login / Cadastro</a>
            <?php endif; ?>

            <a href="#" id="open-cart-btn" style="display: flex; align-items: center; gap: 5px;">
                <i class='bx bx-cart' style="font-size: 1.3rem;"></i>
                <span id="cart-count">(0)</span>
            </a>
        </div>
    </header>

    <main>
        <div class="product-detail-container">
            <div class="product-image-box">
                <img src="<?php echo !empty($produto['imagem']) ? 'adm/uploads/' . htmlspecialchars($produto['imagem']) : 'img/placeholder.png'; ?>"
                    alt="<?php echo htmlspecialchars($produto['nome_produto']); ?>">
            </div>

            <div class="product-info-box">
                <div class="breadcrumb">
                    <a href="index.php">Início</a> >
                    <a href="index.php#<?php echo criarAncora($produto['nome_categoria']); ?>">
                        <?php echo htmlspecialchars($produto['nome_categoria']); ?>
                    </a> >
                    <span>Visualizando Produto</span>
                </div>

                <h1 class="product-title"><?php echo htmlspecialchars($produto['nome_produto']); ?></h1>

                <p class="product-price">R$ <?php echo number_format($produto['preco'], 2, ',', '.'); ?></p>

                <?php if ($produto['estoque'] > 0): ?>
                    <span class="stock-status in-stock"><i class='bx bx-check-circle'></i> Em estoque</span>
                <?php else: ?>
                    <span class="stock-status out-stock"><i class='bx bx-x-circle'></i> Esgotado</span>
                <?php endif; ?>

                <div class="product-description">
                    <p>Esta é uma peça exclusiva da coleção <?php echo htmlspecialchars($produto['nome_categoria']); ?>
                        da Ibisa Acessórios.
                        Produzida com materiais de alta qualidade para garantir durabilidade e brilho intenso.
                        Ideal para compor seu look com elegância e sofisticação.</p>
                    <p>Garantia de qualidade Ibisa. Acompanha embalagem especial para presente.</p>
                </div>

                <?php if ($produto['estoque'] > 0): ?>
                    <button class="btn-buy-large btn-comprar" data-id="<?php echo $produto['id_produto']; ?>">
                        ADICIONAR AO CARRINHO
                    </button>
                <?php else: ?>
                    <button class="btn-buy-large" disabled style="background-color: #ccc; cursor: not-allowed;">
                        INDISPONÍVEL
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <div class="cart-overlay" id="cart-overlay"></div>
    <aside class="cart-sidebar" id="cart-sidebar"></aside>

    <footer class="site-footer" id="sobre">
        <div class="footer-container">
            <div class="footer-main">
                <div class="footer-col">
                    <h4>INSTITUCIONAL</h4>
                    <ul>
                        <li><a href="#" class="modal-trigger" data-target="modal-quem-somos">Quem somos</a></li>
                        <li><a href="#" class="modal-trigger" data-target="modal-trocas">Política de Trocas e
                                Garantia</a></li>
                        <li><a href="#" class="modal-trigger" data-target="modal-privacidade">Política de
                                Privacidade</a></li>
                        <li><a href="#" class="modal-trigger" data-target="modal-representantes">Representantes</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>AJUDA</h4>
                    <ul>
                        <li><a href="#" class="modal-trigger" data-target="modal-duvidas">Dúvidas frequentes</a></li>
                        <li><a href="#" class="modal-trigger" data-target="modal-fale-conosco">Fale conosco</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>MEUS DADOS</h4>
                    <ul>
                        <li><a href="perfil.php">Minha conta</a></li>
                        <li><a href="pedidos.php">Meus pedidos</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>ATENDIMENTO</h4>
                    <p class="icon-text"><i class='bx bxl-whatsapp'></i> (19) 98838-0293</p>
                    <p class="icon-text"><i class='bx bx-phone'></i> (19) 99448-5049</p>
                    <h4 style="margin-top: 20px;">SIGA A GENTE</h4>
                    <div class="footer-socials">
                        <a href="https://www.instagram.com/ibisa.acessorios/" title="Instagram"><i
                                class='bx bxl-instagram-alt'></i></a>
                        <a href="#" title="Facebook"><i class='bx bxl-facebook-circle'></i></a>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <div class="footer-payment">
                    <h4>FORMAS DE PAGAMENTO</h4>
                    <div class="payment">
                        <img src="img/selos-pagamento/mastercard.webp" alt="Mastercard">
                        <img src="img/selos-pagamento/visa.webp" alt="Visa">
                        <img src="img/selos-pagamento/elo.webp" alt="Elo">
                        <img src="img/selos-pagamento/pix.webp" alt="Pix">
                    </div>
                </div>
                <div class="footer-security">
                    <h4>SELOS DE SEGURANÇA</h4>
                    <div class="security-seals">
                        <img src="img/selos-pagamento/letsencrypt.webp" alt="Let's Encrypt">
                    </div>
                </div>
            </div>
        </div>
        <div class="footer-sub">
            <div class="footer-container">
                <p>Ibisa Acessórios LTDA - CNPJ 00.000.000/0001-00</p>
                <p>Endereço: Rua Pombo, 110, 13º andar, CEP 11111-000, São Paulo-SP</p>
                <div class="footer-powered">
                    <span>Powered by BURDOGUI</span>
                    <span>Developed by BURDOGUI</span>
                </div>
            </div>
        </div>
    </footer>

    <script>
        const header = document.getElementById('main-header');
        function handleScroll() { if (window.scrollY > 50) { header.classList.add('sticky'); } else { header.classList.remove('sticky'); } }
        window.addEventListener('scroll', handleScroll);

        document.addEventListener('DOMContentLoaded', () => {
            const openCartBtn = document.getElementById('open-cart-btn');
            const cartSidebar = document.getElementById('cart-sidebar');
            const cartOverlay = document.getElementById('cart-overlay');
            const cartCountEl = document.getElementById('cart-count');

            function openCart() { document.body.classList.add('cart-open'); }
            function closeCart() { document.body.classList.remove('cart-open'); }

            function renderizarCarrinho(data) {
                cartCountEl.textContent = `(${data.total_itens || 0})`;
                if (!data.itens || data.itens.length === 0) {
                    cartSidebar.innerHTML = `<div class="cart-header"><h3>Meu Carrinho (0)</h3><i class='bx bx-x cart-close-btn' id="cart-close-btn"></i></div><div class="cart-body"><p class="cart-empty-message">Seu carrinho está vazio.</p></div><div class="cart-footer"><button class="btn-checkout" disabled style="background-color: #aaa; cursor: not-allowed;">Finalizar Compra</button></div>`;
                    return;
                }
                let itensHTML = '';
                data.itens.forEach(item => {
                    itensHTML += `<div class="cart-item"><img src="${item.imagem}" alt="${item.nome}"><div class="cart-item-info"><h4>${item.nome}</h4><span class="price">R$ ${item.preco}</span><div class="cart-item-controls"><div class="quantity-control"><button class="btn-qty-decrease" data-id="${item.id}" ${item.quantidade <= 1 ? 'disabled' : ''}>-</button><span class="qty-display" data-id="${item.id}">${item.quantidade}</span><button class="btn-qty-increase" data-id="${item.id}" ${item.quantidade >= item.estoque_max ? 'disabled' : ''}>+</button></div><span class="remove-item-btn" data-id="${item.id}">Remover</span></div>${item.quantidade >= item.estoque_max ? '<span style="color:red; font-size:0.8rem; margin-top:5px;">Estoque máximo atingido</span>' : ''}</div></div>`;
                });
                cartSidebar.innerHTML = `<div class="cart-header"><h3>Meu Carrinho (${data.total_itens})</h3><i class='bx bx-x cart-close-btn' id="cart-close-btn"></i></div><div class="cart-body">${itensHTML}</div><div class="cart-footer"><div class="cart-total"><span>Total:</span><span>${data.total_formatado}</span></div><a href="checkout.php" class="btn-checkout">Finalizar Compra</a></div>`;
            }

            async function fetchAPI(url, options = {}) {
                try {
                    const response = await fetch(url, options);
                    if (!response.ok) throw new Error('Erro API');
                    const data = await response.json();
                    if (data.sucesso) { renderizarCarrinho(data); } else { alert(data.mensagem || 'Erro.'); }
                } catch (error) { console.error(error); }
            }

            document.body.addEventListener('click', (event) => {
                const target = event.target;
                if (target.classList.contains('btn-comprar')) {
                    const produtoId = target.getAttribute('data-id');
                    target.textContent = "Adicionando...";
                    fetchAPI('carrinho_api.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id_produto: produtoId }) }).then(() => { openCart(); target.textContent = "ADICIONAR AO CARRINHO"; });
                }
                if (target.id === 'cart-close-btn' || target.id === 'cart-overlay') { closeCart(); }
                if (target.classList.contains('remove-item-btn')) { const produtoId = target.getAttribute('data-id'); fetchAPI('carrinho_api.php', { method: 'DELETE', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id_produto: produtoId }) }); }
                if (target.classList.contains('btn-qty-increase')) { const produtoId = target.getAttribute('data-id'); const qtyEl = document.querySelector(`.qty-display[data-id="${produtoId}"]`); fetchAPI('carrinho_api.php', { method: 'PUT', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id_produto: produtoId, nova_quantidade: parseInt(qtyEl.textContent) + 1 }) }); }
                if (target.classList.contains('btn-qty-decrease')) { const produtoId = target.getAttribute('data-id'); const qtyEl = document.querySelector(`.qty-display[data-id="${produtoId}"]`); fetchAPI('carrinho_api.php', { method: 'PUT', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id_produto: produtoId, nova_quantidade: parseInt(qtyEl.textContent) - 1 }) }); }
            });

            openCartBtn.addEventListener('click', (event) => { event.preventDefault(); openCart(); });
            cartOverlay.addEventListener('click', closeCart);
            fetchAPI('carrinho_api.php');
        });

        // MODAIS
        document.body.addEventListener('click', function (event) {
            const trigger = event.target.closest('.modal-trigger');
            if (trigger) {
                event.preventDefault();
                const targetModalId = trigger.getAttribute('data-target');
                const modal = document.getElementById(targetModalId);
                if (modal) modal.classList.add('is-active');
                return;
            }
            if (event.target.classList.contains('modal-close-btn') || event.target.classList.contains('modal-overlay')) {
                const modal = event.target.closest('.modal-info');
                if (modal) modal.classList.remove('is-active');
            }
        });

        //Site RESPONSIVO
        // --- LÓGICA DO MENU RESPONSIVO ---
        document.addEventListener('DOMContentLoaded', () => {
            const menuToggle = document.getElementById('mobile-menu');
            const navbar = document.querySelector('.navbar');

            if (menuToggle && navbar) {
                // Abrir/Fechar ao clicar no ícone
                menuToggle.addEventListener('click', () => {
                    navbar.classList.toggle('active');

                    // Troca o ícone de "Menu" para "X" e vice-versa
                    const icon = menuToggle.querySelector('i');
                    if (navbar.classList.contains('active')) {
                        icon.classList.replace('bx-menu', 'bx-x');
                    } else {
                        icon.classList.replace('bx-x', 'bx-menu');
                    }
                });

                // Fechar o menu ao clicar em um link (Importante para navegação one-page)
                const navLinks = navbar.querySelectorAll('a');
                navLinks.forEach(link => {
                    link.addEventListener('click', () => {
                        navbar.classList.remove('active');
                        menuToggle.querySelector('i').classList.replace('bx-x', 'bx-menu');
                    });
                });
            }
        });
    </script>

    <div class="modal-info" id="modal-quem-somos">
        <div class="modal-overlay"></div>
        <div class="modal-container">
            <div class="modal-header">
                <h2>Quem Somos</h2><i class='bx bx-x modal-close-btn'></i>
            </div>
            <div class="modal-body">
                <p>Bem-vindo à Ibisa Acessórios!</p>
                <p>Nascemos da paixão por joias e semi-joias que celebram momentos e realçam a beleza.</p>
            </div>
        </div>
    </div>
    <div class="modal-info" id="modal-trocas">
        <div class="modal-overlay"></div>
        <div class="modal-container">
            <div class="modal-header">
                <h2>Política de Trocas e Garantia</h2><i class='bx bx-x modal-close-btn'></i>
            </div>
            <div class="modal-body">
                <p>Sua satisfação é nossa maior joia!</p>
                <p><strong>Garantia:</strong> 90 dias para defeitos de fabricação.</p>
            </div>
        </div>
    </div>
    <div class="modal-info" id="modal-privacidade">
        <div class="modal-overlay"></div>
        <div class="modal-container">
            <div class="modal-header">
                <h2>Política de Privacidade</h2><i class='bx bx-x modal-close-btn'></i>
            </div>
            <div class="modal-body">
                <p>Seus dados estão seguros conosco.</p>
            </div>
        </div>
    </div>
    <div class="modal-info" id="modal-representantes">
        <div class="modal-overlay"></div>
        <div class="modal-container">
            <div class="modal-header">
                <h2>Representantes</h2><i class='bx bx-x modal-close-btn'></i>
            </div>
            <div class="modal-body">
                <p>Quer ser uma parceira Ibisa? Entre em contato!</p>
            </div>
        </div>
    </div>
    <div class="modal-info" id="modal-duvidas">
        <div class="modal-overlay"></div>
        <div class="modal-container">
            <div class="modal-header">
                <h2>Dúvidas Frequentes</h2><i class='bx bx-x modal-close-btn'></i>
            </div>
            <div class="modal-body">
                <p><strong>Prazo?</strong> Calculado no carrinho.</p>
            </div>
        </div>
    </div>

    <div class="modal-info" id="modal-fale-conosco">
        <div class="modal-overlay"></div>
        <div class="modal-container">
            <form action="enviar_contato.php" method="POST" id="form-contato">
                <div class="modal-header">
                    <h2>Fale Conosco</h2><i class='bx bx-x modal-close-btn'></i>
                </div>
                <div class="modal-body">
                    <div style="margin-bottom: 15px;"><label>Seu Nome:</label><input type="text" name="nome" required
                            style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;"
                            value="<?php echo isset($_SESSION['usuario_nome']) ? htmlspecialchars($_SESSION['usuario_nome']) : ''; ?>"
                            <?php echo isset($_SESSION['usuario_logado']) ? 'readonly' : ''; ?>></div>
                    <div style="margin-bottom: 15px;"><label>Seu E-mail:</label><input type="email" name="email"
                            required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;"
                            value="<?php echo isset($_SESSION['usuario_email']) ? htmlspecialchars($_SESSION['usuario_email']) : ''; ?>"
                            <?php echo isset($_SESSION['usuario_logado']) ? 'readonly' : ''; ?>></div>
                    <div style="margin-bottom: 15px;"><label>Assunto:</label><input type="text" name="assunto" required
                            style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;"></div>
                    <div style="margin-bottom: 15px;"><label>Mensagem:</label><textarea name="mensagem" rows="5"
                            required
                            style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-family: 'Montserrat';"></textarea>
                    </div>
                    <button type="submit" class="btn-checkout" style="width: 100%; border: none;">Enviar
                        Mensagem</button>
                </div>
            </form>
        </div>
    </div>

</body>

</html>