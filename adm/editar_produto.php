<?php
// 1. Inicia sessão, conexão e segurança
require_once '../session_config.php';
require_once '../conexao.php'; // - [cite: admin_produtos.php] Caminho para conexão

// 2. Segurança do ADM (O "Bouncer")
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true || $_SESSION['usuario_tipo'] != 2) {
    $_SESSION['mensagem'] = ['tipo' => 'erro', 'texto' => 'Acesso restrito.'];
    header('Location: ../index.php');
    exit;
}

// 3. (NOVO) Processamento de Ação (UPDATE)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Pega todos os dados do formulário
    $id_produto = $_POST['id_produto'];
    $nome_produto = trim($_POST['nome_produto']);
    $id_categoria = trim($_POST['id_categoria']);
    $preco = trim($_POST['preco']);
    $estoque = trim($_POST['estoque']);
    $imagem_antiga = $_POST['imagem_antiga']; // Pega o nome da imagem atual
    
    $imagem_nome_db = $imagem_antiga; // Assume que a imagem não vai mudar

    // Validação
    if (empty($nome_produto) || empty($id_categoria) || empty($preco) || !isset($estoque) || $estoque < 0) {
        $_SESSION['mensagem'] = ['tipo' => 'erro', 'texto' => 'Nome, Categoria, Preço e Estoque são obrigatórios.'];
    } else {

        // (NOVO) Lógica de Upload (SÓ SE UMA NOVA IMAGEM FOR ENVIADA)
        if (isset($_FILES['imagem_produto']) && $_FILES['imagem_produto']['error'] == UPLOAD_ERR_OK) {
            
            // --- Bloco de validação (copiado do admin_produtos.php) ---
            $imagem_temp = $_FILES['imagem_produto']['tmp_name'];
            $imagem_nome_original = $_FILES['imagem_produto']['name'];
            $imagem_tamanho = $_FILES['imagem_produto']['size'];
            $imagem_tipo = $_FILES['imagem_produto']['type'];

            $diretorio_upload = 'uploads/'; // [cite: admin_produtos.php] Caminho relativo dentro de /adm/

            $tipos_permitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $extensao = strtolower(pathinfo($imagem_nome_original, PATHINFO_EXTENSION));
            $mime_permitido = in_array($imagem_tipo, $tipos_permitidos);
            
            if (!$mime_permitido || !in_array($extensao, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                 $_SESSION['mensagem'] = ['tipo' => 'erro', 'texto' => 'Erro: Apenas arquivos JPG, PNG, GIF ou WEBP são permitidos.'];
                 header("Location: editar_produto.php?id=$id_produto"); exit;
            }
            $tamanho_maximo = 2 * 1024 * 1024; // 2MB
            if ($imagem_tamanho > $tamanho_maximo) {
                $_SESSION['mensagem'] = ['tipo' => 'erro', 'texto' => 'Erro: O arquivo da imagem excede o tamanho máximo de 2MB.'];
                header("Location: editar_produto.php?id=$id_produto"); exit;
            }
            // --- Fim da validação ---

            // Gera novo nome e move
            $imagem_nome_db = 'produto_' . uniqid() . '.' . $extensao;
            $caminho_destino = $diretorio_upload . $imagem_nome_db;

            if (move_uploaded_file($imagem_temp, $caminho_destino)) {
                // Sucesso! Agora, apaga a imagem antiga (se ela existir)
                if (!empty($imagem_antiga) && file_exists($diretorio_upload . $imagem_antiga)) {
                    unlink($diretorio_upload . $imagem_antiga);
                }
            } else {
                $_SESSION['mensagem'] = ['tipo' => 'erro', 'texto' => 'Erro ao salvar a nova imagem. O produto não foi atualizado.'];
                $imagem_nome_db = $imagem_antiga; // Reverte para o nome antigo se o upload falhar
                header("Location: editar_produto.php?id=$id_produto"); exit;
            }
        }
        
        // (NOVO) SQL de UPDATE
        $sql = "UPDATE produto SET 
                    nome_produto = :nome, 
                    id_categoria = :id_cat, 
                    preco = :preco, 
                    estoque = :estoque, 
                    imagem = :imagem 
                WHERE id_produto = :id";

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':nome' => $nome_produto,
                ':id_cat' => $id_categoria,
                ':preco' => $preco,
                ':estoque' => $estoque,
                ':imagem' => $imagem_nome_db, // Salva o nome da nova imagem (ou o nome da antiga)
                ':id' => $id_produto
            ]);
            $_SESSION['mensagem'] = ['tipo' => 'sucesso', 'texto' => 'Produto atualizado com sucesso!'];
        } catch (PDOException $e) {
            $_SESSION['mensagem'] = ['tipo' => 'erro', 'texto' => 'Erro ao atualizar o produto: ' . $e->getMessage()];
        }
    }
    
    header('Location: admin_produtos.php'); // Redireciona de volta para a lista
    exit;
}


