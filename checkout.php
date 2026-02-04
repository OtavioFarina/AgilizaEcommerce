<?php
require_once 'session_config.php';
require_once 'conexao.php';

if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
    $_SESSION['mensagem'] = ['tipo' => 'erro', 'texto' => 'Você precisa estar logado para finalizar a compra.'];
    header('Location: conta.php?redirect=checkout.php');
    exit;
}

if (empty($_SESSION['carrinho'])) {
    header('Location: index.php');
    exit;
}

$id_usuario = $_SESSION['usuario_id'];
$enderecos = [];
$formas_pagamento = [];
$itens_carrinho = [];
$total_carrinho = 0;

try {
    $stmt_end = $pdo->prepare("SELECT * FROM enderecos WHERE id_usuario = ?");
    $stmt_end->execute([$id_usuario]);
    $enderecos = $stmt_end->fetchAll(PDO::FETCH_ASSOC);

    // Busca formas de pagamento (AGORA DE VERDADE!)
    $stmt_pag = $pdo->query("SELECT * FROM forma_pagamento ORDER BY id_forma_pagamento");
    $formas_pagamento = $stmt_pag->fetchAll(PDO::FETCH_ASSOC);

    $ids_produtos = array_keys($_SESSION['carrinho']);
    $placeholders = implode(',', array_fill(0, count($ids_produtos), '?'));
    $sql_prod = "SELECT id_produto, nome_produto, preco, imagem FROM produto WHERE id_produto IN ($placeholders)";
    $stmt_prod = $pdo->prepare($sql_prod);
    $stmt_prod->execute($ids_produtos);
    $produtos_db = $stmt_prod->fetchAll(PDO::FETCH_UNIQUE | PDO::FETCH_ASSOC);

    foreach ($_SESSION['carrinho'] as $id => $quantidade) {
        $produto = $produtos_db[$id] ?? null;
        if (!$produto) continue;
        $subtotal = $produto['preco'] * $quantidade;
        $itens_carrinho[] = [
            'nome' => $produto['nome_produto'],
            'preco' => $produto['preco'],
            'imagem' => $produto['imagem'] ? 'adm/uploads/' . htmlspecialchars($produto['imagem']) : 'img/placeholder.png',
            'quantidade' => $quantidade,
            'subtotal' => $subtotal
        ];
        $total_carrinho += $subtotal;
    }

} catch (PDOException $e) {
    $db_error = "Erro ao carregar dados: " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finalizar Compra - Ibisa Acessórios</title>
    <link rel="icon" href="img/background/favicon.jpg" type="img/jpg">
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

img {
    max-width: 100%;
    display: block;
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
    gap: 15px;
    font-weight: 500;
    align-items: center;
    flex-shrink: 0;
    white-space: nowrap;
}

.nav-icons .admin-link {
    color: #b8860b;
    font-weight: 700;
    border: 1px solid #b8860b;
    padding: 5px 10px;
    border-radius: 5px;
}

.site-footer {
    background-color: #222;
    color: #ccc;
    padding-top: 60px;
    font-size: 0.9rem;
    line-height: 1.7;
    margin-top: auto;
}

.checkout-container {
    max-width: 1200px;
    margin: 40px auto;
    padding: 0 20px;
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 40px;
}

@media (max-width: 900px) {
    .checkout-container {
        grid-template-columns: 1fr;
    }

    .checkout-summary {
        grid-row: 1;
    }
}

.checkout-form h2,
.checkout-summary h2 {
    font-size: 1.8rem;
    font-weight: 700;
    margin-bottom: 25px;
    border-bottom: 2px solid #eee;
    padding-bottom: 10px;
}

fieldset {
    border: none;
    margin-bottom: 30px;
}

legend {
    font-size: 1.3rem;
    font-weight: 700;
    color: #b8860b;
    margin-bottom: 15px;
}

.address-option,
.payment-option {
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 10px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 15px;
}

.address-option:hover,
.payment-option:hover {
    border-color: #b8860b;
    background-color: #fdfdfd;
}

.address-option input[type="radio"],
.payment-option input[type="radio"] {
    width: 20px;
    height: 20px;
}

.address-option label,
.payment-option label {
    font-size: 1rem;
    font-weight: 500;
    width: 100%;
}

.address-option p {
    font-size: 0.9rem;
    color: #777;
}

.checkout-summary {
    background: #fdfdfd;
    border: 1px solid #eee;
    border-radius: 8px;
    padding: 25px;
    height: fit-content;
    position: sticky;
    top: 120px;
}

.summary-item {
    display: flex;
    gap: 15px;
    margin-bottom: 15px;
    align-items: center;
}

.summary-item img {
    width: 50px;
    height: 50px;
    object-fit: cover;
    border-radius: 4px;
}

.summary-item-info {
    flex: 1;
}

.summary-item-info h4 {
    font-size: 0.9rem;
    font-weight: 500;
}

.summary-item-info p {
    font-size: 0.85rem;
    color: #888;
}

.summary-item-price {
    font-weight: 700;
}

.summary-total {
    border-top: 1px solid #eee;
    padding-top: 15px;
    margin-top: 15px;
}

.summary-total div {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
    font-size: 1rem;
}

.summary-total .total-final {
    font-size: 1.4rem;
    font-weight: 700;
}

.btn-checkout {
    display: block;
    width: 100%;
    padding: 15px;
    text-align: center;
    background-color: #b8860b;
    color: white;
    border: none;
    border-radius: 5px;
    font-weight: 700;
    font-size: 1.1rem;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.btn-checkout:hover {
    background-color: #a0740a;
}

.mensagem {
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 5px;
    font-weight: 500;
    border: 1px solid transparent;
}

.mensagem.erro {
    background-color: #f8d7da;
    color: #721c24;
    border-color: #f5c6cb;
}
    </style>
</head>
<body>
    
    <header id="main-header">
        </header>

    <main>
        <div class="checkout-container">
            <div class="checkout-form">
                <h2>Finalizar Pedido</h2>
                
                <?php if (isset($_SESSION['mensagem'])): ?>
                    <div class="mensagem <?php echo htmlspecialchars($_SESSION['mensagem']['tipo']); ?>">
                        <?php echo htmlspecialchars($_SESSION['mensagem']['texto']); ?>
                    </div>
                    <?php unset($_SESSION['mensagem']); ?>
                <?php endif; ?>

                <form action="processa_checkout.php" method="POST">
                    <fieldset>
                        <legend>1. Endereço de Entrega</legend>
                        <?php if (empty($enderecos)): ?>
                            <p>Você não tem endereços cadastrados. <a href="perfil.php" style="color: #b8860b;">Cadastrar agora</a></p>
                        <?php else: ?>
                            <?php foreach ($enderecos as $index => $endereco): ?>
                                <div class="address-option">
                                    <input type="radio" name="id_endereco" value="<?php echo $endereco['id_endereco']; ?>" id="end-<?php echo $endereco['id_endereco']; ?>" <?php echo $index == 0 ? 'checked' : ''; ?>>
                                    <label for="end-<?php echo $endereco['id_endereco']; ?>">
                                        <strong><?php echo htmlspecialchars($endereco['tipo_endereco']); ?></strong>
                                        <p><?php echo htmlspecialchars($endereco['rua']); ?>, <?php echo htmlspecialchars($endereco['numerocasa']); ?> - <?php echo htmlspecialchars($endereco['bairro']); ?>, <?php echo htmlspecialchars($endereco['cidade']); ?>/<?php echo htmlspecialchars($endereco['uf']); ?></p>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </fieldset>
                    
                    <fieldset>
                        <legend>2. Forma de Pagamento</legend>
                         <?php foreach ($formas_pagamento as $index => $pagamento): ?>
                            <div class="payment-option">
                                <input type="radio" name="id_forma_pagamento" value="<?php echo $pagamento['id_forma_pagamento']; ?>" id="pag-<?php echo $pagamento['id_forma_pagamento']; ?>" <?php echo $index == 0 ? 'checked' : ''; ?>>
                                <label for="pag-<?php echo $pagamento['id_forma_pagamento']; ?>">
                                    <?php echo htmlspecialchars($pagamento['nome_forma_pagamento']); ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </fieldset>
                    
                    <button type="submit" class="btn-checkout" <?php echo empty($enderecos) ? 'disabled' : ''; ?>>
                        Finalizar Compra
                    </button>
                    <?php if (empty($enderecos)): ?>
                        <p style="color: red; font-size: 0.9rem; margin-top: 10px;">É necessário cadastrar um endereço para continuar.</p>
                    <?php endif; ?>

                </form>
            </div>
            
            <aside class="checkout-summary">
                <h2>Resumo do Pedido</h2>
                <div class="summary-list">
                    <?php foreach ($itens_carrinho as $item): ?>
                    <div class="summary-item">
                        <img src="<?php echo $item['imagem']; ?>" alt="<?php echo htmlspecialchars($item['nome']); ?>">
                        <div class="summary-item-info">
                            <h4><?php echo htmlspecialchars($item['nome']); ?></h4>
                            <p>Qtd: <?php echo $item['quantidade']; ?></p>
                        </div>
                        <span class="summary-item-price">R$ <?php echo number_format($item['subtotal'], 2, ',', '.'); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="summary-total">
                    <div>
                        <span>Subtotal</span>
                        <span>R$ <?php echo number_format($total_carrinho, 2, ',', '.'); ?></span>
                    </div>
                    <div>
                        <span>Frete</span>
                        <span>Grátis</span>
                    </div>
                    <div class="total-final">
                        <span>Total</span>
                        <span>R$ <?php echo number_format($total_carrinho, 2, ',', '.'); ?></span>
                    </div>
                </div>
            </aside>
        </div>
    </main>

    <footer class="site-footer">
       </footer>

</body>
</html>