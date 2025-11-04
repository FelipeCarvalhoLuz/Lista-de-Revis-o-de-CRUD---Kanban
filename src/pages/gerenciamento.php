<?php
require_once '../includes/verificar_sessao.php';
require_once '../config/database.php';

$pageTitle = 'Gerenciamento de Tarefas';
$message = '';
$messageType = '';

$id_usuario_logado = $_SESSION['id_usuario'];

if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    try {
        $conn = getDBConnection();
        $stmt = $conn->prepare("DELETE FROM tarefas WHERE id_tarefa = ? AND id_usuario = ?");
        $stmt->execute([$_GET['id'], $id_usuario_logado]);
        
        $message = 'Tarefa excluída com sucesso!';
        $messageType = 'sucesso';
    } catch (PDOException $e) {
        $message = 'Erro ao excluir tarefa: ' . $e->getMessage();
        $messageType = 'erro';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    try {
        $conn = getDBConnection();
        $stmt = $conn->prepare("UPDATE tarefas SET status = ? WHERE id_tarefa = ? AND id_usuario = ?");
        $stmt->execute([$_POST['status'], $_POST['id_tarefa'], $id_usuario_logado]);
        
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Status atualizado com sucesso!']);
            exit;
        }
        
        $message = 'Status atualizado com sucesso!';
        $messageType = 'sucesso';
    } catch (PDOException $e) {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
        
        $message = 'Erro ao atualizar status: ' . $e->getMessage();
        $messageType = 'erro';
    }
}

$tarefas = [
    'a_fazer' => [],
    'fazendo' => [],
    'pronto' => []
];

