<?php
/* ===== OPÇÕES (edite aqui) ============================================ */

// true  = a tela de login mostra uma senha para criar uma conta nova ("Primeiro acesso?").
// false = cadastro fechado. Depois de criar o seu login, mude para false.
define('PERMITIR_CADASTRO', true);

// Tamanho mínimo e máximo da senha que o usuário escolhe em Configurações.
define('SENHA_MIN', 8);
define('SENHA_MAX', 128);

// Máximo de logins errados por IP dentro da janela (em segundos).
define('LOGIN_MAX_FALHAS', 8);
define('LOGIN_JANELA', 900);

// Máximo de textos novos por conta por hora (proteção contra scripts/loops).
define('CRIAR_MAX_HORA', 30);

// FIM DAS OPCOES


function ipLocalOuPrivado($ip) {
    return filter_var($ip, FILTER_VALIDATE_IP) !== false
        && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
}

function requisicaoHTTPS() {
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        return true;
    }

    $remoto = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
    if (!ipLocalOuPrivado($remoto)) {
        return false;
    }

    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])
        && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') {
        return true;
    }

    if (!empty($_SERVER['HTTP_CF_VISITOR'])) {
        $info = json_decode($_SERVER['HTTP_CF_VISITOR'], true);
        if (is_array($info) && isset($info['scheme']) && $info['scheme'] === 'https') {
            return true;
        }
    }

    return false;
}

function iniciarSessao() {
    $duracao = 60 * 60 * 24 * 30; // 30 dias

    ini_set('session.gc_maxlifetime', (string)$duracao);
    session_name('iscreva_sid');

    $seguro = requisicaoHTTPS();

    if (PHP_VERSION_ID >= 70300) {
        session_set_cookie_params(array(
            'lifetime' => $duracao,
            'path'     => '/',
            'secure'   => $seguro,
            'httponly' => true,
            'samesite' => 'Lax'
        ));
    } else {
        session_set_cookie_params($duracao, '/; samesite=Lax', '', $seguro, true);
    }

    session_start();
}

function cabecalhosSeguranca() {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
}

function jsonSaida($dados) {
    $flags = defined('JSON_INVALID_UTF8_SUBSTITUTE') ? JSON_INVALID_UTF8_SUBSTITUTE : 0;
    $r = json_encode($dados, $flags);
    return ($r === false) ? '{"erro":"Falha ao codificar a resposta"}' : $r;
}

function jsonParaScript($dados) {
    $flags = JSON_HEX_TAG | JSON_HEX_AMP
        | (defined('JSON_INVALID_UTF8_SUBSTITUTE') ? JSON_INVALID_UTF8_SUBSTITUTE : 0);
    $r = json_encode($dados, $flags);
    return ($r === false) ? 'null' : $r;
}

function h($valor) {
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}

function caminhoDB($nome) {
    return __DIR__ . '/' . basename((string)$nome);
}

function nomeArquivoAleatorio($prefixo) {
    $caracteres = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

    do {
        $nome = $prefixo;
        for ($i = 0; $i < 32; $i++) {
            $nome .= $caracteres[random_int(0, strlen($caracteres) - 1)];
        }
        $nome .= '.db';
    } while (is_file(__DIR__ . '/' . $nome));

    return $nome;
}

function carregarLocalizador() {
    static $cache = null;

    if ($cache !== null) {
        return $cache;
    }

    $arquivo = __DIR__ . '/iscreva_master_db.php';
    $MASTER_DB_FILE = null;
    $MASTER_SEGREDO = null;

    if (is_file($arquivo)) {
        require $arquivo;
    }

    $gravar = false;

    if (empty($MASTER_DB_FILE)) {
        $MASTER_DB_FILE = nomeArquivoAleatorio('iscreva_master_');
        $gravar = true;
    } elseif ($MASTER_DB_FILE !== basename($MASTER_DB_FILE)) {
        $MASTER_DB_FILE = basename($MASTER_DB_FILE);
        $gravar = true;
    }

    if (empty($MASTER_SEGREDO) || strlen($MASTER_SEGREDO) < 32) {
        $MASTER_SEGREDO = bin2hex(random_bytes(32));
        $gravar = true;
    }

    if ($gravar) {
        $conteudo = "<?php\n" .
            '$MASTER_DB_FILE = ' . var_export($MASTER_DB_FILE, true) . ";\n" .
            '$MASTER_SEGREDO = ' . var_export($MASTER_SEGREDO, true) . ";\n";

        if (file_put_contents($arquivo, $conteudo, LOCK_EX) === false) {
            throw new RuntimeException('Não foi possível gravar iscreva_master_db.php. Verifique as permissões da pasta.');
        }

        @chmod($arquivo, 0600);
    }

    $cache = array('arquivo' => $MASTER_DB_FILE, 'segredo' => $MASTER_SEGREDO);

    return $cache;
}

