<?php
session_start();
require_once '../conexao.php';

if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true || $_SESSION['usuario_tipo'] != 2) {
    $_SESSION['mensagem'] = ['tipo' => 'erro', 'texto' => 'Acesso restrito.'];
    header('Location: ../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $nome_produto = trim($_POST['nome_produto']);
    $id_categoria = trim($_POST['id_categoria']);
    $preco = trim($_POST['preco']);
    $estoque = trim($_POST['estoque']);
    $imagem_nome_db = null;

    if (empty($nome_produto) || empty($id_categoria) || empty($preco) || !isset($estoque) || $estoque < 0) {
        $_SESSION['mensagem'] = ['tipo' => 'erro', 'texto' => 'Nome, Categoria, Preço e Estoque são obrigatórios.'];
    } else {

        if (isset($_FILES['imagem_produto']) && $_FILES['imagem_produto']['error'] == UPLOAD_ERR_OK) {

            $imagem_temp = $_FILES['imagem_produto']['tmp_name'];
            $imagem_nome_original = $_FILES['imagem_produto']['name'];
            $imagem_tamanho = $_FILES['imagem_produto']['size'];
            $imagem_tipo = $_FILES['imagem_produto']['type'];

            $diretorio_upload = 'uploads/';

            $tipos_permitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $extensao = strtolower(pathinfo($imagem_nome_original, PATHINFO_EXTENSION));
            $mime_permitido = in_array($imagem_tipo, $tipos_permitidos);
            if (!$mime_permitido || !in_array($extensao, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                $_SESSION['mensagem'] = ['tipo' => 'erro', 'texto' => 'Erro: Apenas arquivos JPG, PNG, GIF ou WEBP são permitidos.'];
                header('Location: admin_produtos.php');
                exit;
            }

            $tamanho_maximo = 2 * 1024 * 1024;
            if ($imagem_tamanho > $tamanho_maximo) {
                $_SESSION['mensagem'] = ['tipo' => 'erro', 'texto' => 'Erro: O arquivo da imagem excede o tamanho máximo de 2MB.'];
                header('Location: admin_produtos.php');
                exit;
            }

            $imagem_nome_db = 'produto_' . uniqid() . '.' . $extensao;
            $caminho_destino = $diretorio_upload . $imagem_nome_db;

            if (move_uploaded_file($imagem_temp, $caminho_destino)) {
            } else {

                $error_details = error_get_last();
                $debug_message = 'Erro ao salvar a imagem no servidor. Detalhes: ';

                if (!is_dir($diretorio_upload)) {
                    $debug_message .= 'O diretório de upload NÃO EXISTE (' . realpath($diretorio_upload) . '). Crie a pasta! ';
                } elseif (!is_writable($diretorio_upload)) {
                    $debug_message .= 'O diretório de upload NÃO TEM PERMISSÃO DE ESCRITA (' . realpath($diretorio_upload) . '). Verifique as permissões da pasta. ';
                } else {
                    $debug_message .= 'O diretório existe e parece ter permissão de escrita. Verifique os logs de erro do PHP/Apache. ';
                }

                if ($error_details !== null && isset($error_details['message'])) {
                    $debug_message .= 'Último erro PHP registrado: ' . $error_details['message'];
                } else {
                    $debug_message .= 'Nenhum erro PHP específico registrado para move_uploaded_file.';
                }

                $_SESSION['mensagem'] = ['tipo' => 'erro', 'texto' => $debug_message];

                $imagem_nome_db = null;
                header('Location: admin_produtos.php');
                exit;
            }
        }

        $sql = "INSERT INTO produto (nome_produto, id_categoria, preco, estoque, imagem)
                 VALUES (:nome, :id_cat, :preco, :estoque, :imagem)";

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':nome' => $nome_produto,
                ':id_cat' => $id_categoria,
                ':preco' => $preco,
                ':estoque' => $estoque,
                ':imagem' => $imagem_nome_db
            ]);
            if (!isset($_SESSION['mensagem']) || (isset($_SESSION['mensagem']) && $_SESSION['mensagem']['tipo'] != 'erro')) {
                $_SESSION['mensagem'] = ['tipo' => 'sucesso', 'texto' => 'Produto adicionado com sucesso!'];
            }
        } catch (PDOException $e) {
            $_SESSION['mensagem'] = ['tipo' => 'erro', 'texto' => 'Erro ao adicionar produto no banco: ' . $e->getMessage()];
        }
    }
    header('Location: admin_produtos.php');
    exit;
}

