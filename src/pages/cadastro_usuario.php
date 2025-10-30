<?php
require_once '../config/database.php';

$pageTitle = 'Cadastro de Usuário';
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    
    $errors = [];
    
    if (empty($nome)) {
        $errors[] = 'O nome é obrigatório.';
    }
    
    if (empty($email)) {
        $errors[] = 'O e-mail é obrigatório.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Por favor, insira um e-mail válido.';
    }
    
    if (empty($errors)) {
        try {
            $conn = getDBConnection();
            
            $stmt = $conn->prepare("SELECT id_usuario FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->fetch()) {
                $message = 'Este e-mail já está cadastrado no sistema.';
                $messageType = 'error';
            } else {
                $stmt = $conn->prepare("INSERT INTO usuarios (nome, email) VALUES (?, ?)");
                $stmt->execute([$nome, $email]);
                
                $message = 'Cadastro concluído com sucesso!';
                $messageType = 'success';
                
                $nome = '';
                $email = '';
            }
        } catch (PDOException $e) {
            $message = 'Erro ao cadastrar usuário: ' . $e->getMessage();
            $messageType = 'error';
        }
    } else {
        $message = implode('<br>', $errors);
        $messageType = 'error';
    }
}

include '../includes/header.php';
?>

<div class="container">
    <h1>Cadastro de Usuário</h1>
    
    <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $messageType === 'success' ? 'success' : 'error'; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>
    
    <div class="form-container">
        <form method="POST" action="">
            <div class="form-group">
                <label for="nome">
                    Nome Completo
                    <span class="required">*</span>
                </label>
                <input 
                    type="text" 
                    id="nome" 
                    name="nome" 
                    class="form-control" 
                    placeholder="Digite o nome completo"
                    value="<?php echo htmlspecialchars($nome ?? ''); ?>"
                    required
                >
            </div>
            
            <div class="form-group">
                <label for="email">
                    E-mail
                    <span class="required">*</span>
                </label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    class="form-control" 
                    placeholder="Digite o e-mail (ex: usuario@empresa.com)"
                    value="<?php echo htmlspecialchars($email ?? ''); ?>"
                    required
                >
                <small class="form-help">O e-mail deve ser único no sistema.</small>
            </div>
            
            <div class="btn-group">
                <button type="submit" class="btn btn-primary">
                    Cadastrar Usuário
                </button>
                <a href="gerenciamento.php" class="btn btn-secondary">
                    Voltar
                </a>
            </div>
        </form>
    </div>
</div>

<?php
include '../includes/footer.php';
?>