function getMasterDB() {
    static $mdb = null;

    if ($mdb !== null) {
        return $mdb;
    }

    $loc = carregarLocalizador();
    $caminho = caminhoDB($loc['arquivo']);
    $novo = !is_file($caminho);

    $mdb = new PDO('sqlite:' . $caminho);
    $mdb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $mdb->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $mdb->setAttribute(PDO::ATTR_TIMEOUT, 5);

    if ($novo) {
        @chmod($caminho, 0600);
    }

    $mdb->exec("CREATE TABLE IF NOT EXISTS contas (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        codigo_hash TEXT NOT NULL UNIQUE,
        arquivo_db TEXT NOT NULL,
        criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    $mdb->exec("CREATE TABLE IF NOT EXISTS codigos_pendentes (
        codigo TEXT PRIMARY KEY,
        sessao TEXT NOT NULL,
        criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    $mdb->exec("CREATE TABLE IF NOT EXISTS tentativas (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        chave TEXT NOT NULL,
        quando INTEGER NOT NULL
    )");

    $mdb->exec("CREATE INDEX IF NOT EXISTS idx_tentativas ON tentativas (chave, quando)");

            $mdb->exec("CREATE TABLE IF NOT EXISTS monitor_tokens (
        token TEXT PRIMARY KEY,
        conta_id INTEGER NOT NULL,
        arquivo_live TEXT,
        codigo_curto TEXT,
        criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    $mdb->exec("CREATE INDEX IF NOT EXISTS idx_monitor_tokens_conta ON monitor_tokens (conta_id)");

    $colunas = array();
    try {
        $cols = $mdb->query("PRAGMA table_info(monitor_tokens)")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($cols as $c) {
            if (isset($c['name'])) $colunas[$c['name']] = true;
        }
    } catch (Exception $e) {
        $colunas['arquivo_live'] = true;
        $colunas['codigo_curto'] = true;
    }

    if (empty($colunas['arquivo_live'])) {
        try { $mdb->exec("ALTER TABLE monitor_tokens ADD COLUMN arquivo_live TEXT"); } catch (Exception $e) {}
    }

    if (empty($colunas['codigo_curto'])) {
        try { $mdb->exec("ALTER TABLE monitor_tokens ADD COLUMN codigo_curto TEXT"); } catch (Exception $e) {}
    }

    $temLive = false;
    try {
        $cols = $mdb->query("PRAGMA table_info(monitor_tokens)")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($cols as $c) {
            if (isset($c['name']) && $c['name'] === 'arquivo_live') { $temLive = true; break; }
        }
    } catch (Exception $e) {
        $temLive = true;
    }

    if (!$temLive) {
        try { $mdb->exec("ALTER TABLE monitor_tokens ADD COLUMN arquivo_live TEXT"); } catch (Exception $e) {}
    }

    return $mdb;
}

function hashCodigo($codigo) {
    $loc = carregarLocalizador();
    return hash_hmac('sha256', $codigo, $loc['segredo']);
}

function ipCliente() {
    $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';

    if (ipLocalOuPrivado($ip)) {
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])
            && filter_var($_SERVER['HTTP_CF_CONNECTING_IP'], FILTER_VALIDATE_IP)) {
            return $_SERVER['HTTP_CF_CONNECTING_IP'];
        }

        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $partes = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ultimo = trim($partes[count($partes) - 1]);
            if (filter_var($ultimo, FILTER_VALIDATE_IP)) {
                return $ultimo;
            }
        }
    }

    return $ip;
}

function estadoLimite($chave, $max, $janela) {
    $mdb = getMasterDB();
    $agora = time();

    $stmt = $mdb->prepare("DELETE FROM tentativas WHERE quando < ?");
    $stmt->execute(array($agora - 86400));

    $stmt = $mdb->prepare("SELECT COUNT(*) AS n, MIN(quando) AS primeira FROM tentativas WHERE chave = ? AND quando > ?");
    $stmt->execute(array($chave, $agora - $janela));
    $r = $stmt->fetch(PDO::FETCH_ASSOC);

    $n = (int)$r['n'];
    $espera = 0;

    if ($n >= $max) {
        $espera = max(1, (int)$r['primeira'] + $janela - $agora);
    }

    return array('falhas' => $n, 'espera' => $espera, 'restantes' => max(0, $max - $n));
}

