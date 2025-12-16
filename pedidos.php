<?php
// 1. (NOVO) Inicia sessão persistente e conexão
require_once 'session_config.php';
require_once 'conexao.php';

// 2. (NOVO) Segurança: "Kita" se não estiver logado
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
    $_SESSION['mensagem'] = ['tipo' => 'erro', 'texto' => 'Você precisa estar logado para ver seus pedidos.'];
    header('Location: conta.php');
    exit;
}

// 3. (NOVO) Busca os pedidos REAIS do usuário
$pedidos = [];
$id_usuario_logado = $_SESSION['usuario_id'];
try {
    // Query com JOIN para pegar o nome da forma de pagamento
    $sql = "SELECT v.id_venda, v.data, v.status, v.valor_total, f.nome_forma_pagamento 
            FROM vendas v
            JOIN forma_pagamento f ON v.id_forma_pagamento = f.id_forma_pagamento
            WHERE v.id_usuario = :id_usuario 
            ORDER BY v.data DESC"; //

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id_usuario' => $id_usuario_logado]);
    $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $db_error = "Erro ao carregar pedidos: " . $e->getMessage();
}

// 4. (NOVO) Função "Helper" para traduzir o status para a classe CSS
function getStatusClass($status)
{
    switch ($status) {
        case 'Aguardando Confirmação':
        case 'Pagamento Aprovado':
            return 'status-pending'; // Amarelo
        case 'Em Preparação':
        case 'Em Trânsito':
        case 'Saiu para Entrega':
            return 'status-shipped'; // Azul
        case 'Entregue':
            return 'status-delivered'; // Verde
        case 'Cancelado':
            return 'status-canceled'; // Vermelho
        default:
            return '';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ibisa Acessórios - Meus Pedidos</title>
    <link rel="icon" href="img/background/favicon.jpg" type="img/jpg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>

    <style>
        /* [Seu CSS de antes (body, header, profile-container, etc...)] */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
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

        .profile-container {
            display: flex;
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
            gap: 30px;
        }

        .profile-sidebar {
            flex: 0 0 250px;
            background: #ffffff;
            border: 1px solid #eee;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            padding: 20px;
            align-self: flex-start;
        }

        .profile-sidebar ul li {
            margin-bottom: 10px;
        }

        .profile-sidebar ul li a {
            display: block;
            padding: 12px 15px;
            font-weight: 500;
            border-radius: 5px;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        .profile-sidebar ul li a:hover {
            background-color: #f9f9f9;
        }

        .profile-sidebar ul li a.active {
            background-color: #f0f0f0;
            color: #b8860b;
        }

        .profile-content {
            flex: 1;
            background: #ffffff;
            border: 1px solid #eee;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            padding: 40px;
        }

        .profile-content h2 {
            text-align: left;
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 30px;
            color: #b8860b;
        }

        .table-responsive-wrapper {
            width: 100%;
            overflow-x: auto;
        }

        table.orders-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table.orders-table th,
        table.orders-table td {
            border-bottom: 1px solid #eee;
            padding: 15px;
            text-align: left;
            font-size: 0.95rem;
        }

        table.orders-table th {
            background-color: #f9f9f9;
            font-weight: 700;
        }

        .status {
            padding: 5px 10px;
            border-radius: 5px;
            font-weight: 700;
            font-size: 0.85rem;
            color: #333;
        }

        .status-delivered {
            background-color: #d4edda;
            color: #155724;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-shipped {
            background-color: #d1ecf1;
            color: #0c5460;
        }

        .status-canceled {
            background-color: #f8d7da;
            color: #721c24;
        }

        .btn-secondary {
            background-color: #f0f0f0;
            color: #333;
            padding: 8px 12px;
            border-radius: 5px;
            font-weight: 500;
            font-size: 0.9rem;
            transition: background-color 0.3s ease;
        }

        .btn-secondary:hover {
            background-color: #e0e0e0;
        }

        @media (max-width: 900px) {
            .profile-container {
                flex-direction: column;
            }

            .profile-sidebar {
                flex-basis: 100%;
            }
        }

        /* [CSS do Rodapé Completo] */
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
    </style>
</head>

<body>

    <header id="main-header">
        <div class="logo">IBISA</div>
        <nav>
            <ul>
                <li><a href="index.php">Início</a></li>
                <li><a href="#">Destaques</a></li>
                <li><a href="#">Aneis</a></li>
                <li><a href="#">Brincos</a></li>
                <li><a href="#">Correntes</a></li>
                <li><a href="#">Pingente</a></li>
                <li><a href="#">Pulseiras</a></li>
                <li><a href="#">Sobre</a></li>
            </ul>
        </nav>
        <div class="nav-icons">
            <?php if (isset($_SESSION['usuario_logado']) && $_SESSION['usuario_logado'] === true): ?>
                <a href="perfil.php">Olá, <?php echo htmlspecialchars(explode(' ', $_SESSION['usuario_nome'])[0]); ?></a>
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

    <div class="cart-overlay" id="cart-overlay"></div>
    <aside class="cart-sidebar" id="cart-sidebar"></aside>

    <main>
        <div class="profile-container">
            <aside class="profile-sidebar">
                <ul>
                    <li><a href="perfil.php">Meu Perfil</a></li>
                    <li><a href="pedidos.php" class="active">Meus Pedidos</a></li>
                    <li><a href="enderecos.php">Meus Endereços</a></li>
                    <li><a href="logout.php">Sair</a></li>
                </ul>
            </aside>

            <section class="profile-content">
                <h2>Meus Pedidos</h2>

                <?php if (isset($db_error)): ?>
                    <p style="color: red;"><?php echo $db_error; ?></p>
                <?php endif; ?>

                <div class="table-responsive-wrapper">
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th>Pedido</th>
                                <th>Data</th>
                                <th>Status</th>
                                <th>Total</th>
                                <th>Pagamento</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($pedidos)): ?>
                                <tr>
                                    <td colspan="6" style="text-align: center;">Você ainda não fez nenhum pedido.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($pedidos as $pedido): ?>
                                    <tr>
                                        <td><strong>#<?php echo htmlspecialchars($pedido['id_venda']); ?></strong></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($pedido['data'])); ?></td>
                                        <td>
                                            <span class="status <?php echo getStatusClass($pedido['status']); ?>">
                                                <?php echo htmlspecialchars($pedido['status']); ?>
                                            </span>
                                        </td>
                                        <td>R$ <?php echo number_format($pedido['valor_total'], 2, ',', '.'); ?></td>
                                        <td><?php echo htmlspecialchars($pedido['nome_forma_pagamento']); ?></td>
                                        <td><a href="pedido_detalhes.php?id=<?php echo $pedido['id_venda']; ?>"
                                                class="btn-secondary">Ver Detalhes</a></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </main>

    <footer class="site-footer">
        <div class="footer-container">
            <div class="footer-main">
                <div class="footer-col">
                    <h4>INSTITUCIONAL</h4>
                    <ul>
                        <li><a href="#">Quem somos</a></li>
                        <li><a href="#">Política de Trocas e Garantia</a></li>
                        <li><a href="#">Política de Privacidade</a></li>
                        <li><a href="#">Representantes</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>AJUDA</h4>
                    <ul>
                        <li><a href="#">Dúvidas frequentes</a></li>
                        <li><a href="#">Fale conosco</a></li>
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
        // Script do Header Fixo
        const header = document.getElementById('main-header');
        function handleScroll() {
            if (window.scrollY > 50) { header.classList.add('sticky'); }
            else { header.classList.remove('sticky'); }
        }
        window.addEventListener('scroll', handleScroll);

        // Script do Carrinho (só para o contador e abrir/fechar)
        document.addEventListener('DOMContentLoaded', () => {
            const openCartBtn = document.getElementById('open-cart-btn');
            const cartSidebar = document.getElementById('cart-sidebar');
            const cartOverlay = document.getElementById('cart-overlay');
            const cartCountEl = document.getElementById('cart-count');

            function openCart() { document.body.classList.add('cart-open'); }
            function closeCart() { document.body.classList.remove('cart-open'); }

            async function fetchAPI(url, options = {}) {
                try {
                    const response = await fetch(url, options);
                    if (!response.ok) throw new Error('Erro na API');
                    const data = await response.json();
                    if (data.sucesso) {
                        cartCountEl.textContent = `(${data.total_itens || 0})`;
                        // (O código de renderização do carrinho fica aqui, omitido por brevidade
                        // já que esta página não é a index)
                    }
                } catch (error) { console.error('Erro no Fetch:', error); }
            }

            // Ouvintes para abrir/fechar
            openCartBtn.addEventListener('click', (event) => {
                event.preventDefault();
                fetchAPI('carrinho_api.php'); // Atualiza antes de abrir
                openCart();
            });
            cartOverlay.addEventListener('click', closeCart);
            // Adiciona ouvinte para o botão de fechar (que será criado dinamicamente)
            document.body.addEventListener('click', (event) => {
                if (event.target.id === 'cart-close-btn') {
                    closeCart();
                }
            });

            // Carrega o contador do carrinho ao iniciar a página
            fetchAPI('carrinho_api.php');
        });
    </script>

</body>

</html>