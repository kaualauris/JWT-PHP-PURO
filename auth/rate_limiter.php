<?php
function verificar_limite($db, $identificador) {
    $limite = 10; // 10 tentativas
    $tempo_janela = 60; // em 60 segundos (1 minuto)
    $agora = time();
    $tempo_limite = $agora - $tempo_janela;

    // 1. Limpar registros antigos para não encher o banco (opcional, mas bom)
    $db->prepare("DELETE FROM api_logs WHERE request_time < :tempo")->execute([':tempo' => $tempo_limite]);

    // 2. Contar quantas requisições esse identificador fez no último minuto
    $stmt = $db->prepare("SELECT COUNT(*) FROM api_logs WHERE identifier = :id AND request_time > :tempo");
    $stmt->execute([':id' => $identificador, ':tempo' => $tempo_limite]);
    $total_requisicoes = $stmt->fetchColumn();

    if ($total_requisicoes >= $limite) {
        return false; // Bloqueado
    }

    // 3. Registrar a tentativa atual
    $ins = $db->prepare("INSERT INTO api_logs (identifier, request_time) VALUES (:id, :agora)");
    $ins->execute([':id' => $identificador, ':agora' => $agora]);

    return true; // Permitido
}
?>