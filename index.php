<?php
require_once __DIR__ . '/comum.php';

cabecalhosSeguranca();
iniciarSessao();

function criarTextoExemploSeNecessario($db) {
    $stmt = $db->prepare("SELECT valor FROM configuracoes WHERE chave = 'exemplo_criado'");
    $stmt->execute();

    if ($stmt->fetchColumn()) {
        return;
    }

    $stmt = $db->prepare("INSERT INTO textos (titulo, conteudo) VALUES (?, ?)");
    $stmt->execute(['Não se curve diante da página em branco', 'Não se curve diante da página em branco como um suplicante diante de um altar inalcançável. Ela não é um juiz, mas sim um campo de batalha à sua espera. O medo de escrever mal é o único pecado imperdoável; o rascunho perfeito é uma miragem que condena ao silêncio os melhores talentos. Arranque a primeira palavra que vier à mente, mesmo que ela saia torta, mesmo que pareça menor do que a ideia que a gerou. Apenas comece, pois o movimento da mão é o único feitiço capaz de quebrar a paralisia da alma.

Esqueça a obra-prima. Hoje, você não precisa erguer uma catedral; precisa apenas assentar o primeiro tijolo na lama. Escrever é um ofício de artesão, não de profeta. A beleza não nasce pronta, ela se revela no intervalo entre a escrita e a reescrita, no suor da revisão que lapida o bruto. Permita-se o caos, a frase que não fecha, o parágrafo que desaba. É na escuridão do manuscrito imperfeito que a luz do estilo finalmente encontra fresta para entrar.

Portanto, sente-se na cadeira e cumpra o seu destino. Escreva como quem respira, porque é exatamente disso que se trata: um ato de sobrevivência do espírito. Não escreva para os outros, escreva para exorcizar o que te habita, para dar forma ao redemoinho de pensamentos que só você ouve. O mundo já tem silêncio demais; ele precisa da sua voz, da sua estranheza, da sua verdade. As palavras não vão se escrever sozinhas. A hora é agora. Pegue a caneta ou encoste os dedos no teclado e, contra todas as dúvidas, vá.

Att. Seu amigo DeepSeek :) 8 de Setembro de 2026.']);

    $stmt = $db->prepare("
        INSERT OR REPLACE INTO configuracoes (chave, valor)
        VALUES ('exemplo_criado', '1')
    ");
    $stmt->execute();
}

function chavesConfiguracaoValidas() {
    return array(
        'cor_fundo', 'cor_barra', 'cor_texto',
        'cor_editor_fundo', 'cor_editor_texto',
        'fonte_familia', 'fonte_tamanho', 'fonte_altura_linha', 'fonte_espacamento', 'fonte_peso', 'tecla_imagem', 'cor_tecla_texto', 'estilo_neobrutalista'
    );
}

function getConfiguracoes() {
    if (!dbExiste()) {
        return temaAleatorio();
    }

    return carregarConfiguracoes();
}

function salvarConfiguracao($chave, $valor) {

    $db = getDB();

    $stmt = $db->prepare("
        INSERT OR REPLACE INTO configuracoes (chave, valor)
        VALUES (?, ?)
    ");

    $stmt->execute([$chave, $valor]);
}

function verificarCSRF() {
    $token = (isset($_POST['csrf']) && is_string($_POST['csrf'])) ? $_POST['csrf'] : '';
    if ($token === '' || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        header('Content-Type: application/json; charset=utf-8');
        echo jsonSaida(array('erro' => 'Sessão expirada, recarregue a página'));
        exit;
    }
}

if (isset($_GET['action'])) {

    $action = $_GET['action'];

    set_exception_handler(function ($e) {
        error_log('iScreva: ' . $e->getMessage() . ' em ' . $e->getFile() . ':' . $e->getLine());

        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }

        echo jsonSaida(array('erro' => 'Erro interno do servidor.'));
        exit;
    });

    if ($action !== 'login' && !isset($_SESSION['logado'])) {
        header('Content-Type: application/json; charset=utf-8');
        echo jsonSaida(['erro' => 'Não autenticado']);
        exit;
    }

        $acoesComDB = array(
        'criar',
        'download',
        'listar',
        'carregar',
        'salvar',
        'excluir',
        'listar_configuracoes',
        'salvar_configuracao',
        'live_publicar'
    );

    if (in_array($action, $acoesComDB, true)) {

        if (!dbExiste()) {

            if ($action === 'listar') {
                header('Content-Type: application/json; charset=utf-8');
                echo jsonSaida(array());
                exit;
            }

            header('Content-Type: application/json; charset=utf-8');
            echo jsonSaida(['erro' => 'Conta não encontrada. Faça login novamente.']);
            exit;
        }

        $db = getDB();

    } else {
        $db = null;
    }

    if ($action === 'download') {

        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

        $stmt = $db->prepare("SELECT titulo, conteudo FROM textos WHERE id = ?");
        $stmt->execute([$id]);
        $texto = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$texto) {
            header('Content-Type: text/plain; charset=utf-8');
            echo 'Texto não encontrado.';
            exit;
        }

        $nome = trim($texto['titulo']);

        if ($nome === '') {
            $nome = 'texto';
        }

        $nome = preg_replace('/[\/\\\\:*?"<>|]/', '-', $nome);
        $nome = str_replace(array("\r", "\n"), '', $nome);
        $nome = trim($nome);

        if ($nome === '') {
            $nome = 'texto';
        }

        $ascii = $nome;

        if (function_exists('iconv')) {
            $traduzido = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $nome);
            if ($traduzido !== false) {
                $ascii = $traduzido;
            }
        }

        $ascii = preg_replace('/[^A-Za-z0-9 ._()-]/', '_', $ascii);

        if (trim($ascii, ' ._') === '') {
            $ascii = 'texto';
        }

        $conteudoTexto = (string)$texto['conteudo'];

        header('Content-Type: text/plain; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $ascii . '.txt"; filename*=UTF-8\'\'' . rawurlencode($nome . '.txt'));
        header('Content-Length: ' . strlen($conteudoTexto));
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        echo $conteudoTexto;
        exit;
    }

    header('Content-Type: application/json; charset=utf-8');

    switch ($action) {

        case 'login':

            $chaveLimite = 'login:' . ipCliente();
            $limite = estadoLimite($chaveLimite, LOGIN_MAX_FALHAS, LOGIN_JANELA);

            if ($limite['espera'] > 0) {
                echo jsonSaida(array(
                    'erro' => 'Muitas tentativas. Tente novamente em ' . ceil($limite['espera'] / 60) . ' minuto(s).'
                ));
                break;
            }

            $senha = (isset($_POST['senha']) && is_string($_POST['senha'])) ? $_POST['senha'] : '';

            $captchaInformado = null;
            if (isset($_POST['captcha']) && is_string($_POST['captcha']) && is_numeric(trim($_POST['captcha']))) {
                $captchaInformado = (int)trim($_POST['captcha']);
            }

            $captchaEsperado = isset($_SESSION['captcha_result']) ? (int)$_SESSION['captcha_result'] : null;

            $captchaOk = ($captchaEsperado !== null
                && $captchaInformado !== null
                && hash_equals((string)$captchaEsperado, (string)$captchaInformado));

            $resultadoLogin = $captchaOk ? autenticarOuCriarConta($senha) : null;

            if ($resultadoLogin !== null) {

                session_regenerate_id(true);

                $_SESSION['logado'] = true;
                $_SESSION['conta_id'] = (int)$resultadoLogin['id'];
                $_SESSION['conta_db_file'] = basename($resultadoLogin['arquivo_db']);
                $_SESSION['csrf_token'] = bin2hex(random_bytes(16));

                unset(
                    $_SESSION['captcha_result'],
                    $_SESSION['captcha_num1'],
                    $_SESSION['captcha_num2']
                );

                if (!empty($resultadoLogin['nova'])) {
                    criarTextoExemploSeNecessario(getDB());
                }

                echo jsonSaida(array('sucesso' => true));

            } else {

                registrarEvento($chaveLimite);
                usleep(400000);

                $limite = estadoLimite($chaveLimite, LOGIN_MAX_FALHAS, LOGIN_JANELA);

                if ($limite['espera'] > 0) {
                    echo jsonSaida(array(
                        'erro' => 'Muitas tentativas. Tente novamente em ' . ceil($limite['espera'] / 60) . ' minuto(s).'
                    ));
                } else {
                    echo jsonSaida(array(
                        'erro' => 'Senha ou captcha incorretos. Tentativas restantes: ' . $limite['restantes']
                    ));
                }

                unset(
                    $_SESSION['captcha_result'],
                    $_SESSION['captcha_num1'],
                    $_SESSION['captcha_num2']
                );
            }

            break;

        case 'logout':
            verificarCSRF();
            if (!empty($_SESSION['conta_id'])) {
                try { limparLive((int)$_SESSION['conta_id']); } catch (Throwable $e) {}
            }
            $_SESSION = array();
            session_destroy();
            echo jsonSaida(['sucesso' => true]);
            break;

        case 'listar':

            $stmt = $db->query("
                SELECT id, titulo, data_modificacao
                FROM textos
                ORDER BY data_modificacao DESC
                LIMIT 500
            ");

            $lista = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $fuso = new DateTimeZone('America/Sao_Paulo');

            foreach ($lista as &$item) {

                if (!empty($item['data_modificacao'])) {

                    $data = DateTimeImmutable::createFromFormat(
                        'Y-m-d H:i:s',
                        $item['data_modificacao'],
                        new DateTimeZone('UTC')
                    );

                    if ($data !== false) {
                        $item['data_modificacao'] = $data
                            ->setTimezone($fuso)
                            ->format('Y-m-d H:i:s');
                    }
                }
            }

            unset($item);

            echo jsonSaida($lista);
            break;

        case 'criar':
            verificarCSRF();

            $contaId = isset($_SESSION['conta_id']) ? (int)$_SESSION['conta_id'] : 0;
            $chaveCriar = 'criar:' . $contaId;
            $limiteCriar = estadoLimite($chaveCriar, CRIAR_MAX_HORA, 3600);

            if ($limiteCriar['espera'] > 0) {
                echo jsonSaida(array(
                    'erro' => 'Muitos textos criados. Tente novamente em ' . ceil($limiteCriar['espera'] / 60) . ' minuto(s).'
                ));
                break;
            }

            $titulo = isset($_POST['titulo']) ? trim($_POST['titulo']) : 'Sem título';
            if ($titulo === '') $titulo = 'Sem título';
            $titulo = function_exists('mb_substr') ? mb_substr($titulo, 0, 100, 'UTF-8') : substr($titulo, 0, 100);

            $stmt = $db->prepare("INSERT INTO textos (titulo, conteudo) VALUES (?, '')");
            $stmt->execute([$titulo]);

            registrarEvento($chaveCriar);

            $novoId = (int)$db->lastInsertId();
            echo jsonSaida(['id' => $novoId, 'sucesso' => true]);
            break;

        case 'carregar':
            $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
            $stmt = $db->prepare("SELECT id, titulo, conteudo FROM textos WHERE id = ?");
            $stmt->execute([$id]);
            $dados = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($dados) {
                echo jsonSaida($dados);
            } else {
                echo jsonSaida(['erro' => 'Texto não encontrado']);
            }
            break;

        case 'salvar':
            verificarCSRF();
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

            if (!isset($_POST['conteudo']) || !is_string($_POST['conteudo'])) {
                echo jsonSaida(array('erro' => 'Conteúdo ausente'));
                break;
            }

            $stmt = $db->prepare("UPDATE textos SET conteudo = ?, data_modificacao = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$_POST['conteudo'], $id]);

            if ($stmt->rowCount() < 1) {
                echo jsonSaida(array('erro' => 'Texto não encontrado'));
                break;
            }

            echo jsonSaida(array('sucesso' => true));
            break;

        case 'excluir':
            verificarCSRF();
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            $stmt = $db->prepare("DELETE FROM textos WHERE id = ?");
            $stmt->execute([$id]);
            echo jsonSaida(['sucesso' => true]);
            break;

        case 'listar_configuracoes':
            echo jsonSaida(getConfiguracoes());
            break;

                case 'salvar_configuracao':
            verificarCSRF();

            $dados = (isset($_POST['dados']) && is_string($_POST['dados'])) ? json_decode($_POST['dados'], true) : null;

            if (!is_array($dados)) {
                echo jsonSaida(array('erro' => 'Dados inválidos'));
                break;
            }

            $senhaAtual = null;
            if (isset($dados['senha_atual']) && is_string($dados['senha_atual'])) {
                $senhaAtual = trim($dados['senha_atual']);
            }

            $novaSenha = null;
            if (isset($dados['senha']) && is_string($dados['senha'])) {
                $novaSenha = trim($dados['senha']);
                if ($novaSenha === '') {
                    $novaSenha = null;
                }
            }

            if ($novaSenha !== null && (strlen($novaSenha) < SENHA_MIN || strlen($novaSenha) > SENHA_MAX)) {
                echo jsonSaida(array('erro' => 'A senha deve ter entre ' . SENHA_MIN . ' e ' . SENHA_MAX . ' caracteres.'));
                break;
            }

            $validados = array();

            foreach ($dados as $chave => $valor) {

                if (!in_array($chave, chavesConfiguracaoValidas(), true)) {
                    continue;
                }

                $normalizado = validarConfig($chave, $valor);

                if ($normalizado === null) {
                    echo jsonSaida(array('erro' => 'Valor inválido para "' . $chave . '".'));
                    break 2;
                }

                $validados[$chave] = $normalizado;
            }

            $contaId = isset($_SESSION['conta_id']) ? (int)$_SESSION['conta_id'] : 0;
            $mdb = null;

            if ($novaSenha !== null) {

                if ($contaId <= 0) {
                    echo jsonSaida(array('erro' => 'Conta não identificada. Faça login novamente.'));
                    break;
                }

                $chaveTroca = 'troca:' . $contaId;
                $limite = estadoLimite($chaveTroca, 5, 3600);

                if ($limite['espera'] > 0) {
                    echo jsonSaida(array('erro' => 'Muitas tentativas de troca de senha. Tente novamente em ' . ceil($limite['espera'] / 60) . ' minuto(s).'));
                    break;
                }

                if ($senhaAtual === null || $senhaAtual === '') {
                    echo jsonSaida(array('erro' => 'Informe a senha atual para trocá-la.'));
                    break;
                }

                $mdb = getMasterDB();

                $stmt = $mdb->prepare("SELECT 1 FROM contas WHERE id = ? AND codigo_hash = ?");
                $stmt->execute(array($contaId, hashCodigo($senhaAtual)));

                if (!$stmt->fetchColumn()) {
                    registrarEvento($chaveTroca);
                    echo jsonSaida(array('erro' => 'Senha atual incorreta.'));
                    break;
                }

                $stmt = $mdb->prepare("SELECT 1 FROM contas WHERE codigo_hash = ? AND id <> ?");
                $stmt->execute(array(hashCodigo($novaSenha), $contaId));
                $emUso = $stmt->fetchColumn();

                if (!$emUso) {
                    $stmt = $mdb->prepare("SELECT 1 FROM codigos_pendentes WHERE codigo = ?");
                    $stmt->execute(array($novaSenha));
                    $emUso = $stmt->fetchColumn();
                }

                if ($emUso) {
                    registrarEvento($chaveTroca);
                    echo jsonSaida(array('erro' => 'Essa senha já está em uso. Escolha outra.'));
                    break;
                }
            }

            $db->beginTransaction();

            foreach ($validados as $chave => $valor) {
                salvarConfiguracao($chave, $valor);
            }

            $db->commit();

            if ($novaSenha !== null) {
                $stmt = $mdb->prepare("UPDATE contas SET codigo_hash = ? WHERE id = ?");
                $stmt->execute(array(hashCodigo($novaSenha), $contaId));
                session_regenerate_id(true);
            }

            echo jsonSaida(array('sucesso' => true));
            break;


        case 'apagar_conta':
            verificarCSRF();

            $mdb = getMasterDB();
            $sessaoAtual = session_id();

            $contaApagar = isset($_SESSION['conta_id']) ? (int)$_SESSION['conta_id'] : 0;
            if ($contaApagar > 0) {
                apagarLiveDaConta($contaApagar);
            }

            if (isset($_SESSION['conta_id'])) {
                $stmt = $mdb->prepare("DELETE FROM contas WHERE id = ?");
                $stmt->execute([$_SESSION['conta_id']]);
            }

            $stmt = $mdb->prepare("DELETE FROM codigos_pendentes WHERE sessao = ?");
            $stmt->execute([$sessaoAtual]);

            $arquivoPessoal = isset($_SESSION['conta_db_file']) ? caminhoDB($_SESSION['conta_db_file']) : null;

            $_SESSION = array();
            session_destroy();

            $aviso = null;

            if ($arquivoPessoal !== null && is_file($arquivoPessoal)) {
                if (!@unlink($arquivoPessoal)) {
                    $erro = error_get_last();
                    $aviso = 'A conta foi removida, mas o arquivo do banco não pôde ser apagado do disco' .
                        ($erro ? ' (' . $erro['message'] . ')' : '') .
                        '. Verifique as permissões da pasta.';
                }

                foreach (array('-journal', '-wal', '-shm') as $sufixo) {
                    $arquivoExtra = $arquivoPessoal . $sufixo;
                    if (is_file($arquivoExtra)) {
                        @unlink($arquivoExtra);
                    }
                }
            }

            $resposta = ['sucesso' => true];
            if ($aviso !== null) {
                $resposta['aviso'] = $aviso;
            }

            echo jsonSaida($resposta);
            break;

                case 'live_publicar':
            verificarCSRF();

            $contaId = isset($_SESSION['conta_id']) ? (int)$_SESSION['conta_id'] : 0;
            if ($contaId <= 0) {
                echo jsonSaida(array('erro' => 'Conta não identificada.'));
                break;
            }

            $id       = isset($_POST['id'])       ? (int)$_POST['id']              : 0;
            $titulo   = isset($_POST['titulo'])   ? (string)$_POST['titulo']       : '';
            $conteudo = isset($_POST['conteudo']) ? (string)$_POST['conteudo']     : '';
            $rev      = isset($_POST['rev'])      ? (int)$_POST['rev']             : 0;

            if (isset($_POST['limpar']) && $_POST['limpar'] === '1') {
                if (!limparLive($contaId)) {
                    echo jsonSaida(array('erro' => 'Falha ao limpar.'));
                    break;
                }
                echo jsonSaida(array('sucesso' => true));
                break;
            }

            if ($id <= 0) {
                echo jsonSaida(array('erro' => 'Id inválido.'));
                break;
            }

            if (strlen($conteudo) > 1048576) {
                echo jsonSaida(array('erro' => 'Texto grande demais para o monitor.'));
                break;
            }

            $stmt = $db->prepare("SELECT 1 FROM textos WHERE id = ?");
            $stmt->execute(array($id));
            if (!$stmt->fetchColumn()) {
                echo jsonSaida(array('erro' => 'Texto não encontrado.'));
                break;
            }

            if (!publicarLive($contaId, $id, $titulo, $conteudo, $rev)) {
                echo jsonSaida(array('erro' => 'Falha ao publicar.'));
                break;
            }

            echo jsonSaida(array('sucesso' => true));
            break;

        case 'ping':
            echo jsonSaida(array('sucesso' => true));
            break;

        default:
            echo jsonSaida(array('erro' => 'Ação inválida'));
    }
    exit;
}

if (!isset($_SESSION['captcha_result'])) {
    $num1 = random_int(1, 9);
    $num2 = random_int(1, 9);
    $_SESSION['captcha_result'] = $num1 + $num2;
    $_SESSION['captcha_num1'] = $num1;
    $_SESSION['captcha_num2'] = $num2;
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}

$codigoAcesso = null;
if (!isset($_SESSION['logado'])) {
    try {
        $codigoAcesso = obterCodigoPendente();
    } catch (Exception $e) {
        error_log('iScreva: ' . $e->getMessage());
    }
}

$config = getConfiguracoes();
$logoImagem = logoDaTecla($config['tecla_imagem']);

$tokenMonitor = null;
$urlMonitor = null;

if (isset($_SESSION['logado'], $_SESSION['conta_id'])) {
    try {
        $mon = obterMonitorDaConta((int)$_SESSION['conta_id']);

        $esquema = requisicaoHTTPS() ? 'https' : 'http';
        $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
        $caminho = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');

        $urlMonitor = $esquema . '://' . $host . $caminho
                    . '/m/' . rawurlencode($mon['codigo_curto']);

        $urlMonitorFallback = $esquema . '://' . $host . $caminho
                    . '/monitor.php?c=' . rawurlencode($mon['codigo_curto']);
    } catch (Exception $e) {
        error_log('iScreva: ' . $e->getMessage());
    }
}

$neobrutalismo = isset($config['estilo_neobrutalista']) && $config['estilo_neobrutalista'] === '1';

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: " . gmdate("D, d M Y H:i:s", time() - 3600) . " GMT"); ?><!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>iScreva</title>
<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
<meta http-equiv="Pragma" content="no-cache">
<meta http-equiv="Expires" content="0">
<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">
<meta name="format-detection" content="telephone=no">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="iScreva">    
<meta name="referrer" content="strict-origin-when-cross-origin">
<meta name="robots" content="noindex,nofollow,noimageindex,noarchive">
<link rel="icon" type="image/x-icon" href="favicon.ico">
<link rel="apple-touch-icon" sizes="180x180" href="images/icone180.png">
<link rel="icon" type="image/png" sizes="192x192" href="images/icone192.png">
<link rel="icon" type="image/png" sizes="512x512" href="images/icone512.png">
<link rel="icon" type="image/png" sizes="32x32" href="images/icone32.png">
<link rel="icon" type="image/png" sizes="16x16" href="images/icone16.png">
<link rel="manifest" href="site.webmanifest">
<meta name="theme-color" content="<?php echo h($config['cor_barra']); ?>">
<style type="text/css">

@font-face {
  font-family: 'TT2020';
  font-style: normal;
  src: url(fonts/TT2020StyleE-Regular.ttf) format('truetype');
}

@font-face {
  font-family: Domitian;
  src: url(fonts/Domitian-Roman.otf);
  font-style: normal;
}
@font-face {
  font-family: MarkerFelt;
  src: url(fonts/MarkerFelt.ttf);
  font-style: normal;
}
@font-face {
  font-family: Merriweather;
  src: url(fonts/Merriweather-Regular.ttf);
  font-style: normal;
}
@font-face {
  font-family: Noteworthy;
  src: url(fonts/Noteworthy-Lt.ttf);
  font-style: normal;
}

html {
    -webkit-text-size-adjust: 100%;
}

a, input, textarea {
    -webkit-tap-highlight-color: rgba(0,0,0,0);
}

input,
textarea {
    margin: 0;
    padding: 0;
    border: 0;
    outline: none;
    background: none;
    font-size: 100%;
    font: inherit;
    vertical-align: baseline;
    color: inherit;
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    box-shadow: none;
    border-radius: 0;
}

*, *:before, *:after {
    -webkit-box-sizing: border-box;
    box-sizing: border-box;
}

html, body, div, span, p, a, img, small, strong,
form, label {
    margin: 0;
    padding: 0;
    border: 0;
    font-size: 100%;
    font: inherit;
    vertical-align: baseline;
}

table {
    margin:4px auto;
    border-collapse: collapse;
    border-spacing: 0;
    font-family: 'Domitian';
    font-size:0.85em;
}
td {
    padding:2px;
}
table, th, td {
  border: 1px solid;
}

img {
    max-width: 100%;
    height: auto;
    display: inline-block;
}

#login,
#lista,
#editor,
#ajuda{
    padding-top:15px;
    display:none;
    width:100%;
    height:100%;
    -webkit-box-sizing:border-box;
    box-sizing:border-box;
}

#login {
    display: none;
    position: absolute;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    text-align: center;
}

#loginCentro {
    position: absolute;
    left: 50%;
    top: 50%;
    width: 100%;
    max-width: 500px;

    -webkit-transform: translate(-50%, -50%);
    transform: translate(-50%, -50%);
}