if (isset($_GET['action']) && $_GET['action'] == 'delete') {
    $id_produto = $_GET['id'] ?? 0;
    if (is_numeric($id_produto) && $id_produto > 0) {
        $imagem_para_deletar = null;
        try {
            $stmt_img = $pdo->prepare("SELECT imagem FROM produto WHERE id_produto = :id");
            $stmt_img->execute([':id' => $id_produto]);
            $imagem_para_deletar = $stmt_img->fetchColumn();
        } catch (PDOException $e) {
        }

        $sql = "DELETE FROM produto WHERE id_produto = :id";
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':id' => $id_produto]);
            if ($imagem_para_deletar) {

                $caminho_arquivo = 'uploads/' . $imagem_para_deletar;

                if (file_exists($caminho_arquivo)) {
                    unlink($caminho_arquivo);
                }
            }
            $_SESSION['mensagem'] = ['tipo' => 'sucesso', 'texto' => 'Produto excluído com sucesso!'];
        } catch (PDOException $e) {
            if ($e->getCode() == '23000' || $e->getCode() == '1451') {
                $_SESSION['mensagem'] = ['tipo' => 'erro', 'texto' => 'Erro: Produto associado a vendas existentes.'];
            } else {
                $_SESSION['mensagem'] = ['tipo' => 'erro', 'texto' => 'Erro ao excluir produto: ' . $e->getMessage()];
            }
        }
    } else {
        $_SESSION['mensagem'] = ['tipo' => 'erro', 'texto' => 'ID de produto inválido.'];
    }
    header('Location: admin_produtos.php');
    exit;
}

$mensagem = null;
if (isset($_SESSION['mensagem'])) {
    $mensagem = $_SESSION['mensagem'];
    unset($_SESSION['mensagem']);
}

