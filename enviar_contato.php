<?php
// Usamos session_config.php para garantir que a sessão seja iniciada corretamente
require_once 'session_config.php';
require_once 'conexao.php';

// 1. Verificar se o método é POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Redireciona se não for POST
    header('Location: index.php');
    exit;
}

// 2. Coletar e Validar os dados
// trim() remove espaços em branco no início e fim
$nome = trim($_POST['nome']);
$email = trim($_POST['email']);
$assunto = trim($_POST['assunto']);
$mensagem = trim($_POST['mensagem']);

// Validação básica
if (empty($nome) || empty($email) || empty($assunto) || empty($mensagem)) {
    $_SESSION['mensagem'] = ['tipo' => 'erro', 'texto' => 'Por favor, preencha todos os campos.'];
    header('Location: index.php');
    exit;
}

// Validação de e-mail
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['mensagem'] = ['tipo' => 'erro', 'texto' => 'Por favor, insira um e-mail válido.'];
    header('Location: index.php');
    exit;
}

// 3. Verificar se o usuário está logado
$id_usuario = NULL; // Padrão para visitante

if (isset($_SESSION['usuario_logado']) && $_SESSION['usuario_logado'] === true) {
    // Se logado, usa o ID da sessão
    // **IMPORTANTE**: Garanta que 'id_usuario' está salvo na sessão no login!
    if (isset($_SESSION['id_usuario'])) {
        $id_usuario = $_SESSION['id_usuario'];
    }
}

// 4. Preparar e Inserir no Banco de Dados (Segurança com Prepared Statements)
try {
    $sql = "INSERT INTO mensagens_contato (id_usuario, nome, email, assunto, mensagem) 
            VALUES (:id_usuario, :nome, :email, :assunto, :mensagem)";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ':id_usuario' => $id_usuario,
        ':nome' => $nome,
        ':email' => $email,
        ':assunto' => $assunto,
        ':mensagem' => $mensagem
    ]);

    // 5. Enviar feedback de sucesso
    $_SESSION['mensagem'] = ['tipo' => 'sucesso', 'texto' => 'Mensagem enviada com sucesso! Responderemos em breve.'];

} catch (PDOException $e) {
    // 6. Enviar feedback de erro
    // Em produção, você poderia logar $e->getMessage() em vez de mostrá-lo
    $_SESSION['mensagem'] = ['tipo' => 'erro', 'texto' => 'Erro ao enviar mensagem. Tente novamente.'];
}

// 7. Redirecionar de volta para a index
header('Location: index.php');
exit;
?>