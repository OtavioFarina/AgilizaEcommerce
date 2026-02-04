<?php
require_once 'session_config.php';

$mensagem = null;
if (isset($_SESSION['mensagem'])) {
    $mensagem = $_SESSION['mensagem'];
    unset($_SESSION['mensagem']);
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ibisa Acessórios - Minha Conta</title>
    <link rel="icon" href="img/favicon.jpg" type="img/jpg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
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
            gap: 20px;
            font-weight: 500;
        }

        footer {
            background-color: #222;
            color: #ccc;
            text-align: center;
            padding: 40px 20px;
            margin-top: auto;
        }

        footer p {
            font-size: 0.9rem;
        }

        .login-register-container {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 60px 20px;
        }

        .form-container {
            background: #ffffff;
            border: 1px solid #eee;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            padding: 40px;
            width: 100%;
            max-width: 500px;
        }

        .form-container h2 {
            text-align: center;
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 30px;
            color: #b8860b;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-weight: 500;
            margin-bottom: 5px;
            font-size: 0.9rem;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-family: 'Montserrat', sans-serif;
            font-size: 1rem;
        }

        .cta-button {
            background-color: #b8860b;
            color: white;
            padding: 14px 28px;
            font-weight: 700;
            border-radius: 5px;
            transition: background-color 0.3s ease, transform 0.3s ease;
            width: 100%;
            border: none;
            cursor: pointer;
            font-size: 1.1rem;
        }

        .cta-button:hover {
            background-color: #d4af37;
            transform: translateY(-3px);
        }

        .form-toggle {
            text-align: center;
            margin-top: 20px;
            font-size: 0.9rem;
        }

        .form-toggle a {
            color: #b8860b;
            font-weight: 700;
        }

        #register-form {
            display: none;
        }

        .mensagem {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            font-weight: 500;
            text-align: center;
            border: 1px solid transparent;
        }

        .mensagem.sucesso {
            background-color: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
        }

        .mensagem.erro {
            background-color: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }
    </style>
</head>

<body>

    <header id="main-header">
        <div class="logo">IBISA</div>
        <nav>
            <ul>
                <li><a href="index.php">Início</a></li>
                <li><a href="index.php">Coleções</a></li>
                <li><a href="index.php">Brincos</a></li>
                <li><a href="index.php">Colares</a></li>
                <li><a href="index.php">Sobre</a></li>
            </ul>
        </nav>
        <div class="nav-icons">
            <a href="conta.php">Minha Conta</a>
        </div>
    </header>

    <main>
        <section class="login-register-container">
            <div class="form-container">

                <?php if ($mensagem): ?>
                    <div class="mensagem <?php echo htmlspecialchars($mensagem['tipo']); ?>">
                        <?php echo htmlspecialchars($mensagem['texto']); ?>
                    </div>
                <?php endif; ?>

                <div id="login-form">
                    <h2>Login</h2>
                    <form action="processa_login.php" method="POST">
                        <div class="form-group">
                            <label for="login-email">Email:</label>
                            <input type="email" id="login-email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label for="login-senha">Senha:</label>
                            <input type="password" id="login-senha" name="senha" required>
                        </div>
                        <button type="submit" class="cta-button">Entrar</button>
                    </form>
                    <p class="form-toggle">
                        Não tem uma conta? <a href="#" id="show-register">Cadastre-se</a>
                    </p>
                </div>

                <div id="register-form">
                    <h2>Cadastro</h2>
                    <form action="processa_cadastro.php" method="POST">
                        <div class="form-group">
                            <label for="reg-nome">Nome Completo:</label>
                            <input type="text" id="reg-nome" name="nome_usuario" required>
                        </div>
                        <div class="form-group">
                            <label for="reg-email">Email:</label>
                            <input type="email" id="reg-email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label for="reg-senha">Senha:</label>
                            <input type="password" id="reg-senha" name="senha" required>
                        </div>
                        <div class="form-group">
                            <label for="reg-telefone">Telefone (com DDD):</label>
                            <input type="tel" id="reg-telefone" name="telefone" required maxlength="15">
                        </div>
                        <div class="form-group">
                            <label for="reg-cpf">CPF:</label>
                            <input type="text" id="reg-cpf" name="cpf" required maxlength="14">
                        </div>
                        <button type="submit" class="cta-button">Criar Conta</button>
                    </form>
                    <p class="form-toggle">
                        Já tem uma conta? <a href="#" id="show-login">Faça Login</a>
                    </p>
                </div>

            </div>
        </section>
    </main>

    <footer>
        <p>&copy; 2025 Ibisa Semi-Joias. Todos os direitos reservados.</p>
        <p>Desenvolvido com ❤️</p>
    </footer>

    <script>
        const header = document.getElementById('main-header');
        function handleScroll() {
            if (window.scrollY > 50) { header.classList.add('sticky'); }
            else { header.classList.remove('sticky'); }
        }
        window.addEventListener('scroll', handleScroll);

        const loginForm = document.getElementById('login-form');
        const registerForm = document.getElementById('register-form');
        const showRegisterLink = document.getElementById('show-register');
        const showLoginLink = document.getElementById('show-login');

        showRegisterLink.addEventListener('click', (e) => {
            e.preventDefault();
            loginForm.style.display = 'none';
            registerForm.style.display = 'block';
        });

        showLoginLink.addEventListener('click', (e) => {
            e.preventDefault();
            loginForm.style.display = 'block';
            registerForm.style.display = 'none';
        });

        const inputCPF = document.getElementById('reg-cpf');
        const inputTelefone = document.getElementById('reg-telefone');

        inputCPF.addEventListener('input', formatarCPF);
        inputTelefone.addEventListener('input', formatarTelefone);

        function formatarCPF() {
            let valor = inputCPF.value.replace(/\D/g, '');
            valor = valor.substring(0, 11);
            let valorFormatado = '';
            if (valor.length > 9) {
                valorFormatado = valor.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
            } else if (valor.length > 6) {
                valorFormatado = valor.replace(/(\d{3})(\d{3})(\d{1,3})/, '$1.$2.$3');
            } else if (valor.length > 3) {
                valorFormatado = valor.replace(/(\d{3})(\d{1,3})/, '$1.$2');
            } else {
                valorFormatado = valor;
            }
            inputCPF.value = valorFormatado;
        }

        function formatarTelefone() {
            let valor = inputTelefone.value.replace(/\D/g, '');
            valor = valor.substring(0, 11);
            let valorFormatado = '';
            if (valor.length > 10) {
                valorFormatado = valor.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
            } else if (valor.length > 6) {
                valorFormatado = valor.replace(/(\d{2})(\d{4})(\d{0,4})/, '($1) $2-$3');
            } else if (valor.length > 2) {
                valorFormatado = valor.replace(/(\d{2})(\d{0,5})/, '($1) $2');
            } else if (valor.length > 0) {
                valorFormatado = valor.replace(/(\d{0,2})/, '($1');
            }
            inputTelefone.value = valorFormatado;
        }

    </script>

</body>

</html>