function registrarEvento($chave) {
    $stmt = getMasterDB()->prepare("INSERT INTO tentativas (chave, quando) VALUES (?, ?)");
    $stmt->execute(array($chave, time()));
}

function obterCodigoPendente() {
    if (!PERMITIR_CADASTRO) {
        return null;
    }

    $mdb = getMasterDB();
    $sessao = session_id();

    $mdb->exec("DELETE FROM codigos_pendentes WHERE criado_em < datetime('now', '-1 hour')");
    $mdb->exec("DELETE FROM codigos_pendentes WHERE rowid NOT IN (
        SELECT rowid FROM codigos_pendentes ORDER BY criado_em DESC, rowid DESC LIMIT 1000
    )");

    $stmt = $mdb->prepare("SELECT codigo FROM codigos_pendentes WHERE sessao = ?");
    $stmt->execute(array($sessao));
    $existente = $stmt->fetchColumn();

    if ($existente !== false && $existente !== null) {
        return (string)$existente;
    }

    for ($tentativas = 0; $tentativas < 20; $tentativas++) {
        $codigo = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $stmt = $mdb->prepare("SELECT 1 FROM contas WHERE codigo_hash = ?");
        $stmt->execute(array(hashCodigo($codigo)));

        if ($stmt->fetchColumn()) {
            continue;
        }

        try {
            $stmt = $mdb->prepare("INSERT INTO codigos_pendentes (codigo, sessao) VALUES (?, ?)");
            $stmt->execute(array($codigo, $sessao));
            return $codigo;
        } catch (PDOException $e) {
            // Colidiu com a senha pendente de outro visitante; tenta outra.
        }
    }

    throw new RuntimeException('Não foi possível gerar uma senha de acesso no momento. Recarregue a página.');
}

function criarBancoPessoal() {
    $nome = nomeArquivoAleatorio('iscreva_conta_');
    $arquivo = caminhoDB($nome);

    $db = new PDO('sqlite:' . $arquivo);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $db->exec("CREATE TABLE IF NOT EXISTS textos (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        titulo TEXT NOT NULL,
        conteudo TEXT,
        data_modificacao DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS configuracoes (
        chave TEXT PRIMARY KEY,
        valor TEXT NOT NULL
    )");

    @chmod($arquivo, 0600);

    return $nome;
}

function buscarContaPorSenha($mdb, $senha) {
    $stmt = $mdb->prepare("SELECT id, arquivo_db FROM contas WHERE codigo_hash = ?");
    $stmt->execute(array(hashCodigo($senha)));
    $conta = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$conta) {
        return null;
    }

    $nome = basename($conta['arquivo_db']);

    if ($nome !== $conta['arquivo_db']) {
        $up = $mdb->prepare("UPDATE contas SET arquivo_db = ? WHERE id = ?");
        $up->execute(array($nome, $conta['id']));
    }

    $conta['arquivo_db'] = $nome;
    $conta['nova'] = false;

    return $conta;
}

function autenticarOuCriarConta($senha) {
    $senha = trim($senha);

    if ($senha === '') {
        return null;
    }

    $mdb = getMasterDB();

    $conta = buscarContaPorSenha($mdb, $senha);

    if ($conta) {
        return $conta;
    }

    if (!PERMITIR_CADASTRO) {
        return null;
    }

    $stmt = $mdb->prepare("DELETE FROM codigos_pendentes WHERE codigo = ? AND sessao = ?");
    $stmt->execute(array($senha, session_id()));

    if ($stmt->rowCount() < 1) {
        return null;
    }

    $nomeDB = criarBancoPessoal();

    $stmt = $mdb->prepare("INSERT INTO contas (codigo_hash, arquivo_db) VALUES (?, ?)");
    $stmt->execute(array(hashCodigo($senha), $nomeDB));
    $id = $mdb->lastInsertId();

    return array('id' => (int)$id, 'arquivo_db' => $nomeDB, 'nova' => true);
}

function dbExiste() {
    return isset($_SESSION['conta_db_file']) && is_file(caminhoDB($_SESSION['conta_db_file']));
}

