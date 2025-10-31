<?php
require_once '../config/database.php';

$pageTitle = 'Cadastro de Tarefa';
$message = '';
$messageType = '';
$isEdit = false;
$tarefa = null;

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $isEdit = true;
    $pageTitle = 'Editar Tarefa';
    
    try {
        $conn = getDBConnection();
        $stmt = $conn->prepare("SELECT * FROM tarefas WHERE id_tarefa = ?");
        $stmt->execute([$_GET['id']]);
        $tarefa = $stmt->fetch();
        
        if (!$tarefa) {
            header('Location: gerenciamento.php');
            exit;
        }
    } catch (PDOException $e) {
        $message = 'Erro ao carregar tarefa: ' . $e->getMessage();
        $messageType = 'error';
    }
}

$usuarios = [];
try {
    $conn = getDBConnection();
    $stmt = $conn->query("SELECT id_usuario, nome, email FROM usuarios ORDER BY nome");
    $usuarios = $stmt->fetchAll();
} catch (PDOException $e) {
    $message = 'Erro ao carregar usuários: ' . $e->getMessage();
    $messageType = 'error';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_usuario = $_POST['id_usuario'] ?? '';
    $descricao = trim($_POST['descricao'] ?? '');
    $setor = trim($_POST['setor'] ?? '');
    $prioridade = $_POST['prioridade'] ?? '';
    $status = $_POST['status'] ?? 'a_fazer';
    $id_tarefa = $_POST['id_tarefa'] ?? null;
    
    $errors = [];
    
    if (empty($id_usuario)) {
        $errors[] = 'Selecione um usuário responsável.';
    }
    
    if (empty($descricao)) {
        $errors[] = 'A descrição da tarefa é obrigatória.';
    }
    
    if (empty($setor)) {
        $errors[] = 'O setor é obrigatório.';
    }
    
    if (empty($prioridade)) {
        $errors[] = 'Selecione a prioridade da tarefa.';
    }
    
    if (empty($errors)) {
        try {
            $conn = getDBConnection();
            
            if ($id_tarefa) {
                $stmt = $conn->prepare("
                    UPDATE tarefas 
                    SET id_usuario = ?, descricao = ?, setor = ?, prioridade = ?, status = ?
                    WHERE id_tarefa = ?
                ");
                $stmt->execute([$id_usuario, $descricao, $setor, $prioridade, $status, $id_tarefa]);
                
                $message = 'Tarefa atualizada com sucesso!';
                $messageType = 'success';
                
                header("refresh:2;url=gerenciamento.php");
            } else {
                $stmt = $conn->prepare("
                    INSERT INTO tarefas (id_usuario, descricao, setor, prioridade, status) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$id_usuario, $descricao, $setor, $prioridade, $status]);
                
                $message = 'Cadastro concluído com sucesso!';
                $messageType = 'success';
                
                $id_usuario = '';
                $descricao = '';
                $setor = '';
                $prioridade = '';
                $status = 'a_fazer';
            }
        } catch (PDOException $e) {
            $message = 'Erro ao salvar tarefa: ' . $e->getMessage();
            $messageType = 'error';
        }
    } else {
        $message = implode('<br>', $errors);
        $messageType = 'error';
    }
}

include '../includes/header.php';
?>

