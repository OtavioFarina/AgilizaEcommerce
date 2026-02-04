<?php
// 1. (IMPORTANTE) Usa a mesma configuração de sessão persistente
require_once 'session_config.php';
require_once 'conexao.php'; // Precisamos para o nome do usuário

// 2. (SEGURANÇA) Trava de acesso
// Pega o ID do pedido que o processa_checkout.php salvou
$id_pedido = $_SESSION['pedido_sucesso_id'] ?? null;

// Se NÃO houver ID na sessão (usuário digitou a URL ou apertou F5), "kita" ele
if ($id_pedido === null) {
    header('Location: index.php');
    exit;
}

// 3. (IMPORTANTE) Limpa o ID da sessão
// Isso garante que se o usuário apertar F5, ele caia no "if" acima e seja "kitado"
unset($_SESSION['pedido_sucesso_id']);
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compra Finalizada! - Ibisa Acessórios</title>

    <link rel="icon" href="img/background/favicon.jpg" type="img/jpg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>

    <style>
        /* (COPIADO DO INDEX) Estilos Globais, Header e Footer */
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
        }

        .site-footer {
            background-color: #222;
            color: #ccc;
            padding-top: 60px;
            font-size: 0.9rem;
            line-height: 1.7;
            margin-top: auto;
        }

        /* (CSS do rodapé completo omitido por brevidade...) */
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
        }

        /* --- (NOVO) CSS Específico da Página de Sucesso --- */
        .success-container {
            text-align: center;
            padding: 80px 40px;
            max-width: 700px;
            margin: 60px auto;
            /* Espaçamento */
            background: #ffffff;
            border: 1px solid #eee;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .success-icon {
            font-size: 5rem;
            color: #28a745;
            /* Verde sucesso */
            margin-bottom: 10px;
        }

        .success-container h1 {
            font-size: 2.5rem;
            color: #b8860b;
            /* Nosso dourado */
            margin-bottom: 15px;
            font-weight: 700;
        }

        .success-container p {
            font-size: 1.1rem;
            margin-bottom: 30px;
            line-height: 1.8;
        }

        .success-container .btn-home {
            background-color: #333;
            color: white;
            padding: 14px 28px;
            border-radius: 5px;
            font-weight: 500;
            text-decoration: none;
            transition: background-color 0.3s ease;
            font-size: 1rem;
        }

        .success-container .btn-home:hover {
            background-color: #555;
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

    <main>
        <div class="success-container">
            <i class='bx bx-check-circle success-icon'></i>
            <h1>Obrigado, <?php echo htmlspecialchars(explode(' ', $_SESSION['usuario_nome'])[0]); ?>!</h1>
            <p>
                Seu pedido <strong>#<?php echo htmlspecialchars($id_pedido); ?></strong> foi finalizado com sucesso.
                Enviamos os detalhes da confirmação para o seu email.
            </p>
            <a href="index.php" class="btn-home">Continuar Comprando</a>
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

        // Script do Carrinho (necessário para o header e contador)
        document.addEventListener('DOMContentLoaded', () => {
            const cartCountEl = document.getElementById('cart-count');

            // Função para "falar" com a API
            async function fetchAPI(url, options = {}) {
                try {
                    const response = await fetch(url, options);
                    if (!response.ok) throw new Error('Erro na resposta da API');
                    const data = await response.json();

                    if (data.sucesso) {
                        // Apenas atualiza o contador do header
                        cartCountEl.textContent = `(${data.total_itens || 0})`;
                    }
                } catch (error) {
                    console.error('Erro no Fetch:', error);
                }
            }

            // Ao carregar a página, busca o estado atual do carrinho (que estará vazio)
            fetchAPI('carrinho_api.php');

            // Previne erro se o usuário tentar abrir o carrinho (que não existe aqui)
            const openCartBtn = document.getElementById('open-cart-btn');
            if (openCartBtn) {
                openCartBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    alert('Seu carrinho está vazio.');
                });
            }
        });
    </script>

</body>

</html>