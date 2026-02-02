<?php
// Importamos a conexão com o banco
require_once '../config/database.php';

// Permitir que qualquer um acesse essa URL (CORS) e definir que o retorno é JSON
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

// 1. Instanciar o banco de dados
$database = new Database();
$db = $database->getConnection();

// 2. Pegar os dados enviados no corpo da requisição (JSON)
$data = json_decode(file_get_contents("php://input"));

// 3. Verificar se os campos obrigatórios foram preenchidos
if(!empty($data->name) && !empty($data->email) && !empty($data->password)){
    
    // Preparar a query SQL para inserir o usuário
    $query = "INSERT INTO users (name, email, password) VALUES (:name, :email, :password)";
    $stmt = $db->prepare($query);

    // 4. Limpar os dados (higienização básica)
    $name = htmlspecialchars(strip_tags($data->name));
    $email = htmlspecialchars(strip_tags($data->email));
    
    // 5. CRIPTOGRAFAR A SENHA: O PHP faz isso de forma segura com password_hash
    $password_hashed = password_hash($data->password, PASSWORD_BCRYPT);

    // Vincular os valores aos parâmetros da query
    $stmt->bindParam(":name", $name);
    $stmt->bindParam(":email", $email);
    $stmt->bindParam(":password", $password_hashed);

    // 6. Executar e responder
    if($stmt->execute()){
        http_response_code(201); // Código 201: Criado com sucesso
        echo json_encode(["message" => "Usuário cadastrado com sucesso!"]);
    } else {
        http_response_code(503); // Erro de serviço
        echo json_encode(["message" => "Não foi possível cadastrar o usuário."]);
    }
} else {
    http_response_code(400); // Erro do cliente (dados incompletos)
    echo json_encode(["message" => "Dados incompletos."]);
}
?>