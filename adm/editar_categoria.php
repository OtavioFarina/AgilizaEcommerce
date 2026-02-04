<?php
// 1. Inicia sessão, conexão e segurança
require_once '../session_config.php';
require_once '../conexao.php';

// 2. Segurança do ADM (O "Bouncer")
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true || $_SESSION['usuario_tipo'] != 2) {
    $_SESSION['mensagem'] = ['tipo' => 'erro', 'texto' => 'Acesso restrito.'];
    header('Location: ../index.php');
    exit;
}

// 3. (NOVO) Processamento de Ação (UPDATE)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $id_categoria = $_POST['id_categoria'];
    $nome_categoria = trim($_POST['nome_categoria']);

    if (empty($nome_categoria)) {
        $_SESSION['mensagem'] = ['tipo' => 'erro', 'texto' => 'O nome da categoria é obrigatório.'];
    } else {
        // (NOVO) SQL de UPDATE
        $sql = "UPDATE categoria SET nome_categoria = :nome WHERE id_categoria = :id";
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':nome' => $nome_categoria,
                ':id' => $id_categoria
            ]);
            $_SESSION['mensagem'] = ['tipo' => 'sucesso', 'texto' => 'Categoria atualizada com sucesso!'];
        } catch (PDOException $e) {
            $_SESSION['mensagem'] = ['tipo' => 'erro', 'texto' => 'Erro ao atualizar categoria: ' . $e->getMessage()];
        }
    }
    header('Location: admin_categorias.php'); // Redireciona de volta para a lista
    exit;
}

// --- 4. (NOVO) Preparação para Exibição (GET Request) ---
$id_categoria_editar = $_GET['id'] ?? 0;
if (!is_numeric($id_categoria_editar) || $id_categoria_editar <= 0) {
    $_SESSION['mensagem'] = ['tipo' => 'erro', 'texto' => 'ID de categoria inválido.'];
    header('Location: admin_categorias.php');
    exit;
}

