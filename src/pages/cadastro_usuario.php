<?php
session_start();

require_once '../config/database.php';

$pageTitle = 'Cadastro de Usuário';
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';
    
    $errors = [];
    
    if (empty($nome)) {
        $errors[] = 'O nome é obrigatório.';
    }
    
    if (empty($email)) {
        $errors[] = 'O e-mail é obrigatório.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Por favor, insira um e-mail válido.';
    }
    
    if (empty($senha)) {
        $errors[] = 'A senha é obrigatória.';
    } elseif (strlen($senha) < 6) {
        $errors[] = 'A senha deve ter no mínimo 6 caracteres.';
    }
    
    if ($senha !== $confirmar_senha) {
        $errors[] = 'As senhas não conferem.';
    }
    
    if (empty($errors)) {
        try {
            $conn = getDBConnection();
            
            $stmt = $conn->prepare("SELECT id_usuario FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->fetch()) {
                $message = 'Este e-mail já está cadastrado no sistema.';
                $messageType = 'erro';
            } else {
                $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
                
                $stmt = $conn->prepare("INSERT INTO usuarios (nome, email, senha) VALUES (?, ?, ?)");
                $stmt->execute([$nome, $email, $senha_hash]);
                
                $message = 'Cadastro concluído com sucesso! Faça login para acessar o sistema.';
                $messageType = 'sucesso';
                
                $nome = '';
                $email = '';
            }
        } catch (PDOException $e) {
            $message = 'Erro ao cadastrar usuário: ' . $e->getMessage();
            $messageType = 'erro';
        }
    } else {
        $message = implode('<br>', $errors);
        $messageType = 'erro';
    }
}

include '../includes/header.php';
?>

<h1>Cadastro de Usuário</h1>

<?php if (!empty($message)): ?>
    <div class="alert <?php echo $messageType === 'sucesso' ? 'success' : ''; ?>">
        <?php echo $message; ?>
    </div>
<?php endif; ?>

<div class="form-box">
    <form method="POST" action="">
        <div class="form-group">
            <label for="nome">Nome Completo</label>
            <input 
                type="text" 
                id="nome" 
                name="nome" 
                placeholder="Digite o nome completo"
                value="<?php echo htmlspecialchars($nome ?? ''); ?>"
                required
            >
        </div>
        
        <div class="form-group">
            <label for="email">E-mail</label>
            <input 
                type="email" 
                id="email" 
                name="email" 
                placeholder="Digite o e-mail"
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
                placeholder="Mínimo 6 caracteres"
                required
            >
        </div>
        
        <div class="form-group">
            <label for="confirmar_senha">Confirmar Senha</label>
            <input 
                type="password" 
                id="confirmar_senha" 
                name="confirmar_senha" 
                placeholder="Digite a senha novamente"
                required
            >
        </div>
        
        <div>
            <button type="submit" class="button">Cadastrar</button>
            <a href="login.php" class="button">Voltar</a>
        </div>
    </form>
</div>

<?php
include '../includes/footer.php';
?>
