<?php
/*
 * Arquivo de Funções Úteis (utils) do Site
 */

/**
 * Cria um 'slug' (âncora) seguro para URLs ou IDs HTML a partir de um texto.
 * Ex: "Anéis & Brincos" vira "aneisebrincos"
 *
 * @param string $nome O texto de entrada.
 * @return string O texto limpo e seguro.
 */
function criarAncora($nome) {
    // 1. Converte para minúsculas e remove espaços no início/fim
    $nome = strtolower(trim($nome));
    
    // 2. Lista de acentos e seus equivalentes
    $acentos = array(
        'á','à','â','ã','ä','å',
        'é','è','ê','ë',
        'í','ì','î','ï',
        'ó','ò','ô','õ','ö',
        'ú','ù','û','ü',
        'ç','ñ'
    );
    
    $sem_acentos = array(
        'a','a','a','a','a','a',
        'e','e','e','e',
        'i','i','i','i',
        'o','o','o','o','o',
        'u','u','u','u',
        'c','n'
    );
    
    // 3. Substitui os acentos
    $nome = str_replace($acentos, $sem_acentos, $nome);
    
    // 4. Remove QUALQUER coisa que não seja letra (a-z) ou número (0-9)
    // Isso é ótimo para segurança, pois remove & , ' " / etc.
    $nome = preg_replace('/[^a-z0-9]+/', '', $nome); 
    
    return $nome;
}

// (No futuro, se tivermos mais funções, elas entram aqui)
?>