try {
    $conn = getDBConnection();
    $stmt = $conn->prepare("
        SELECT 
            t.id_tarefa,
            t.descricao,
            t.setor,
            t.prioridade,
            t.status,
            t.data_cadastro,
            u.id_usuario,
            u.nome AS usuario_nome,
            u.email AS usuario_email
        FROM tarefas t
        INNER JOIN usuarios u ON t.id_usuario = u.id_usuario
        WHERE t.id_usuario = ?
        ORDER BY t.data_cadastro DESC
    ");
    $stmt->execute([$id_usuario_logado]);
    $result = $stmt->fetchAll();
    
    foreach ($result as $tarefa) {
        $tarefas[$tarefa['status']][] = $tarefa;
    }
} catch (PDOException $e) {
    $message = 'Erro ao carregar tarefas: ' . $e->getMessage();
    $messageType = 'erro';
}

function formatarData($data) {
    $date = new DateTime($data);
    return $date->format('d/m/Y H:i');
}

include '../includes/header.php';
?>

<h1>Minhas Tarefas</h1>

<div>
    <a href="cadastro_tarefa.php" class="button">Nova Tarefa</a>
    <a href="cadastro_usuario.php" class="button">Novo Usuário</a>
</div>

<?php if (!empty($message)): ?>
    <div class="alert <?php echo $messageType === 'sucesso' ? 'success' : ''; ?>">
        <?php echo $message; ?>
    </div>
<?php endif; ?>

<div class="stats">
    <div class="stat-box">
        <div class="stat-number"><?php echo count($tarefas['a_fazer']); ?></div>
        <div>A Fazer</div>
    </div>
    <div class="stat-box">
        <div class="stat-number"><?php echo count($tarefas['fazendo']); ?></div>
        <div>Fazendo</div>
    </div>
    <div class="stat-box">
        <div class="stat-number"><?php echo count($tarefas['pronto']); ?></div>
        <div>Pronto</div>
    </div>
    <div class="stat-box">
        <div class="stat-number"><?php echo count($tarefas['a_fazer']) + count($tarefas['fazendo']) + count($tarefas['pronto']); ?></div>
        <div>Total</div>
    </div>
</div>

<div class="kanban">
    <div class="column">
        <h2>A Fazer (<?php echo count($tarefas['a_fazer']); ?>)</h2>
        
        <?php if (empty($tarefas['a_fazer'])): ?>
            <div class="empty">Nenhuma tarefa</div>
        <?php else: ?>
            <?php foreach ($tarefas['a_fazer'] as $tarefa): ?>
                <div class="task-card">
                    <div class="priority <?php echo $tarefa['prioridade']; ?>">
                        <?php echo ucfirst($tarefa['prioridade']); ?>
                    </div>
                    
                    <div class="task-description">
                        <?php echo nl2br(htmlspecialchars($tarefa['descricao'])); ?>
                    </div>
                    
                    <div class="task-info">
                        <div><strong>Responsável:</strong> <?php echo htmlspecialchars($tarefa['usuario_nome']); ?></div>
                        <div><strong>Setor:</strong> <?php echo htmlspecialchars($tarefa['setor']); ?></div>
                        <div><strong>Cadastro:</strong> <?php echo formatarData($tarefa['data_cadastro']); ?></div>
                    </div>
                    
                    <div class="task-buttons">
                        <form method="POST" style="margin: 0;">
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="id_tarefa" value="<?php echo $tarefa['id_tarefa']; ?>">
                            <select name="status" onchange="this.form.submit()">
                                <option value="a_fazer" selected>A Fazer</option>
                                <option value="fazendo">Fazendo</option>
                                <option value="pronto">Pronto</option>
                            </select>
                        </form>
                        <a href="cadastro_tarefa.php?id=<?php echo $tarefa['id_tarefa']; ?>" class="button">Editar</a>
                        <a href="?action=delete&id=<?php echo $tarefa['id_tarefa']; ?>" 
                           class="button" 
                           onclick="return confirm('Tem certeza que deseja excluir esta tarefa?')">
                            Excluir
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <div class="column">
        <h2>Fazendo (<?php echo count($tarefas['fazendo']); ?>)</h2>
        
        <?php if (empty($tarefas['fazendo'])): ?>
            <div class="empty">Nenhuma tarefa</div>
        <?php else: ?>
            <?php foreach ($tarefas['fazendo'] as $tarefa): ?>
                <div class="task-card">
                    <div class="priority <?php echo $tarefa['prioridade']; ?>">
                        <?php echo ucfirst($tarefa['prioridade']); ?>
                    </div>
                    
                    <div class="task-description">
                        <?php echo nl2br(htmlspecialchars($tarefa['descricao'])); ?>
                    </div>
                    
                    <div class="task-info">
                        <div><strong>Responsável:</strong> <?php echo htmlspecialchars($tarefa['usuario_nome']); ?></div>
                        <div><strong>Setor:</strong> <?php echo htmlspecialchars($tarefa['setor']); ?></div>
                        <div><strong>Cadastro:</strong> <?php echo formatarData($tarefa['data_cadastro']); ?></div>
                    </div>
                    
                    <div class="task-buttons">
                        <form method="POST" style="margin: 0;">
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="id_tarefa" value="<?php echo $tarefa['id_tarefa']; ?>">
                            <select name="status" onchange="this.form.submit()">
                                <option value="a_fazer">A Fazer</option>
                                <option value="fazendo" selected>Fazendo</option>
                                <option value="pronto">Pronto</option>
                            </select>
                        </form>
                        <a href="cadastro_tarefa.php?id=<?php echo $tarefa['id_tarefa']; ?>" class="button">Editar</a>
                        <a href="?action=delete&id=<?php echo $tarefa['id_tarefa']; ?>" 
                           class="button" 
                           onclick="return confirm('Tem certeza que deseja excluir esta tarefa?')">
                            Excluir
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <div class="column">
        <h2>Pronto (<?php echo count($tarefas['pronto']); ?>)</h2>
        
        <?php if (empty($tarefas['pronto'])): ?>
            <div class="empty">Nenhuma tarefa</div>
        <?php else: ?>
            <?php foreach ($tarefas['pronto'] as $tarefa): ?>
                <div class="task-card">
                    <div class="priority <?php echo $tarefa['prioridade']; ?>">
                        <?php echo ucfirst($tarefa['prioridade']); ?>
                    </div>
                    
                    <div class="task-description">
                        <?php echo nl2br(htmlspecialchars($tarefa['descricao'])); ?>
                    </div>
                    
                    <div class="task-info">
                        <div><strong>Responsável:</strong> <?php echo htmlspecialchars($tarefa['usuario_nome']); ?></div>
                        <div><strong>Setor:</strong> <?php echo htmlspecialchars($tarefa['setor']); ?></div>
                        <div><strong>Cadastro:</strong> <?php echo formatarData($tarefa['data_cadastro']); ?></div>
                    </div>
                    
                    <div class="task-buttons">
                        <form method="POST" style="margin: 0;">
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="id_tarefa" value="<?php echo $tarefa['id_tarefa']; ?>">
                            <select name="status" onchange="this.form.submit()">
                                <option value="a_fazer">A Fazer</option>
                                <option value="fazendo">Fazendo</option>
                                <option value="pronto" selected>Pronto</option>
                            </select>
                        </form>
                        <a href="cadastro_tarefa.php?id=<?php echo $tarefa['id_tarefa']; ?>" class="button">Editar</a>
                        <a href="?action=delete&id=<?php echo $tarefa['id_tarefa']; ?>" 
                           class="button" 
                           onclick="return confirm('Tem certeza que deseja excluir esta tarefa?')">
                            Excluir
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
function atualizarStatus(event, form) {
    return true;
}
</script>

<?php
include '../includes/footer.php';
?>
