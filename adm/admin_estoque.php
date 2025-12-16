<?php
// 1. Inicia a sessão e conexão (com o caminho ../)
session_start();
require_once '../conexao.php';

// 2. Segurança do ADM (O "Bouncer")
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true || $_SESSION['usuario_tipo'] != 2) {
    $_SESSION['mensagem'] = ['tipo' => 'erro', 'texto' => 'Acesso restrito.'];
    header('Location: ../index.php');
    exit;
}

// --- 3. Processamento de Ações (Movimentar Estoque) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Pega os dados do formulário
    $id_produto = $_POST['id_produto'];
    $tipo_movimentacao = $_POST['tipo_movimentacao'];
    $quantidade = (int) $_POST['quantidade'];
    $valor_custo = !empty($_POST['valor_custo_unitario']) ? trim($_POST['valor_custo_unitario']) : null;
    // (CORREÇÃO) Removemos a variável $motivo, pois ela não existe no seu ibisa.sql
    // $motivo = trim($_POST['motivo']); 

    // Validação
    if (empty($id_produto) || empty($tipo_movimentacao) || $quantidade <= 0) {
        $_SESSION['mensagem'] = ['tipo' => 'erro', 'texto' => 'Produto, Tipo e Quantidade (maior que 0) são obrigatórios.'];
    } else {

        // --- A MÁGICA: TRANSAÇÃO NO BANCO ---
        try {
            $pdo->beginTransaction(); // Inicia a transação

            // 1. INSERE no LOG
            // (CORREÇÃO) Trocado 'movimentacao_estoque' para 'estoque'
            // (CORREÇÃO) Removida a coluna 'motivo' do INSERT
            $sql_log = "INSERT INTO estoque (id_produto, tipo_movimentacao, quantidade, valor_custo_unitario)
                        VALUES (:id_produto, :tipo, :qtd, :custo)";
            $stmt_log = $pdo->prepare($sql_log);
            $stmt_log->execute([
                ':id_produto' => $id_produto,
                ':tipo' => $tipo_movimentacao,
                ':qtd' => $quantidade,
                ':custo' => $valor_custo
                // (CORREÇÃO) Removido o bind de ':motivo'
            ]);

            // 2. ATUALIZA o SNAPSHOT (na tabela produto)
            if ($tipo_movimentacao == 'Entrada') {
                $sql_update = "UPDATE produto SET estoque = estoque + :qtd WHERE id_produto = :id";
            } else {
                $sql_update = "UPDATE produto SET estoque = estoque - :qtd WHERE id_produto = :id";
            }
            $stmt_update = $pdo->prepare($sql_update);
            $stmt_update->execute([':qtd' => $quantidade, ':id' => $id_produto]);

            // Se chegou aqui, deu tudo certo!
            $pdo->commit(); // Confirma as mudanças
            $_SESSION['mensagem'] = ['tipo' => 'sucesso', 'texto' => 'Movimentação de estoque registrada com sucesso!'];

        } catch (PDOException $e) {
            // Se algo deu errado, desfaz tudo
            $pdo->rollBack();
            $_SESSION['mensagem'] = ['tipo' => 'erro', 'texto' => 'Erro ao processar movimentação: ' . $e->getMessage()];
        }
        // --- Fim da Transação ---
    }

    header('Location: admin_estoque.php');
    exit;
}

// --- Fim do Processamento de Ações ---

// 4. Preparação para Exibição (O "Read")
$mensagem = null;
if (isset($_SESSION['mensagem'])) {
    $mensagem = $_SESSION['mensagem'];
    unset($_SESSION['mensagem']);
}

// Buscar dados
$produtos_lista = []; // Para o <select>
$estoque_atual = [];  // Para a tabela "Estoque Atual"
$historico_mov = [];  // Para o "Log"

