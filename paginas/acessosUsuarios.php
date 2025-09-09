<?php
// Função para salvar os acessos do usuário
if (isset($_POST['enviarUpdate'])) {
    $userId = $_POST['user_id'];
    $modulos = isset($_POST['modulos']) ? json_encode($_POST['modulos']) : '[]';
    $acessos = isset($_POST['acessos']) ? json_encode($_POST['acessos']) : '[]';

    $query = $pdoM->prepare("UPDATE usuarios SET modulos = :modulos, acessos = :acessos WHERE id = :id");
    $query->execute(['modulos' => $modulos, 'acessos' => $acessos, 'id' => $userId]);

    echo "<div class='alert alert-success'>Permissões atualizadas com sucesso!</div>";
}

// Obter lista de usuários
$usuarios = $pdoM->query("SELECT id, nome, modulos, acessos FROM usuarios")->fetchAll(PDO::FETCH_ASSOC);

// Obter lista de módulos e acessos dinamicamente
$modulos = $pdoM->query("SELECT * FROM modulosacessos")->fetchAll(PDO::FETCH_ASSOC);
$acessosPorModulo = [];
foreach ($modulos as $modulo) {
    $acessos = $pdoM->prepare("SELECT * FROM acessos WHERE moduloid = :moduloid");
    $acessos->execute(['moduloid' => $modulo['id']]);
    $acessosPorModulo[$modulo['id']] = $acessos->fetchAll(PDO::FETCH_ASSOC);
}

// Função para verificar se o valor está marcado
function isChecked($value, $savedValues) {
    $savedArray = json_decode($savedValues, true);
    return is_array($savedArray) && in_array($value, $savedArray) ? 'checked' : '';
}
?>

<div class="container">
    <h2 class="text-center">Gerenciar Acessos dos Usuários</h2>
    <form method="POST">
        <div class="form-group">
            <label for="user_id">Selecione o Usuário:</label>
            <select name="user_id" id="user_id" class="form-control" onchange="this.form.submit()">
            <option value="">Selecione...</option>
                <?php
                foreach ($usuarios as $usuario) {
                    $selected = isset($_POST['user_id']) && $_POST['user_id'] == $usuario['id'] ? 'selected' : '';
                    echo "<option value='{$usuario['id']}' {$selected}>{$usuario['nome']}</option>";
                }
                ?>
            </select>
        </div>

        <?php
        // Recuperar acessos salvos para o usuário selecionado
        $userModulos = '';
        $userAcessos = '';
        if (isset($_POST['user_id'])) {
            $userId = $_POST['user_id'];
            $user = $pdoM->prepare("SELECT modulos, acessos FROM usuarios WHERE id = :id");
            $user->execute(['id' => $userId]);
            $userData = $user->fetch(PDO::FETCH_ASSOC);
            $userModulos = $userData['modulos'] ?? '[]';
            $userAcessos = $userData['acessos'] ?? '[]';
        }
        ?>

        <h3>Módulos</h3>
        <div class="checkbox">
            <?php
            foreach ($modulos as $modulo) {
                $checkedModulo = isChecked($modulo['id'], $userModulos);
                echo "<div><label><input type='checkbox' name='modulos[]' value='{$modulo['id']}' {$checkedModulo}> {$modulo['nome']}</label></div>";
                if (isset($acessosPorModulo[$modulo['id']])) {
                    foreach ($acessosPorModulo[$modulo['id']] as $acesso) {
                        $checkedAcesso = isChecked($acesso['nome'], $userAcessos);
                        echo "<div style='margin-left: 20px;'><label><input type='checkbox' name='acessos[]' value='{$acesso['nome']}' {$checkedAcesso}> {$acesso['nome']}</label></div>";
                    }
                }
            }
            ?>
        </div>

        <button type="submit" name="enviarUpdate" class="btn btn-primary">Salvar</button>
    </form>
</div>