label {font-size:21px;}

#login input {    
    width:100%;
    border-radius: 4px;
    margin-bottom:8px;
    font-size:21px;
    padding:4px;
    -webkit-box-sizing:border-box;
    box-sizing:border-box;
    display:inline-block;
}

.logo{
    text-align:center;
    margin:auto;
    max-width:222px;
    max-height:129px;
    position: relative;
    border: 1px solid <?php echo htmlspecialchars($config['cor_editor_texto']); ?>;
    -webkit-box-shadow:
        0 0 0 1px <?php echo htmlspecialchars($config['cor_barra']); ?>,
        0 0 0 2px <?php echo htmlspecialchars($config['cor_texto']); ?>,
        0 0 0 4px <?php echo htmlspecialchars($config['cor_editor_fundo']); ?>,
        0 0 0 5px <?php echo htmlspecialchars($config['cor_fundo']); ?>;
    box-shadow:
        0 0 0 1px <?php echo htmlspecialchars($config['cor_barra']); ?>,
        0 0 0 2px <?php echo htmlspecialchars($config['cor_texto']); ?>,
        0 0 0 4px <?php echo htmlspecialchars($config['cor_editor_fundo']); ?>,
        0 0 0 5px <?php echo htmlspecialchars($config['cor_fundo']); ?>;
}