function getDBPorArquivo($arquivo) {
    static $cache = array();
    $arquivo = basename((string)$arquivo);
    if (isset($cache[$arquivo])) return $cache[$arquivo];

    $caminho = caminhoDB($arquivo);
    if (!is_file($caminho)) throw new RuntimeException('Conta não encontrada.');

    $db = new PDO('sqlite:' . $caminho);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $db->setAttribute(PDO::ATTR_TIMEOUT, 5);

    $cache[$arquivo] = $db;
    return $db;
}

function getDB() {
    if (!dbExiste()) throw new RuntimeException('Conta não encontrada.');
    return getDBPorArquivo($_SESSION['conta_db_file']);
}

/* ===== Configurações e temas ========================================== */

function configPadrao() {
    return array(
        'cor_fundo'          => '#424242',
        'cor_barra'          => '#424242',
        'cor_texto'          => '#ebebeb',
        'cor_editor_fundo'   => '#ebebeb',
        'cor_editor_texto'   => '#424242',
        'fonte_familia'      => 'domitian',
        'fonte_tamanho'      => '1.4',
        'fonte_altura_linha' => '1.6',
        'fonte_espacamento'  => '0.5',
        'fonte_peso'         => 'normal',
        'tecla_imagem'       => 'tecla_default.png',
        'cor_tecla_texto'    => '#fffde5',
        'estilo_neobrutalista' => '0'
    );
}

// Fonte única das paletas. Ordem: fundo (= barra), texto, editor fundo, editor texto,
// texto da tecla, fonte, tamanho, altura da linha, espaçamento, peso.
function temasRecomendados() {
    $dados = array(
        'tecla_default.png' => array('#424242', '#ebebeb', '#ebebeb', '#424242', '#fffde5', 'domitian',     '1.4', '1.6', '0.5', 'normal'),
        'tecla_osm.png'     => array('#383838', '#ff7b00', '#fffdb8', '#614f00', '#383838', 'markerfelt',   '1.4', '1.6', '1.5', 'normal'),
        'tecla_osm2.png'    => array('#fcd1ff', '#6d0a73', '#d7cad8', '#6d0a73', '#ffffff', 'TT2020',       '1.4', '1.6', '0',   'normal'),
        'tecla_scs55.png'   => array('#008a67', '#e5fff7', '#e5fff7', '#008a67', '#ffffff', 'merriweather', '1.4', '1.6', '0.5', 'normal'),
        'tecla_is.png'      => array('#f5f5f5', '#000000', '#f5f5f5', '#000000', '#f5f5f5', 'merriweather', '1.4', '1.6', '0.5', 'normal'),
        'tecla_ftp1.png'    => array('#696354', '#ededed', '#ededed', '#696354', '#38352e', 'domitian',     '1.4', '1.6', '0.5', 'normal'),
        'tecla_ol32.png'    => array('#32808b', '#dee8e6', '#dee8e6', '#32808b', '#ffffff', 'markerfelt',   '1.4', '1.6', '1.5', 'normal'),
        'tecla_ov.png'      => array('#dbccff', '#7a0000', '#dbccff', '#7a0000', '#fffde5', 'noteworthy',   '1.5', '1.7', '1.5', '600'),
        'tecla_hb57.png'    => array('#f5fffd', '#4e746e', '#f5fffd', '#4e746e', '#ffffff', 'noteworthy',   '1.5', '1.7', '1.5', '600'),
        'tecla_u5.png'      => array('#472000', '#faffd1', '#faffd1', '#472000', '#fff36b', 'TT2020',       '1.4', '1.6', '0',   'normal'),
    );

    $temas = array();

    foreach ($dados as $tecla => $d) {
        $temas[$tecla] = array(
            'cor_fundo'          => $d[0],
            'cor_barra'          => $d[0],
            'cor_texto'          => $d[1],
            'cor_editor_fundo'   => $d[2],
            'cor_editor_texto'   => $d[3],
            'cor_tecla_texto'    => $d[4],
            'fonte_familia'      => $d[5],
            'fonte_tamanho'      => $d[6],
            'fonte_altura_linha' => $d[7],
            'fonte_espacamento'  => $d[8],
            'fonte_peso'         => $d[9]
        );
    }

    return $temas;
}

function temaAleatorio() {
    $temas = temasRecomendados();
    $tecla = array_rand($temas);
    $tema = $temas[$tecla];
    $tema['tecla_imagem'] = $tecla;

    return array_merge(configPadrao(), $tema);
}

function listaTeclas() {
    return array_keys(temasRecomendados());
}

function listaFontes() {
    return array('TT2020', 'domitian', 'markerfelt', 'merriweather', 'noteworthy',
                 'monospace', 'serif', 'sans-serif', 'cursive');
}

