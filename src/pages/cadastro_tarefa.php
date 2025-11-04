<?php
require_once '../includes/verificar_sessao.php';
require_once '../config/database.php';

$pageTitle = 'Cadastro de Tarefa';
$message = '';
$messageType = '';
$isEdit = false;
$tarefa = null;

$id_usuario_logado = $_SESSION['id_usuario'];

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $isEdit = true;
    $pageTitle = 'Editar Tarefa';
    
    try {
        $conn = getDBConnection();
        $stmt = $conn->prepare("SELECT * FROM tarefas WHERE id_tarefa = ? AND id_usuario = ?");
        $stmt->execute([$_GET['id'], $id_usuario_logado]);
        $tarefa = $stmt->fetch();
        
        if (!$tarefa) {
            header('Location: gerenciamento.php');
            exit;
        }
    } catch (PDOException $e) {
        $message = 'Erro ao carregar tarefa: ' . $e->getMessage();
        $messageType = 'erro';
    }
}

$usuarios = [];
try {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT id_usuario, nome, email FROM usuarios WHERE id_usuario = ? ORDER BY nome");
    $stmt->execute([$id_usuario_logado]);
    $usuarios = $stmt->fetchAll();
} catch (PDOException $e) {
    $message = 'Erro ao carregar usuários: ' . $e->getMessage();
    $messageType = 'erro';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_usuario = $id_usuario_logado;
    $descricao = trim($_POST['descricao'] ?? '');
    $setor = trim($_POST['setor'] ?? '');
    $prioridade = $_POST['prioridade'] ?? '';
    $status = $_POST['status'] ?? 'a_fazer';
    $id_tarefa = $_POST['id_tarefa'] ?? null;
    
    $errors = [];
    
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
                    SET descricao = ?, setor = ?, prioridade = ?, status = ?
                    WHERE id_tarefa = ? AND id_usuario = ?
                ");
                $stmt->execute([$descricao, $setor, $prioridade, $status, $id_tarefa, $id_usuario_logado]);
                
                $message = 'Tarefa atualizada com sucesso!';
                $messageType = 'sucesso';
                
                header("refresh:2;url=gerenciamento.php");
            } else {
                $stmt = $conn->prepare("
                    INSERT INTO tarefas (id_usuario, descricao, setor, prioridade, status) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$id_usuario, $descricao, $setor, $prioridade, $status]);
                
                $message = 'Cadastro concluído com sucesso!';
                $messageType = 'sucesso';
                
                $descricao = '';
                $setor = '';
                $prioridade = '';
                $status = 'a_fazer';
            }
        } catch (PDOException $e) {
            $message = 'Erro ao salvar tarefa: ' . $e->getMessage();
            $messageType = 'erro';
        }
    } else {
        $message = implode('<br>', $errors);
        $messageType = 'erro';
    }
}

include '../includes/header.php';
?>

<h1><?php echo $isEdit ? 'Editar Tarefa' : 'Cadastro de Tarefa'; ?></h1>

<?php if (!empty($message)): ?>
    <div class="alert <?php echo $messageType === 'sucesso' ? 'success' : ''; ?>">
        <?php echo $message; ?>
    </div>
<?php endif; ?>

<div class="form-box">
    <form method="POST" action="">
        <?php if ($isEdit && $tarefa): ?>
            <input type="hidden" name="id_tarefa" value="<?php echo $tarefa['id_tarefa']; ?>">
        <?php endif; ?>
        
        <div class="form-group">
            <label for="descricao">Descrição da Tarefa</label>
            <textarea 
                id="descricao" 
                name="descricao" 
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
        
        <div class="form-group">
            <label for="setor">Setor</label>
            <input 
                type="text" 
                id="setor" 
                name="setor" 
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
            <button type="button" class="button" onclick="buscarCEP()" style="margin-top: 10px;">
                Buscar por CEP
            </button>
        </div>
        
        <div class="form-group" id="campo-cep" style="display: none;">
            <label for="cep">CEP</label>
            <input 
                type="text" 
                id="cep" 
                name="cep" 
                placeholder="Digite o CEP (ex: 01310-100)"
                maxlength="9"
            >
            <button type="button" class="button" onclick="consultarCEP()">Consultar</button>
            <div id="resultado-cep"></div>
        </div>
        
        <div class="form-group">
            <label for="prioridade">Prioridade</label>
            <select id="prioridade" name="prioridade" required>
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
        <div class="form-group">
            <label for="status">Status</label>
            <select id="status" name="status" required>
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
        
        <div>
            <button type="submit" class="button">
                <?php echo $isEdit ? 'Atualizar Tarefa' : 'Cadastrar Tarefa'; ?>
            </button>
            <a href="gerenciamento.php" class="button">Voltar</a>
        </div>
    </form>
</div>

<script>
function buscarCEP() {
    const campo = document.getElementById('campo-cep');
    if (campo.style.display === 'none') {
        campo.style.display = 'block';
    } else {
        campo.style.display = 'none';
    }
}

function consultarCEP() {
    const cep = document.getElementById('cep').value.replace(/\D/g, '');
    const resultado = document.getElementById('resultado-cep');
    const campoSetor = document.getElementById('setor');
    
    if (cep.length !== 8) {
        resultado.innerHTML = '<p style="color: red;">CEP inválido! Digite 8 dígitos.</p>';
        return;
    }
    
    resultado.innerHTML = '<p>Consultando CEP...</p>';
    
    fetch(`https://viacep.com.br/ws/${cep}/json/`)
        .then(response => response.json())
        .then(data => {
            if (data.erro) {
                resultado.innerHTML = '<p style="color: red;">CEP não encontrado!</p>';
            } else {
                resultado.innerHTML = `
                    <p style="color: green; margin-top: 10px;">
                        <strong>Endereço encontrado:</strong><br>
                        ${data.logradouro}<br>
                        ${data.bairro} - ${data.localidade}/${data.uf}
                    </p>
                `;
                campoSetor.value = data.bairro || data.localidade;
            }
        })
        .catch(error => {
            resultado.innerHTML = '<p style="color: red;">Erro ao consultar CEP!</p>';
            console.error('Erro:', error);
        });
}
</script>

<?php
include '../includes/footer.php';
?>
