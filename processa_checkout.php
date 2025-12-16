<?php

require_once 'session_config.php';
require_once 'conexao.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['usuario_logado'])) {
    header('Location: index.php');
    exit;
}

$id_usuario = $_SESSION['usuario_id'];
$carrinho = $_SESSION['carrinho'];

$id_endereco = $_POST['id_endereco'] ?? null;
$id_forma_pagamento = $_POST['id_forma_pagamento'] ?? null;

if (empty($carrinho) || $id_endereco === null || $id_forma_pagamento === null) {
    $_SESSION['mensagem'] = ['tipo' => 'erro', 'texto' => 'Informações incompletas. Tente novamente.'];
    header('Location: checkout.php');
    exit;
}

try {
    $pdo->beginTransaction();

    $ids_produtos = array_keys($carrinho);
    $placeholders = implode(',', array_fill(0, count($ids_produtos), '?'));

    $sql_lock = "SELECT * FROM produto WHERE id_produto IN ($placeholders) FOR UPDATE";
    $stmt_lock = $pdo->prepare($sql_lock);
    $stmt_lock->execute($ids_produtos);
    $produtos_db = $stmt_lock->fetchAll(PDO::FETCH_ASSOC);

    $valor_total_real = 0;

    foreach ($produtos_db as $produto) {
        $id = $produto['id_produto'];
        $qtd_pedida = $carrinho[$id];

        if ($produto['estoque'] < $qtd_pedida) {
            throw new Exception("Desculpe, o produto '{$produto['nome_produto']}' tem apenas {$produto['estoque']} unidades restantes.");
        }

        $valor_total_real += $produto['preco'] * $qtd_pedida;
    }

    $sql_venda = "INSERT INTO vendas (id_usuario, id_forma_pagamento, valor_total) VALUES (?, ?, ?)";
    $stmt_venda = $pdo->prepare($sql_venda);
    $stmt_venda->execute([$id_usuario, $id_forma_pagamento, $valor_total_real]);
    $id_venda = $pdo->lastInsertId();

    $sql_item_venda = "INSERT INTO itens_venda (id_venda, id_produto, quantidade, preco_unitario) VALUES (?, ?, ?, ?)";
    $sql_log_estoque = "INSERT INTO estoque (id_produto, tipo_movimentacao, quantidade, motivo) VALUES (?, 'Saída', ?, ?)";
    $sql_update_prod = "UPDATE produto SET estoque = estoque - ? WHERE id_produto = ?";

    $stmt_item = $pdo->prepare($sql_item_venda);
    $stmt_log = $pdo->prepare($sql_log_estoque);
    $stmt_update = $pdo->prepare($sql_update_prod);

    foreach ($produtos_db as $produto) {
        $id_prod = $produto['id_produto'];
        $qtd = $carrinho[$id_prod];
        $preco_unit = $produto['preco'];
        $motivo_saida = "Venda #" . $id_venda;

        $stmt_item->execute([$id_venda, $id_prod, $qtd, $preco_unit]);

        $stmt_update->execute([$qtd, $id_prod]);

        $stmt_log->execute([$id_prod, $qtd, $motivo_saida]);
    }

    $pdo->commit();

    unset($_SESSION['carrinho']);
    $_SESSION['pedido_sucesso_id'] = $id_venda;
    header('Location: checkout_sucesso.php');
    exit;

} catch (Exception $e) {

    $pdo->rollBack();

    $_SESSION['mensagem'] = ['tipo' => 'erro', 'texto' => $e->getMessage()];
    header('Location: checkout.php');
    exit;
}
?>