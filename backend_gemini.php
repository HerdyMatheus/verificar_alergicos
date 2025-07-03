<?php
// backend_gemini.php

header("Content-Type: application/json");
ini_set('display_errors', 1);
error_reporting(E_ALL);

$entrada = json_decode(file_get_contents("php://input"), true);
$produto = isset($entrada['produto']) ? trim($entrada['produto']) : "";

if (!$produto) {
    echo json_encode(["resposta" => "❌ Nome do produto não informado."]);
    exit;
}

$chave_api = "CHAVE_GEMINI"; // Substitua pela sua chave Gemini
$url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=$chave_api";

$prompt = <<<EOT
Considere o produto "$produto".

Me diga, separadamente, se ele contém ou não os seguintes ingredientes ou alérgenos:
- Lactose
- Ovo
- Aveia
- Corantes
- Frutos do Mar
- Glúten

Responda com frases curtas, mas explicativas para cada um, por exemplo:
"Lactose: Sim, contém leite."
"Ovo: Não, não contém."
"Aveia: Pode conter, dependendo da marca."
... e assim por diante.

Exibia primeiramente, esse produto é alergico para: (Exibir se tiver algum), e abaixo contendo a explicação.
Se não houver informação, diga que não foi possível determinar.
Coloque um ícone na frente de cada alergenico  para que fique mais amigavel ao usuário.
Caso seja um produto industrializado exiba a tabela nutricional daquele produto de forma sucinta e pratica. 



EOT;

$dados = [
    "contents" => [
        [
            "parts" => [
                ["text" => $prompt]
            ]
        ]
    ]
];

$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS => json_encode($dados)
]);

$resposta = curl_exec($curl);
$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

if ($httpCode === 200) {
    $json = json_decode($resposta, true);
    $texto = $json['candidates'][0]['content']['parts'][0]['text'] ?? '';

    if ($texto) {
        echo json_encode(["resposta" => nl2br(trim($texto))]);
        exit;
    } else {
        echo json_encode(["resposta" => "❌ Resposta da IA vazia."]);
        exit;
    }
} else {
    echo json_encode([
        "resposta" => "❌ Erro inesperado na requisição Gemini.",
        "http_code" => $httpCode,
        "raw" => $resposta
    ]);
    exit;
}
