<?php
session_start();

require_once '../conexao.php';

if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true || $_SESSION['usuario_tipo'] != 2) {
    $_SESSION['mensagem'] = [
        'tipo' => 'erro',
        'texto' => 'Acesso restrito. Você não tem permissão de administrador.'
    ];
    header('Location: index.php');
    exit;
}

try {
    $stmt_users = $pdo->query("SELECT COUNT(id_usuario) FROM usuarios WHERE tipo = 1");
    $total_clientes = $stmt_users->fetchColumn();

    $stmt_prods = $pdo->query("SELECT COUNT(id_produto) FROM produto");
    $total_produtos = $stmt_prods->fetchColumn();

    $stmt_pedidos = $pdo->query("SELECT COUNT(id_venda) FROM vendas");
    $total_pedidos = $stmt_pedidos->fetchColumn();

    $stmt_fatura = $pdo->query("SELECT SUM(valor_total) FROM vendas");
    $faturamento_total = $stmt_fatura->fetchColumn();
    $faturamento_formatado = 'R$ ' . number_format($faturamento_total ?? 0, 2, ',', '.');

    $sql_ult_pedidos = "SELECT v.id_venda, v.data, v.valor_total, u.nome_usuario 
                        FROM vendas v
                        JOIN usuarios u ON v.id_usuario = u.id_usuario
                        ORDER BY v.data DESC
                        LIMIT 5";
    $stmt_ult_pedidos = $pdo->query($sql_ult_pedidos);
    $ultimos_pedidos = $stmt_ult_pedidos->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $erro_kpi = "Erro ao carregar estatísticas: " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Administrativo - Ibisa</title>

    <link rel="icon" href="../img/favicon.jpg" type="img/jpg">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
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
    </style>
</head>

<body>

    <div class="admin-container">
        <aside class="admin-sidebar">
            <a href="index.php" class="logo">IBISA</a>

            <ul class="admin-menu">
                <li>
                    <a href="painel_admin.php" class="active">
                        <i class='bx bxs-dashboard'></i> Dashboard
                    </a>
                </li>
                <li>
                    <a href="admin_produtos.php">
                        <i class='bx bxs-package'></i>
                        Produtos
                    </a>
                </li>
                <li>
                    <a href="admin_estoque.php">
                        <i class='bx bxs-bar-chart-alt-2'></i>
                        Gerenciar Estoque
                    </a>
                </li>
                <li>
                    <a href="admin_categorias.php">
                        <i class='bx bxs-category-alt'></i>
                        Categorias
                    </a>
                </li>
                <li>
                    <a href="admin_pedidos.php">
                        <i class='bx bxs-cart-alt'></i>
                        Ver Pedidos
                    </a>
                </li>
                <li>
                    <a href="admin_usuarios.php">
                        <i class='bx bxs-user-account'></i>
                        Gerenciar Clientes
                    </a>
                </li>
                <li>
                    <a href="admin_mensagens.php">
                        <i class='bx bxs-envelope'></i>
                        Mensagens
                    </a>
                </li>
                <li class="menu-divider"></li>
                <li>
                    <a href="../index.php">
                        <i class='bx bx-arrow-back'></i>
                        Voltar ao Site
                    </a>
                </li>
                <li>
                    <a href="../logout.php">
                        <i class='bx bxs-log-out'></i>
                        Sair
                    </a>
                </li>
            </ul>
        </aside>

        <main class="admin-content">
            <div class="admin-header">
                <h1>Dashboard</h1>
                <div class="admin-profile">
                    Bem-vindo(a),
                    <strong><?php echo htmlspecialchars(explode(' ', $_SESSION['usuario_nome'])[0]); ?></strong>!
                </div>
            </div>

            <div class="stat-grid">
                <div class="stat-card">
                    <i class='bx bxs-dollar-circle'></i>
                    <h3>Faturamento Total</h3>
                    <p><?php echo $faturamento_formatado; ?></p>
                </div>
                <div class="stat-card">
                    <i class='bx bxs-cart-alt'></i>
                    <h3>Total de Pedidos</h3>
                    <p><?php echo $total_pedidos; ?></p>
                </div>
                <div class="stat-card">
                    <i class='bx bxs-user'></i>
                    <h3>Total de Clientes</h3>
                    <p><?php echo $total_clientes; ?></p>
                </div>
                <div class="stat-card">
                    <i class='bx bxs-package'></i>
                    <h3>Produtos Cadastrados</h3>
                    <p><?php echo $total_produtos; ?></p>
                </div>
            </div>

            <div class="recent-activity">
                <h2>Últimos Pedidos</h2>
                <?php if (empty($ultimos_pedidos)): ?>
                    <p>Nenhum pedido encontrado ainda.</p>
                <?php else: ?>
                    <table class="recent-table">
                        <thead>
                            <tr>
                                <th>Nº Pedido</th>
                                <th>Cliente</th>
                                <th>Data</th>
                                <th>Valor</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ultimos_pedidos as $pedido): ?>
                                <tr>
                                    <td>#<?php echo htmlspecialchars($pedido['id_venda']); ?></td>
                                    <td><?php echo htmlspecialchars($pedido['nome_usuario']); ?></td>
                                    <td><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($pedido['data']))); ?></td>
                                    <td>R$ <?php echo number_format($pedido['valor_total'], 2, ',', '.'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

        </main>
    </div>

</body>

</html>