.logop {text-align:inherit;
    margin:0;
    max-width:82px;
    max-height:48px;
        position: relative;
    border: 1px solid <?php echo htmlspecialchars($config['cor_editor_texto']); ?>;
    -webkit-box-shadow:
        0 0 0 1px <?php echo htmlspecialchars($config['cor_barra']); ?>,
        0 0 0 1px <?php echo htmlspecialchars($config['cor_texto']); ?>,
        0 0 0 2px <?php echo htmlspecialchars($config['cor_editor_fundo']); ?>,
        0 0 0 3px <?php echo htmlspecialchars($config['cor_fundo']); ?>;
    box-shadow:
        0 0 0 1px <?php echo htmlspecialchars($config['cor_barra']); ?>,
        0 0 0 1px <?php echo htmlspecialchars($config['cor_texto']); ?>,
        0 0 0 2px <?php echo htmlspecialchars($config['cor_editor_fundo']); ?>,
        0 0 0 3px <?php echo htmlspecialchars($config['cor_fundo']); ?>;
}

.frase,.gh {
    max-width:320px;
    margin:auto;
    padding:5px;
    border-radius:5px; 
    margin-top:10px;
}
.gh {text-align:center;}
.gh a {color:<?php echo htmlspecialchars($config['cor_texto']); ?>}

#editor{
    display:none;
    position:absolute;
    left:0;
    top:0;
    right:0;
    bottom:0;
    -webkit-box-orient:vertical;
    -webkit-flex-direction:column;
    flex-direction:column;
}

#visualizarTexto{
    display:none;
    position:absolute;
    left:0;
    top:0;
    right:0;
    bottom:0;
    z-index:10000;
    -webkit-box-orient:vertical;
    -webkit-flex-direction:column;
    flex-direction:column;
}

#visualizarTexto .frase {
    width:auto;
    max-width:100%;
    margin:0 auto;
    -webkit-box-sizing:border-box;
    box-sizing:border-box;
    -webkit-box-flex: 0;
    -webkit-flex: 0 0 auto;
    flex: 0 0 auto;
}

#conteudoDownload {
    -webkit-box-flex: 1;
    -webkit-flex: 1 1 auto;
    flex: 1 1 auto;
    min-height: 0;
}

.barra-superior, .barra-inferior {
    padding: 5px;
    display: -webkit-box;
    display: -webkit-flex;
    display: flex;
    -webkit-box-pack: justify;
    -webkit-justify-content: space-between;
    justify-content: space-between;
    -webkit-box-align: center;
    -webkit-align-items: center;
    align-items: center;    
}

.barra-inferior {
    -webkit-box-pack: center;
    -webkit-justify-content: center;
    justify-content: center;
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    z-index: 2;
}

#conteudoEditor {
    position: absolute;
    left: 0;
    top: 86px;
    right: 0;
    bottom: 64px;
    z-index: 1;   
}

textarea {
    padding: 22px 3px 10px 20px;
    overflow-y: scroll;
    -webkit-overflow-scrolling: touch;
    -webkit-font-smoothing: subpixel-antialiased;
    hyphens: auto;
    -ms-hyphens: auto;
    -moz-hyphens: auto;
    -webkit-hyphens: auto;
    font-kerning: normal;
    font-variant-ligatures: common-ligatures no-discretionary-ligatures historical-ligatures contextual;
    text-wrap: pretty;
}

textarea, #conteudoEditor {
    font-family: <?php
        $mapaFontes = array(
            'TT2020'      => "'TT2020', monospace",
            'monospace'   => 'monospace',
            'serif'       => 'serif',
            'sans-serif'  => 'sans-serif',
            'cursive'     => 'cursive',
            'domitian'    => "'Domitian', serif",
            'markerfelt'  => "'MarkerFelt', cursive",
            'merriweather' => "'Merriweather', serif",
            'noteworthy'  => "'Noteworthy', cursive"
        );
        echo isset($mapaFontes[$config['fonte_familia']]) ? $mapaFontes[$config['fonte_familia']] : "'TT2020', monospace";
    ?>;
    font-size: <?php echo number_format((float)$config['fonte_tamanho'], 2, '.', ''); ?>em;
    line-height: <?php echo number_format((float)$config['fonte_altura_linha'], 2, '.', ''); ?>;
    letter-spacing: <?php echo number_format((float)$config['fonte_espacamento'], 2, '.', ''); ?>px;
    font-weight: <?php
        $pesosValidos = array('normal', '300', '500', '600', 'bold', '800');
        echo in_array($config['fonte_peso'], $pesosValidos, true) ? $config['fonte_peso'] : 'normal';
    ?>;
}

body, textarea,#editor{
    width: 100%;
    max-width: 640px;
    margin: 0 auto;
}

.item{
    margin:4px 10px 4px 4px;
}

.item.selecionado {
    margin:4px 10px 4px 0px;
    padding-left:1px;
    border-left: 3px solid <?php echo htmlspecialchars($config['cor_texto']); ?>;
    border-right: 3px solid <?php echo htmlspecialchars($config['cor_texto']); ?>;
}

form {text-align: center;width:222px;margin:auto;}

.col-titulo {
    font-size:25px;
    cursor:pointer;
}

.col-data {
    font-size:16px;
}

a {display:inline-block}

.modal-overlay{
    display:none;
    position:absolute;
    left:0;
    top:0;
    width:100%;
    height:100%;
    z-index:9999;
    background:black;
}

.modal-box {
    width: 260px;
    min-height: 150px;
    max-height: 200px;
    margin: 0 auto;
    padding: 20px 10px;
    text-align: center;
    border-radius: 5px;
}

.modal-box p {
    font-family: 'TT2020', monospace;
    font-size: 18px;
    line-height: 1.35;
    margin-bottom: 15px;
}

.modal-box input[type="text"] {
    width: 100%;
    font-family: 'TT2020', monospace;
    font-size: 18px;
    padding: 8px;
    margin-bottom: 15px;
    border-radius: 4px;
    -webkit-box-sizing: border-box;
    box-sizing: border-box;
    background: #ffffff;
    color: #111111;
}

.esquerda{
    -webkit-box-flex:1;
    -webkit-flex:1;
    flex:1;
    min-width: 0;
}
.titulo-edicao {
    font-size: 16px;
    letter-spacing: -1px;
    display: block;
    max-width: 100%;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.contador {
    font-size: 11px;
    letter-spacing: -1px;
}

.direita{
    white-space:nowrap;
}

.tecla, .teclap {
    display: inline-block;
    display: -webkit-inline-box;
    -webkit-box-orient: horizontal;
    -webkit-box-align: center;
    -webkit-box-pack: center;
    width: 54px;
    height: 54px;
    background: url("images/<?php echo htmlspecialchars($config['tecla_imagem'], ENT_QUOTES, 'UTF-8'); ?>") no-repeat 0 0;
    text-align: center;
    line-height: 54px;
    font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
    font-size: 1.4em;
    text-decoration: none;
    color: <?php echo htmlspecialchars($config['cor_tecla_texto'], ENT_QUOTES, 'UTF-8'); ?>;
}

.teclap {
    width: 32px;
    height: 32px;  
    background-size: cover;  
    line-height: 32px;
    font-size:1em;
}


@media only screen and (min-width: 600px) {
    #lista,
    #ajuda {
        max-width: 800px;
        margin: 0 auto;
    }
}

@media only screen and (orientation: landscape) and (max-height: 400px) {
    #login {
        padding-top: 10px;
    }

    #login .logo img {
        width: 150px;
    }
}

html, body {
    background-color: #ffffff;
    color: #000000;
    background-color: <?php echo htmlspecialchars($config['cor_fundo']); ?>;
    color: <?php echo htmlspecialchars($config['cor_texto']); ?>;
    width:100%;
    height:100%;
    font-family:'TT2020', monospace;
    line-height: 1.2;
}

