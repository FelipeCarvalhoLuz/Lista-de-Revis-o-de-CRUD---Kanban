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
    $stmt = $conn->query("
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
        ORDER BY t.data_cadastro DESC
    ");
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

<div class="recipiente-fluido">
    <div class="secao-cabecalho">
        <h1>Gerenciamento de Tarefas</h1>
        <div class="grupo-botoes">
            <a href="cadastro_tarefa.php" class="botao botao-primario">
                Nova Tarefa
            </a>
            <a href="cadastro_usuario.php" class="botao botao-sucesso">
                Novo Usuário
            </a>
        </div>
    </div>
    
    <?php if (!empty($message)): ?>
        <div class="alerta alerta-<?php echo $messageType === 'success' ? 'sucesso' : 'erro'; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>
    
    <div class="estatisticas">
        <div class="cartao-estatistica estatistica-afazer">
            <div class="numero-estatistica"><?php echo count($tarefas['a_fazer']); ?></div>
            <div class="rotulo-estatistica">A Fazer</div>
        </div>
        <div class="cartao-estatistica estatistica-fazendo">
            <div class="numero-estatistica"><?php echo count($tarefas['fazendo']); ?></div>
            <div class="rotulo-estatistica">Fazendo</div>
        </div>
        <div class="cartao-estatistica estatistica-pronto">
            <div class="numero-estatistica"><?php echo count($tarefas['pronto']); ?></div>
            <div class="rotulo-estatistica">Pronto</div>
        </div>
        <div class="cartao-estatistica estatistica-total">
            <div class="numero-estatistica"><?php echo count($tarefas['a_fazer']) + count($tarefas['fazendo']) + count($tarefas['pronto']); ?></div>
            <div class="rotulo-estatistica">Total</div>
        </div>
    </div>
    
    <div class="quadro-kanban">
        <div class="coluna-kanban afazer">
            <div class="cabecalho-coluna-kanban">
                <div class="titulo-coluna-kanban">
                    A Fazer
                    <span class="contador-coluna-kanban"><?php echo count($tarefas['a_fazer']); ?></span>
                </div>
            </div>
            
            <?php if (empty($tarefas['a_fazer'])): ?>
                <div class="estado-vazio">
                    <div class="texto-estado-vazio">Nenhuma tarefa</div>
                </div>
            <?php else: ?>
                <?php foreach ($tarefas['a_fazer'] as $tarefa): ?>
                    <div class="cartao-tarefa">
                        <div class="cabecalho-tarefa">
                            <span class="prioridade-tarefa <?php echo $tarefa['prioridade']; ?>">
                                <?php echo ucfirst($tarefa['prioridade']); ?>
                            </span>
                        </div>
                        
                        <div class="descricao-tarefa">
                            <?php echo nl2br(htmlspecialchars($tarefa['descricao'])); ?>
                        </div>
                        
                        <div class="meta-tarefa">
                            <div class="item-meta-tarefa">
                                <strong>Responsável:</strong>
                                <?php echo htmlspecialchars($tarefa['usuario_nome']); ?>
                            </div>
                            <div class="item-meta-tarefa">
                                <strong>Setor:</strong>
                                <?php echo htmlspecialchars($tarefa['setor']); ?>
                            </div>
                            <div class="item-meta-tarefa">
                                <strong>Cadastro:</strong>
                                <?php echo formatarData($tarefa['data_cadastro']); ?>
                            </div>
                        </div>
                        
                        <div class="acoes-tarefa">
                            <form method="POST" class="formulario-tarefa" onsubmit="return atualizarStatus(event, this)">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="id_tarefa" value="<?php echo $tarefa['id_tarefa']; ?>">
                                <select name="status" class="controle-formulario selecao-status-tarefa" onchange="this.form.submit()">
                                    <option value="a_fazer" selected>A Fazer</option>
                                    <option value="fazendo">Fazendo</option>
                                    <option value="pronto">Pronto</option>
                                </select>
                            </form>
                            <a href="cadastro_tarefa.php?id=<?php echo $tarefa['id_tarefa']; ?>" class="botao botao-aviso botao-pequeno">
                                Editar
                            </a>
                            <a href="?action=delete&id=<?php echo $tarefa['id_tarefa']; ?>" 
                               class="botao botao-perigo botao-pequeno" 
                               onclick="return confirm('Tem certeza que deseja excluir esta tarefa?')">
                                Excluir
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <div class="coluna-kanban fazendo">
            <div class="cabecalho-coluna-kanban">
                <div class="titulo-coluna-kanban">
                    Fazendo
                    <span class="contador-coluna-kanban"><?php echo count($tarefas['fazendo']); ?></span>
                </div>
            </div>
            
            <?php if (empty($tarefas['fazendo'])): ?>
                <div class="estado-vazio">
                    <div class="texto-estado-vazio">Nenhuma tarefa</div>
                </div>
            <?php else: ?>
                <?php foreach ($tarefas['fazendo'] as $tarefa): ?>
                    <div class="cartao-tarefa">
                        <div class="cabecalho-tarefa">
                            <span class="prioridade-tarefa <?php echo $tarefa['prioridade']; ?>">
                                <?php echo ucfirst($tarefa['prioridade']); ?>
                            </span>
                        </div>
                        
                        <div class="descricao-tarefa">
                            <?php echo nl2br(htmlspecialchars($tarefa['descricao'])); ?>
                        </div>
                        
                        <div class="meta-tarefa">
                            <div class="item-meta-tarefa">
                                <strong>Responsável:</strong>
                                <?php echo htmlspecialchars($tarefa['usuario_nome']); ?>
                            </div>
                            <div class="item-meta-tarefa">
                                <strong>Setor:</strong>
                                <?php echo htmlspecialchars($tarefa['setor']); ?>
                            </div>
                            <div class="item-meta-tarefa">
                                <strong>Cadastro:</strong>
                                <?php echo formatarData($tarefa['data_cadastro']); ?>
                            </div>
                        </div>
                        
                        <div class="acoes-tarefa">
                            <form method="POST" class="formulario-tarefa" onsubmit="return atualizarStatus(event, this)">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="id_tarefa" value="<?php echo $tarefa['id_tarefa']; ?>">
                                <select name="status" class="controle-formulario selecao-status-tarefa" onchange="this.form.submit()">
                                    <option value="a_fazer">A Fazer</option>
                                    <option value="fazendo" selected>Fazendo</option>
                                    <option value="pronto">Pronto</option>
                                </select>
                            </form>
                            <a href="cadastro_tarefa.php?id=<?php echo $tarefa['id_tarefa']; ?>" class="botao botao-aviso botao-pequeno">
                                Editar
                            </a>
                            <a href="?action=delete&id=<?php echo $tarefa['id_tarefa']; ?>" 
                               class="botao botao-perigo botao-pequeno" 
                               onclick="return confirm('Tem certeza que deseja excluir esta tarefa?')">
                                Excluir
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <div class="coluna-kanban pronto">
            <div class="cabecalho-coluna-kanban">
                <div class="titulo-coluna-kanban">
                    Pronto
                    <span class="contador-coluna-kanban"><?php echo count($tarefas['pronto']); ?></span>
                </div>
            </div>
            
            <?php if (empty($tarefas['pronto'])): ?>
                <div class="estado-vazio">
                    <div class="texto-estado-vazio">Nenhuma tarefa</div>
                </div>
            <?php else: ?>
                <?php foreach ($tarefas['pronto'] as $tarefa): ?>
                    <div class="cartao-tarefa">
                        <div class="cabecalho-tarefa">
                            <span class="prioridade-tarefa <?php echo $tarefa['prioridade']; ?>">
                                <?php echo ucfirst($tarefa['prioridade']); ?>
                            </span>
                        </div>
                        
                        <div class="descricao-tarefa">
                            <?php echo nl2br(htmlspecialchars($tarefa['descricao'])); ?>
                        </div>
                        
                        <div class="meta-tarefa">
                            <div class="item-meta-tarefa">
                                <strong>Responsável:</strong>
                                <?php echo htmlspecialchars($tarefa['usuario_nome']); ?>
                            </div>
                            <div class="item-meta-tarefa">
                                <strong>Setor:</strong>
                                <?php echo htmlspecialchars($tarefa['setor']); ?>
                            </div>
                            <div class="item-meta-tarefa">
                                <strong>Cadastro:</strong>
                                <?php echo formatarData($tarefa['data_cadastro']); ?>
                            </div>
                        </div>
                        
                        <div class="acoes-tarefa">
                            <form method="POST" class="formulario-tarefa" onsubmit="return atualizarStatus(event, this)">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="id_tarefa" value="<?php echo $tarefa['id_tarefa']; ?>">
                                <select name="status" class="controle-formulario selecao-status-tarefa" onchange="this.form.submit()">
                                    <option value="a_fazer">A Fazer</option>
                                    <option value="fazendo">Fazendo</option>
                                    <option value="pronto" selected>Pronto</option>
                                </select>
                            </form>
                            <a href="cadastro_tarefa.php?id=<?php echo $tarefa['id_tarefa']; ?>" class="botao botao-aviso botao-pequeno">
                                Editar
                            </a>
                            <a href="?action=delete&id=<?php echo $tarefa['id_tarefa']; ?>" 
                               class="botao botao-perigo botao-pequeno" 
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
