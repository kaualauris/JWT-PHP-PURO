<?php
// Função simples para codificar em Base64 de forma segura para URLs
function base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function gerar_jwt($payload) {
    // Chave secreta (Mantenha isso seguro e complexo!)
    $key = "sua_chave_secreta_super_dificil";

    // 1. Header: Tipo de token e algoritmo usado (HS256)
    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);

    // Codificando Header e Payload para Base64
    $base64UrlHeader = base64url_encode($header);
    $base64UrlPayload = base64url_encode(json_encode($payload));

    // 2. Signature: Criando a assinatura usando HMAC SHA256
    $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $key, true);
    $base64UrlSignature = base64url_encode($signature);

    // 3. Resultado final: header.payload.signature
    return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
}


// Função para validar o JWT recebido
function validar_jwt($jwt) {
    $key = "sua_chave_secreta_super_dificil";

    // O JWT é dividido por pontos (.) em 3 partes
    $partes = explode('.', $jwt);
    if (count($partes) != 3) return false;

    $header = $partes[0];
    $payload = $partes[1];
    $signature_enviada = $partes[2];

    // Recalculamos a assinatura para ver se bate com a que foi enviada
    $validar_assinatura = base64url_encode(hash_hmac('sha256', $header . "." . $payload, $key, true));

    if ($signature_enviada === $validar_assinatura) {
        // Se a assinatura bater, decodificamos os dados (payload)
        $dados = json_decode(base64_decode($payload));
        
        // Verificamos se o token ainda está no prazo de validade (exp)
        if ($dados->exp > time()) {
            return $dados; // Token válido! Retorna os dados do usuário
        }
    }

    return false; // Token inválido ou expirado
}


?>