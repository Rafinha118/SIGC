<?php
/**
 * Cabeçalho Unificado - SIGC Lee Market
 * Responsivo (Mobile First), Tipografia Isometra e Paleta de Cores Oficial
 */

require_once __DIR__ . '/../config/auth.php';

$pageTitle = $pageTitle ?? 'SIGC — Lee Market';
$currentUser = usuarioAtual();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>

    <!-- Tipografia Obrigatória: Google Fonts Isometra -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Isometra:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Tailwind CSS CDN com Configuração da Paleta Lee Market -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'lee-primary': '#111184',
                        'lee-primary-dark': '#0c0c66',
                        'lee-primary-light': '#ededfc',
                        'lee-accent': '#FFD32C',
                        'lee-accent-dark': '#e6bd20',
                        'lee-accent-light': '#fff8db',
                        'lee-white': '#FFFFFF',
                        'lee-black': '#000000',
                    },
                    fontFamily: {
                        sans: ['Isometra', 'system-ui', 'sans-serif'],
                    }
                }
            }
        }
    </script>

    <!-- Estilos Customizados e Comportamentos -->
    <link rel="stylesheet" href="<?= baseUrl('assets/css/style.css') ?>">
</head>
<body class="bg-gray-50 text-gray-900 font-sans min-h-screen flex flex-col">