function listaPesos() {
    return array('normal', '300', '500', '600', 'bold', '800');
}

function logosPorTecla() {
    $logos = array();

    foreach (listaTeclas() as $tecla) {
        $logos[$tecla] = str_replace('tecla_', 'logo_', $tecla);
    }

    return $logos;
}

function logoDaTecla($teclaImagem) {
    $logos = logosPorTecla();

    return isset($logos[$teclaImagem]) ? $logos[$teclaImagem] : 'logo_default.png';
}

function numeroEntre($valor, $min, $max) {
    if (!is_numeric($valor)) {
        return null;
    }

    $n = (float)$valor;

    if ($n < $min || $n > $max) {
        return null;
    }

    return rtrim(rtrim(number_format($n, 2, '.', ''), '0'), '.');
}

function validarConfig($chave, $valor) {
    if (!is_string($valor) && !is_int($valor) && !is_float($valor)) {
        return null;
    }

    $valor = trim((string)$valor);

    switch ($chave) {
        case 'cor_fundo':
        case 'cor_barra':
        case 'cor_texto':
        case 'cor_editor_fundo':
        case 'cor_editor_texto':
        case 'cor_tecla_texto':
            return preg_match('/^#[0-9a-fA-F]{6}$/', $valor) ? strtolower($valor) : null;

        case 'fonte_familia':
            return in_array($valor, listaFontes(), true) ? $valor : null;

        case 'fonte_peso':
            return in_array($valor, listaPesos(), true) ? $valor : null;

        case 'tecla_imagem':
            return in_array($valor, listaTeclas(), true) ? $valor : null;

        case 'fonte_tamanho':
            return numeroEntre($valor, 0.8, 3);

        case 'fonte_altura_linha':
            return numeroEntre($valor, 1, 3);

        case 'fonte_espacamento':
            return numeroEntre($valor, -3, 10);
            
        case 'estilo_neobrutalista':
            return ($valor === '0' || $valor === '1') ? $valor : null;
    }

    return null;
}

function carregarConfiguracoes() {
    $config = configPadrao();

    if (!dbExiste()) {
        return $config;
    }

    $stmt = getDB()->query("SELECT chave, valor FROM configuracoes");

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if (array_key_exists($row['chave'], $config)) {
            $v = validarConfig($row['chave'], $row['valor']);

            if ($v !== null) {
                $config[$row['chave']] = $v;
            }
        }
    }

    return $config;
}

/* ===== Monitor (espelhamento em outro aparelho) ====================== */

function contaPorTokenMonitor($token) {
    if (!is_string($token) || $token === '') return null;

    $mdb = getMasterDB();
    $stmt = $mdb->prepare("
        SELECT c.id, c.arquivo_db
        FROM monitor_tokens m
        INNER JOIN contas c ON c.id = m.conta_id
        WHERE m.token = ?
    ");
    $stmt->execute(array($token));
    $r = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$r) return null;

    return array(
        'id'         => (int)$r['id'],
        'arquivo_db' => basename($r['arquivo_db'])
    );
}

function obterMonitorDaConta($contaId) {
    static $cache = array();

    $contaId = (int)$contaId;
    if (isset($cache[$contaId])) return $cache[$contaId];

    $mdb = getMasterDB();
    $stmt = $mdb->prepare("SELECT token, arquivo_live, codigo_curto FROM monitor_tokens WHERE conta_id = ?");
    $stmt->execute(array($contaId));
    $r = $stmt->fetch(PDO::FETCH_ASSOC);

    $token = ($r && !empty($r['token']))        ? (string)$r['token']         : null;
    $live  = ($r && !empty($r['arquivo_live'])) ? (string)$r['arquivo_live']  : null;
    $curto = ($r && !empty($r['codigo_curto'])) ? (string)$r['codigo_curto']  : null;

    if ($token !== null && $live !== null && $curto !== null) {
        $cache[$contaId] = array('token' => $token, 'arquivo_live' => $live, 'codigo_curto' => $curto);
        return $cache[$contaId];
    }

    if ($token === null) $token = bin2hex(random_bytes(24));
    if ($live  === null) $live  = nomeArquivoAleatorio('iscreva_live_');
    if ($curto === null) $curto = gerarCodigoCurtoMonitor();

    if ($r) {
        $stmt = $mdb->prepare("UPDATE monitor_tokens SET token = ?, arquivo_live = ?, codigo_curto = ? WHERE conta_id = ?");
        $stmt->execute(array($token, $live, $curto, $contaId));
    } else {
        $stmt = $mdb->prepare("INSERT INTO monitor_tokens (token, conta_id, arquivo_live, codigo_curto) VALUES (?, ?, ?, ?)");
        $stmt->execute(array($token, $contaId, $live, $curto));
    }

    $cache[$contaId] = array('token' => $token, 'arquivo_live' => $live, 'codigo_curto' => $curto);
    return $cache[$contaId];
}

