<?php

function rv_analise_rondas_limites() {
    return array(
        'metros_coordenada_repetida' => 5,
        'metros_muito_proximas' => 20,
        'segundos_intervalo_reduzido' => 15,
        'metros_minimos_intervalo_reduzido' => 80,
        'segundos_sequencia' => 120,
        'min_pontos_sequencia' => 3,
        'segundos_nova_sequencia' => 2400
    );
}

function rv_analise_rondas_haversine($lat1, $lon1, $lat2, $lon2) {
    $raio = 6371000;
    $p1 = deg2rad((float) $lat1);
    $p2 = deg2rad((float) $lat2);
    $dp = deg2rad((float) $lat2 - (float) $lat1);
    $dl = deg2rad((float) $lon2 - (float) $lon1);
    $a = sin($dp / 2) * sin($dp / 2) + cos($p1) * cos($p2) * sin($dl / 2) * sin($dl / 2);
    $c = 2 * atan2(sqrt($a), sqrt(max(0, 1 - $a)));
    return $raio * $c;
}

function rv_analise_rondas_coord_ok($lat, $lng) {
    if ($lat === null || $lng === null || $lat === '' || $lng === '') {
        return false;
    }
    if (!is_numeric($lat) || !is_numeric($lng)) {
        return false;
    }
    $lat = (float) $lat;
    $lng = (float) $lng;
    if ($lat == 0 && $lng == 0) {
        return false;
    }
    if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
        return false;
    }
    return true;
}

function rv_analise_rondas_data_sql($valor) {
    $valor = trim((string) $valor);
    if ($valor === '') {
        return '';
    }
    $dt = DateTime::createFromFormat('d/m/Y', $valor);
    if (!$dt) {
        return '';
    }
    $erros = DateTime::getLastErrors();
    if ($erros && ($erros['warning_count'] > 0 || $erros['error_count'] > 0)) {
        return '';
    }
    return $dt->format('Y-m-d');
}

function rv_analise_rondas_tem($colaborador, $dtEntrada) {
    $lista = rv_analise_rondas_listar($colaborador, $dtEntrada, '', '');
    return count($lista) > 0;
}

function rv_analise_rondas_listar($colaborador, $dtEntrada, $dtInicio, $dtFim) {
    $colaborador = trim((string) $colaborador);
    $dia = rv_analise_rondas_data_sql($dtEntrada);
    $inicio = $dia !== '' ? $dia : rv_analise_rondas_data_sql($dtInicio);
    $fim = $dia !== '' ? $dia : rv_analise_rondas_data_sql($dtFim);

    $where = array();
    if ($colaborador !== '') {
        $where[] = "usuario LIKE '%" . mysql_real_escape_string($colaborador) . "%'";
    }
    if ($inicio !== '' || $fim !== '') {
        if ($inicio !== '') {
            $where[] = "DATE(hora_usuario) >= '" . mysql_real_escape_string($inicio) . "'";
        }
        if ($fim !== '') {
            $where[] = "DATE(hora_usuario) <= '" . mysql_real_escape_string($fim) . "'";
        }
    } else {
        $where[] = "hora_usuario >= CURDATE() - INTERVAL 6 DAY AND hora_usuario < CURDATE() + INTERVAL 1 DAY";
    }

    $sql = "SELECT id, usuario, codigo, latitude, longitude, hora_usuario, hora_servidor FROM localizacao";
    if (count($where)) {
        $sql .= " WHERE " . implode(' AND ', $where);
    }
    $sql .= " ORDER BY usuario ASC, hora_usuario ASC, id ASC";

    $consulta = mysql_query($sql);
    if (!$consulta) {
        return array();
    }
    $linhas = array();
    while ($ln = mysql_fetch_assoc($consulta)) {
        $linhas[] = $ln;
    }
    return rv_analise_rondas_avaliar($linhas);
}

