<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?>Sistema Kanban</title>
    <link rel="stylesheet" href="/atividades_felipe/LISTA_EXERCICIOS/Lista%20de%20Revis%C3%A3o%20de%20CRUD%20-%20Kanban/assets/css/style.css">
</head>
<body>
    <header>
        <div class="header-content">
            <div class="logo">Sistema Kanban</div>
            <nav>
                <ul class="menu">
                    <?php if (isset($_SESSION['id_usuario'])): ?>
                        <li><a href="../pages/gerenciamento.php">Gerenciamento</a></li>
                        <li><a href="../pages/cadastro_tarefa.php">Nova Tarefa</a></li>
                        <li><a href="../pages/logout.php">Sair (<?php echo htmlspecialchars($_SESSION['nome_usuario']); ?>)</a></li>
                    <?php else: ?>
                        <li><a href="../pages/login.php">Login</a></li>
                        <li><a href="../pages/cadastro_usuario.php">Cadastrar</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>
    
    <div class="container">
