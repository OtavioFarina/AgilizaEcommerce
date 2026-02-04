<?php
// 1. Inicia sessão, conexão e segurança
require_once '../session_config.php';
require_once '../conexao.php';

if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true || $_SESSION['usuario_tipo'] != 2) {
    $_SESSION['mensagem'] = ['tipo' => 'erro', 'texto' => 'Acesso restrito.'];
    header('Location: ../index.php');
    exit;
}

// 2. (NOVO) Processa a atualização de status (Self-Posting)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update_status') {
    $id_venda = $_POST['id_venda'];
    $novo_status = $_POST['novo_status'];

    // Lista de status permitidos (para segurança)
    $status_permitidos = ['Aguardando Confirmação', 'Pagamento Aprovado', 'Em Preparação', 'Em Trânsito', 'Saiu para Entrega', 'Entregue', 'Cancelado'];

    if (in_array($novo_status, $status_permitidos)) {
        try {
            $sql_update = "UPDATE vendas SET status = :status WHERE id_venda = :id_venda"; //
            $stmt_update = $pdo->prepare($sql_update);
            $stmt_update->execute([':status' => $novo_status, ':id_venda' => $id_venda]);
            
            $_SESSION['mensagem'] = ['tipo' => 'sucesso', 'texto' => "Status do pedido #$id_venda atualizado para $novo_status."];
        } catch (PDOException $e) {
            $_SESSION['mensagem'] = ['tipo' => 'erro', 'texto' => 'Erro ao atualizar status: ' . $e->getMessage()];
        }
    } else {
        $_SESSION['mensagem'] = ['tipo' => 'erro', 'texto' => 'Status inválido selecionado.'];
    }
    
    header('Location: admin_pedidos.php'); // Recarrega a página
    exit;
}


// 3. Prepara mensagens e busca os dados para exibir
$mensagem = null;
if (isset($_SESSION['mensagem'])) {
    $mensagem = $_SESSION['mensagem'];
    unset($_SESSION['mensagem']);
}

$pedidos = [];
try {
    // Query com JOINs para pegar NOME do usuário e NOME do pagamento
    $sql = "SELECT v.id_venda, v.data, v.status, v.valor_total, u.nome_usuario, f.nome_forma_pagamento 
            FROM vendas v
            JOIN usuarios u ON v.id_usuario = u.id_usuario
            JOIN forma_pagamento f ON v.id_forma_pagamento = f.id_forma_pagamento
            ORDER BY v.data DESC"; //
            
    $stmt = $pdo->query($sql);
    $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $db_error = "Erro ao carregar pedidos: " . $e->getMessage();
}

