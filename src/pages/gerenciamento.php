<?php
require_once '../config/database.php';

$pageTitle = 'Gerenciamento de Tarefas';
$message = '';
$messageType = '';

if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    try {
        $conn = getDBConnection();
        $stmt = $conn->prepare("DELETE FROM tarefas WHERE id_tarefa = ?");
        $stmt->execute([$_GET['id']]);
        
        $message = 'Tarefa excluída com sucesso!';
        $messageType = 'success';
    } catch (PDOException $e) {
        $message = 'Erro ao excluir tarefa: ' . $e->getMessage();
        $messageType = 'error';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    try {
        $conn = getDBConnection();
        $stmt = $conn->prepare("UPDATE tarefas SET status = ? WHERE id_tarefa = ?");
        $stmt->execute([$_POST['status'], $_POST['id_tarefa']]);
        
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Status atualizado com sucesso!']);
            exit;
        }
        
        $message = 'Status atualizado com sucesso!';
        $messageType = 'success';
    } catch (PDOException $e) {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
        
        $message = 'Erro ao atualizar status: ' . $e->getMessage();
        $messageType = 'error';
    }
}

$tarefas = [
    'a_fazer' => [],
    'fazendo' => [],
    'pronto' => []
];

try {
    $conn = getDBConnection();
    $stmt = $conn->query("SELECT * FROM vw_tarefas_completas");
    $result = $stmt->fetchAll();
    
    foreach ($result as $tarefa) {
        $tarefas[$tarefa['status']][] = $tarefa;
    }
} catch (PDOException $e) {
    $message = 'Erro ao carregar tarefas: ' . $e->getMessage();
    $messageType = 'error';
}

function formatarData($data) {
    $date = new DateTime($data);
    return $date->format('d/m/Y H:i');
}