// --- 4. (NOVO) Preparação para Exibição (GET Request) ---
// Se não for POST, é um GET (usuário clicou em "Editar")

$id_produto_editar = $_GET['id'] ?? 0;
if (!is_numeric($id_produto_editar) || $id_produto_editar <= 0) {
    $_SESSION['mensagem'] = ['tipo' => 'erro', 'texto' => 'ID de produto inválido.'];
    header('Location: admin_produtos.php');
    exit;
}

$produto = null;
$categorias = [];
try {
    // Query 1: Busca o produto específico
    $stmt_prod = $pdo->prepare("SELECT * FROM produto WHERE id_produto = ?");
    $stmt_prod->execute([$id_produto_editar]);
    $produto = $stmt_prod->fetch(PDO::FETCH_ASSOC);

    // Se o ID do produto não existir, "kita"
    if (!$produto) {
        $_SESSION['mensagem'] = ['tipo' => 'erro', 'texto' => 'Produto não encontrado.'];
        header('Location: admin_produtos.php');
        exit;
    }

    // Query 2: Busca todas as categorias para o <select>
    $stmt_cat = $pdo->query("SELECT id_categoria, nome_categoria FROM categoria ORDER BY nome_categoria");
    $categorias = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $db_error = "Erro ao carregar dados: " . $e->getMessage();
}

// Pega mensagem de erro (ex: falha no upload)
$mensagem = null;
if (isset($_SESSION['mensagem'])) {
    $mensagem = $_SESSION['mensagem'];
    unset($_SESSION['mensagem']);
}

