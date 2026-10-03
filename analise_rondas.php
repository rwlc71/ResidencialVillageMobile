<!-- Adicionar o CSS -->
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<link href="css/style.css" type="text/css" rel="stylesheet" />
<link rel="stylesheet" href="css/menu.css" type="text/css" />
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
<script type="text/javascript" src="js/ajax.js"></script>
<script type="text/javascript" src="js/scripts.js"></script>
<script type="text/javascript" src="js/componentes.js"></script>
<script type="text/javascript" src="jquery-autocomplete/lib/jquery.bgiframe.min.js"></script>
<script type="text/javascript" src="jquery-autocomplete/lib/jquery.ajaxQueue.js"></script>
<script type="text/javascript" src="jquery-autocomplete/lib/thickbox-compressed.js"></script>
<script type="text/javascript" src="jquery-autocomplete/jquery.autocomplete.js"></script>
<link rel="stylesheet" type="text/css" href="jquery-autocomplete/jquery.autocomplete.css"/>
<link rel="stylesheet" type="text/css" href="jquery-autocomplete/lib/thickbox.css?v=20260927f"/>

<?php
session_name('SESSAO_PHP');
include "topo.php";
include "conexao.php";
include "valida/verifica_acessoAdm.php";
include "valida/verifica_autenticacao.php";
include "funcoes_analise_rondas.php";

$colaborador = '';
if (isset($_REQUEST['colaborador'])) {
    $colaborador = trim($_REQUEST['colaborador']);
}
$dtEntrada = '';
if (isset($_REQUEST['dt_entrada'])) {
    $dtEntrada = trim($_REQUEST['dt_entrada']);
}
$dtInicio = '';
if (isset($_REQUEST['dt_inicio'])) {
    $dtInicio = trim($_REQUEST['dt_inicio']);
}
$dtFim = '';
if (isset($_REQUEST['dt_fim'])) {
    $dtFim = trim($_REQUEST['dt_fim']);
}
if ($dtEntrada !== '') {
    $dtInicio = $dtEntrada;
    $dtFim = $dtEntrada;
}

$ocorrencias = rv_analise_rondas_listar($colaborador, $dtEntrada, $dtInicio, $dtFim);

function rv_ar_h($texto) {
    return htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
}
?>
<div id="conteudo">
    <div id="cont">
        <h2>Análise de Rondas</h2>
        <p><font size="2">Este relatório aponta registros que necessitam de avaliação. O resultado não comprova irregularidade e deve ser interpretado pelo responsável.</font></p>
        <hr>
        <form method="get" action="analise_rondas.php">
            <table border="0">
                <tr>
                    <td align="left"><font size="2"><b>Colaborador:</b></font></td>
                    <td><input type="text" name="colaborador" value="<?= rv_ar_h($colaborador) ?>" size="40" /></td>
                </tr>
                <tr>
                    <td align="left"><font size="2"><b>Período:</b></font></td>
                    <td>
                        <input type="text" id="dt_inicio" name="dt_inicio" value="<?= rv_ar_h($dtInicio) ?>" size="10" maxlength="10" />
                        <font size="2"> até </font>
                        <input type="text" id="dt_fim" name="dt_fim" value="<?= rv_ar_h($dtFim) ?>" size="10" maxlength="10" />
                    </td>
                </tr>
            </table>
            <center><input type="submit" value="Pesquisar" /></center>
        </form>
        <script type="text/javascript">
            $(function () {
                $("#dt_inicio, #dt_fim").datepicker({
                    dateFormat: "dd/mm/yy",
                    changeMonth: true,
                    changeYear: true,
                    showButtonPanel: true
                });
            });
        </script>
        <hr>
        <div class="estiloTabelas table-responsive">
            <table border="2">
                <tr>
                    <td align="center" bgcolor="#191970"><font size="2" color="#F5FFFA"><b>Data/Hora</b></font></td>
                    <td align="center" bgcolor="#191970"><font size="2" color="#F5FFFA"><b>Colaborador</b></font></td>
                    <td align="center" bgcolor="#191970"><font size="2" color="#F5FFFA"><b>Ponto/QR Code</b></font></td>
                    <td align="center" bgcolor="#191970"><font size="2" color="#F5FFFA"><b>Ponto anterior</b></font></td>
                    <td align="center" bgcolor="#191970"><font size="2" color="#F5FFFA"><b>Distância entre registros</b></font></td>
                    <td align="center" bgcolor="#191970"><font size="2" color="#F5FFFA"><b>Intervalo de tempo</b></font></td>
                    <td align="center" bgcolor="#191970"><font size="2" color="#F5FFFA"><b>Motivo da avaliação</b></font></td>
                    <td align="center" bgcolor="#191970"><font size="2" color="#F5FFFA"><b>Trajeto</b></font></td>
                </tr>
                <?php if (count($ocorrencias) === 0) { ?>
                <tr>
                    <td colspan="8" align="center"><font size="2">Nenhuma ocorrência que necessite de avaliação foi identificada no período analisado.</font></td>
                </tr>
                <?php } else { ?>
                <?php foreach ($ocorrencias as $oc) { ?>
                <tr>
                    <td align="center"><font size="2"><?= rv_ar_h($oc['hora']) ?></font></td>
                    <td><font size="2"><?= rv_ar_h($oc['colaborador']) ?></font></td>
                    <td><font size="2"><?= rv_ar_h($oc['ponto']) ?></font></td>
                    <td><font size="2"><?= rv_ar_h($oc['ponto_anterior']) ?></font></td>
                    <td align="center"><font size="2"><?= rv_ar_h($oc['distancia']) ?></font></td>
                    <td align="center"><font size="2"><?= rv_ar_h($oc['intervalo']) ?></font></td>
                    <td><font size="2">Necessita de avaliação. <?= rv_ar_h($oc['motivo']) ?></font></td>
                    <td align="center" valign="middle" bgcolor="#FFFFFA"><font size="2"><?php
                        $origemOk = rv_analise_rondas_coord_ok($oc['latitude_anterior'], $oc['longitude_anterior']);
                        $destinoOk = rv_analise_rondas_coord_ok($oc['latitude'], $oc['longitude']);
                        if ($origemOk && $destinoOk) {
                            $maps = 'https://www.google.com/maps/dir/?api=1'
                                . '&origin=' . rawurlencode($oc['latitude_anterior'] . ',' . $oc['longitude_anterior'])
                                . '&destination=' . rawurlencode($oc['latitude'] . ',' . $oc['longitude'])
                                . '&travelmode=walking';
                            echo '<a href="' . rv_ar_h($maps) . '" target="_blank" title="Ver trajeto">';
                            echo '<img src="images/localizacao.png" title="Ver trajeto" height="20" width="20" align="middle" border="0">';
                            echo ' Ver trajeto</a>';
                        } else {
                            echo '—';
                        }
                    ?></font></td>
                </tr>
                <?php } ?>
                <?php } ?>
            </table>
        </div>
    </div>
</div>
<?php
include "rodape.php";
?>
