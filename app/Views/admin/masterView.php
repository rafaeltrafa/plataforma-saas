<?php

/**
 * Master View - Template Principal do Dashboard Administrativo
 * 
 * Este arquivo serve como layout base para todas as páginas do painel administrativo.
 * Inclui os componentes comuns (header, sidebar, topo, footer) e renderiza o conteúdo
 * específico de cada página através da seção 'content'.
 * 
 * Estrutura:
 * - Header: Meta tags, CSS, scripts base
 * - Sidebar: Menu lateral de navegação
 * - Topo: Barra superior com navegação/usuário  
 * - Content: Área dinâmica para conteúdo das páginas filhas
 * - Footer: Scripts finais e fechamento do HTML
 * 
 * Uso: As páginas filhas devem estender este template usando:
 * <?php echo $this->extend('admin/masterView') ?>
 * 
 * @author StudioAlef
 * @version 1.0
 * @since 2025
 */
?>

<?= $this->include('admin/partials/header') ?>
<?= $this->include('admin/partials/sidebar') ?>
<?= $this->include('admin/partials/topo') ?>
<?= $this->renderSection('content') ?>
<?= $this->include('admin/partials/footer') ?>