?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Produto - Painel ADM</title>
    <!-- Links (iguais ao admin_produtos.php) -->
    <link rel="icon" href="../img/favicon.jpg" type="img/jpg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    
    <style>
        /* [CSS IDÊNTICO ao admin_produtos.php] */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Montserrat', sans-serif; background-color: #f4f7fa; color: #333; display: flex; min-height: 100vh; }
        a { text-decoration: none; color: inherit; }
        ul { list-style: none; }
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
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; }
        .form-group { display: flex; flex-direction: column; }
        .form-group label { font-weight: 500; margin-bottom: 5px; font-size: 0.9rem; }
        .form-group input, .form-group select { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-family: 'Montserrat', sans-serif; font-size: 1rem; background-color: #fdfdfd; }
        .btn-submit { background-color: #b8860b; color: white; padding: 12px 25px; font-weight: 700; border-radius: 5px; transition: background-color 0.3s ease; border: none; cursor: pointer; font-size: 1rem; }
        .btn-submit:hover { background-color: #d4af37; }
        .mensagem { padding: 15px; margin-bottom: 20px; border-radius: 5px; font-weight: 500; border: 1px solid transparent; }
        .mensagem.sucesso { background-color: #d4edda; color: #155724; border-color: #c3e6cb; }
        .mensagem.erro { background-color: #f8d7da; color: #721c24; border-color: #f5c6cb; }
        .custom-file-input { color: transparent; }
        .custom-file-input::-webkit-file-upload-button { visibility: hidden; }
        .custom-file-input::before { content: 'Escolher Nova Imagem'; color: #333; display: inline-block; background: #f0f0f0; border: 1px solid #ddd; border-radius: 5px; padding: 12px 20px; outline: none; white-space: nowrap; -webkit-user-select: none; cursor: pointer; font-weight: 500; font-size: 1rem; transition: background-color 0.3s ease; }
        .custom-file-input:hover::before { background-color: #e0e0e0; border-color: #ccc; }
        .custom-file-input:active::before { background-color: #d0d0d0; }
        .file-name-display { margin-top: 5px; font-size: 0.85rem; color: #555; display: block; min-height: 1.2em; }
        .current-image-preview { margin-top: 10px; }
        .current-image-preview img { max-width: 100px; max-height: 100px; border-radius: 4px; border: 1px solid #ddd; }
        .current-image-preview span { font-size: 0.9rem; color: #777; margin-left: 10px; }
    </style>
</head>

<body>
    <div class="admin-container">
        <!-- --- 1. Sidebar --- -->
        <aside class="admin-sidebar">
            <a href="../index.php" class="logo">IBISA</a>
            <ul class="admin-menu">
                <li><a href="painel_admin.php"><i class='bx bxs-dashboard'></i>Dashboard</a></li>
                <!-- (NOVO) Link "Produtos" agora está ativo -->
                <li><a href="admin_produtos.php" class="active"><i class='bx bxs-package'></i>Produtos</a></li>
                <li><a href="admin_estoque.php"><i class='bx bxs-bar-chart-alt-2'></i>Estoque</a></li>
                <li><a href="admin_categorias.php"><i class='bx bxs-category-alt'></i>Categorias</a></li>
                <li><a href="admin_pedidos.php"><i class='bx bxs-cart-alt'></i>Ver Pedidos</a></li>
                <li><a href="admin_usuarios.php"><i class='bx bxs-user-account'></i>Gerenciar Clientes</a></li>
                <li><a href="admin_mensagens.php"><i class='bx bxs-envelope'></i> Mensagens</a></li>
                <li class="menu-divider"></li>
                <li><a href="../index.php"><i class='bx bx-arrow-back'></i>Voltar ao Site</a></li>
                <li><a href="../logout.php"><i class='bx bxs-log-out'></i>Sair</a></li>
            </ul>
        </aside>

        <!-- --- 2. Conteúdo Principal --- -->
        <main class="admin-content">
            <div class="admin-header">
                <!-- (NOVO) Título da Página -->
                <h1>Editar Produto</h1>
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

            <!-- (NOVO) Card de Edição -->
            <div class="content-card">
                <?php if ($produto): // Só mostra o form se o produto foi encontrado ?>
                <h2>Editando: <?php echo htmlspecialchars($produto['nome_produto']); ?></h2>
                
                <form action="editar_produto.php" method="POST" class="form-grid" enctype="multipart/form-data">
                    
                    <!-- (NOVO) Inputs ocultos para enviar o ID e a imagem antiga -->
                    <input type="hidden" name="id_produto" value="<?php echo $produto['id_produto']; ?>">
                    <input type="hidden" name="imagem_antiga" value="<?php echo htmlspecialchars($produto['imagem']); ?>">
                    
                    <div class="form-group">
                        <label for="nome_produto">Nome do Produto</label>
                        <!-- (NOVO) value="" preenchido -->
                        <input type="text" id="nome_produto" name="nome_produto" 
                               value="<?php echo htmlspecialchars($produto['nome_produto']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="id_categoria">Categoria</label>
                        <select id="id_categoria" name="id_categoria" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($categorias as $categoria): ?>
                                <!-- (NOVO) Lógica do 'selected' -->
                                <option value="<?php echo $categoria['id_categoria']; ?>" 
                                    <?php echo ($categoria['id_categoria'] == $produto['id_categoria']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($categoria['nome_categoria']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="preco">Preço</label>
                        <!-- (NOVO) value="" preenchido -->
                        <input type="text" id="preco" name="preco" 
                               value="<?php echo htmlspecialchars($produto['preco']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="estoque">Estoque</label>
                        <!-- (NOVO) value="" preenchido -->
                        <input type="number" id="estoque" name="estoque" 
                               value="<?php echo htmlspecialchars($produto['estoque']); ?>" min="0" required>
                    </div>

                    <div class="form-group">
                        <label for="imagem_produto">Alterar Imagem (Opcional)</label>
                        <!-- (NOVO) O 'required' foi removido -->
                        <input type="file" id="imagem_produto" name="imagem_produto" class="custom-file-input" 
                               accept="image/jpeg, image/png, image/gif, image/webp">
                        <span id="file-name" class="file-name-display"></span>
                        
                        <!-- (NOVO) Preview da imagem atual -->
                        <div class="current-image-preview">
                            <?php if (!empty($produto['imagem'])): ?>
                                <img src="uploads/<?php echo htmlspecialchars($produto['imagem']); ?>" alt="Imagem Atual">
                                <span>Imagem Atual: <?php echo htmlspecialchars($produto['imagem']); ?></span>
                            <?php else: ?>
                                <span>Este produto não possui imagem.</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>&nbsp;</label> 
                        <button type="submit" class="btn-submit">Salvar Alterações</button>
                    </div>
                </form>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- (NOVO) JS para o nome do arquivo (igual ao admin_produtos.php) -->
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