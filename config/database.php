<?php
class Database {
    // Credenciais do banco de dados
    private $host = "localhost";
    private $db_name = "api_php_jwt";
    private $username = "root";
    private $password = "";
    public $conn;

    // Método para obter a conexão
    public function getConnection() {
        $this->conn = null;

        try {
            // Criando a conexão usando a classe PDO
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            // Configurando para lançar exceções em caso de erro
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            // Define o charset para evitar problemas com acentuação
            $this->conn->exec("set names utf8");
        } catch(PDOException $exception) {
            // Se der erro, exibe a mensagem
            echo "Erro de conexão: " . $exception->getMessage();
        }

        return $this->conn;
    }
}
?>