<div class="recipiente">
    <h1><?php echo $isEdit ? 'Editar Tarefa' : 'Cadastro de Tarefa'; ?></h1>
    
    <?php if (empty($usuarios)): ?>
        <div class="alerta alerta-aviso">
            Atenção: Não há usuários cadastrados no sistema. 
            <a href="cadastro_usuario.php" class="link-alerta">
                Cadastre um usuário primeiro
            </a>.
        </div>
    <?php endif; ?>
    
    <?php if (!empty($message)): ?>
        <div class="alerta alerta-<?php echo $messageType === 'success' ? 'sucesso' : 'erro'; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>
    
    <div class="recipiente-formulario">
        <form method="POST" action="">
            <?php if ($isEdit && $tarefa): ?>
                <input type="hidden" name="id_tarefa" value="<?php echo $tarefa['id_tarefa']; ?>">
            <?php endif; ?>
            
            <div class="grupo-formulario">
                <label for="id_usuario">
                    Usuário Responsável
                    <span class="obrigatorio">*</span>
                </label>
                <select 
                    id="id_usuario" 
                    name="id_usuario" 
                    class="controle-formulario" 
                    required
                    <?php echo empty($usuarios) ? 'disabled' : ''; ?>
                >
                    <option value="">Selecione um usuário</option>
                    <?php foreach ($usuarios as $usuario): ?>
                        <option 
                            value="<?php echo $usuario['id_usuario']; ?>"
                            <?php 
                                if ($isEdit && $tarefa && $tarefa['id_usuario'] == $usuario['id_usuario']) {
                                    echo 'selected';
                                } elseif (isset($_POST['id_usuario']) && $_POST['id_usuario'] == $usuario['id_usuario']) {
                                    echo 'selected';
                                }
                            ?>
                        >
                            <?php echo htmlspecialchars($usuario['nome']); ?> 
                            (<?php echo htmlspecialchars($usuario['email']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="grupo-formulario">
                <label for="descricao">
                    Descrição da Tarefa
                    <span class="obrigatorio">*</span>
                </label>
                <textarea 
                    id="descricao" 
                    name="descricao" 
                    class="controle-formulario" 
                    placeholder="Descreva a tarefa detalhadamente"
                    rows="4"
                    required
                ><?php 
                    if ($isEdit && $tarefa) {
                        echo htmlspecialchars($tarefa['descricao']);
                    } elseif (isset($_POST['descricao'])) {
                        echo htmlspecialchars($_POST['descricao']);
                    }
                ?></textarea>
            </div>
            
            <div class="grupo-formulario">
                <label for="setor">
                    Setor
                    <span class="obrigatorio">*</span>
                </label>
                <input 
                    type="text" 
                    id="setor" 
                    name="setor" 
                    class="controle-formulario" 
                    placeholder="Ex: Produção, Qualidade, Financeiro"
                    value="<?php 
                        if ($isEdit && $tarefa) {
                            echo htmlspecialchars($tarefa['setor']);
                        } elseif (isset($_POST['setor'])) {
                            echo htmlspecialchars($_POST['setor']);
                        }
                    ?>"
                    required
                >
            </div>
            
            <div class="grupo-formulario">
                <label for="prioridade">
                    Prioridade
                    <span class="obrigatorio">*</span>
                </label>
                <select 
                    id="prioridade" 
                    name="prioridade" 
                    class="controle-formulario" 
                    required
                >
                    <option value="">Selecione a prioridade</option>
                    <option value="baixa" <?php 
                        if (($isEdit && $tarefa && $tarefa['prioridade'] == 'baixa') || 
                            (isset($_POST['prioridade']) && $_POST['prioridade'] == 'baixa')) {
                            echo 'selected';
                        }
                    ?>>Baixa</option>
                    <option value="media" <?php 
                        if (($isEdit && $tarefa && $tarefa['prioridade'] == 'media') || 
                            (isset($_POST['prioridade']) && $_POST['prioridade'] == 'media')) {
                            echo 'selected';
                        }
                    ?>>Média</option>
                    <option value="alta" <?php 
                        if (($isEdit && $tarefa && $tarefa['prioridade'] == 'alta') || 
                            (isset($_POST['prioridade']) && $_POST['prioridade'] == 'alta')) {
                            echo 'selected';
                        }
                    ?>>Alta</option>
                </select>
            </div>
            
            <?php if ($isEdit): ?>
            <div class="grupo-formulario">
                <label for="status">
                    Status
                    <span class="obrigatorio">*</span>
                </label>
                <select 
                    id="status" 
                    name="status" 
                    class="controle-formulario" 
                    required
                >
                    <option value="a_fazer" <?php echo ($tarefa && $tarefa['status'] == 'a_fazer') ? 'selected' : ''; ?>>
                        A Fazer
                    </option>
                    <option value="fazendo" <?php echo ($tarefa && $tarefa['status'] == 'fazendo') ? 'selected' : ''; ?>>
                        Fazendo
                    </option>
                    <option value="pronto" <?php echo ($tarefa && $tarefa['status'] == 'pronto') ? 'selected' : ''; ?>>
                        Pronto
                    </option>
                </select>
            </div>
            <?php else: ?>
                <input type="hidden" name="status" value="a_fazer">
            <?php endif; ?>
            
            <div class="grupo-botoes">
                <button 
                    type="submit" 
                    class="botao botao-primario"
                    <?php echo empty($usuarios) ? 'disabled' : ''; ?>
                >
                    <?php echo $isEdit ? 'Atualizar Tarefa' : 'Cadastrar Tarefa'; ?>
                </button>
                <a href="gerenciamento.php" class="botao botao-secundario">
                    Voltar
                </a>
            </div>
        </form>
    </div>
</div>

<?php
include '../includes/footer.php';
?>
