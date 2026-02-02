<?php
/**
 * ARQUIVO DE ENTRADA ÚNICO (FRONT CONTROLLER)
 * Este arquivo processa todas as requisições da API.
 */

// 1. CONFIGURAÇÕES DE SEGURANÇA E LOGS
ini_set('display_errors', 0);           // Não exibe erros na tela (segurança)
ini_set('log_errors', 1);              // Ativa o registro de erros em arquivo
ini_set('error_log', __DIR__ . '/../logs/php_errors.log'); // Salva na pasta /logs

// 2. FUNÇÃO DE HIGIENIZAÇÃO (XSS/Injection)
function limpar($dado) {
    return $dado !== null ? htmlspecialchars(strip_tags(trim($dado))) : null;
}

// 3. INCLUDES NECESSÁRIOS
require_once '../config/database.php';
require_once '../auth/jwt_helper.php';
require_once '../auth/rate_limiter.php';

// 4. CABEÇALHOS PADRÃO
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// 5. TRATAMENTO DE ROTA (URL AMIGÁVEL)
// Pega a URL, limpa e descobre qual é o recurso (ex: produtos)
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$parts = explode('/', trim($uri, '/'));

// Se você usa localhost/minha-api/, o 'produtos' estará na posição 1. 
// Se for direto localhost/, estará na posição 0.
// Essa lógica abaixo tenta encontrar a palavra 'produtos' ou 'login' de forma flexível.
$resource = "";
if (in_array('produtos', $parts)) $resource = 'produtos';
if (in_array('login', $parts)) $resource = 'login';

$method = $_SERVER['REQUEST_METHOD'];
$ip_usuario = $_SERVER['REMOTE_ADDR'];

// 6. CONEXÃO COM O BANCO
$database = new Database();
$db = $database->getConnection();

// 7. SEGURANÇA: RATE LIMIT POR IP
if (!verificar_limite($db, $ip_usuario)) {
    http_response_code(429);
    echo json_encode(["error" => "Muitas requisições. Tente novamente em 1 minuto."]);
    exit;
}

// Se a URL contiver '/docs', não processa como API, deixa o Apache abrir o arquivo físico
if (strpos($uri, '/docs') !== false) {
    return false; 
}

// 8. ROTEADOR
switch ($resource) {
    case 'produtos':
        // --- PROTEÇÃO JWT ---
        $headers = apache_request_headers();
        $token = str_replace("Bearer ", "", $headers['Authorization'] ?? '');
        $user_data = validar_jwt($token);

        if (!$user_data) {
            http_response_code(401);
            echo json_encode(["error" => "Token inválido ou ausente."]);
            exit;
        }

        // --- MÉTODOS REST PARA PRODUTOS ---
        switch ($method) {
            case 'GET':
                $stmt = $db->query("SELECT * FROM products");
                echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
                break;

            case 'POST':
            // 1. Tenta pegar do JSON. Se estiver vazio, tenta pegar do $_POST (Formulário)
            $raw_data = json_decode(file_get_contents("php://input"));
            
            $name  = $raw_data->name ?? $_POST['name'] ?? null;
            $price = $raw_data->price ?? $_POST['price'] ?? null;
            $desc  = $raw_data->description ?? $_POST['description'] ?? '';

            if (!empty($name) && !empty($price)) {
                $sql = "INSERT INTO products (name, price, description) VALUES (:n, :p, :d)";
                $stmt = $db->prepare($sql);
                $stmt->execute([
                    ':n' => limpar($name),
                    ':p' => limpar($price),
                    ':d' => limpar($desc)
                ]);
                http_response_code(201);
                echo json_encode(["message" => "Produto criado (via " . ($_POST ? 'Form' : 'JSON') . ")!"]);
            } else {
                http_response_code(400);
                echo json_encode(["error" => "Nome e preço são obrigatórios."]);
            }
            break;

            case 'PUT':
                // 2. O PHP não preenche $_POST nativamente para o método PUT.
                // Para ler formulários via PUT, precisamos ler o 'input' e processar a string.
                $raw_data = json_decode(file_get_contents("php://input"), true);
                
                if (!$raw_data) {
                    // Se não for JSON, tenta processar como string de formulário (x-www-form-urlencoded)
                    parse_str(file_get_contents("php://input"), $form_data);
                    $raw_data = $form_data;
                }
                
                $id    = $raw_data['id'] ?? null;
                $name  = $raw_data['name'] ?? null;
                $price = $raw_data['price'] ?? null;
                $desc  = $raw_data['description'] ?? null;

                if (!empty($id)) {
                    $sql = "UPDATE products SET name = :n, price = :p, description = :d WHERE id = :id";
                    $stmt = $db->prepare($sql);
                    $stmt->execute([
                        ':n' => limpar($name),
                        ':p' => limpar($price),
                        ':d' => limpar($desc),
                        ':id' => limpar($id)
                    ]);
                    echo json_encode(["message" => "Produto atualizado com sucesso!"]);
                } else {
                    http_response_code(400);
                    echo json_encode(["error" => "ID é obrigatório para atualização."]);
                }
            break;

            case 'DELETE':
                $id = $_GET['id'] ?? null;
                if ($id) {
                    $stmt = $db->prepare("DELETE FROM products WHERE id = :id");
                    $stmt->execute([':id' => limpar($id)]);
                    echo json_encode(["message" => "Produto removido!"]);
                } else {
                    http_response_code(400);
                    echo json_encode(["error" => "ID necessário."]);
                }
                break;
        }
        break;

    case 'login':
        // Aqui você pode incluir o seu login.php ou colar a lógica aqui
        require_once 'login.php';
        break;

    default:
        http_response_code(404);
        echo json_encode(["error" => "Rota não encontrada", "debug_uri" => $uri]);
        break;
}