try {
    // Query 1: Lista de produtos para o formulário
    $stmt_prods = $pdo->query("SELECT id_produto, nome_produto FROM produto ORDER BY nome_produto");
    $produtos_lista = $stmt_prods->fetchAll(PDO::FETCH_ASSOC);

    // Query 2: Snapshot do estoque atual (da tabela produto)
    $stmt_estoque = $pdo->query("SELECT id_produto, nome_produto, estoque FROM produto ORDER BY nome_produto");
    $estoque_atual = $stmt_estoque->fetchAll(PDO::FETCH_ASSOC);

    // Query 3: Histórico de movimentações
    // (CORREÇÃO) Trocado 'movimentacao_estoque' para 'estoque'
    // (CORREÇÃO) Removido m.motivo do SELECT, pois não existe
    $sql_hist = "SELECT m.data, m.tipo_movimentacao, m.quantidade, p.nome_produto 
                 FROM estoque m
                 JOIN produto p ON m.id_produto = p.id_produto
                 ORDER BY m.data DESC
                 LIMIT 50"; // Pega só os 50 últimos
    $stmt_hist = $pdo->query($sql_hist);
    $historico_mov = $stmt_hist->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // (CORREÇÃO) A mensagem de erro da sua print é capturada aqui!
    $mensagem = ['tipo' => 'erro', 'texto' => 'Erro ao carregar dados: ' . $e->getMessage()];
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Estoque - Painel ADM</title>

    <link rel="icon" href="../img/favicon.jpg" type="img/jpg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>

    <style>
        /* [Seu CSS idêntico ao de admin_produtos.php] */
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

        .card-layout-grid {
            display: grid;
            grid-template-columns: 1fr 1.5fr;
            gap: 30px;
        }

        @media (max-width: 1200px) {
            .card-layout-grid {
                grid-template-columns: 1fr;
            }
        }

        .form-grid-mov {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-weight: 500;
            margin-bottom: 5px;
            font-size: 0.9rem;
        }

        .form-group input,
        .form-group select {
            width: 100%;
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
            margin-top: 10px;
            align-self: flex-end;
        }

        .btn-submit:hover {
            background-color: #d4af37;
        }

        .generic-table th,
        .generic-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #f0f0f0;
        }

        .generic-table th {
            font-weight: 700;
            background-color: #f9f9f9;
        }

        .generic-table td {
            font-size: 0.9rem;
        }

        .stock-level {
            font-weight: 700;
            font-size: 1rem;
        }

        .stock-level.low {
            color: #dc3545;
        }

        .stock-level.ok {
            color: #28a745;
        }

        .mov-entrada {
            color: #28a745;
            font-weight: 700;
        }

        .mov-saida {
            color: #dc3545;
            font-weight: 700;
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
        <aside class="admin-sidebar">
            <a href="../index.php" class="logo">IBISA</a>
            <ul class="admin-menu">
                <li><a href="painel_admin.php"><i class='bx bxs-dashboard'></i>Dashboard</a></li>
                <li><a href="admin_produtos.php"><i class='bx bxs-package'></i>Produtos</a></li>
                <li><a href="admin_estoque.php" class="active"><i class='bx bxs-bar-chart-alt-2'></i>Gerenciar
                        Estoque</a></li>
                <li><a href="admin_categorias.php"><i class='bx bxs-category-alt'></i>Categorias</a></li>
                <li><a href="admin_pedidos.php"><i class='bx bxs-cart-alt'></i>Ver Pedidos</a></li>
                <li><a href="admin_usuarios.php"><i class='bx bxs-user-account'></i>Gerenciar Clientes</a></li>
                <li><a href="admin_mensagens.php"><i class='bx bxs-envelope'></i> Mensagens</a></li>
                <li class="menu-divider"></li>
                <li><a href="../index.php"><i class='bx bx-arrow-back'></i>Voltar ao Site</a></li>
                <li><a href="../logout.php"><i class='bx bxs-log-out'></i>Sair</a></li>
            </ul>
        </aside>

        <main class="admin-content">
            <div class="admin-header">
                <h1>Gerenciar Estoque</h1>
                <div class="admin-profile">
                    Admin: <strong><?php echo htmlspecialchars(explode(' ', $_SESSION['usuario_nome'])[0]); ?></strong>
                </div>
            </div>

            <?php if ($mensagem): ?>
                <div class="mensagem <?php echo htmlspecialchars($mensagem['tipo']); ?>">
                    <?php echo htmlspecialchars($mensagem['texto']); ?>
                </div>
            <?php endif; ?>

            <div class="content-card">
                <h2>Registrar Movimentação Manual</h2>
                <form action="admin_estoque.php" method="POST">
                    <div class="form-grid-mov">
                        <div class="form-group">
                            <label for="id_produto">Produto</label>
                            <select id="id_produto" name="id_produto" required>
                                <option value="">Selecione um produto...</option>
                                <?php foreach ($produtos_lista as $produto): ?>
                                    <option value="<?php echo $produto['id_produto']; ?>">
                                        <?php echo htmlspecialchars($produto['nome_produto']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="tipo_movimentacao">Tipo</label>
                            <select id="tipo_movimentacao" name="tipo_movimentacao" required>
                                <option value="Entrada">Entrada (Adicionar ao estoque)</option>
                                <option value="Saída">Saída (Remover do estoque)</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-grid-mov" style="margin-top: 20px;">
                        <div class="form-group">
                            <label for="quantidade">Quantidade</label>
                            <input type="number" id="quantidade" name="quantidade" min="1" required>
                        </div>
                        <div class="form-group">
                            <label for="valor_custo_unitario">Valor de Custo (Unitário)</label>
                            <input type="text" id="valor_custo_unitario" name="valor_custo_unitario"
                                placeholder="19.90 (Opcional)">
                        </div>
                    </div>

                    <div class="form-group" style="margin-top: 20px; align-items: flex-end;">
                        <button type="submit" class="btn-submit">Confirmar Movimentação</button>
                    </div>
                </form>
            </div>

            <div class="card-layout-grid">
                <div class="content-card">
                    <h2>Estoque Atual</h2>
                    <table class="generic-table">
                        <thead>
                            <tr>
                                <th>Produto</th>
                                <th style="text-align: right;">Qtd.</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($estoque_atual as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['nome_produto']); ?></td>
                                    <td style="text-align: right;">
                                        <span class="stock-level <?php echo ($item['estoque'] <= 5 ? 'low' : 'ok'); ?>">
                                            <?php echo $item['estoque']; ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="content-card">
                    <h2>Histórico de Movimentações</h2>
                    <table class="generic-table">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Produto</th>
                                <th>Tipo</th>
                                <th>Qtd.</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($historico_mov)): ?>
                                <tr>
                                    <td colspan="4" style="text-align: center;">Nenhuma movimentação registrada.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($historico_mov as $mov): ?>
                                    <tr>
                                        <td><?php echo date('d/m/y H:i', strtotime($mov['data'])); ?></td>
                                        <td><?php echo htmlspecialchars($mov['nome_produto']); ?></td>
                                        <td>
                                            <span
                                                class="<?php echo ($mov['tipo_movimentacao'] == 'Entrada' ? 'mov-entrada' : 'mov-saida'); ?>">
                                                <?php echo $mov['tipo_movimentacao']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo $mov['quantidade']; ?></td>
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