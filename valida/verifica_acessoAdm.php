<?php

$usuario = isset($_SESSION['usuario']) ? $_SESSION['usuario'] : '';

if ($usuario == '') {
    $usuario = isset($_COOKIE['usuario']) ? $_COOKIE['usuario'] : '';
}

$sql1 = mysql_query("SELECT * FROM usuarios WHERE usuario = '$usuario'");
$ln1 = mysql_fetch_array($sql1);

if (mysql_num_rows($sql1) == true) {

    $tipo_acesso = isset($ln1['tipo_acesso']) ? $ln1['tipo_acesso'] : '';
    $conselho = isset($ln1['conselho']) ? trim(strtolower($ln1['conselho'])) : '';

    /*
     * Têm acesso:
     * - Segurança (seg)
     * - Administração (adm)
     * - Supervisor (sup)
     * - Conselheiro: tipo_acesso = con E conselho = sim
     */
    $acesso_permitido = (
        $tipo_acesso == 'seg' ||
        $tipo_acesso == 'adm' ||
        $tipo_acesso == 'sup' ||
        ($tipo_acesso == 'con' && $conselho == 'sim')
    );

    if (!$acesso_permitido) {
        echo "<meta http-equiv='refresh' content='0; URL=autentica.php'>
              <script type=\"text/javascript\">
              alert(\"Acesso restrito à administração e aos conselheiros do Residencial Village!\");
              </script>";

        return die;
    }
}
?>