<?php
// Importamos os arquivos necessários
require_once '../config/database.php';
require_once '../auth/jwt_helper.php';

// Configurações de cabeçalho para aceitar requisições JSON
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

// 1. Conectar ao banco
$database = new Database();
$db = $database->getConnection();

// 2. Receber os dados do login (email e senha)
$data = json_decode(file_get_contents("php://input"));

if (!empty($data->email) && !empty($data->password)) {
    
    // 3. Buscar o usuário no banco pelo email
    $query = "SELECT id, name, password FROM users WHERE email = :email LIMIT 0,1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':email', $data->email);
    $stmt->execute();
    
    // Verificamos se o usuário existe
    if ($stmt->rowCount() > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // 4. VERIFICAR A SENHA: O password_verify compara a senha digitada com o hash do banco
        if (password_verify($data->password, $row['password'])) {
            
            // Se a senha estiver correta, preparamos as informações do Token (Payload)
            $payload = [
                "uid" => $row['id'],          // ID do usuário
                "name" => $row['name'],        // Nome do usuário
                "exp" => time() + (60 * 60)    // O token expira em 1 hora (tempo atual + 3600 segundos)
            ];

            // 5. GERAR O TOKEN usando aquela função que criamos no jwt_helper.php
            $jwt = gerar_jwt($payload);

            // Retornamos o token para o usuário
            http_response_code(200);
            echo json_encode([
                "message" => "Login realizado com sucesso!",
                "token" => $jwt
            ]);
        } else {
            // Senha incorreta
            http_response_code(401);
            echo json_encode(["message" => "Senha inválida."]);
        }
    } else {
        // Email não encontrado
        http_response_code(404);
        echo json_encode(["message" => "Usuário não encontrado."]);
    }
} else {
    // Dados faltando no JSON
    http_response_code(400);
    echo json_encode(["message" => "Preencha e-mail e senha."]);
}
?>