<?php
session_start();
require_once 'conexao.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $nome_usuario = trim($_POST['nome_usuario']);
    $email = trim($_POST['email']);
    $senha = trim($_POST['senha']);
    $telefone = preg_replace('/\D/', '', $_POST['telefone']);
    $cpf = preg_replace('/\D/', '', $_POST['cpf']);

    $tipo_usuario = 1;

    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

    $sql = "INSERT INTO usuarios (tipo, nome_usuario, email, senha, telefone, cpf) 
            VALUES (:tipo, :nome, :email, :senha, :telefone, :cpf)";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':tipo', $tipo_usuario);
        $stmt->bindParam(':nome', $nome_usuario);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':senha', $senha_hash);
        $stmt->bindParam(':telefone', $telefone);
        $stmt->bindParam(':cpf', $cpf);
        $stmt->execute();

        $_SESSION['mensagem'] = [
            'tipo' => 'sucesso',
            'texto' => 'Cadastro realizado com sucesso! Faça seu login.'
        ];
        header('Location: conta.php');
        exit;

    } catch (PDOException $e) {

        $mensagem_erro = 'Ocorreu um erro ao cadastrar. Tente novamente.';

        if ($e->getCode() == 23000) {
            if (str_contains($e->getMessage(), 'email')) {
                $mensagem_erro = 'Este email já está cadastrado. Tente outro.';
            } elseif (str_contains($e->getMessage(), 'cpf')) {
                $mensagem_erro = 'Este CPF já está cadastrado.';
            } else {
                $mensagem_erro = 'Erro: Email ou CPF já cadastrado.';
            }
        }

        $_SESSION['mensagem'] = [
            'tipo' => 'erro',
            'texto' => $mensagem_erro
        ];
        header('Location: conta.php');
        exit;
    }

} else {
    header('Location: conta.php');
    exit;
}
?>