function publicarLive($contaId, $textoId, $titulo, $conteudo, $rev) {
    $m = obterMonitorDaConta($contaId);
    $arquivo = caminhoDB($m['arquivo_live']);

    $dados = array(
        'rev'      => (int)$rev,
        'id'       => (int)$textoId,
        'titulo'   => (string)$titulo,
        'conteudo' => (string)$conteudo,
        'ts'       => time()
    );

    $json = json_encode($dados, JSON_UNESCAPED_UNICODE
        | (defined('JSON_INVALID_UTF8_SUBSTITUTE') ? JSON_INVALID_UTF8_SUBSTITUTE : 0));

    if ($json === false) return false;

    $tmp = $arquivo . '.tmp.' . bin2hex(random_bytes(4));

    if (@file_put_contents($tmp, $json, LOCK_EX) === false) return false;
    @chmod($tmp, 0600);

    if (!@rename($tmp, $arquivo)) { @unlink($tmp); return false; }

    return true;
}

function gerarCodigoCurtoMonitor() {
    $alfabeto = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';  
    $mdb = getMasterDB();

    for ($tentativa = 0; $tentativa < 20; $tentativa++) {
        $codigo = '';
        for ($i = 0; $i < 7; $i++) {
            $codigo .= $alfabeto[random_int(0, strlen($alfabeto) - 1)];
        }

        $stmt = $mdb->prepare("SELECT 1 FROM monitor_tokens WHERE codigo_curto = ?");
        $stmt->execute(array($codigo));

        if (!$stmt->fetchColumn()) {
            return $codigo;
        }
    }

    throw new RuntimeException('Não foi possível gerar um código curto de monitor.');
}

function contaPorCodigoCurtoMonitor($codigo) {
    if (!is_string($codigo) || $codigo === '') return null;

    if (!preg_match('/^[A-Za-z0-9]{1,16}$/', $codigo)) return null;

    $mdb = getMasterDB();
    $stmt = $mdb->prepare("
        SELECT c.id, c.arquivo_db
        FROM monitor_tokens m
        INNER JOIN contas c ON c.id = m.conta_id
        WHERE m.codigo_curto = ?
    ");
    $stmt->execute(array(strtoupper($codigo)));
    $r = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$r) return null;

    return array(
        'id'         => (int)$r['id'],
        'arquivo_db' => basename($r['arquivo_db'])
    );
}

function lerLive($arquivoLive) {
    $arquivo = caminhoDB($arquivoLive);
    if (!is_file($arquivo)) return null;

    $json = @file_get_contents($arquivo);
    if ($json === false || $json === '') return null;

    $d = json_decode($json, true);
    if (!is_array($d) || !isset($d['rev'])) return null;

    return array(
        'rev'      => (int)$d['rev'],
        'id'       => isset($d['id'])       ? (int)$d['id']           : 0,
        'titulo'   => isset($d['titulo'])   ? (string)$d['titulo']    : '',
        'conteudo' => isset($d['conteudo']) ? (string)$d['conteudo']  : '',
        'ts'       => isset($d['ts'])       ? (int)$d['ts']           : 0
    );
}

function apagarLiveDaConta($contaId) {
    $mdb = getMasterDB();
    $stmt = $mdb->prepare("SELECT arquivo_live FROM monitor_tokens WHERE conta_id = ?");
    $stmt->execute(array((int)$contaId));
    $live = $stmt->fetchColumn();

    if ($live) {
        $arquivo = caminhoDB($live);
        if (is_file($arquivo)) @unlink($arquivo);
        @unlink($arquivo . '.tmp');
    }

    $stmt = $mdb->prepare("DELETE FROM monitor_tokens WHERE conta_id = ?");
    $stmt->execute(array((int)$contaId));
}

function limparLive($contaId) {
    $m = obterMonitorDaConta((int)$contaId);
    if (!is_file(caminhoDB($m['arquivo_live']))) return true;
    return publicarLive((int)$contaId, 0, '', '', (int)(microtime(true) * 1000));
}