#login input {
    background-color: <?php echo htmlspecialchars($config['cor_fundo']); ?>;
    color: <?php echo htmlspecialchars($config['cor_texto']); ?>;
    border: 1px solid <?php echo htmlspecialchars($config['cor_texto']); ?>;
}

#login input::-webkit-input-placeholder {
    color: <?php echo htmlspecialchars($config['cor_texto']); ?>;
    opacity: 1;
}

#editor, #visualizarTexto {
    background-color: <?php echo htmlspecialchars($config['cor_fundo']); ?>;
}

.modal-box {
    background-color: <?php echo htmlspecialchars($config['cor_fundo']); ?>;
    color: <?php echo htmlspecialchars($config['cor_texto']); ?>;
}

.barra-superior,.barra-inferior {
    background-color: <?php echo htmlspecialchars($config['cor_barra']); ?>;
}

.barra-inferior,#conteudoEditor {border-top: 1px solid <?php echo htmlspecialchars($config['cor_editor_texto']); ?>;}

textarea, .frase {
    background-color: <?php echo htmlspecialchars($config['cor_editor_fundo']); ?>;
    color: <?php echo htmlspecialchars($config['cor_editor_texto']); ?>;
}

#conteudoEditor {
    color: <?php echo htmlspecialchars($config['cor_editor_texto']); ?>;
}

.col-titulo span,.col-data span{
    color: <?php echo htmlspecialchars($config['cor_fundo']); ?>;
    background-color: <?php echo htmlspecialchars($config['cor_texto']); ?>;
}

.modo-foco #conteudoEditor {
    top: 0 !important;
    bottom: 0 !important;
}

.modo-foco .barra-superior,
.modo-foco .barra-inferior {
    display: none !important;
}

#conteudoImpressao {
    display: none;
}


.balao {
    position: relative;
    display: inline-block;
}

.balao a {
   color: <?php echo htmlspecialchars($config['cor_texto']); ?>;
    text-decoration: none;
}

.balao .texto-balao {
    display: none;
    position: absolute;
    bottom: 30px;
    left: 50%;
    width: 220px;
    margin-left: -110px;
    padding: 15px;
    background-color: <?php echo htmlspecialchars($config['cor_fundo']); ?>;
    color: <?php echo htmlspecialchars($config['cor_texto']); ?>;
    border: 4px solid <?php echo htmlspecialchars($config['cor_texto']); ?>;
    font-size: 15px;
    line-height: 1.4;
    -webkit-box-shadow: 0 2px 6px rgba(0,0,0,0.3);
}

.balao .texto-balao:target {
    display: block;
}
.xbalao {float:right;font-family:sans-serif;font-size:1.7em;}

.vazio { padding: 15px; font-size: 21px; }

#loginErro { margin-top: 10px; font-size: 16px; min-height: 20px; }

/* ===================================================
IMPRESSAO */

@page {
  size: A4 portrait;
  margin: 1in;
}

@media print {
  body {
    background: #ffffff !important;
    color: #000000 !important;
    max-width: none !important;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
  }

#login, #lista, #ajuda, .direita, .contador, .modal-overlay, #conteudoEditor, .barra-inferior {
    display: none !important;
}

  .barra-superior {
    background: none !important;
    padding: 0 !important;
  }

  #editor {
    display: block !important;
    position: static !important;
    width: 100% !important;
    max-width: none !important;
    height: auto !important;
    background: #ffffff !important;
    color: #000000 !important;
  }

  #conteudoImpressao, .titulo-edicao {
    display: block !important;
    position: static !important;
    width: auto !important;
    height: auto !important;
    min-height: 0 !important;
    margin: 0 !important;
    padding: 0 !important;
    border: 0 !important;
    background: #ffffff !important;
    color: #000000 !important;
    font-family: "TT2020", "Courier New", Courier, monospace !important;
    font-size: 12pt !important;
    line-height: 2 !important;
    font-weight: normal !important;
    letter-spacing: 0 !important;
    word-wrap: break-word !important;
  }
    .titulo-edicao {
        font-weight: bold !important;
        text-transform: uppercase !important;
}

  #conteudoImpressao p {
    margin: 0 !important;
    padding: 0 !important;
    text-indent: 0.5in !important;
    text-align: left !important;
    orphans: 2 !important;
    widows: 2 !important;
    white-space: normal !important;
    word-wrap: break-word !important;

    hyphens: auto;
    -ms-hyphens: auto;
    -moz-hyphens: auto;
    -webkit-hyphens: auto;
    font-kerning: normal;
    font-variant-ligatures: common-ligatures no-discretionary-ligatures historical-ligatures contextual;
    text-wrap: pretty;
  }
}

/* ============================================================
   ESTILO NEOBRUTALISTA
   ============================================================ */

body.neobrutalista {
    background-color: #FFE600;
    color: #000000;
    font-family: 'TT2020', monospace;
}

body.neobrutalista #editor,
body.neobrutalista #visualizarTexto {
    background-color: #FFE600;
}

/* ---------- Login ---------- */

body.neobrutalista #login input {
    background-color: #FFFFFF;
    color: #000000;
    border: 3px solid #000000;
    -webkit-box-shadow: 4px 4px 0 #000000;
    box-shadow: 4px 4px 0 #000000;
    border-radius: 0;
    font-weight: bold;
}

body.neobrutalista #login input::-webkit-input-placeholder {
    color: #888888;
    opacity: 1;
}

body.neobrutalista #login input:-moz-placeholder {
    color: #888888;
    opacity: 1;
}

body.neobrutalista #loginErro {
    color: #CC0000;
    font-weight: bold;
    text-shadow: 1px 1px 0 #FFFFFF;
}

/* ---------- Logo ---------- */

body.neobrutalista .logo {
    background-color: #FFFFFF;
    border: 4px solid #000000;
    -webkit-box-shadow: 8px 8px 0 #000000;
    box-shadow: 8px 8px 0 #000000;
}

body.neobrutalista .logop {
    background-color: #FFFFFF;
    border: 3px solid #000000;
    -webkit-box-shadow: 4px 4px 0 #000000;
    box-shadow: 4px 4px 0 #000000;
}

/* ---------- Barras ---------- */

body.neobrutalista .barra-inferior {
   background-color: #FFE600;
}

body.neobrutalista .barra-superior {
    background-color: #FFFFFF;
}

body.neobrutalista .barra-superior {
    border-bottom: 3px solid #000000;
}

body.neobrutalista .barra-inferior {
    border-top: 3px solid #000000;
}

body.neobrutalista .barra-inferior,
body.neobrutalista #conteudoEditor {
    border-top: 3px solid #000000;
}

/* ---------- Editor ---------- */

body.neobrutalista textarea,
body.neobrutalista .frase {
    background-color: #FFFFFF;
    color: #000000;
}

body.neobrutalista #conteudoEditor {
    color: #000000;
    border-left: 3px solid #000000;
    border-right: 3px solid #000000;
}

body.neobrutalista .frase {
    border: 3px solid #000000;
    -webkit-box-shadow: 4px 4px 0 #000000;
    box-shadow: 4px 4px 0 #000000;
    border-radius: 0;
}

/* ---------- Lista de textos ---------- */

body.neobrutalista .item {
    background-color: #FFFFFF;
    border: 3px solid #000000;
    -webkit-box-shadow: 4px 4px 0 #000000;
    box-shadow: 4px 4px 0 #000000;
    margin: 8px 10px 8px 4px;
    padding: 10px 12px;
    border-radius: 0;
}

body.neobrutalista .item.selecionado {
    background-color: #FF90E8;
    border-left: 3px solid #000000;
    border-right: 3px solid #000000;
    padding-left: 12px;
    margin-left: 4px;
}

body.neobrutalista .col-titulo span,
body.neobrutalista .col-data span {
    color: #000000;
    background-color: transparent;
}

body.neobrutalista .vazio {
    color: #000000;
    font-weight: bold;
}

/* ---------- Botões (teclas em CSS puro) ---------- */

body.neobrutalista .tecla,
body.neobrutalista .teclap {
    background-image: none;
    background-color: #FF90E8;
    color: #000000;
    border: 3px solid #000000;
    -webkit-box-shadow: 4px 4px 0 #000000;
    box-shadow: 4px 4px 0 #000000;
    border-radius: 0;
    font-family: 'TT2020', monospace;
    font-weight: bold;
    -webkit-box-sizing: border-box;
    box-sizing: border-box;
    padding: 0 8px;
    text-align: center;
    text-decoration: none;
    vertical-align: middle;
    -webkit-transition: none;
    transition: none;
}

body.neobrutalista .tecla {
    min-width: 54px;
    height: 54px;
    line-height: 48px;
    font-size: 1.4em;
}

body.neobrutalista .teclap {
    min-width: 32px;
    height: 32px;
    line-height: 26px;
    font-size: 0.95em;
    padding: 0 4px;
    -webkit-box-shadow: 2px 2px 0 #000000;
    box-shadow: 2px 2px 0 #000000;
}

body.neobrutalista .tecla:active,
body.neobrutalista .teclap:active {
    -webkit-box-shadow: 1px 1px 0 #000000;
    box-shadow: 1px 1px 0 #000000;
    -webkit-transform: translate(3px, 3px);
    transform: translate(3px, 3px);
}

body.neobrutalista .barra-inferior .tecla {
    margin: 0 5px;
}

body.neobrutalista .barra-inferior {
    padding: 8px 4px;
}

/* ---------- Balão "Primeiro acesso?" ---------- */

body.neobrutalista .balao a {
    color: #000000;
    text-decoration: underline;
}

body.neobrutalista .balao .texto-balao {
    background-color: #FFFFFF;
    color: #000000;
    border: 3px solid #000000;
    -webkit-box-shadow: 6px 6px 0 #000000;
    box-shadow: 6px 6px 0 #000000;
    border-radius: 0;
}

/* ---------- Modal ---------- */

body.neobrutalista .modal-box {
    background-color: #FFFFFF;
    color: #000000;
    border: 4px solid #000000;
    -webkit-box-shadow: 8px 8px 0 #000000;
    box-shadow: 8px 8px 0 #000000;
    border-radius: 0;
}

body.neobrutalista .modal-box input[type="text"] {
    background-color: #FFE600;
    color: #000000;
    border: 3px solid #000000;
    border-radius: 0;
}

/* ---------- Tabela de ajuda ---------- */

body.neobrutalista table {
    background-color: #FFFFFF;
    border-collapse: separate;
    border-spacing: 0;
    border: 3px solid #000000;
    -webkit-box-shadow: 6px 6px 0 #000000;
    box-shadow: 6px 6px 0 #000000;
}

body.neobrutalista table,
body.neobrutalista th,
body.neobrutalista td {
    border: 2px solid #000000;
}

body.neobrutalista th {
    background-color: #FFE600;
    font-weight: bold;
}

body.neobrutalista td {
    background-color: #FFFFFF;
}

body.neobrutalista code {
    background-color: #FFFFFF;
    border: 2px solid #000000;
    padding: 2px 6px;
    border-radius: 0;
}
</style>
</head>
<body<?php if ($neobrutalismo) echo ' class="neobrutalista"'; ?>>

