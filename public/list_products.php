<?php
// Importamos a conexão e as funções do JWT
require_once '../config/database.php';
require_once '../auth/jwt_helper.php';

// Configurações para retorno JSON
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// 1. Pegar todos os cabeçalhos (headers) da requisição
$headers = apache_request_headers();

// 2. Verificar se o cabeçalho 'Authorization' existe
if (isset($headers['Authorization'])) {
    
    // O formato comum é: "Bearer [TOKEN]", então vamos limpar a palavra "Bearer"
    $token = str_replace("Bearer ", "", $headers['Authorization']);

    // 3. VALIDAR O TOKEN usando a função que criamos no jwt_helper.php
    $dados_usuario = validar_jwt($token);

    if ($dados_usuario) {
        // SE O TOKEN FOR VÁLIDO: Realizamos a consulta no banco
        $database = new Database();
        $db = $database->getConnection();

        $query = "SELECT * FROM products";
        $stmt = $db->prepare($query);
        $stmt->execute();

        $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Retornamos os produtos e uma mensagem de boas-vindas personalizada com o nome do usuário que veio no Token
        http_response_code(200);
        echo json_encode([
            "usuario_logado" => $dados_usuario->name,
            "produtos" => $produtos
        ]);

    } else {
        // Token inválido ou expirado
        http_response_code(401);
        echo json_encode(["message" => "Acesso negado. Token inválido."]);
    }
} else {
    // Se o usuário nem enviou o cabeçalho de autorização
    http_response_code(400);
    echo json_encode(["message" => "Token de autorização não encontrado."]);
}
?>