<?php
session_start();
require_once 'session_config.php';
require_once 'conexao.php';

if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
    header('Location: conta.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $id_usuario_logado = $_SESSION['usuario_id'];

    $nome_usuario = trim($_POST['nome_usuario']);
    $email = trim($_POST['email']);

    $telefone = preg_replace('/\D/', '', $_POST['telefone']);
    $cep = preg_replace('/\D/', '', $_POST['cep']);

    $rua = trim($_POST['rua']);
    $numerocasa = trim($_POST['numerocasa']);
    $bairro = trim($_POST['bairro']);
    $cidade = trim($_POST['cidade']);
    $uf = trim($_POST['uf']);

    $sql = "UPDATE usuarios SET 
                nome_usuario = :nome, 
                email = :email, 
                telefone = :telefone, 
                cep = :cep, 
                rua = :rua, 
                numerocasa = :num, 
                bairro = :bairro, 
                cidade = :cidade, 
                uf = :uf 
            WHERE 
                id_usuario = :id";

    try {
        $stmt = $pdo->prepare($sql);

        $stmt->bindParam(':nome', $nome_usuario);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':telefone', $telefone);
        $stmt->bindParam(':cep', $cep);
        $stmt->bindParam(':rua', $rua);
        $stmt->bindParam(':num', $numerocasa);
        $stmt->bindParam(':bairro', $bairro);
        $stmt->bindParam(':cidade', $cidade);
        $stmt->bindParam(':uf', $uf);
        $stmt->bindParam(':id', $id_usuario_logado, PDO::PARAM_INT);

        $stmt->execute();

        $_SESSION['usuario_nome'] = $nome_usuario;

        $_SESSION['mensagem'] = [
            'tipo' => 'sucesso',
            'texto' => 'Perfil atualizado com sucesso!'
        ];
        header('Location: perfil.php');
        exit;

    } catch (PDOException $e) {
        $mensagem_erro = 'Ocorreu um erro ao atualizar seu perfil.';

        if ($e->getCode() == 23000) {
            $mensagem_erro = 'Este email já está em uso por outra conta. Tente outro.';
        }

        $_SESSION['mensagem'] = [
            'tipo' => 'erro',
            'texto' => $mensagem_erro
        ];
        header('Location: perfil.php');
        exit;
    }

} else {
    header('Location: perfil.php');
    exit;
}
?>