<div id="login">
    <div id="loginCentro">
        <div class="logo"><img src="images/<?php echo htmlspecialchars($logoImagem, ENT_QUOTES, 'UTF-8'); ?>" alt="iScreva"></div><br><br>        
        <form id="formLogin" onsubmit="return false;">        
            <input placeholder="senha" type="password" id="senha" autofocus>
            <input placeholder="<?php echo $_SESSION['captcha_num1'] . '+' . $_SESSION['captcha_num2'] . '='; ?>" type="text" id="captcha" inputmode="numeric" pattern="[0-9]*">
            <br>
            <a title="Login" class="tecla" href="#" onclick="fazerLogin(); return false;">&#x21B5;</a>
            <div id="loginErro" class="erro"></div>
        </form>
        <?php if ($codigoAcesso !== null): ?>
            <div class="balao"><br>
                <small><a href="#explicacao">Primeiro acesso?</a></small>
                <div id="explicacao" class="texto-balao">
                    <a class="xbalao" href="#">X</a><br><br>
                    Use essa senha para criar sua conta: <strong><?php echo htmlspecialchars($codigoAcesso); ?></strong>
                </div>                
            </div>
        <?php endif; ?>
    </div>
</div>

<div id="lista">
    <div class="barra-superior">
        <div class="esquerda"><div class="logop"><img src="images/<?php echo htmlspecialchars($logoImagem, ENT_QUOTES, 'UTF-8'); ?>" alt="iScreva"></div></div>
        <div class="direita">
        <a title="Novo/New" class="tecla" href="#" onclick="novoTexto(); return false;">&#x271A;</a> 
        <a title="Ajuda/Help" class="tecla" href="#" onclick="mostrarTela('ajuda'); return false;">?</a> 
        <a title="Fechar/Close" class="tecla" href="#" onclick="deslogar(); return false;">&#x241B;</a>
        </div>        
    </div>
    
    <div id="listaConteudo"></div>
    
</div>

<div id="ajuda">
    <div class="barra-superior">
        <div class="esquerda"><div class="logop"><img src="images/<?php echo htmlspecialchars($logoImagem, ENT_QUOTES, 'UTF-8'); ?>" alt="iScreva"></div></div>
        <div class="direita">
            <a title="Configurações/Config." class="tecla" href="config.php">C</a>           
            <a title="Fechar/Close" class="tecla" href="#" onclick="mostrarTela('lista'); carregarLista(); return false;">&#x241B;</a> 
        </div>
    </div>
<table>
    <tr>
        <th>Tecla</th><th>Função</th><th>Atalho</th>
    </tr>
    <tr>
        <td><a class="teclap" href="#">&#x21B5;</a></td><td>Entrar / Login</td><td>Enter</td>
    </tr><tr>
        <td><a class="teclap" href="#">&#x2b;</a></td><td>Novo / New</td><td>N</td>
    </tr><tr>
        <td><a class="teclap" href="#">?</a></td><td>Ajuda / Help</td><td>H ou ?</td>
    </tr><tr>
        <td><a class="teclap" href="#">C</a></td><td>Configurações / Config.</td><td>C</td>
    </tr><tr>
        <td><a class="teclap" href="#">&#x2421;</a></td><td>Apagar / Delete</td><td>Ctrl + ⌫</td>
    </tr><tr>
        <td><a class="teclap" href="#">D</a></td><td>Baixar / Download</td><td>Ctrl + D</td>
    </tr><tr>
        <td><a class="teclap" href="#">&#x241B;</a></td><td>Fechar / Close</td><td>Esc*</td>
    </tr><tr>
        <td><a class="teclap" href="#">M</a></td><td>Enviar por email / Send by email</td><td>Ctrl + M</td>
    </tr><tr>
        <td><a class="teclap" href="#">P</a></td><td>Imprimir / Print</td><td>Ctrl + P</td>
    </tr><tr>
        <td><a class="teclap" href="#">No</a></td><td>Não / No</td><td>Esc* ou N</td>
    </tr><tr>
        <td><a class="teclap" href="#">Si</a></td><td>Sim / Yes</td><td>Enter, Y ou S</td>
    </tr>
    <tr>
        <td></td><td>Escolher texto</td><td>↑/↓</td>
    </tr>
    <tr>
        <td></td><td>Abrir texto</td><td>Enter</td>
    </tr>
    <tr>
        <td></td><td>Salvar (mas é automático)</td><td>Ctrl+S</td>
    </tr>
    <tr>
        <td></td><td>* O funcionamento depende do teclado e do dispositivo usados.</td><td></td>
    </tr>
</table>

<div class="frase" style="max-width:inherit;">
<?php if ($urlMonitor !== null): ?>
        <strong>Espelhar em outro aparelho</strong><br><br>
        <code style="word-break:break-all;"><?php echo h($urlMonitor); ?></code> ou <br><?php if (isset($urlMonitorFallback)): ?><code style="word-break:break-all;"><?php echo h($urlMonitorFallback); ?></code>
        <?php endif; ?>
<?php endif; ?></div>
<br>
<div class="frase"><small>"Screvo meu livro à beira-mágoa." Fernando Pessoa</small></div>
<div class="gh"><small><a href="https://github.com/robertostrabelli/iScreva">GitHub/iScreva</a></small></div>
</div>

<div id="editor">
    <div class="barra-superior">
        <div class="esquerda">
            <span class="titulo-edicao" id="tituloEditor">(título)</span>
            <span class="contador" id="contadorPalavras">0 palavras</span>
            <span class="contador" id="contadorCaracteres">0 caracteres</span>
            <span class="contador" id="tempoLeitura">0 min. de leitura</span>
        </div>
        <div class="direita">
            <a title="Fechar/Close" class="tecla" href="#" onclick="fecharEditor(); return false;">&#x241B;</a>            
        </div>
    </div>
    <textarea id="conteudoEditor" wrap="soft" autocorrect="off" autocapitalize="off" autocomplete="off" spellcheck="false"></textarea>
    <div class="barra-inferior">
        <a title="Apagar/Delete" class="tecla" href="#" onclick="confirmarExclusao(textoAtualId); return false;">&#x2421;</a>
        <a title="Baixar/Download" class="tecla" href="#" onclick="salvarAntes(function() { baixarTexto(textoAtualId); }); return false;">D</a>
        <a title="Enviar por e-mail/Send by e-mail" class="tecla" href="#" onclick="salvarAntes(function() { enviarPorEmailId(textoAtualId); }); return false;">M</a>
        <a title="Imprimir/Print" class="tecla" href="#" onclick="salvarAntes(function() { imprimirTextoId(textoAtualId); }); return false;">P</a>
    </div>
    <div id="conteudoImpressao"></div>
</div>

<div id="visualizarTexto">
    <div class="barra-superior">
        <div class="esquerda"><span class="titulo-edicao" id="tituloDownload">(título)</span></div>
        <div class="direita">
            <a class="tecla" href="#" onclick="fecharVisualizacaoDownload(); return false;">&#x241B;</a>
        </div>
    </div>
    <div class="frase"><small>Seu dispositivo não permite salvar o arquivo diretamente. Toque e segure sobre o texto abaixo e escolha "Selecionar tudo" e "Copiar", depois cole onde quiser.</small></div>
    <textarea id="conteudoDownload" wrap="soft" autocorrect="off" autocapitalize="off" autocomplete="off" spellcheck="false"></textarea>
</div>

<div class="modal-overlay" id="modalOverlay">
    <div class="modal-box">
        <p id="modalMensagem">Deseja salvar antes de fechar?</p>
        <input type="text" id="modalInput" style="display:none;">
        <div>
            <a title="Sim/Yes" class="tecla" href="#" onclick="modalResponder(true); return false;">Si</a> 
            <a title="Não/No" class="tecla" href="#" onclick="modalResponder(false); return false;">No</a> 
        </div>
    </div>
</div>

<script>

var usuarioRolando = false;
var scrollProgramado = false;
var timeoutRolagem = null;
var ultimaPosicaoCursor = -1;
var textoAtualId = null;
var textoAtualTitulo = '';
var conteudoOriginal = '';
var acaoPendente = null;
var CSRF_TOKEN = <?php echo jsonParaScript($_SESSION['csrf_token']); ?>;
var timeoutSalvar = null;
var ultimoSalvamentoConcluido = 0;
var timerForcado = null;
var DEBOUNCE_PARADA = 150;      // ms após a última tecla
var INTERVALO_MAXIMO = 600;     // força save mesmo digitando sem parar
var liveRev = (new Date()).getTime();   // não reinicia em 0 ao recarregar
var liveEmVoo = false;
var livePendente = false;
var liveLimpar = false;
var liveTimeout = null;
var liveForcado = null;
var DEBOUNCE_LIVE = 60;     // ms após a última tecla
var LIVE_MAX = 250;         // força publicação mesmo digitando sem parar
var salvamentoEmAndamento = false;
var salvamentoPendente = false;
var callbacksSalvamento = [];
var modoFocoAtivo = false;
var falhasSalvamento = 0;
var CONTA_ID = <?php echo isset($_SESSION['conta_id']) ? (int)$_SESSION['conta_id'] : 0; ?>;
var acaoAposFecharEditor = null;
var itensLista = [];
var indiceListaSelecionado = 0;
var ultimoTextoAbertoId = null;

function ativarModoFoco() {
    if (!modoFocoAtivo) {
        document.body.classList.add('modo-foco');
        modoFocoAtivo = true;
    }
}

function desativarModoFoco() {
    if (modoFocoAtivo) {
        document.body.classList.remove('modo-foco');
        modoFocoAtivo = false;
    }
}

function $(id) { return document.getElementById(id); }

function editorVisivel() {
    var d = $('editor').style.display;
    return d !== '' && d !== 'none';
}

function chaveRascunho(id) {
    return 'iscreva_rascunho_' + CONTA_ID + '_' + id;
}

function guardarRascunho(id, conteudo) {
    try { localStorage.setItem(chaveRascunho(id), conteudo); } catch (e) {}
}

function lerRascunho(id) {
    try { return localStorage.getItem(chaveRascunho(id)); } catch (e) { return null; }
}

function removerRascunho(id) {
    try { localStorage.removeItem(chaveRascunho(id)); } catch (e) {}
}

