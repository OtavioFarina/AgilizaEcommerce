<?php
// 1. Inicia a sessão com persistência
require_once 'session_config.php';
require_once 'conexao.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $senha_digitada = $_POST['senha'];

    // (MODIFICADO) Busca também a nova coluna 'conta_status'
    $sql = "SELECT * FROM usuarios WHERE email = :email"; // [cite: ibisa.sql]

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        // 1ª Verificação: O usuário existe E a senha está correta?
        if ($usuario && password_verify($senha_digitada, $usuario['senha'])) {

            // --- (NOVO) 2ª Verificação: A conta está ATIVA? ---
            if ($usuario['conta_status'] == 'Bloqueada') {
                // Usuário correto, senha correta, MAS BLOQUEADO
                $_SESSION['mensagem'] = [
                    'tipo' => 'erro',
                    'texto' => 'Sua conta foi bloqueada. Entre em contato com o suporte.'
                ];
                header('Location: conta.php');
                exit;
            }
            // --- Fim da 2ª Verificação ---

            // Se passou por tudo, está Aprovado!
            $_SESSION['usuario_logado'] = true;
            $_SESSION['usuario_id'] = $usuario['id_usuario'];
            $_SESSION['usuario_nome'] = $usuario['nome_usuario'];
            $_SESSION['usuario_tipo'] = $usuario['tipo'];
            $_SESSION['conta_status'] = $usuario['conta_status']; // Salva o status na sessão
            $_SESSION['usuario_email'] = $usuario['email'];

            // Redireciona com base no TIPO
            if ($_SESSION['usuario_tipo'] == 2) {
                header('Location: adm/painel_admin.php');
                exit;
            } else {
                header('Location: index.php');
                exit;
            }

        } else {
            // Email ou senha inválidos
            $_SESSION['mensagem'] = [
                'tipo' => 'erro',
                'texto' => 'Email ou senha inválidos. Tente novamente.'
            ];
            header('Location: conta.php');
            exit;
        }

    } catch (PDOException $e) {
        $_SESSION['mensagem'] = ['tipo' => 'erro', 'texto' => 'Ocorreu um erro no servidor. Tente mais tarde.'];
        header('Location: conta.php');
        exit;
    }

} else {
    header('Location: conta.php');
    exit;
}
?>