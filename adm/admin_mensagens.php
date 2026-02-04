<?php
session_start();
require_once '../conexao.php';

// Bloco de segurança
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true || $_SESSION['usuario_tipo'] != 2) {
    $_SESSION['mensagem'] = ['tipo' => 'erro', 'texto' => 'Acesso restrito.'];
    header('Location: ../index.php');
    exit;
}

try {
    // Busca as mensagens, colocando as "Pendentes" primeiro e as mais novas no topo
    $stmt = $pdo->query("SELECT id_mensagem, nome, email, assunto, data_envio, status 
                         FROM mensagens_contato 
                         ORDER BY status ASC, data_envio DESC");
    $mensagens = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $erro = "Erro ao buscar mensagens: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Mensagens - Fale Conosco</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        /* --- CSS GERAL DO PAINEL (LIMPO) --- */
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

        .table-responsive-wrapper {
            width: 100%;
            overflow-x: auto;
        }

        .user-table th,
        .user-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #f0f0f0;
            vertical-align: middle;
            white-space: nowrap;
        }

        .user-table th {
            font-weight: 700;
            background-color: #f9f9f9;
        }

        .user-table td {
            font-size: 0.95rem;
        }

        /* (NOVO) Estilos para Status e Botões de Ação */
        .status-badge {
            padding: 5px 10px;
            border-radius: 5px;
            font-weight: 700;
            font-size: 0.85rem;
            color: #333;
        }

        .status-ativa {
            background-color: #d4edda;
            color: #155724;
        }

        .status-bloqueada {
            background-color: #f8d7da;
            color: #721c24;
        }

        .action-button {
            padding: 8px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            font-size: 0.9rem;
            transition: background-color 0.3s ease;
            color: #fff;
        }

        .btn-block {
            background-color: #dc3545;
        }

        /* Vermelho */
        .btn-block:hover {
            background-color: #c82333;
        }

        .btn-unblock {
            background-color: #28a745;
        }

        /* Verde */
        .btn-unblock:hover {
            background-color: #218838;
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

        /* Wrapper para rolagem horizontal em telas pequenas */
        .table-responsive-wrapper {
            width: 100%;
            overflow-x: auto;
        }

        /* --- ESTILOS ESPECÍFICOS DA TABELA DE MENSAGENS --- */

        .message-table th,
        .message-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #f0f0f0;
            vertical-align: middle;
            white-space: nowrap;
            /* Impede que o texto quebre linha */
        }

        .message-table th {
            font-weight: 700;
            background-color: #f9f9f9;
        }

        .message-table td {
            font-size: 0.95rem;
        }

        .status-pendente {
            font-weight: 700;
            color: #d9534f;
            /* Vermelho */
            padding: 4px 8px;
            border-radius: 4px;
            background-color: #f8d7da;
        }

        .status-lida {
            font-weight: 500;
            color: #5cb85c;
            /* Verde */
            padding: 4px 8px;
            border-radius: 4px;
            background-color: #d4edda;
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
                <h1>Mensagens - Fale Conosco</h1>
            </div>

            <div class="content-card">
                <h2>Caixa de Entrada</h2>
                <?php if (isset($erro)): ?>
                    <p style="color: red;"><?php echo $erro; ?></p>
                <?php elseif (empty($mensagens)): ?>
                    <p>Nenhuma mensagem recebida ainda.</p>
                <?php else: ?>
                    <div class="table-responsive-wrapper">
                        <table class="message-table">
                            <thead>
                                <tr>
                                    <th>Status</th>
                                    <th>Data</th>
                                    <th>De</th>
                                    <th>Assunto</th>
                                    <th>Ação</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($mensagens as $msg): ?>
                                    <tr>
                                        <td>
                                            <span
                                                class="<?php echo $msg['status'] == 'Pendente' ? 'status-pendente' : 'status-lida'; ?>">
                                                <?php echo htmlspecialchars($msg['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($msg['data_envio']))); ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($msg['nome']); ?>
                                            (<?php echo htmlspecialchars($msg['email']); ?>)</td>
                                        <td><?php echo htmlspecialchars($msg['assunto']); ?></td>
                                        <td>
                                            <a href="admin_ver_mensagem.php?id=<?php echo $msg['id_mensagem']; ?>"
                                                style="color: #b8860b; font-weight: 700;">
                                                Ver Mensagem
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div> <?php endif; ?>
            </div>
        </main>
    </div>
</body>

</html>