function publicarLiveAgora() {
    clearTimeout(liveTimeout);
    liveTimeout = null;
    clearTimeout(liveForcado);
    liveForcado = null;

    if (!textoAtualId && !liveLimpar) return;

    if (liveEmVoo) { livePendente = true; return; }
    liveEmVoo = true;

    liveRev++;

    var corpo;
    if (liveLimpar) {
        liveLimpar = false;
        corpo = 'limpar=1&rev=' + encodeURIComponent(liveRev) +
                '&csrf=' + encodeURIComponent(CSRF_TOKEN);
    } else {
        corpo =
            'id=' + encodeURIComponent(textoAtualId) +
            '&titulo=' + encodeURIComponent(textoAtualTitulo) +
            '&conteudo=' + encodeURIComponent($('conteudoEditor').value) +
            '&rev=' + encodeURIComponent(liveRev) +
            '&csrf=' + encodeURIComponent(CSRF_TOKEN);
    }

    var xhr = new XMLHttpRequest();
    xhr.open('POST', '?action=live_publicar', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
    try { xhr.timeout = 10000; } catch (e) {}
    xhr.onreadystatechange = function() {
        if (xhr.readyState !== 4) return;
        liveEmVoo = false;
        if (livePendente) {
            livePendente = false;
            publicarLiveAgora();   // lê o texto mais recente do textarea
        }
    };
    xhr.send(corpo);
}

function agendarLive() {
    clearTimeout(liveTimeout);
    liveTimeout = setTimeout(publicarLiveAgora, DEBOUNCE_LIVE);

    if (liveForcado === null) {
        liveForcado = setTimeout(function() {
            liveForcado = null;
            publicarLiveAgora();
        }, LIVE_MAX);
    }
}

function salvarAntes(fn) {
    if (!textoAtualId) {
        alert('Aguarde um instante e tente de novo.');
        return;
    }

    if ($('conteudoEditor').value === conteudoOriginal && !salvamentoEmAndamento) {
        fn();
        return;
    }

    salvarTexto(function(sucesso) {
        if (sucesso) {
            fn();
        }
    });
}

function mostrarTela(tela) {
    var telas = ['login', 'lista', 'editor', 'ajuda'];
    for (var i = 0; i < telas.length; i++) {
        var el = $(telas[i]);
        if (telas[i] === tela) {
            if (tela === 'editor') {

    el.style.display = '-webkit-box';
    el.style.display = '-webkit-flex';
    el.style.display = 'flex';

} else {
                el.style.display = 'block';
            }
        } else {
            el.style.display = 'none';
        }
    }
    esconderBarraEndereco();
}

function esconderBarraEndereco() {
    window.scrollTo(0, 1);
}

function ajax(method, url, dados, callback) {
    var xhr = new XMLHttpRequest();
    xhr.open(method, url, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');

    xhr.onreadystatechange = function() {
        if (xhr.readyState !== 4) return;

        if (xhr.status !== 200) {
            callback({erro: 'Erro de conexão'});
            return;
        }

        var texto = xhr.responseText || '';

        // Remove BOM UTF-8, BOM UTF-16, NBSP e espaços iniciais.
        texto = texto.replace(/^[\uFEFF\uFFFE\xEF\xBB\xBF\s\xA0]+/, '');

        var resp;
        try {
            resp = JSON.parse(texto);
        } catch (e) {
            if (window.console && console.log) {
                console.log('Resposta bruta:', JSON.stringify(texto.substring(0, 300)));
            }
            callback({erro: 'Erro ao processar resposta'});
            return;
        }

        callback(resp);
    };

    var arr = [];
    if (dados) {
        for (var key in dados) {
            if (dados.hasOwnProperty(key)) {
                arr.push(encodeURIComponent(key) + '=' + encodeURIComponent(dados[key]));
            }
        }
    }
    if (method === 'POST') {
        arr.push('csrf=' + encodeURIComponent(CSRF_TOKEN));
    }
    xhr.send(arr.join('&'));
}

function exibirErro(msg) {
    $('loginErro').textContent = msg;
}

function guardarErroLogin(msg) {
    try {
        sessionStorage.setItem('iscreva_erro_login', msg);
        return true;
    } catch (e) {
        return false;
    }
}

function fazerLogin() {
    var senha = $('senha').value;
    var captcha = $('captcha').value;
    if (!senha || !captcha) {
        exibirErro('Preencha todos os campos');
        return;
    }
    ajax('POST', '?action=login', {senha: senha, captcha: captcha}, function(resp) {
        if (resp.sucesso) {
            window.location.reload();
        } else {
            var msg = resp.erro || 'Falha no login';
            if (!guardarErroLogin(msg)) {
                alert(msg);
            }
            window.location.reload();
        }
    });
}

function carregarLista() {
    ajax('GET', '?action=listar', null, renderizarLista);
}

function renderizarLista(resp) {
    if (resp && resp.erro) {
        if (resp.erro === 'Não autenticado') {
            mostrarTela('login');
        } else {
            alert(resp.erro);
        }
        return;
    }

    var html = '';
    if (resp.length === 0) {
        html = '<div class="vazio">Nenhum texto ainda.<br>Clique em "&#x271A;" para começar.</div>';
    } else {
        for (var i = 0; i < resp.length; i++) {
            var item = resp[i];
            var data = item.data_modificacao ? item.data_modificacao.substring(0, 16) : '';
            html += '<div class="item" data-id="' + item.id + '">';
            html += '<div class="col-titulo" onclick="abrirTexto(' + item.id + ')"><span>' + escapeHtml(item.titulo) + '</span></div>';
            html += '<div class="col-data"><span>' + data + '</span></div>';
            html += '</div>';
        }
    }

    $('listaConteudo').innerHTML = html;

    // Reindexa a lista e reaplica a seleção.
    atualizarItensLista();

    var indiceDesejado = 0;
    if (ultimoTextoAbertoId !== null) {
        for (var j = 0; j < itensLista.length; j++) {
            if (parseInt(itensLista[j].getAttribute('data-id'), 10) === ultimoTextoAbertoId) {
                indiceDesejado = j;
                break;
            }
        }
    }

    destacarItemLista(indiceDesejado);
}

function atualizarItensLista() {
    itensLista = document.querySelectorAll('#listaConteudo .item');
}

function destacarItemLista(indice) {
    if (!itensLista || itensLista.length === 0) {
        indiceListaSelecionado = 0;
        return;
    }

    for (var i = 0; i < itensLista.length; i++) {
        itensLista[i].className = 'item';
    }

    if (indice < 0) indice = 0;
    if (indice >= itensLista.length) indice = itensLista.length - 1;

    indiceListaSelecionado = indice;
    itensLista[indice].className = 'item selecionado';

    // Rola a tela se o item estiver fora da área visível.
    try {
        var rect = itensLista[indice].getBoundingClientRect();
        var altura = window.innerHeight || document.documentElement.clientHeight;
        if (rect.top < 0) {
            itensLista[indice].scrollIntoView(true);
        } else if (rect.bottom > altura) {
            itensLista[indice].scrollIntoView(false);
        }
    } catch (e) {}
}

function moverSelecaoLista(delta) {
    if (itensLista.length === 0) return;
    destacarItemLista(indiceListaSelecionado + delta);
}

function abrirItemSelecionado() {
    if (itensLista.length === 0) return;
    if (indiceListaSelecionado < 0 || indiceListaSelecionado >= itensLista.length) return;

    var el = itensLista[indiceListaSelecionado];
    var id = parseInt(el.getAttribute('data-id'), 10);

    if (id) {
        abrirTexto(id);
    }
}

function escapeHtml(text) {
    var div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function novoTexto() {
    acaoPendente = 'criar';
    $('modalMensagem').textContent = 'Título do novo texto:';

    var input = $('modalInput');
    input.value = 'Meu texto';
    input.style.display = 'block';

    $('modalOverlay').style.display = '-webkit-box';

    setTimeout(function() {
        input.focus();
        try { input.select(); } catch (e) {}
    }, 50);
}

function abrirTexto(id) {
    ultimoTextoAbertoId = parseInt(id, 10);
    var ta = $('conteudoEditor');

    textoAtualId = parseInt(id, 10);
    textoAtualTitulo = '';
    conteudoOriginal = '';
    falhasSalvamento = 0;

    var idPedido = textoAtualId;

    $('tituloEditor').textContent = 'Carregando…';
    ta.value = '';
    ta.readOnly = true;

    clearTimeout(timeoutSalvar);
    atualizarContador();
    mostrarTela('editor');

    ta.focus();

    ajax(
        'GET',
        '?action=carregar&id=' + encodeURIComponent(id),
        null,
        function(resp) {

            if (textoAtualId !== idPedido) {
                return;
            }

            if (resp.erro) {
                ta.readOnly = false;
                if (resp.erro === 'Não autenticado') {
                    mostrarTela('login');
                } else {
                    alert(resp.erro);
                    mostrarTela('lista');
                    carregarLista();
                }
                return;
            }

            textoAtualId = parseInt(resp.id, 10);
            textoAtualTitulo = resp.titulo || 'Sem título';
            conteudoOriginal = resp.conteudo || '';

            $('tituloEditor').textContent = textoAtualTitulo;
            ta.value = conteudoOriginal;
            ta.readOnly = false;

            atualizarContador();

            try {
                ta.selectionStart = ta.selectionEnd = ta.value.length;
            } catch (e) {}

            esconderBarraEndereco();
            setTimeout(esconderBarraEndereco, 300);

            verificarRascunho();
            publicarLiveAgora();
            
        }
    );
}

function verificarRascunho() {
    var r = lerRascunho(textoAtualId);

    if (r === null) {
        return;
    }

    if (r === conteudoOriginal) {
        removerRascunho(textoAtualId);
        return;
    }

    acaoPendente = 'rascunho';
    $('modalMensagem').textContent = 'Existe uma versão deste texto que não chegou a ser salva no servidor. Recuperar?';
    $('modalOverlay').style.display = '-webkit-box';
}

var suportaDownloadArquivo = (function() {
    try {
        return 'download' in document.createElement('a');
    } catch (e) {
        return false;
    }
})();

function baixarTexto(id) {
    if (suportaDownloadArquivo) {
        window.location.href = '?action=download&id=' + encodeURIComponent(id);
        return;
    }

    ajax('GET', '?action=carregar&id=' + encodeURIComponent(id), null, function(resp) {
        if (resp.erro) {
            alert('Erro ao carregar o texto.');
            return;
        }
        exibirVisualizacaoDownload(resp.conteudo || '', resp.titulo || 'texto');
    });
}

function exibirVisualizacaoDownload(conteudo, titulo) {
    var ta = $('conteudoDownload');
    $('tituloDownload').textContent = titulo || 'texto';
    ta.value = conteudo;

    $('visualizarTexto').style.display = '-webkit-box';
    $('visualizarTexto').style.display = '-webkit-flex';
    $('visualizarTexto').style.display = 'flex';

    esconderBarraEndereco();

    setTimeout(function() {
        try {
            ta.focus();
            ta.setSelectionRange(0, ta.value.length);
        } catch (e) {
            try { ta.select(); } catch (e2) {}
        }
    }, 100);
}

function fecharVisualizacaoDownload() {
    $('visualizarTexto').style.display = 'none';
    esconderBarraEndereco();
}

function salvarSeNecessario() {
    clearTimeout(timeoutSalvar);
    timeoutSalvar = null;

    if (timerForcado !== null) {
        clearTimeout(timerForcado);
        timerForcado = null;
    }

    var conteudoAtual = $('conteudoEditor').value;
    if (conteudoAtual === conteudoOriginal) {
        return;
    }

    if (!textoAtualId) {
        agendarSalvar();
        return;
    }

    salvarTexto();
}

function agendarSalvar() {
    // Debounce normal: dispara após uma pausa curta na digitação.
    clearTimeout(timeoutSalvar);
    timeoutSalvar = setTimeout(salvarSeNecessario, DEBOUNCE_PARADA);

    // Enquanto o texto estiver mudando, garante que um save aconteça
    // pelo menos a cada INTERVALO_MAXIMO — senão quem escreve sem parar
    // nunca veria o monitor atualizar.
    if (timerForcado === null) {
        var desdeUltimo = (new Date()).getTime() - ultimoSalvamentoConcluido;
        var falta = Math.max(100, INTERVALO_MAXIMO - desdeUltimo);

        timerForcado = setTimeout(function() {
            timerForcado = null;
            salvarSeNecessario();
        }, falta);
    }
}

function salvarTexto(callback) {

    if (callback) {
        callbacksSalvamento.push(callback);
    }

    clearTimeout(timeoutSalvar);
    timeoutSalvar = null;

    if (!textoAtualId) {
        finalizarSalvamentos(false);
        return;
    }

    var conteudoAtual = $('conteudoEditor').value;

    if (conteudoAtual === conteudoOriginal) {

        if (!salvamentoEmAndamento) {
            finalizarSalvamentos(true);
        }

        return;
    }

    if (salvamentoEmAndamento) {
        salvamentoPendente = true;
        return;
    }

    salvamentoEmAndamento = true;

    var id = textoAtualId;
    var conteudo = conteudoAtual;

    guardarRascunho(id, conteudo);

    ajax(
        'POST',
        '?action=salvar',
        {
            id: id,
            conteudo: conteudo
        },
        function(resp) {

            salvamentoEmAndamento = false;

            if (id !== textoAtualId) {
                if (resp.sucesso) {
                    removerRascunho(id);
                }
                salvamentoPendente = false;
                finalizarSalvamentos(!!resp.sucesso);
                return;
            }

            

            if (!resp.sucesso) {

                salvamentoPendente = false;
                falhasSalvamento++;

                $('tituloEditor').textContent = textoAtualTitulo + ' ⚠';

                var semSessao = (resp.erro === 'Não autenticado' ||
                                 resp.erro === 'Sessão expirada, recarregue a página');

                if (semSessao) {
                    alert('Sua sessão expirou. O texto continua guardado neste aparelho: recarregue a página, entre de novo e abra o texto para recuperá-lo.');
                } else if (callbacksSalvamento.length > 0) {
                    alert('Não foi possível salvar agora. O texto continua aberto.');
                } else {
                    clearTimeout(timeoutSalvar);
                    timeoutSalvar = setTimeout(salvarSeNecessario, Math.min(30000, 2000 * falhasSalvamento));
                }

                finalizarSalvamentos(false);

                return;
            }

            ultimoSalvamentoConcluido = (new Date()).getTime();
            falhasSalvamento = 0;
            conteudoOriginal = conteudo;
            removerRascunho(id);

            atualizarContador();

            var tituloEl = $('tituloEditor');

            tituloEl.textContent =
                textoAtualTitulo + ' ✓';

            setTimeout(function() {
                tituloEl.textContent = textoAtualTitulo;
            }, 800);

            if ($('conteudoEditor').value !== conteudoOriginal) {
                salvamentoPendente = false;
                agendarSalvar();
                return;
            }

            salvamentoPendente = false;

            finalizarSalvamentos(true);
        }
    );
}

function finalizarSalvamentos(sucesso) {

    var callbacks = callbacksSalvamento.slice();

    callbacksSalvamento = [];

    for (var i = 0; i < callbacks.length; i++) {
        callbacks[i](sucesso);
    }
}

function reverterTexto() {
    acaoPendente = 'reverter';
    $('modalMensagem').textContent = 'Descartar alterações não salvas e recarregar a versão salva?';
    $('modalOverlay').style.display = '-webkit-box';
}

function fecharEditor() {
    var conteudoAtual = $('conteudoEditor').value;
    if (conteudoAtual !== conteudoOriginal) {
        acaoPendente = 'fechar';
        $('modalMensagem').textContent = 'Há alterações não salvas. Salvar antes de fechar?';
        $('modalOverlay').style.display = '-webkit-box';
    } else {
        aoFecharEditor();
    }
}

function confirmarExclusao(id) {
    acaoPendente = 'excluir';
    $('modalMensagem').textContent = 'Tem certeza que deseja excluir este texto permanentemente?';
    $('modalOverlay').style.display = '-webkit-box';
    $('modalOverlay').setAttribute('data-id-excluir', id);
}

function modalResponder(sim) {
    $('modalOverlay').style.display = 'none';
    $('modalInput').style.display = 'none';

    var acao = acaoPendente;
    acaoPendente = null;

        if (acao === 'fechar') {

        if (sim) {

            salvarTexto(function(sucesso) {

                if (sucesso) {
                    aoFecharEditor();
                }

            });

        } else {

            // fechar sem salvar: descarta de verdade (inclusive a cópia local)
            clearTimeout(timeoutSalvar);

            if (textoAtualId) {
                removerRascunho(textoAtualId);
            }

            conteudoOriginal = $('conteudoEditor').value;

            aoFecharEditor();

        }

    } else if (acao === 'excluir') {

        if (sim) {
            var id = parseInt($('modalOverlay').getAttribute('data-id-excluir'), 10);

            if (id) {
                clearTimeout(timeoutSalvar);

                ajax('POST', '?action=excluir', {id: id}, function(resp) {
                    if (resp.sucesso) {
                        removerRascunho(id);

                        if (textoAtualId === id) {
                            liveLimpar = true; 
                            publicarLiveAgora();
                            textoAtualId = null;
                            conteudoOriginal = '';
                            $('conteudoEditor').value = '';
                            mostrarTela('lista');
                        }

                        carregarLista();
                    } else {
                        alert('Erro ao excluir: ' + (resp.erro || ''));
                    }
                });
            }
        }

    } else if (acao === 'criar') {

        if (sim) {
            var titulo = $('modalInput').value.replace(/^\s+|\s+$/g, '');

            if (!titulo) {
                titulo = 'Sem título';
            }

            var taNovo = $('conteudoEditor');

            textoAtualId = null;
            textoAtualTitulo = titulo;
            conteudoOriginal = '';
            falhasSalvamento = 0;

            $('tituloEditor').textContent = titulo;
            taNovo.value = '';
            taNovo.readOnly = false;

            clearTimeout(timeoutSalvar);
            atualizarContador();
            mostrarTela('editor');

            taNovo.focus();

            ajax('POST', '?action=criar', {titulo: titulo}, function(resp) {
                if (resp.sucesso) {
                    textoAtualId = parseInt(resp.id, 10);     
                    publicarLiveAgora();               
                    carregarLista();
                } else {
                    clearTimeout(timeoutSalvar);
                    taNovo.value = '';
                    conteudoOriginal = '';
                    alert('Erro ao criar: ' + (resp.erro || ''));
                    mostrarTela('lista');
                    carregarLista();
                }
            });
        }

    } else if (acao === 'rascunho') {

        if (sim) {
            var recuperado = lerRascunho(textoAtualId);

            if (recuperado !== null) {
                $('conteudoEditor').value = recuperado;
                atualizarContador();
                agendarSalvar();
            }
        } else {
            removerRascunho(textoAtualId);
        }

    } else if (acao === 'reverter') {

        if (sim) {
            $('conteudoEditor').value = conteudoOriginal;
            atualizarContador();
        }
    }
}

function modalAberto() {
    var d = $('modalOverlay').style.display;
    return d !== 'none' && d !== '';
}

function telaVisivel(id) {
    var d = $(id).style.display;
    return d !== 'none' && d !== '';
}

function campoDeTextoFocado() {
    var el = document.activeElement;
    if (!el) return false;
    var tag = el.tagName;
    return tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT';
}

function aoFecharEditor() {
    liveLimpar = true; 
    publicarLiveAgora();
    mostrarTela('lista');
    carregarLista();

    if (acaoAposFecharEditor === 'novo') {
        acaoAposFecharEditor = null;
        novoTexto();
    } else {
        acaoAposFecharEditor = null;
    }
}

function atualizarContador() {
    var textoOriginal = $('conteudoEditor').value;
    var texto = textoOriginal.trim();
    var palavras = 0;
    if (texto) {
        var ocorrencias = texto.match(/\S+/g);
        palavras = ocorrencias ? ocorrencias.length : 0;
    }
    var caracteres = textoOriginal.length;
    var minutosLeitura = palavras > 0 ? Math.max(1, Math.round(palavras / 200)) : 0;

    $('contadorPalavras').textContent = palavras + ' palavra' + (palavras !== 1 ? 's' : '');
    $('contadorCaracteres').textContent = caracteres + ' caractere' + (caracteres !== 1 ? 's' : '');
    $('tempoLeitura').textContent = minutosLeitura + ' min. de leitura';
}

function deslogar() {
    ajax('POST', '?action=logout', null, function(resp) {
        if (resp.sucesso) {
            window.location.reload();
        } else {
            alert('Erro ao sair.');
        }
    });
}

function ajustarPaddingEditor() {
    var ta = $('conteudoEditor');
    if (!ta) return;

    var alturaVisivel = ta.clientHeight;
    ta.style.paddingBottom = Math.floor(alturaVisivel / 2) + 'px';
}

function centralizarLinhaDigitacao() {
    var ta = $('conteudoEditor');
    if (!ta) return;

    if (usuarioRolando) return;

    var alturaVisivel = ta.clientHeight;
    var posCursor = ta.selectionStart;
    var totalCaracteres = ta.value.length;

    if (totalCaracteres > 0) {
        var proporcao = posCursor / totalCaracteres;
        var alturaTotalTexto = ta.scrollHeight - (alturaVisivel / 2);
        var scrollAlvo = (alturaTotalTexto * proporcao) - (alturaVisivel / 3);
        
        if (scrollAlvo > 0) {
            scrollProgramado = true;
            ta.scrollTop = scrollAlvo;
            setTimeout(function() {
                scrollProgramado = false;
            }, 50);
        }
    }
}

function criarDebounce(fn, intervalo) {
    var timeout = null;
    return function() {
        clearTimeout(timeout);
        timeout = setTimeout(fn, intervalo);
    };
}

var centralizarDebounced = criarDebounce(centralizarLinhaDigitacao, 600);

var atualizarContadorDebounced = criarDebounce(atualizarContador, 1000);

function agendarProcessamentoDigitacao() {
    centralizarDebounced();
    atualizarContadorDebounced();
}

var taEditor = $('conteudoEditor');

taEditor.addEventListener('focus', function() {
    ativarModoFoco();
    setTimeout(function() {
        ajustarPaddingEditor();
        centralizarLinhaDigitacao();
    }, 100);
});

taEditor.addEventListener('blur', function() {
    desativarModoFoco();
});

taEditor.addEventListener('input', function() {
    agendarSalvar();
    agendarLive();
    ativarModoFoco();
    agendarProcessamentoDigitacao();
});

taEditor.addEventListener('click', function() {
    centralizarDebounced();
});

taEditor.addEventListener('touchend', function() {
    centralizarDebounced();
});

taEditor.addEventListener('keyup', function(e) {
    var codigo = e.keyCode;
    if (codigo === 13) {
        setTimeout(centralizarLinhaDigitacao, 150);
    } else if ((codigo >= 37 && codigo <= 40) || codigo === 8 || codigo === 46) {
        agendarProcessamentoDigitacao();
    }
});

taEditor.addEventListener('keydown', function(e) {
    var codigo = e.keyCode;
    if (codigo >= 37 && codigo <= 40) {
        desativarModoFoco();
    }
});

taEditor.addEventListener('scroll', function() {
    if (scrollProgramado) return;

    usuarioRolando = true;
    clearTimeout(timeoutRolagem);
    timeoutRolagem = setTimeout(function() {
        usuarioRolando = false;
    }, 500);
});

$('conteudoEditor').addEventListener('blur', function() {
    atualizarContador();
    salvarSeNecessario();
});

document.addEventListener('visibilitychange', function() {
    if (document.hidden) {
        atualizarContador();
        salvarSeNecessario();
    }
});

window.addEventListener('pagehide', function() {
    salvarSeNecessario();
}, false);

function aoRedimensionar() {
    if (editorVisivel()) {
        ajustarPaddingEditor();
    }
}

window.addEventListener('orientationchange', function() {
    setTimeout(aoRedimensionar, 300);
}, false);

window.addEventListener('resize', aoRedimensionar, false);

setInterval(function() {
    if (editorVisivel()) {
        ajax('GET', '?action=ping', null, function() {});
    }
}, 240000);

document.addEventListener('touchstart', function(e) {
    if (modoFocoAtivo && e.target !== $('conteudoEditor') && document.getElementById('editor').style.display !== 'none') {
        desativarModoFoco();
    }
});

document.addEventListener('click', function(e) {
    if (modoFocoAtivo && e.target !== $('conteudoEditor') && document.getElementById('editor').style.display !== 'none') {
        desativarModoFoco();
    }
});

ajax('GET', '?action=listar', null, function(resp) {
    if (resp.erro && resp.erro === 'Não autenticado') {
        mostrarTela('login');
    } else if (Array.isArray(resp)) {
        mostrarTela('lista');
        renderizarLista(resp);
    } else {
        mostrarTela('login');
    }
});

document.addEventListener('keydown', function(e) {
    var codigo = e.keyCode;
    var ctrl = e.ctrlKey || e.metaKey;

    // ------------------------------------------------------------
    // Esc: fecha o que estiver por cima
    // ------------------------------------------------------------
    if (codigo === 27) {

        if (modalAberto()) {
            if (e.preventDefault) e.preventDefault();
            // Esc apenas fecha o modal sem decidir nada. Quem quiser
            // confirmar/cancelar usa S/Y ou N.
            $('modalOverlay').style.display = 'none';
            $('modalInput').style.display = 'none';
            acaoPendente = null;
            acaoAposFecharEditor = null;
            return;
        }

        if (telaVisivel('visualizarTexto')) {
            if (e.preventDefault) e.preventDefault();
            fecharVisualizacaoDownload();
            return;
        }

        if (editorVisivel()) {
            if (e.preventDefault) e.preventDefault();
            fecharEditor();
            return;
        }

        if (telaVisivel('ajuda')) {
            if (e.preventDefault) e.preventDefault();
            mostrarTela('lista');
            carregarLista();
            return;
        }

        if (telaVisivel('lista')) {
            if (e.preventDefault) e.preventDefault();
            deslogar();
            return;
        }

        return;
    }

    // ------------------------------------------------------------
    // Login: Enter
    // ------------------------------------------------------------
    if (codigo === 13 && telaVisivel('login')) {
        fazerLogin();
        return;
    }

    // ------------------------------------------------------------
    // Modal aberto
    // ------------------------------------------------------------
    if (modalAberto()) {
        var inputVisivel = $('modalInput').style.display !== 'none'
                        && $('modalInput').style.display !== '';

        if (inputVisivel) {
            // O modal de novo texto tem campo próprio: só Enter confirma,
            // para que digitar "N" no título não feche como "não".
            if (codigo === 13) {
                if (e.preventDefault) e.preventDefault();
                modalResponder(true);
            }
            return;
        }

        if (codigo === 83 || codigo === 89) { // S ou Y
            if (e.preventDefault) e.preventDefault();
            modalResponder(true);
            return;
        }
        if (codigo === 78) { // N
            if (e.preventDefault) e.preventDefault();
            modalResponder(false);
            return;
        }
        return;
    }

    // ------------------------------------------------------------
    // Editor
    // ------------------------------------------------------------
    if (editorVisivel()) {
        if (!ctrl) {
            return; // deixa o usuário digitar normalmente
        }
        if (codigo === 83) {            // Ctrl+S — salvar
            if (e.preventDefault) e.preventDefault();
            salvarTexto();
            return;
        }
        if (codigo === 68) {            // Ctrl+D — baixar
            if (e.preventDefault) e.preventDefault();
            salvarAntes(function() { baixarTexto(textoAtualId); });
            return;
        }
        if (codigo === 77) {            // Ctrl+M — e-mail
            if (e.preventDefault) e.preventDefault();
            salvarAntes(function() { enviarPorEmailId(textoAtualId); });
            return;
        }
        if (codigo === 80) {            // Ctrl+P — imprimir
            if (e.preventDefault) e.preventDefault();
            salvarAntes(function() { imprimirTextoId(textoAtualId); });
            return;
        }
        if (codigo === 8 || codigo === 46) { // Ctrl+⌫ / Ctrl+Delete — apagar
            if (e.preventDefault) e.preventDefault();
            confirmarExclusao(textoAtualId);
            return;
        }
        return;
    }

    // ------------------------------------------------------------
    // Lista
    // ------------------------------------------------------------
    if (telaVisivel('lista')) {
        if (campoDeTextoFocado()) return;

        if (codigo === 78) {            // N — novo
            if (e.preventDefault) e.preventDefault();
            novoTexto();
            return;
        }
        if (codigo === 72 || codigo === 191) { // H ou ? — ajuda
            if (e.preventDefault) e.preventDefault();
            mostrarTela('ajuda');
            return;
        }
        if (codigo === 67) {            // C — configurações
            if (e.preventDefault) e.preventDefault();
            window.location.href = 'config.php';
            return;
        }
        if (codigo === 38) {            // ↑ — item anterior
            if (e.preventDefault) e.preventDefault();
            moverSelecaoLista(-1);
            return;
        }
        if (codigo === 40) {            // ↓ — próximo item
            if (e.preventDefault) e.preventDefault();
            moverSelecaoLista(1);
            return;
        }
        if (codigo === 13) {            // Enter — abrir o item selecionado
            if (e.preventDefault) e.preventDefault();
            abrirItemSelecionado();
            return;
        }
        return;
    }

    // ------------------------------------------------------------
    // Ajuda
    // ------------------------------------------------------------
    if (telaVisivel('ajuda')) {
        if (codigo === 72 || codigo === 191) { // H ou ? — volta
            if (e.preventDefault) e.preventDefault();
            mostrarTela('lista');
            carregarLista();
            return;
        }
        if (codigo === 67) {            // C — configurações
            if (e.preventDefault) e.preventDefault();
            window.location.href = 'config.php';
            return;
        }
        return;
    }
});

(function() {
    try {
        var msg = sessionStorage.getItem('iscreva_erro_login');
        if (msg) {
            sessionStorage.removeItem('iscreva_erro_login');
            exibirErro(msg);
        }
    } catch (e) {}
})();

setTimeout(esconderBarraEndereco, 100);

window.addEventListener("load", function () {

    setTimeout(function () {
        window.scrollTo(0, 1);
    }, 0);

});

(function () {

    var ta = $('conteudoEditor');
    var espelho = "";

    var mapa = {
        "'a":"á", "'A":"Á",
        "'e":"é", "'E":"É",
        "'i":"í", "'I":"Í",
        "'o":"ó", "'O":"Ó",
        "'u":"ú", "'U":"Ú",
        "`a":"à", "`A":"À",
        "`e":"è", "`E":"È",
        "`i":"ì", "`I":"Ì",
        "`o":"ò", "`O":"Ò",
        "`u":"ù", "`U":"Ù",
        "^a":"â", "^A":"Â",
        "^e":"ê", "^E":"Ê",
        "^i":"î", "^I":"Î",
        "^o":"ô", "^O":"Ô",
        "^u":"û", "^U":"Û",
        "~a":"ã", "~A":"Ã",
        "~o":"õ", "~O":"Õ",
        "~n":"ñ", "~N":"Ñ",
        "'c":"ç", "'C":"Ç"
    };

      ta.addEventListener("keypress", function (e) {
        var codigo = e.charCode || e.which || e.keyCode;
        
        if (codigo >= 32) {
            espelho += String.fromCharCode(codigo);
            if (espelho.length > 2) {
                espelho = espelho.slice(-2);
            }
        }
    }, false);

    ta.addEventListener("keyup", function (e) {
        var k = e.keyCode;

        if (k === 8 || k === 13 || k === 46 || (k >= 37 && k <= 40)) {
            espelho = "";
            return;
        }

        if (!espelho || !mapa[espelho]) {
            return;
        }

        var p = ta.selectionStart;
        if (p < 2) return;
        if (ta.selectionStart !== ta.selectionEnd) return;

        var txt = ta.value;
        var ultimos = txt.substring(p-2, p);

        if (ultimos === espelho) {
            ta.value = txt.substring(0, p-2) + mapa[espelho] + txt.substring(p);
            ta.selectionStart = ta.selectionEnd = p-1;
            espelho = ""; 
            agendarProcessamentoDigitacao();
            agendarSalvar();
            agendarLive();
        }

    }, false);

    ta.addEventListener("click", function() { espelho = ""; }, false);
    ta.addEventListener("touchend", function() { espelho = ""; }, false);

})();

function imprimirTextoId(id) {

    ajax('GET', '?action=carregar&id=' + encodeURIComponent(id), null, function(resp) {

        if (resp.erro) {
            alert(resp.erro);
            return;
        }

        var texto = resp.conteudo || '';
        var impressao = $('conteudoImpressao');

        impressao.innerHTML = '';

        var linhas = texto.split(/\r?\n/);

        for (var i = 0; i < linhas.length; i++) {

            var linha = linhas[i];

            if (linha === '') {
                continue;
            }

            var p = document.createElement('p');
            p.textContent = linha;

            impressao.appendChild(p);
        }

        window.print();
    });
}

function enviarPorEmailId(id) {

    ajax('GET', '?action=carregar&id=' + encodeURIComponent(id), null, function(resp) {

        if (resp.erro) {
            alert(resp.erro);
            return;
        }

        var texto = resp.conteudo || '';

        if (!texto.trim()) {
            alert('O texto está vazio.');
            return;
        }

        var titulo = resp.titulo ? resp.titulo : 'Texto do iScreva';

        var assunto = encodeURIComponent(titulo);
        var corpo = encodeURIComponent(texto);

        window.location.href = 'mailto:?subject=' + assunto + '&body=' + corpo;
    });
}

  if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('service-worker.js')
      .then(function(registration) {
        console.log('Service Worker registrado com sucesso!');
      })
      .catch(function(error) {
        console.log('Falha ao registrar o Service Worker:', error);
      });
  }
</script>
</body>
</html>