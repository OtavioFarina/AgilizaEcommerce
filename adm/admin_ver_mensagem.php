<?php
session_start();
require_once '../conexao.php';

// Bloco de segurança
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true || $_SESSION['usuario_tipo'] != 2) {
    $_SESSION['mensagem'] = ['tipo' => 'erro', 'texto' => 'Acesso restrito.'];
    header('Location: ../index.php');
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: admin_mensagens.php');
    exit;
}

$id_mensagem = intval($_GET['id']);

try {
    // 1. Marca a mensagem como "Lida"
    $stmt_update = $pdo->prepare("UPDATE mensagens_contato SET status = 'Lida' WHERE id_mensagem = :id");
    $stmt_update->execute([':id' => $id_mensagem]);

    // 2. Busca a mensagem para exibir
    $stmt_select = $pdo->prepare("SELECT * FROM mensagens_contato WHERE id_mensagem = :id");
    $stmt_select->execute([':id' => $id_mensagem]);
    $msg = $stmt_select->fetch(PDO::FETCH_ASSOC);

    if (!$msg) {
        header('Location: admin_mensagens.php');
        exit;
    }
} catch (PDOException $e) {
    die("Erro ao ler mensagem: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Ler Mensagem</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Montserrat', sans-serif;
            background-color: #f4f7fa;
            color: #333;
            line-height: 1.6;
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

        table {
            border-collapse: collapse;
            width: 100%;
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

        .stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .stat-card {
            background-color: #ffffff;
            border-radius: 10px;
            padding: 25px;
            border: 1px solid #e0e0e0;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
        }

        .stat-card i {
            font-size: 2.5rem;
            color: #b8860b;
            margin-bottom: 10px;
        }

        .stat-card h3 {
            font-size: 1.1rem;
            font-weight: 500;
            color: #777;
            text-transform: uppercase;
        }

        .stat-card p {
            font-size: 2.2rem;
            font-weight: 700;
            color: #333;
        }

        .recent-activity {
            background-color: #ffffff;
            border-radius: 10px;
            padding: 25px;
            border: 1px solid #e0e0e0;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
        }

        .recent-activity h2 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 20px;
        }

        .recent-table th,
        .recent-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #f0f0f0;
        }

        .recent-table th {
            font-weight: 700;
            background-color: #f9f9f9;
        }

        .recent-table td {
            font-size: 0.95rem;
        }

        .message-details {
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 25px;
            line-height: 1.8;
        }

        .message-details p {
            margin-bottom: 10px;
        }

        .message-body {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 5px;
            margin-top: 20px;
            white-space: pre-wrap;
            /* Preserva quebras de linha */
        }
    </style>
</head>

<body>
    <div class="admin-container">
        <aside class="admin-sidebar">
            <a href="painel_admin.php" class="logo">IBISA</a>
            <ul class="admin-menu">
                <li><a href="painel_admin.php"><i class='bx bxs-dashboard'></i> Dashboard</a></li>
                <li><a href="admin_produtos.php"><i class='bx bxs-package'></i> Produtos</a></li>
                <li><a href="admin_estoque.php"><i class='bx bxs-bar-chart-alt-2'></i> Gerenciar Estoque</a></li>
                <li><a href="admin_categorias.php"><i class='bx bxs-category-alt'></i> Categorias</a></li>
                <li><a href="admin_pedidos.php"><i class='bx bxs-cart-alt'></i> Ver Pedidos</a></li>
                <li><a href="admin_usuarios.php"><i class='bx bxs-user-account'></i> Gerenciar Clientes</a></li>
                <li><a href="admin_mensagens.php" class="active"><i class='bx bxs-envelope'></i> Mensagens</a></li>
                <li class="menu-divider"></li>
                <li><a href="../index.php"><i class='bx bx-arrow-back'></i> Voltar ao Site</a></li>
                <li><a href="../logout.php"><i class='bx bxs-log-out'></i> Sair</a></li>
            </ul>
        </aside>

        <main class="admin-content">
            <div class="admin-header">
                <h1>Ler Mensagem</h1>
                <a href="admin_mensagens.php" style="font-weight: 700; color: #b8860b;">&larr; Voltar para Caixa de
                    Entrada</a>
            </div>

            <div class="message-details">
                <p><strong>De:</strong> <?php echo htmlspecialchars($msg['nome']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($msg['email']); ?></p>
                <p><strong>Data:</strong>
                    <?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($msg['data_envio']))); ?></p>
                <p><strong>Assunto:</strong> <?php echo htmlspecialchars($msg['assunto']); ?></p>
                <hr style="margin: 20px 0; border: 0; border-top: 1px solid #eee;">

                <strong>Mensagem:</strong>
                <div class="message-body">
                    <?php echo nl2br(htmlspecialchars($msg['mensagem'])); // nl2br para quebrar linhas ?>
                </div>
            </div>
        </main>
    </div>
</body>

</html>