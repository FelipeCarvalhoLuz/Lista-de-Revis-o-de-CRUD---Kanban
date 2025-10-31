<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?>Sistema Kanban</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <header>
        <div class="conteudo-cabecalho">
            <div class="logotipo">Sistema Kanban</div>
            <nav class="barra-navegacao">
                <ul class="menu-navegacao">
                    <li><a href="../pages/gerenciamento.php" <?php echo (basename($_SERVER['PHP_SELF']) == 'gerenciamento.php') ? 'class="active"' : ''; ?>>Gerenciamento</a></li>
                    <li><a href="../pages/cadastro_usuario.php" <?php echo (basename($_SERVER['PHP_SELF']) == 'cadastro_usuario.php') ? 'class="active"' : ''; ?>>Cadastrar Usuário</a></li>
                    <li><a href="../pages/cadastro_tarefa.php" <?php echo (basename($_SERVER['PHP_SELF']) == 'cadastro_tarefa.php') ? 'class="active"' : ''; ?>>Cadastrar Tarefa</a></li>
                </ul>
            </nav>
        </div>
    </header>