$categoria = null;
try {
    $stmt_cat = $pdo->prepare("SELECT * FROM categoria WHERE id_categoria = ?");
    $stmt_cat->execute([$id_categoria_editar]);
    $categoria = $stmt_cat->fetch(PDO::FETCH_ASSOC);

    if (!$categoria) {
        $_SESSION['mensagem'] = ['tipo' => 'erro', 'texto' => 'Categoria não encontrada.'];
        header('Location: admin_categorias.php');
        exit;
    }
} catch (PDOException $e) {
    $db_error = "Erro ao carregar dados: " . $e->getMessage();
}

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
    <title>Editar Categoria - Painel ADM</title>
    <!-- Links (iguais) -->
    <link rel="icon" href="../img/favicon.jpg" type="img/jpg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>

    <style>
        /* [CSS IDÊNTICO ao admin_categorias.php] */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Montserrat', sans-serif;
            background-color: #f4f7fa;
            color: #333;
            display: flex;
            min-height: 100vh;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        ul {
            list-style: none;
        }

        .admin-container {
            display: flex;
            width: 100%;
        }

        .admin-sidebar {
            flex: 0 0 260px;
            background-color: #ffffff;
            min-height: 100vh;
            border-right: 1px solid #e0e0e0;
            padding: 20px;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.05);
        }

        .admin-sidebar .logo {
            font-size: 1.8rem;
            font-weight: 700;
            letter-spacing: 1px;
            color: #b8860b;
            text-align: center;
            margin-bottom: 30px;
            display: block;
        }

        .admin-sidebar .admin-menu li {
            margin-bottom: 10px;
        }

        .admin-sidebar .admin-menu li a {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 14px 20px;
            font-weight: 500;
            border-radius: 8px;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        .admin-sidebar .admin-menu li a i {
            font-size: 1.3rem;
        }

        .admin-sidebar .admin-menu li a:hover {
            background-color: #f0f0f0;
        }

        .admin-sidebar .admin-menu li a.active {
            background-color: #b8860b;
            color: #ffffff;
        }

        .admin-sidebar .admin-menu .menu-divider {
            height: 1px;
            background-color: #eee;
            margin: 20px 0;
        }

        .admin-content {
            flex: 1;
            padding: 30px 40px;
            overflow-y: auto;
        }

        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .admin-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
        }

        .admin-header .admin-profile {
            font-weight: 500;
            color: #555;
        }

        .content-card {
            background-color: #ffffff;
            border-radius: 10px;
            padding: 25px;
            border: 1px solid #e0e0e0;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
            margin-bottom: 30px;
        }

        .content-card h2 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 20px;
        }

        .form-inline .form-group {
            display: inline-block;
            margin-right: 15px;
            vertical-align: bottom;
        }

        .form-group label {
            display: block;
            font-weight: 500;
            margin-bottom: 5px;
            font-size: 0.9rem;
        }

        .form-group input {
            width: 300px;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-family: 'Montserrat', sans-serif;
            font-size: 1rem;
            background-color: #fdfdfd;
        }

        .btn-submit {
            background-color: #b8860b;
            color: white;
            padding: 12px 25px;
            font-weight: 700;
            border-radius: 5px;
            transition: background-color 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 1rem;
        }

        .btn-submit:hover {
            background-color: #d4af37;
        }

        .mensagem {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            font-weight: 500;
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
    <div class="admin-container">
        <!-- --- 1. Sidebar --- -->
        <aside class="admin-sidebar">
            <a href="../index.php" class="logo">IBISA</a>
            <ul class="admin-menu">
                <li><a href="painel_admin.php"><i class='bx bxs-dashboard'></i>Dashboard</a></li>
                <li><a href="admin_produtos.php"><i class='bx bxs-package'></i>Produtos</a></li>
                <li><a href="admin_estoque.php"><i class='bx bxs-bar-chart-alt-2'></i>Estoque</a></li>
                <!-- (NOVO) Link "Categorias" agora está ativo -->
                <li><a href="admin_categorias.php" class="active"><i class='bx bxs-category-alt'></i>Categorias</a></li>
                <li><a href="admin_pedidos.php"><i class='bx bxs-cart-alt'></i>Ver Pedidos</a></li>
                <li><a href="admin_usuarios.php"><i class='bx bxs-user-account'></i>Gerenciar Clientes</a></li>
                <li><a href="admin_mensagens.php"><i class='bx bxs-envelope'></i> Mensagens</a></li>
                <li class="menu-divider"></li>
                <li><a href="../index.php"><i class='bx bx-arrow-back'></i>Voltar ao Site</a></li>
                <li><a href="../logout.php"><i class='bx bxs-log-out'></i>Sair</a></li>
            </ul>
        </aside>

        <!-- --- 2. Conteúdo Principal --- -->
        <main class="admin-content">
            <div class="admin-header">
                <h1>Editar Categoria</h1>
                <div class="admin-profile">
                    Admin: <strong><?php echo htmlspecialchars(explode(' ', $_SESSION['usuario_nome'])[0]); ?></strong>
                </div>
            </div>

            <!-- Bloco de Mensagens -->
            <?php if ($mensagem): ?>
                <div class="mensagem <?php echo htmlspecialchars($mensagem['tipo']); ?>">
                    <?php echo htmlspecialchars($mensagem['texto']); ?>
                </div>
            <?php endif; ?>
            <?php if (isset($db_error)): ?>
                <div class="mensagem erro"><?php echo $db_error; ?></div>
            <?php endif; ?>

            <!-- Card: Editar Categoria (Update) -->
            <div class="content-card">
                <?php if ($categoria): ?>
                    <h2>Editando: <?php echo htmlspecialchars($categoria['nome_categoria']); ?></h2>
                    <form action="editar_categoria.php" method="POST" class="form-inline">

                        <input type="hidden" name="id_categoria" value="<?php echo $categoria['id_categoria']; ?>">

                        <div class="form-group">
                            <label for="nome_categoria">Novo Nome da Categoria</label>
                            <input type="text" id="nome_categoria" name="nome_categoria"
                                value="<?php echo htmlspecialchars($categoria['nome_categoria']); ?>" required>
                        </div>
                        <button type="submit" class="btn-submit">Salvar Alterações</button>
                    </form>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>

</html>