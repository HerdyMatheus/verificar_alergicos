<?php

header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set('America/Sao_Paulo');

// RECEBE O GTIN
$input = json_decode(file_get_contents('php://input'), true);
$gtin = $input['gtin'] ?? '';

// VALIDAÇÃO BÁSICA
if (!preg_match('/^\d{8,14}$/', $gtin)) {
    echo json_encode(['erro' => '❌ Código de barras inválido.']);
    exit;
}

// 1ª TENTATIVA: OpenFoodFacts BR
$openfood_br = "https://br.openfoodfacts.org/api/v0/product/$gtin.json";
$dados_br = @file_get_contents($openfood_br);
if ($dados_br) {
    $json_br = json_decode($dados_br, true);
    if (isset($json_br['product']['product_name']) && $json_br['status'] === 1) {
        echo json_encode(["nome" => trim($json_br['product']['product_name']), "fonte" => "openfoodfacts-br"]);
        exit;
    }
}

// 2ª TENTATIVA: OpenFoodFacts WORLD
$openfood_world = "https://world.openfoodfacts.org/api/v0/product/$gtin.json";
$dados_world = @file_get_contents($openfood_world);
if ($dados_world) {
    $json_world = json_decode($dados_world, true);
    if (isset($json_world['product']['product_name']) && $json_world['status'] === 1) {
        echo json_encode(["nome" => trim($json_world['product']['product_name']), "fonte" => "openfoodfacts-world"]);
        exit;
    }
}

// SE NÃO ENCONTRAR EM NENHUM DOS DOIS
echo json_encode([
    "erro" => "❌ Código de barras não encontrado. Este código não está presente em nossa base de dados.",
    "sugestao" => "💡 Tente buscar pelo nome do produto para obter melhores resultados."
]);
exit;