include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="header-section">
        <h1>Gerenciamento de Tarefas</h1>
        <div class="btn-group">
            <a href="cadastro_tarefa.php" class="btn btn-primary">
                Nova Tarefa
            </a>
            <a href="cadastro_usuario.php" class="btn btn-success">
                Novo Usuário
            </a>
        </div>
    </div>
    
    <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $messageType === 'success' ? 'success' : 'error'; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>
    
    <div class="statistics">
        <div class="stat-card stat-afazer">
            <div class="stat-number"><?php echo count($tarefas['a_fazer']); ?></div>
            <div class="stat-label">A Fazer</div>
        </div>
        <div class="stat-card stat-fazendo">
            <div class="stat-number"><?php echo count($tarefas['fazendo']); ?></div>
            <div class="stat-label">Fazendo</div>
        </div>
        <div class="stat-card stat-pronto">
            <div class="stat-number"><?php echo count($tarefas['pronto']); ?></div>
            <div class="stat-label">Pronto</div>
        </div>
        <div class="stat-card stat-total">
            <div class="stat-number"><?php echo count($tarefas['a_fazer']) + count($tarefas['fazendo']) + count($tarefas['pronto']); ?></div>
            <div class="stat-label">Total</div>
        </div>
    </div>
    
    <div class="kanban-board">
        <div class="kanban-column afazer">
            <div class="kanban-column-header">
                <div class="kanban-column-title">
                    A Fazer
                    <span class="kanban-column-count"><?php echo count($tarefas['a_fazer']); ?></span>
                </div>
            </div>
            
            <?php if (empty($tarefas['a_fazer'])): ?>
                <div class="empty-state">
                    <div class="empty-state-text">Nenhuma tarefa</div>
                </div>
            <?php else: ?>
                <?php foreach ($tarefas['a_fazer'] as $tarefa): ?>
                    <div class="task-card">
                        <div class="task-header">
                            <span class="task-priority <?php echo $tarefa['prioridade']; ?>">
                                <?php echo ucfirst($tarefa['prioridade']); ?>
                            </span>
                        </div>
                        
                        <div class="task-description">
                            <?php echo nl2br(htmlspecialchars($tarefa['descricao'])); ?>
                        </div>
                        
                        <div class="task-meta">
                            <div class="task-meta-item">
                                <strong>Responsável:</strong>
                                <?php echo htmlspecialchars($tarefa['usuario_nome']); ?>
                            </div>
                            <div class="task-meta-item">
                                <strong>Setor:</strong>
                                <?php echo htmlspecialchars($tarefa['setor']); ?>
                            </div>
                            <div class="task-meta-item">
                                <strong>Cadastro:</strong>
                                <?php echo formatarData($tarefa['data_cadastro']); ?>
                            </div>
                        </div>
                        
                        <div class="task-actions">
                            <form method="POST" class="task-form" onsubmit="return atualizarStatus(event, this)">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="id_tarefa" value="<?php echo $tarefa['id_tarefa']; ?>">
                                <select name="status" class="form-control task-status-select" onchange="this.form.submit()">
                                    <option value="a_fazer" selected>A Fazer</option>
                                    <option value="fazendo">Fazendo</option>
                                    <option value="pronto">Pronto</option>
                                </select>
                            </form>
                            <a href="cadastro_tarefa.php?id=<?php echo $tarefa['id_tarefa']; ?>" class="btn btn-warning btn-sm">
                                Editar
                            </a>
                            <a href="?action=delete&id=<?php echo $tarefa['id_tarefa']; ?>" 
                               class="btn btn-danger btn-sm" 
                               onclick="return confirm('Tem certeza que deseja excluir esta tarefa?')">
                                Excluir
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <div class="kanban-column fazendo">
            <div class="kanban-column-header">
                <div class="kanban-column-title">
                    Fazendo
                    <span class="kanban-column-count"><?php echo count($tarefas['fazendo']); ?></span>
                </div>
            </div>
            
            <?php if (empty($tarefas['fazendo'])): ?>
                <div class="empty-state">
                    <div class="empty-state-text">Nenhuma tarefa</div>
                </div>
            <?php else: ?>
                <?php foreach ($tarefas['fazendo'] as $tarefa): ?>
                    <div class="task-card">
                        <div class="task-header">
                            <span class="task-priority <?php echo $tarefa['prioridade']; ?>">
                                <?php echo ucfirst($tarefa['prioridade']); ?>
                            </span>
                        </div>
                        
                        <div class="task-description">
                            <?php echo nl2br(htmlspecialchars($tarefa['descricao'])); ?>
                        </div>
                        
                        <div class="task-meta">
                            <div class="task-meta-item">
                                <strong>Responsável:</strong>
                                <?php echo htmlspecialchars($tarefa['usuario_nome']); ?>
                            </div>
                            <div class="task-meta-item">
                                <strong>Setor:</strong>
                                <?php echo htmlspecialchars($tarefa['setor']); ?>
                            </div>
                            <div class="task-meta-item">
                                <strong>Cadastro:</strong>
                                <?php echo formatarData($tarefa['data_cadastro']); ?>
                            </div>
                        </div>
                        
                        <div class="task-actions">
                            <form method="POST" class="task-form" onsubmit="return atualizarStatus(event, this)">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="id_tarefa" value="<?php echo $tarefa['id_tarefa']; ?>">
                                <select name="status" class="form-control task-status-select" onchange="this.form.submit()">
                                    <option value="a_fazer">A Fazer</option>
                                    <option value="fazendo" selected>Fazendo</option>
                                    <option value="pronto">Pronto</option>
                                </select>
                            </form>
                            <a href="cadastro_tarefa.php?id=<?php echo $tarefa['id_tarefa']; ?>" class="btn btn-warning btn-sm">
                                Editar
                            </a>
                            <a href="?action=delete&id=<?php echo $tarefa['id_tarefa']; ?>" 
                               class="btn btn-danger btn-sm" 
                               onclick="return confirm('Tem certeza que deseja excluir esta tarefa?')">
                                Excluir
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <div class="kanban-column pronto">
            <div class="kanban-column-header">
                <div class="kanban-column-title">
                    Pronto
                    <span class="kanban-column-count"><?php echo count($tarefas['pronto']); ?></span>
                </div>
            </div>
            
            <?php if (empty($tarefas['pronto'])): ?>
                <div class="empty-state">
                    <div class="empty-state-text">Nenhuma tarefa</div>
                </div>
            <?php else: ?>
                <?php foreach ($tarefas['pronto'] as $tarefa): ?>
                    <div class="task-card">
                        <div class="task-header">
                            <span class="task-priority <?php echo $tarefa['prioridade']; ?>">
                                <?php echo ucfirst($tarefa['prioridade']); ?>
                            </span>
                        </div>
                        
                        <div class="task-description">
                            <?php echo nl2br(htmlspecialchars($tarefa['descricao'])); ?>
                        </div>
                        
                        <div class="task-meta">
                            <div class="task-meta-item">
                                <strong>Responsável:</strong>
                                <?php echo htmlspecialchars($tarefa['usuario_nome']); ?>
                            </div>
                            <div class="task-meta-item">
                                <strong>Setor:</strong>
                                <?php echo htmlspecialchars($tarefa['setor']); ?>
                            </div>
                            <div class="task-meta-item">
                                <strong>Cadastro:</strong>
                                <?php echo formatarData($tarefa['data_cadastro']); ?>
                            </div>
                        </div>
                        
                        <div class="task-actions">
                            <form method="POST" class="task-form" onsubmit="return atualizarStatus(event, this)">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="id_tarefa" value="<?php echo $tarefa['id_tarefa']; ?>">
                                <select name="status" class="form-control task-status-select" onchange="this.form.submit()">
                                    <option value="a_fazer">A Fazer</option>
                                    <option value="fazendo">Fazendo</option>
                                    <option value="pronto" selected>Pronto</option>
                                </select>
                            </form>
                            <a href="cadastro_tarefa.php?id=<?php echo $tarefa['id_tarefa']; ?>" class="btn btn-warning btn-sm">
                                Editar
                            </a>
                            <a href="?action=delete&id=<?php echo $tarefa['id_tarefa']; ?>" 
                               class="btn btn-danger btn-sm" 
                               onclick="return confirm('Tem certeza que deseja excluir esta tarefa?')">
                                Excluir
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
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
