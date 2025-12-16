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
$enderecos_salvos = [];

try {
    $sql_enderecos = "SELECT * FROM enderecos WHERE id_usuario = :id_usuario ORDER BY tipo_endereco";
    $stmt_enderecos = $pdo->prepare($sql_enderecos);
    $stmt_enderecos->execute(['id_usuario' => $id_usuario_logado]);

    $enderecos_salvos = $stmt_enderecos->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $mensagem = ['tipo' => 'erro', 'texto' => 'Erro ao carregar endereços salvos.'];
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ibisa Acessórios - Meus Endereços</title>
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

        .address-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .address-card {
            background-color: #fdfdfd;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
        }

        .address-card h3 {
            font-weight: 700;
            color: #333;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        .address-card p {
            font-size: 0.95rem;
            margin-bottom: 5px;
        }

        .address-actions {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
            font-size: 0.9rem;
            font-weight: 700;
        }

        .address-actions a {
            color: #b8860b;
        }

        .address-actions a:hover {
            text-decoration: underline;
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

        .mensagem.info {
            background-color: #d1ecf1;
            color: #0c5460;
            border-color: #bee5eb;
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

        @media (max-width: 700px) {
            .form-grid {
                grid-template-columns: 1fr;
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
                    <li><a href="perfil.php">Meu Perfil</a></li>
                    <li><a href="pedidos.php">Meus Pedidos</a></li>
                    <li><a href="enderecos.php" class="active">Meus Endereços</a></li>
                    <li><a href="logout.php">Sair</a></li>
                </ul>
            </aside>

            <section class="profile-content">
                <h2>Meus Endereços</h2>

                <?php if ($mensagem): ?>
                    <div class="mensagem <?php echo htmlspecialchars($mensagem['tipo']); ?>">
                        <?php echo htmlspecialchars($mensagem['texto']); ?>
                    </div>
                <?php endif; ?>

                <div class="address-grid">

                    <?php if (empty($enderecos_salvos)): ?>
                        <div class="mensagem info">
                            Você ainda não tem nenhum endereço cadastrado.
                            Use o formulário abaixo para adicionar seu primeiro endereço.
                        </div>
                    <?php else: ?>
                        <?php foreach ($enderecos_salvos as $endereco): ?>
                            <div class="address-card">
                                <h3><?php echo htmlspecialchars($endereco['tipo_endereco']); ?></h3>
                                <p><?php echo htmlspecialchars($endereco['rua']); ?>,
                                    <?php echo htmlspecialchars($endereco['numerocasa']); ?>
                                </p>
                                <p><?php echo htmlspecialchars($endereco['bairro']); ?></p>
                                <p><?php echo htmlspecialchars($endereco['cidade']); ?>,
                                    <?php echo htmlspecialchars($endereco['uf']); ?>
                                </p>
                                <p>CEP: <?php echo htmlspecialchars($endereco['cep']); ?></p>
                                <div class="address-actions">
                                    <a href="editar_endereco.php?id=<?php echo $endereco['id_endereco']; ?>">Editar</a> |
                                    <a href="excluir_endereco.php?id=<?php echo $endereco['id_endereco']; ?>"
                                        style="color: #c00;"
                                        onclick="return confirm('Tem certeza que deseja excluir este endereço?');">Excluir</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                </div>

                <form action="adiciona_endereco.php" method="POST">
                    <fieldset>
                        <legend>Adicionar Novo Endereço</legend>

                        <div class="form-group">
                            <label for="tipo_endereco">Nome deste endereço (Ex: Casa, Trabalho):</label>
                            <input type="text" id="tipo_endereco" name="tipo_endereco" placeholder="Casa" required>
                        </div>

                        <div class="form-group">
                            <label for="cep">CEP:</label>
                            <input type="text" id="cep" name="cep" placeholder="Digite o CEP" required maxlength="9">
                        </div>
                        <div class="form-group">
                            <label for="rua">Rua / Logradouro:</label>
                            <input type="text" id="rua" name="rua" placeholder="Nome da Rua" required>
                        </div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="numero">Número:</label>
                                <input type="text" id="numero" name="numerocasa" placeholder="123B" required>
                            </div>
                            <div class="form-group">
                                <label for="bairro">Bairro:</label>
                                <input type="text" id="bairro" name="bairro" placeholder="Nome do Bairro" required>
                            </div>
                        </div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="cidade">Cidade:</label>
                                <input type="text" id="cidade" name="cidade" placeholder="Nome da Cidade" required>
                            </div>
                            <div class="form-group">
                                <label for="uf">Estado (UF):</label>
                                <input type="text" id="uf" name="uf" placeholder="SP" maxlength="2" required>
                            </div>
                        </div>
                    </fieldset>
                    <button type="submit" class="cta-button">Salvar Endereço</button>
                </form>

            </section>
        </div>
    </main>

    <footer>
        <p>&copy; 2025 Ibisa Semi-Joias. Todos os direitos reservados.</p>
    </footer>

    <script>
        const header = document.getElementById('main-header');
        function handleScroll() { if (window.scrollY > 50) { header.classList.add('sticky'); } else { header.classList.remove('sticky'); } }
        window.addEventListener('scroll', handleScroll);

        const inputCEP = document.getElementById('cep');

        if (inputCEP) inputCEP.addEventListener('input', formatarCEP);

        if (inputCEP) inputCEP.addEventListener('blur', preencherEndereco);

        function formatarCEP() {
            let valor = inputCEP.value.replace(/\D/g, '');
            valor = valor.substring(0, 8);
            if (valor.length > 5) {
                valor = valor.replace(/(\d{5})(\d{1,3})/, '$1-$2');
            }
            inputCEP.value = valor;
        }

        async function preencherEndereco() {
            const cepLimpo = inputCEP.value.replace(/\D/g, '');
            if (cepLimpo.length !== 8) return;
            const url = `https://viacep.com.br/ws/${cepLimpo}/json/`;
            try {
                const response = await fetch(url);
                const data = await response.json();
                if (data.erro) {
                    alert("CEP não encontrado."); return;
                }
                document.getElementById('rua').value = data.logradouro;
                document.getElementById('bairro').value = data.bairro;
                document.getElementById('cidade').value = data.localidade;
                document.getElementById('uf').value = data.uf;
                document.getElementById('numero').focus();
            } catch (error) {
                alert("Não foi possível buscar o CEP. Verifique sua conexão.");
            }
        }
    </script>

</body>

</html>