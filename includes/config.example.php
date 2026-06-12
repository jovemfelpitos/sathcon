<?php
$dbHost = "localhost";
$dbUsername = "usuario_do_banco";
$dbPassword = "senha_do_banco";
$dbName = "nome_do_banco";

$conexao = new mysqli($dbHost, $dbUsername, $dbPassword, $dbName);

if ($conexao->connect_error) {
    die("Erro de conexao: " . $conexao->connect_error);
}

?>

