<?php
/**
 * Sistema Administrativo Multiempresa
 * Desenvolvido por: Rafael S Mendes
 * Contato: contato@seusite.com
 * © 2025 Rafael S Mendes - Todos os direitos reservados
 *
 * Descrição:
 * Este arquivo faz parte do sistema desenvolvido por Rafael S Mendes.
 * É proibida a cópia, redistribuição ou modificação sem autorização.
 *
 * Atualizado 07/09/2025
 * author: Rafael S. Mendes
 */
// ----------------------- CACHE DE PÁGINA -----------------------
$cache_time = 3600; // 1 hora
$cache_dir  = __DIR__ . '/cache/';
$cache_file = $cache_dir . basename($_SERVER['SCRIPT_FILENAME'], '.php') . '.html';

// Cria pasta cache se não existir
if (!file_exists($cache_dir)) mkdir($cache_dir, 0755, true);

// Serve cache se ainda for válido
if (file_exists($cache_file) && (time() - filemtime($cache_file) < $cache_time)) {
    readfile($cache_file);
    exit;
}

// Inicia output buffer para salvar cache depois
ob_start();