// 4. (NOVO) Função Helper de Status (copiada do pedidos.php)
function getStatusClass($status) {
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

// (NOVO) Array com as opções de status para o <select>
$opcoes_status = ['Aguardando Confirmação', 'Pagamento Aprovado', 'Em Preparação', 'Em Trânsito', 'Saiu para Entrega', 'Entregue', 'Cancelado'];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Pedidos - Painel ADM</title>
    <link rel="icon" href="../img/background/favicon.jpg" type="img/jpg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        /* [Seu CSS do painel_admin.php COPIADO AQUI] */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Montserrat', sans-serif; background-color: #f4f7fa; color: #333; display: flex; min-height: 100vh; }
        a { text-decoration: none; color: inherit; }
        ul { list-style: none; }
        table { border-collapse: collapse; width: 100%; }
        .admin-container { display: flex; width: 100%; }
        .admin-sidebar { flex: 0 0 260px; background-color: #ffffff; min-height: 100vh; border-right: 1px solid #e0e0e0; padding: 20px; box-shadow: 2px 0 10px rgba(0, 0, 0, 0.05); }
        .admin-sidebar .logo { font-size: 1.8rem; font-weight: 700; letter-spacing: 1px; color: #b8860b; text-align: center; margin-bottom: 30px; display: block; }
        .admin-sidebar .admin-menu li { margin-bottom: 10px; }
        .admin-sidebar .admin-menu li a { display: flex; align-items: center; gap: 15px; padding: 14px 20px; font-weight: 500; border-radius: 8px; transition: background-color 0.3s ease, color 0.3s ease; }
        .admin-sidebar .admin-menu li a i { font-size: 1.3rem; }
        .admin-sidebar .admin-menu li a:hover { background-color: #f0f0f0; }
        .admin-sidebar .admin-menu li a.active { background-color: #b8860b; color: #ffffff; }
        .admin-sidebar .admin-menu .menu-divider { height: 1px; background-color: #eee; margin: 20px 0; }
        .admin-content { flex: 1; padding: 30px 40px; overflow-y: auto; }
        .admin-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .admin-header h1 { font-size: 2.5rem; font-weight: 700; }
        .admin-header .admin-profile { font-weight: 500; color: #555; }
        .content-card { background-color: #ffffff; border-radius: 10px; padding: 25px; border: 1px solid #e0e0e0; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04); margin-bottom: 30px; }
        .content-card h2 { font-size: 1.5rem; font-weight: 700; margin-bottom: 20px; }
        
        /* Estilos da Tabela de Pedidos ADM */
        .table-responsive-wrapper { width: 100%; overflow-x: auto; }
        .admin-orders-table th, .admin-orders-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #f0f0f0;
            vertical-align: middle;
        }
        .admin-orders-table th { font-weight: 700; background-color: #f9f9f9; white-space: nowrap; }
        .admin-orders-table td { font-size: 0.95rem; }
        
        /* (NOVO) Estilo para o formulário de status dentro da tabela */
        .status-form {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .status-form select {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-family: 'Montserrat', sans-serif;
            font-size: 0.9rem;
        }
        .btn-update {
            background-color: #b8860b;
            color: white;
            padding: 8px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
        }
        .btn-update:hover { background-color: #a0740a; }
        
        /* (COPIADO DO pedidos.php) Estilos dos Status */
        .status { padding: 5px 10px; border-radius: 5px; font-weight: 700; font-size: 0.85rem; color: #333; }
        .status-delivered { background-color: #d4edda; color: #155724; }
        .status-pending { background-color: #fff3cd; color: #856404; }
        .status-shipped { background-color: #d1ecf1; color: #0c5460; }
        .status-canceled { background-color: #f8d7da; color: #721c24; }
        
        /* Mensagens de Feedback */
        .mensagem { padding: 15px; margin-bottom: 20px; border-radius: 5px; font-weight: 500; border: 1px solid transparent; }
        .mensagem.sucesso { background-color: #d4edda; color: #155724; border-color: #c3e6cb; }
        .mensagem.erro { background-color: #f8d7da; color: #721c24; border-color: #f5c6cb; }
    </style>
</head>
<body>
    <div class="admin-container">
        <aside class="admin-sidebar">
            <a href="../index.php" class="logo">IBISA</a>
            <ul class="admin-menu">
                <li><a href="painel_admin.php"><i class='bx bxs-dashboard'></i>Dashboard</a></li>
                <li><a href="admin_produtos.php"><i class='bx bxs-package'></i>Produtos</a></li>
                <li><a href="admin_estoque.php"><i class='bx bxs-bar-chart-alt-2'></i>Gerenciar Estoque</a></li>
                <li><a href="admin_categorias.php"><i class='bx bxs-category-alt'></i>Categorias</a></li>
                <li><a href="admin_pedidos.php" class="active"><i class='bx bxs-cart-alt'></i>Ver Pedidos</a></li>
                <li><a href="admin_usuarios.php"><i class='bx bxs-user-account'></i>Gerenciar Clientes</a></li>
                <li><a href="admin_mensagens.php"><i class='bx bxs-envelope'></i> Mensagens</a></li>
                <li class="menu-divider"></li>
                <li><a href="../index.php"><i class='bx bx-arrow-back'></i>Voltar ao Site</a></li>
                <li><a href="../logout.php"><i class='bx bxs-log-out'></i>Sair</a></li>
            </ul>
        </aside>

        <main class="admin-content">
            <div class="admin-header">
                <h1>Gerenciar Pedidos</h1>
                <div class="admin-profile">
                    Admin: <strong><?php echo htmlspecialchars(explode(' ', $_SESSION['usuario_nome'])[0]); ?></strong>
                </div>
            </div>

            <?php if ($mensagem): ?>
                <div class="mensagem <?php echo htmlspecialchars($mensagem['tipo']); ?>">
                    <?php echo htmlspecialchars($mensagem['texto']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($db_error)): ?>
                <div class="mensagem erro"><?php echo $db_error; ?></div>
            <?php endif; ?>

            <div class="content-card">
                <h2>Todos os Pedidos</h2>
                <div class="table-responsive-wrapper">
                    <table class="admin-orders-table">
                        <thead>
                            <tr>
                                <th>Pedido</th>
                                <th>Data</th>
                                <th>Cliente</th>
                                <th>Valor Total</th>
                                <th>Status Atual</th>
                                <th>Mudar Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($pedidos)): ?>
                                <tr><td colspan="6" style="text-align: center;">Nenhum pedido encontrado.</td></tr>
                            <?php else: ?>
                                <?php foreach ($pedidos as $pedido): ?>
                                <tr>
                                    <td><strong>#<?php echo htmlspecialchars($pedido['id_venda']); ?></strong></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($pedido['data'])); ?></td>
                                    <td><?php echo htmlspecialchars($pedido['nome_usuario']); ?></td>
                                    <td>R$ <?php echo number_format($pedido['valor_total'], 2, ',', '.'); ?></td>
                                    <td>
                                        <span class="status <?php echo getStatusClass($pedido['status']); ?>">
                                            <?php echo htmlspecialchars($pedido['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <form action="admin_pedidos.php" method="POST" class="status-form">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="id_venda" value="<?php echo $pedido['id_venda']; ?>">
                                            
                                            <select name="novo_status">
                                                <?php foreach ($opcoes_status as $status): ?>
                                                    <option value="<?php echo $status; ?>" <?php echo ($status == $pedido['status'] ? 'selected' : ''); ?>>
                                                        <?php echo $status; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            
                                            <button type="submit" class="btn-update">Atualizar</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>