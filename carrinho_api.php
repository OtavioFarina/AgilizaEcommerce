<?php
require_once 'session_config.php';
require_once 'conexao.php';

header('Content-Type: application/json');

function getCartData($pdo)
{
    $carrinho_data = [];
    $total_carrinho = 0;
    $total_itens = 0;

    if (!empty($_SESSION['carrinho'])) {
        $ids_produtos = array_keys($_SESSION['carrinho']);
        $placeholders = implode(',', array_fill(0, count($ids_produtos), '?'));

        $sql = "SELECT id_produto, nome_produto, preco, imagem, estoque FROM produto WHERE id_produto IN ($placeholders)"; //
        $stmt_cart = $pdo->prepare($sql);
        $stmt_cart->execute($ids_produtos);
        // --- CORREÇÃO AQUI ---
        $produtos_no_carrinho = $stmt_cart->fetchAll(PDO::FETCH_UNIQUE | PDO::FETCH_ASSOC);

        foreach ($_SESSION['carrinho'] as $id => $quantidade) {
            $produto = $produtos_no_carrinho[$id] ?? null;
            if (!$produto) {
                unset($_SESSION['carrinho'][$id]);
                continue;
            }

            if ($quantidade > $produto['estoque']) {
                $quantidade = (int) $produto['estoque'];
                $_SESSION['carrinho'][$id] = $quantidade;
            }
            if ($quantidade <= 0) {
                unset($_SESSION['carrinho'][$id]);
                continue;
            }

            $subtotal = $produto['preco'] * $quantidade;
            $carrinho_data[] = [
                'id' => $id,
                'nome' => $produto['nome_produto'],
                'preco' => number_format($produto['preco'], 2, ',', '.'),
                'imagem' => $produto['imagem'] ? 'adm/uploads/' . htmlspecialchars($produto['imagem']) : 'img/placeholder.png', // Caminho da imagem
                'quantidade' => $quantidade,
                'subtotal' => number_format($subtotal, 2, ',', '.'),
                'estoque_max' => (int) $produto['estoque']
            ];

            $total_carrinho += $subtotal;
            $total_itens += $quantidade;
        }
    }

    return [
        'sucesso' => true,
        'itens' => $carrinho_data,
        'total_formatado' => 'R$ ' . number_format($total_carrinho, 2, ',', '.'),
        'total_itens' => $total_itens
    ];
}

$metodo = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

try {
    if ($metodo === 'POST' && isset($input['id_produto'])) {
        $id = (int) $input['id_produto'];

        $stmt_estoque = $pdo->prepare("SELECT estoque FROM produto WHERE id_produto = ?"); //
        $stmt_estoque->execute([$id]);
        $estoque_disponivel = $stmt_estoque->fetchColumn();

        if ($estoque_disponivel === false)
            throw new Exception('Produto não encontrado.');

        $qtd_no_carrinho = $_SESSION['carrinho'][$id] ?? 0;

        if (($qtd_no_carrinho + 1) <= $estoque_disponivel) {
            $_SESSION['carrinho'][$id] = $qtd_no_carrinho + 1;
        } else {
            $_SESSION['carrinho'][$id] = $estoque_disponivel;
        }
    } elseif ($metodo === 'PUT' && isset($input['id_produto'])) {
        $id = (int) $input['id_produto'];
        $nova_quantidade = (int) $input['nova_quantidade'];

        if ($nova_quantidade <= 0) {
            unset($_SESSION['carrinho'][$id]);
        } else {
            $stmt_estoque = $pdo->prepare("SELECT estoque FROM produto WHERE id_produto = ?"); //
            $stmt_estoque->execute([$id]);
            $estoque_disponivel = $stmt_estoque->fetchColumn();

            if ($nova_quantidade > $estoque_disponivel) {
                $_SESSION['carrinho'][$id] = $estoque_disponivel;
            } else {
                $_SESSION['carrinho'][$id] = $nova_quantidade;
            }
        }
    } elseif ($metodo === 'DELETE' && isset($input['id_produto'])) {
        $id = (int) $input['id_produto'];
        unset($_SESSION['carrinho'][$id]);
    }

    $dados_carrinho = getCartData($pdo);
    echo json_encode($dados_carrinho);

} catch (Exception $e) {
    echo json_encode(['sucesso' => false, 'mensagem' => $e->getMessage()]);
}
?>