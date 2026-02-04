<?php
session_start();
require_once 'conexao.php';

if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
    header('Location: conta.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $id_usuario_logado = $_SESSION['usuario_id'];

    $cep = preg_replace('/\D/', '', $_POST['cep']);
    $rua = trim($_POST['rua']);
    $numerocasa = trim($_POST['numerocasa']);
    $bairro = trim($_POST['bairro']);
    $cidade = trim($_POST['cidade']);
    $uf = trim($_POST['uf']);

    $tipo_endereco = trim($_POST['tipo_endereco'] ?? 'Casa');

    $sql = "INSERT INTO enderecos (id_usuario, tipo_endereco, cep, rua, numerocasa, bairro, cidade, uf) 
            VALUES (:id_usuario, :tipo, :cep, :rua, :num, :bairro, :cidade, :uf)";

    try {
        $stmt = $pdo->prepare($sql);

        $stmt->bindParam(':id_usuario', $id_usuario_logado);
        $stmt->bindParam(':tipo', $tipo_endereco);
        $stmt->bindParam(':cep', $cep);
        $stmt->bindParam(':rua', $rua);
        $stmt->bindParam(':num', $numerocasa);
        $stmt->bindParam(':bairro', $bairro);
        $stmt->bindParam(':cidade', $cidade);
        $stmt->bindParam(':uf', $uf);

        $stmt->execute();

        // 8. Deu certo!
        $_SESSION['mensagem'] = [
            'tipo' => 'sucesso',
            'texto' => 'Novo endereço salvo com sucesso!'
        ];
        header('Location: enderecos.php');
        exit;

    } catch (PDOException $e) {
        $_SESSION['mensagem'] = [
            'tipo' => 'erro',
            'texto' => 'Ocorreu um erro ao salvar o endereço. Tente novamente.'
        ];
        header('Location: enderecos.php');
        exit;
    }

} else {
    header('Location: enderecos.php');
    exit;
}
?>