function rv_analise_rondas_avaliar($linhas) {
    $limites = rv_analise_rondas_limites();
    $itens = array();
    foreach ($linhas as $ln) {
        $hora = '';
        if (isset($ln['hora_usuario']) && trim($ln['hora_usuario']) !== '' && $ln['hora_usuario'] !== '0000-00-00 00:00:00') {
            $hora = $ln['hora_usuario'];
        } elseif (isset($ln['hora_servidor'])) {
            $hora = $ln['hora_servidor'];
        }
        $ts = $hora !== '' ? strtotime($hora) : false;
        if ($ts === false) {
            $ts = 0;
        }
        $itens[] = array(
            'id' => isset($ln['id']) ? $ln['id'] : 0,
            'usuario' => isset($ln['usuario']) ? $ln['usuario'] : '',
            'codigo' => isset($ln['codigo']) ? trim($ln['codigo']) : '',
            'latitude' => isset($ln['latitude']) ? $ln['latitude'] : '',
            'longitude' => isset($ln['longitude']) ? $ln['longitude'] : '',
            'hora' => $hora,
            'ts' => $ts,
            'ponto_anterior' => '',
            'latitude_anterior' => '',
            'longitude_anterior' => '',
            'distancia' => null,
            'tempo' => null,
            'motivo' => ''
        );
    }

    $n = count($itens);
    $anterior = null;
    for ($i = 0; $i < $n; $i++) {
        if ($anterior !== null
            && strcasecmp($anterior['usuario'], $itens[$i]['usuario']) === 0
            && $anterior['ts'] > 0
            && $itens[$i]['ts'] > 0
            && ($itens[$i]['ts'] - $anterior['ts']) <= $limites['segundos_nova_sequencia']
        ) {
            $itens[$i]['ponto_anterior'] = $anterior['codigo'];
            $itens[$i]['latitude_anterior'] = $anterior['latitude'];
            $itens[$i]['longitude_anterior'] = $anterior['longitude'];
            $itens[$i]['tempo'] = $itens[$i]['ts'] - $anterior['ts'];
            if (rv_analise_rondas_coord_ok($anterior['latitude'], $anterior['longitude'])
                && rv_analise_rondas_coord_ok($itens[$i]['latitude'], $itens[$i]['longitude'])
            ) {
                $itens[$i]['distancia'] = rv_analise_rondas_haversine(
                    $anterior['latitude'],
                    $anterior['longitude'],
                    $itens[$i]['latitude'],
                    $itens[$i]['longitude']
                );
            }
        }
        $anterior = $itens[$i];
    }

    $sequencia = array();
    for ($i = 0; $i < $n; $i++) {
        $sequencia[$i] = false;
        if (!rv_analise_rondas_coord_ok($itens[$i]['latitude'], $itens[$i]['longitude'])) {
            continue;
        }
        $codigos = array();
        $codigos[strtolower($itens[$i]['codigo'])] = $i;
        for ($j = $i - 1; $j >= 0; $j--) {
            if (strcasecmp($itens[$j]['usuario'], $itens[$i]['usuario']) !== 0) {
                break;
            }
            if ($itens[$i]['ts'] <= 0 || $itens[$j]['ts'] <= 0) {
                break;
            }
            if (($itens[$i]['ts'] - $itens[$j]['ts']) > $limites['segundos_sequencia']) {
                break;
            }
            if (!rv_analise_rondas_coord_ok($itens[$j]['latitude'], $itens[$j]['longitude'])) {
                continue;
            }
            $dist = rv_analise_rondas_haversine(
                $itens[$j]['latitude'],
                $itens[$j]['longitude'],
                $itens[$i]['latitude'],
                $itens[$i]['longitude']
            );
            if ($dist <= $limites['metros_muito_proximas']) {
                $codigos[strtolower($itens[$j]['codigo'])] = $j;
            }
        }
        if (count($codigos) >= $limites['min_pontos_sequencia']) {
            foreach ($codigos as $indice) {
                $sequencia[$indice] = true;
            }
        }
    }

    $saida = array();
    for ($i = 0; $i < $n; $i++) {
        $motivo = '';
        if ($sequencia[$i]) {
            $motivo = 'Sequência de leituras que necessita de avaliação';
        } elseif ($itens[$i]['ponto_anterior'] !== ''
            && strcasecmp($itens[$i]['ponto_anterior'], $itens[$i]['codigo']) !== 0
            && $itens[$i]['distancia'] !== null
        ) {
            if ($itens[$i]['distancia'] <= $limites['metros_coordenada_repetida']) {
                $motivo = 'Coordenadas repetidas';
            } elseif ($itens[$i]['distancia'] <= $limites['metros_muito_proximas']) {
                $motivo = 'Localizações muito próximas para pontos diferentes';
            } elseif ($itens[$i]['tempo'] !== null
                && $itens[$i]['tempo'] >= 0
                && $itens[$i]['tempo'] <= $limites['segundos_intervalo_reduzido']
                && $itens[$i]['distancia'] >= $limites['metros_minimos_intervalo_reduzido']
            ) {
                $motivo = 'Intervalo de tempo reduzido entre pontos';
            }
        }
        if ($motivo === '' && !rv_analise_rondas_coord_ok($itens[$i]['latitude'], $itens[$i]['longitude'])) {
            $motivo = 'Geolocalização ausente';
        }
        if ($motivo === '') {
            continue;
        }
        $distancia = '-';
        if ($itens[$i]['distancia'] !== null) {
            $distancia = round($itens[$i]['distancia']) . ' m';
        }
        $intervalo = '-';
        if ($itens[$i]['tempo'] !== null) {
            $segundos = (int) $itens[$i]['tempo'];
            if ($segundos < 60) {
                $intervalo = $segundos . ' s';
            } else {
                $intervalo = floor($segundos / 60) . ' min ' . ($segundos % 60) . ' s';
            }
        }
        $saida[] = array(
            'hora' => $itens[$i]['hora'],
            'colaborador' => $itens[$i]['usuario'],
            'ponto' => $itens[$i]['codigo'],
            'ponto_anterior' => $itens[$i]['ponto_anterior'] !== '' ? $itens[$i]['ponto_anterior'] : '-',
            'latitude' => $itens[$i]['latitude'],
            'longitude' => $itens[$i]['longitude'],
            'latitude_anterior' => $itens[$i]['latitude_anterior'],
            'longitude_anterior' => $itens[$i]['longitude_anterior'],
            'distancia' => $distancia,
            'intervalo' => $intervalo,
            'motivo' => $motivo
        );
    }
    return $saida;
}