$categorias = [];
$produtos = [];
try {
    $stmt_cat = $pdo->query("SELECT id_categoria, nome_categoria FROM categoria ORDER BY nome_categoria");
    $categorias = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);

    $sql_prod = "SELECT p.id_produto, p.nome_produto, p.preco, p.estoque, p.imagem, c.nome_categoria
                 FROM produto p
                 LEFT JOIN categoria c ON p.id_categoria = c.id_categoria
                 ORDER BY p.nome_produto";
    $stmt_prod = $pdo->query($sql_prod);
    $produtos = $stmt_prod->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    if ($mensagem === null || $mensagem['tipo'] !== 'erro') {
        $mensagem = ['tipo' => 'erro', 'texto' => 'Erro ao carregar dados: ' . $e->getMessage()];
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Produtos - Painel ADM</title>
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

        img.product-thumb {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 4px;
            border: 1px solid #eee;
            vertical-align: middle;
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

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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
        }

        .btn-submit:hover {
            background-color: #d4af37;
        }

        .product-table th,
        .product-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #f0f0f0;
            vertical-align: middle;
        }

        .product-table th {
            font-weight: 700;
            background-color: #f9f9f9;
        }

        .product-table td {
            font-size: 0.95rem;
        }

        .actions-cell a {
            margin-right: 10px;
            font-size: 1.2rem;
            transition: color 0.3s ease;
        }

        .actions-cell a.edit-btn:hover {
            color: #007bff;
        }

        .actions-cell a.delete-btn:hover {
            color: #dc3545;
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

        .custom-file-input {
            color: transparent;
        }

        .custom-file-input::-webkit-file-upload-button {
            visibility: hidden;
        }

        .custom-file-input::before {
            content: 'Escolher Imagem';
            color: #333;
            display: inline-block;
            background: #f0f0f0;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 1px 20px;
            outline: none;
            white-space: nowrap;
            -webkit-user-select: none;
            cursor: pointer;
            font-weight: 500;
            font-size: 1rem;
            transition: background-color 0.3s ease;
        }

        .custom-file-input:hover::before {
            background-color: #e0e0e0;
            border-color: #ccc;
        }

        .custom-file-input:active::before {
            background-color: #d0d0d0;
        }

        .file-name-display {
            margin-top: 5px;
            font-size: 0.85rem;
            color: #555;
            display: block;
            min-height: 1.2em;
        }
    </style>
</head>

<body>
    <div class="admin-container">
        <aside class="admin-sidebar">
            <a href="../index.php" class="logo">IBISA</a>
            <ul class="admin-menu">
                <li><a href="painel_admin.php"><i class='bx bxs-dashboard'></i>Dashboard</a></li>
                <li><a href="admin_produtos.php" class="active"><i class='bx bxs-package'></i>Produtos</a></li>
                <li><a href="admin_estoque.php"><i class='bx bxs-bar-chart-alt-2'></i>Gerenciar Estoque</a></li>
                <li><a href="admin_categorias.php"><i class='bx bxs-category-alt'></i>Categorias</a></li>
                <li><a href="admin_pedidos.php"><i class='bx bxs-cart-alt'></i>Ver Pedidos</a></li>
                <li><a href="admin_usuarios.php"><i class='bx bxs-user-account'></i>Gerenciar Clientes</a></li>
                <li><a href="admin_mensagens.php"><i class='bx bxs-envelope'></i>Mensagens</a></li>
                <li class="menu-divider"></li>
                <li><a href="../index.php"><i class='bx bx-arrow-back'></i>Voltar ao Site</a></li>
                <li><a href="../logout.php"><i class='bx bxs-log-out'></i>Sair</a></li>
            </ul>
        </aside>

        <main class="admin-content">
            <div class="admin-header">
                <h1>Gerenciar Produtos</h1>
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
                <h2>Adicionar Novo Produto</h2>
                <form action="admin_produtos.php" method="POST" class="form-grid" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="nome_produto">Nome do Produto</label>
                        <input type="text" id="nome_produto" name="nome_produto" required>
                    </div>
                    <div class="form-group">
                        <label for="id_categoria">Categoria</label>
                        <select id="id_categoria" name="id_categoria" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($categorias as $categoria): ?>
                                <option value="<?php echo $categoria['id_categoria']; ?>">
                                    <?php echo htmlspecialchars($categoria['nome_categoria']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="preco">Preço</label>
                        <input type="text" id="preco" name="preco" required>
                    </div>
                    <div class="form-group">
                        <label for="estoque">Estoque Inicial</label>
                        <input type="number" id="estoque" name="estoque" value="0" min="0" required>
                    </div>
                    <div class="form-group">
                        <label for="imagem_produto">Imagem do Produto</label>
                        <input type="file" id="imagem_produto" name="imagem_produto" class="custom-file-input"
                            accept="image/jpeg, image/png, image/gif, image/webp">
                        <span id="file-name" class="file-name-display"></span>
                    </div>
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn-submit">Adicionar Produto</button>
                    </div>
                </form>
            </div>

            <div class="content-card">
                <h2>Produtos Cadastrados</h2>
                <?php if (empty($produtos)): ?>
                    <p>Nenhum produto cadastrado ainda.</p>
                <?php else: ?>
                    <table class="product-table">
                        <thead>
                            <tr>
                                <th></th>
                                <th>ID</th>
                                <th>Nome</th>
                                <th>Categoria</th>
                                <th>Preço</th>
                                <th>Estoque</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($produtos as $produto): ?>
                                <tr>
                                    <td>
                                        <?php if (!empty($produto['imagem'])): ?>

                                            <img src="uploads/<?php echo htmlspecialchars($produto['imagem']); ?>"
                                                alt="<?php echo htmlspecialchars($produto['nome_produto']); ?>"
                                                class="product-thumb">

                                        <?php else: ?>
                                            <img src="../img/placeholder.png" alt="Sem Imagem" class="product-thumb">
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($produto['id_produto']); ?></td>
                                    <td><strong><?php echo htmlspecialchars($produto['nome_produto']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($produto['nome_categoria'] ?? 'Sem Categoria'); ?></td>
                                    <td>R$ <?php echo number_format($produto['preco'], 2, ',', '.'); ?></td>
                                    <td><?php echo htmlspecialchars($produto['estoque']); ?></td>
                                    <td class="actions-cell">
                                        <a href="editar_produto.php?id=<?php echo $produto['id_produto']; ?>" class="edit-btn"
                                            title="Editar"><i class='bx bxs-edit'></i></a>
                                        <a href="admin_produtos.php?action=delete&id=<?php echo $produto['id_produto']; ?>"
                                            class="delete-btn" title="Excluir" onclick="return confirm('Tem certeza?');"><i
                                                class='bx bxs-trash'></i></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        const fileInput = document.getElementById('imagem_produto');
        const fileNameDisplay = document.getElementById('file-name');
        if (fileInput) {
            fileInput.addEventListener('change', function () {
                if (fileInput.files.length > 0) { fileNameDisplay.textContent = fileInput.files[0].name; }
                else { fileNameDisplay.textContent = ''; }
            });
        }
    </script>
</body>

</html>