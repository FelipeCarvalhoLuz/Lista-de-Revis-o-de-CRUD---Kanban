<?php
session_start();

if (isset($_SESSION['id_usuario'])) {
    header('Location: gerenciamento.php');
    exit;
}

require_once '../config/database.php';

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    
    if (empty($email) || empty($senha)) {
        $message = 'Por favor, preencha todos os campos.';
        $messageType = 'erro';
    } else {
        try {
            $conn = getDBConnection();
            $stmt = $conn->prepare("SELECT id_usuario, nome, senha FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            $usuario = $stmt->fetch();
            
            if ($usuario && password_verify($senha, $usuario['senha'])) {
                $_SESSION['id_usuario'] = $usuario['id_usuario'];
                $_SESSION['nome_usuario'] = $usuario['nome'];
                $_SESSION['email_usuario'] = $email;
                
                header('Location: gerenciamento.php');
                exit;
            } else {
                $message = 'E-mail ou senha incorretos.';
                $messageType = 'erro';
            }
        } catch (PDOException $e) {
            $message = 'Erro ao fazer login: ' . $e->getMessage();
            $messageType = 'erro';
        }
    }
}

$pageTitle = 'Login';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Sistema Kanban</title>
    <link rel="stylesheet" href="/atividades_felipe/LISTA_EXERCICIOS/Lista%20de%20Revis%C3%A3o%20de%20CRUD%20-%20Kanban/assets/css/style.css">
</head>
<body>
    <header>
        <div class="header-content">
            <div class="logo">Sistema Kanban</div>
        </div>
    </header>

    <div class="container">
        <h1>Login</h1>
        
        <?php if (!empty($message)): ?>
            <div class="alert <?php echo $messageType === 'sucesso' ? 'success' : ''; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <div class="form-box">
            <form method="POST" action="">
                <div class="form-group">
                    <label for="email">E-mail</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        placeholder="Digite seu e-mail"
                        value="<?php echo htmlspecialchars($email ?? ''); ?>"
                        required
                    >
                </div>
                
                <div class="form-group">
                    <label for="senha">Senha</label>
                    <input 
                        type="password" 
                        id="senha" 
                        name="senha" 
                        placeholder="Digite sua senha"
                        required
                    >
                </div>
                
                <div>
                    <button type="submit" class="button">Entrar</button>
                    <a href="cadastro_usuario.php" class="button">Criar Conta</a>
                </div>
            </form>
        </div>
    </div>
    
    <footer>
        <p>&copy; 2025 Sistema Kanban - Todos os direitos reservados</p>
    </footer>
</body>
</html>
