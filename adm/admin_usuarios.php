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

// 3. (NOVO) Processa a atualização de STATUS (Bloquear/Desbloquear)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'toggle_status') {

    $id_usuario_alvo = $_POST['id_usuario_alvo'];
    $novo_status = $_POST['novo_status'];
    $id_admin_logado = $_SESSION['usuario_id'];

    // (SEGURANÇA) Impede que um admin se bloqueie
    if ($id_usuario_alvo == $id_admin_logado) {
        $_SESSION['mensagem'] = ['tipo' => 'erro', 'texto' => 'Ação inválida. Você não pode bloquear a si mesmo.'];
    }
    // Validação extra para garantir que o status seja válido
    else if ($novo_status == 'Ativa' || $novo_status == 'Bloqueada') {
        try {
            $sql_update = "UPDATE usuarios SET conta_status = :status WHERE id_usuario = :id"; // [cite: ibisa.sql]
            $stmt_update = $pdo->prepare($sql_update);
            $stmt_update->execute([':status' => $novo_status, ':id' => $id_usuario_alvo]);

            $acao = ($novo_status == 'Bloqueada') ? 'bloqueado' : 'desbloqueado';
            $_SESSION['mensagem'] = ['tipo' => 'sucesso', 'texto' => "Usuário (ID: $id_usuario_alvo) foi $acao com sucesso."];
        } catch (PDOException $e) {
            $_SESSION['mensagem'] = ['tipo' => 'erro', 'texto' => 'Erro ao atualizar status: ' . $e->getMessage()];
        }
    } else {
        $_SESSION['mensagem'] = ['tipo' => 'erro', 'texto' => 'Status inválido.'];
    }

    header('Location: admin_usuarios.php'); // Recarrega a página
    exit;
}

// 4. Prepara mensagens e busca os dados para exibir
$mensagem = null;
if (isset($_SESSION['mensagem'])) {
    $mensagem = $_SESSION['mensagem'];
    unset($_SESSION['mensagem']);
}

$usuarios = [];
try {
    // Busca todos os usuários (Admin e Comum)
    $stmt = $pdo->query("SELECT id_usuario, tipo, nome_usuario, email, telefone, cpf, conta_status FROM usuarios ORDER BY nome_usuario ASC"); // [cite: ibisa.sql]
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $db_error = "Erro ao carregar usuários: " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Clientes - Painel ADM</title>
    <!-- Links (com ../ para subir um nível) -->
    <link rel="icon" href="../img/background/favicon.jpg" type="img/jpg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>

    <style>
        /* [CSS do Painel ADM - Idêntico aos outros] */
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
    </style>
</head>

<body>
    <div class="admin-container">
        <!-- --- 1. Sidebar (Menu Lateral) --- -->
        <aside class="admin-sidebar">
            <a href="../index.php" class="logo">IBISA</a>
            <ul class="admin-menu">
                <li><a href="painel_admin.php"><i class='bx bxs-dashboard'></i>Dashboard</a></li>
                <li><a href="admin_produtos.php"><i class='bx bxs-package'></i>Produtos</a></li>
                <li><a href="admin_estoque.php"><i class='bx bxs-bar-chart-alt-2'></i>Gerenciar Estoque</a></li>
                <li><a href="admin_categorias.php"><i class='bx bxs-category-alt'></i>Categorias</a></li>
                <li><a href="admin_pedidos.php"><i class='bx bxs-cart-alt'></i>Ver Pedidos</a></li>
                <!-- (NOVO) Link Ativo -->
                <li><a href="admin_usuarios.php" class="active"><i class='bx bxs-user-account'></i>Gerenciar
                        Clientes</a></li>
                <li><a href="admin_mensagens.php"><i class='bx bxs-envelope'></i> Mensagens</a></li>
                <li class="menu-divider"></li>
                <li><a href="../index.php"><i class='bx bx-arrow-back'></i>Voltar ao Site</a></li>
                <li><a href="../logout.php"><i class='bx bxs-log-out'></i>Sair</a></li>
            </ul>
        </aside>

        <!-- --- 2. Conteúdo Principal --- -->
        <main class="admin-content">
            <div class="admin-header">
                <h1>Gerenciar Clientes</h1>
                <div class="admin-profile">
                    Admin: <strong><?php echo htmlspecialchars(explode(' ', $_SESSION['usuario_nome'])[0]); ?></strong>
                </div>
            </div>

            <!-- Bloco de Mensagens de Feedback -->
            <?php if ($mensagem): ?>
                <div class="mensagem <?php echo htmlspecialchars($mensagem['tipo']); ?>">
                    <?php echo htmlspecialchars($mensagem['texto']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($db_error)): ?>
                <div class="mensagem erro"><?php echo $db_error; ?></div>
            <?php endif; ?>

            <!-- Card: Listagem de Usuários -->
            <div class="content-card">
                <h2>Todos os Usuários</h2>
                <div class="table-responsive-wrapper">
                    <table class="user-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nome</th>
                                <th>Email</th>
                                <th>Telefone</th>
                                <th>CPF</th>
                                <th>Tipo</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($usuarios)): ?>
                                <tr>
                                    <td colspan="8" style="text-align: center;">Nenhum usuário encontrado.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($usuarios as $usuario): ?>
                                    <tr>
                                        <td><strong>#<?php echo htmlspecialchars($usuario['id_usuario']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($usuario['nome_usuario']); ?></td>
                                        <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                                        <td><?php echo htmlspecialchars($usuario['telefone']); ?></td>
                                        <td><?php echo htmlspecialchars($usuario['cpf']); ?></td>
                                        <td>
                                            <!-- Mostra "Admin" ou "Comum" -->
                                            <?php if ($usuario['tipo'] == 2): ?>
                                                <span style="font-weight: 700; color: #b8860b;">Admin</span>
                                            <?php else: ?>
                                                <span>Comum</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <!-- Badge de Status -->
                                            <span
                                                class="status-badge <?php echo ($usuario['conta_status'] == 'Ativa' ? 'status-ativa' : 'status-bloqueada'); ?>">
                                                <?php echo htmlspecialchars($usuario['conta_status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <!-- (NOVO) Formulário de Ação -->
                                            <?php if ($usuario['id_usuario'] == $_SESSION['usuario_id']): ?>
                                                <span style="color: #888;">(Você)</span>
                                            <?php else: ?>
                                                <form action="admin_usuarios.php" method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="toggle_status">
                                                    <input type="hidden" name="id_usuario_alvo"
                                                        value="<?php echo $usuario['id_usuario']; ?>">

                                                    <?php if ($usuario['conta_status'] == 'Ativa'): ?>
                                                        <input type="hidden" name="novo_status" value="Bloqueada">
                                                        <button type="submit" class="action-button btn-block"
                                                            title="Bloquear este usuário">Bloquear</button>
                                                    <?php else: ?>
                                                        <input type="hidden" name="novo_status" value="Ativa">
                                                        <button type="submit" class="action-button btn-unblock"
                                                            title="Desbloquear este usuário">Desbloquear</button>
                                                    <?php endif; ?>
                                                </form>
                                            <?php endif; ?>
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