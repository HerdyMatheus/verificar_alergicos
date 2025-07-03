<?php
// CONFIGURAÇÕES
$apiKey = 'AIzaSyCaoNYBFqrmSpvscy-cfpuKKGv7sRlxAv0';
$cx = '04633fbd8fbaa4714';

header('Content-Type: application/json');

$entrada = json_decode(file_get_contents("php://input"), true);

if (!isset($entrada['produto']) || empty($entrada['produto'])) {
    echo json_encode(["erro" => "Nome do produto não informado."]);
    exit;
}

$produto = $entrada['produto'];

// Monta a URL de pesquisa
$query = urlencode($produto);
$url = "https://www.googleapis.com/customsearch/v1?q=$query&cx=$cx&key=$apiKey&searchType=image&imgSize=large&num=5";

// Faz a requisição
$response = file_get_contents($url);
$data = json_decode($response, true);

if (!isset($data['items']) || count($data['items']) === 0) {
    echo json_encode(["erro" => "Nenhuma imagem encontrada."]);
    exit;
}

// Tenta encontrar a melhor imagem que contenha nome do produto no título
foreach ($data['items'] as $item) {
    $title = strtolower($item['title']);
    if (strpos($title, strtolower($produto)) !== false || strpos($title, explode(" ", strtolower($produto))[0]) !== false) {
        echo json_encode(["imagem" => $item['link']]);
        exit;
    }
}

// Caso nenhuma imagem contenha o nome, retorna a primeira imagem mesmo assim
echo json_encode(["imagem" => $data['items'][0]['link']]);
exit;
