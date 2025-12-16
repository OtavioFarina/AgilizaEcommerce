<?php
session_start();
require_once 'conexao.php';

if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
    header('Location: conta.php');
    exit;
}

$mensagem = null;
if (isset($_SESSION['mensagem'])) {
    $mensagem = $_SESSION['mensagem'];
    unset($_SESSION['mensagem']);
}

$id_usuario_logado = $_SESSION['usuario_id'];
$sql_user = "SELECT * FROM usuarios WHERE id_usuario = :id";
try {
    $stmt_user = $pdo->prepare($sql_user);
    $stmt_user->execute(['id' => $id_usuario_logado]);
    $usuario = $stmt_user->fetch(PDO::FETCH_ASSOC);
    if (!$usuario) {
        header('Location: logout.php');
        exit;
    }
} catch (PDOException $e) {
    die("Erro ao buscar dados do usuário: " . $e->getMessage());
}

$total_enderecos = 0;
try {
    $sql_end_check = "SELECT COUNT(*) FROM enderecos WHERE id_usuario = :id";
    $stmt_end_check = $pdo->prepare($sql_end_check);
    $stmt_end_check->execute(['id' => $id_usuario_logado]);
    $total_enderecos = $stmt_end_check->fetchColumn();
} catch (PDOException $e) {
    $mensagem = ['tipo' => 'erro', 'texto' => 'Erro ao verificar endereços.'];
}

?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ibisa Acessórios - Meu Perfil</title>

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
            margin-bottom: 15px;
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
            background-color: #fdfdfd;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        fieldset {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 20px;
            margin-bottom: 20px;
        }

        legend {
            font-weight: 700;
            color: #333;
            padding: 0 10px;
        }

        .cta-button {
            background-color: #b8860b;
            color: white;
            padding: 14px 28px;
            font-weight: 700;
            border-radius: 5px;
            transition: background-color 0.3s ease, transform 0.3s ease;
            width: auto;
            border: none;
            cursor: pointer;
            font-size: 1.1rem;
        }

        .cta-button:hover {
            background-color: #d4af37;
            transform: translateY(-3px);
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

        .mensagem.alerta {
            background-color: #fff3cd;
            color: #856404;
            border-color: #ffeeba;
            text-align: left;
        }

        @media (max-width: 900px) {
            .profile-container {
                flex-direction: column;
            }

            .profile-sidebar {
                flex-basis: 100%;
            }
        }
    </style>
</head>

<body>

    <header id="main-header">
        <div class="logo">IBISA</div>
        <nav>
            <ul>
                <li><a href="index.php">Início</a></li>
                <li><a href="#">Coleções</a></li>
                <li><a href="#">Brincos</a></li>
                <li><a href="#">Colares</a></li>
                <li><a href="#">Sobre</a></li>
            </ul>
        </nav>
        <div class="nav-icons">
            <?php if (isset($_SESSION['usuario_logado']) && $_SESSION['usuario_logado'] === true): ?>
                <a href="perfil.php">Olá,
                    <strong><?php echo htmlspecialchars(explode(' ', $_SESSION['usuario_nome'])[0]); ?></a></strong>
            <?php else: ?>
                <a href="conta.php">Login / Cadastro</a>
            <?php endif; ?>
            <a href="#">Carrinho (0)</a>
        </div>
    </header>

    <main>
        <div class="profile-container">

            <aside class="profile-sidebar">
                <ul>
                    <li><a href="perfil.php" class="active">Meu Perfil</a></li>
                    <li><a href="pedidos.php">Meus Pedidos</a></li>
                    <li><a href="enderecos.php">Meus Endereços</a></li>
                    <li><a href="logout.php">Sair</a></li>
                    <?php if (isset($_SESSION['usuario_tipo']) && $_SESSION['usuario_tipo'] == 2): ?>

                        <li><a href="adm/painel_admin.php"
                                style="color: #b8860b; font-weight: 700; border: 1px solid #b8860b; padding: 5px 10px; border-radius: 5px;">
                                Painel ADM
                            </a></li>

                    <?php endif; ?>
                </ul>
            </aside>

            <section class="profile-content">
                <h2>Meu Perfil</h2>

                <?php if ($mensagem): ?>
                    <div class="mensagem <?php echo htmlspecialchars($mensagem['tipo']); ?>">
                        <?php echo htmlspecialchars($mensagem['texto']); ?>
                    </div>
                <?php endif; ?>

                <?php if ($total_enderecos == 0): ?>
                    <div class="mensagem alerta">
                        <strong>Alerta!</strong> Você ainda não tem nenhum endereço de entrega.
                        <a href="enderecos.php" style="font-weight: 700; text-decoration: underline;">
                            Clique aqui para cadastrar seu primeiro endereço.
                        </a>
                    </div>
                <?php else: ?>
                    <p style="margin-bottom: 20px;">Aqui você pode atualizar seus dados pessoais.</p>
                <?php endif; ?>


                <form action="atualiza_perfil.php" method="POST">

                    <fieldset>
                        <legend>Dados Pessoais</legend>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="nome">Nome Completo:</label>
                                <input type="text" id="nome" name="nome_usuario"
                                    value="<?php echo htmlspecialchars($usuario['nome_usuario']); ?>">
                            </div>
                            <div class="form-group">
                                <label for="cpf">CPF:</label>
                                <input type="text" id="cpf" name="cpf_display"
                                    value="<?php echo htmlspecialchars($usuario['cpf']); ?>" readonly>
                            </div>
                        </div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="email">Email:</label>
                                <input type="email" id="email" name="email"
                                    value="<?php echo htmlspecialchars($usuario['email']); ?>">
                            </div>
                            <div class="form-group">
                                <label for="telefone">Telefone:</label>
                                <input type="tel" id="telefone" name="telefone"
                                    value="<?php echo htmlspecialchars($usuario['telefone']); ?>" maxlength="15">
                            </div>
                        </div>
                    </fieldset>

                    <button type="submit" class="cta-button">Salvar Alterações</button>
                </form>
            </section>

        </div>
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

        const inputCPF = document.getElementById('cpf');
        const inputTelefone = document.getElementById('telefone');

        if (inputCPF) inputCPF.addEventListener('input', formatarCPF);
        if (inputTelefone) inputTelefone.addEventListener('input', formatarTelefone);

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

        formatarCPF();
        formatarTelefone()
    </script>

</body>

</html>