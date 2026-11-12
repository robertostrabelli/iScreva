<?php

require_once __DIR__ . '/comum.php';

cabecalhosSeguranca();

$ehPeek = isset($_GET['peek']);

/**
 * Encerra a requisição com uma mensagem de erro no formato certo:
 * JSON para o polling (peek), texto puro para quem abre a URL no navegador.
 * "fatal" diz ao cliente para parar de tentar.
 */
function monitorErro($mensagem, $ehPeek, $fatal = true) {
    if ($ehPeek) {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        echo jsonSaida(array('erro' => $mensagem, 'fatal' => $fatal));
    } else {
        header('Content-Type: text/plain; charset=utf-8');
        echo $mensagem . "\n";
    }
    exit;
}

$token = '';
$codigoCurto = '';

if (isset($_GET['t']) && is_string($_GET['t'])) {
    $token = (string)$_GET['t'];
}

if (isset($_GET['c']) && is_string($_GET['c'])) {
    $codigoCurto = (string)$_GET['c'];
}

if ($token === '' && $codigoCurto === '') {
    monitorErro(
        "Falta o parâmetro ?t=<token> ou ?c=<codigo>.\n" .
        "Abra a tela de Ajuda dentro do iScreva para obter o endereço do monitor.",
        $ehPeek
    );
}

try {

    $chaveLimite = 'monitor:' . ipCliente();
    $limite = estadoLimite($chaveLimite, 60, 900);

    if ($limite['espera'] > 0) {
        monitorErro(
            'Muitas tentativas. Tente novamente em ' . ceil($limite['espera'] / 60) . ' minuto(s).',
            $ehPeek
        );
    }

    $conta = null;

    if ($token !== '') {
        $conta = contaPorTokenMonitor($token);
    }

    if ($conta === null && $codigoCurto !== '') {
        $conta = contaPorCodigoCurtoMonitor($codigoCurto);
    }

    if (!$conta) {
        registrarEvento($chaveLimite);
        monitorErro('Código inválido.', $ehPeek);
    }

    $monitor = obterMonitorDaConta($conta['id']);
    $arquivoLive = $monitor['arquivo_live'];

} catch (Throwable $e) {
    error_log('iScreva monitor: ' . $e->getMessage());
    monitorErro('Erro interno do servidor.', $ehPeek, false);
}

$paramPolling = '';
if ($token !== '') {
    $paramPolling = 't=' . rawurlencode($token);
} else {
    $paramPolling = 'c=' . rawurlencode($codigoCurto);
}

/* ===== Endpoint JSON (long-poll) ====================================== */

if ($ehPeek) {

    $maxEspera = 20;
    $tickUs    = 80000;

    @set_time_limit($maxEspera + 10);

    header('X-Accel-Buffering: no');
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');

    $revCliente = isset($_GET['rev']) ? (int)$_GET['rev'] : -1;

    try {
        if ($revCliente < 0) {
            $dados = lerLive($arquivoLive);

            if ($dados === null) {
                echo jsonSaida(array('ativo' => false, 'revisao' => 0));
            } else {
                echo jsonSaida(array(
                    'ativo'    => true,
                    'revisao'  => $dados['rev'],
                    'id'       => $dados['id'],
                    'titulo'   => $dados['titulo'],
                    'conteudo' => $dados['conteudo']
                ));
            }
            exit;
        }

        $inicio = microtime(true);

        while (true) {

            $dados = lerLive($arquivoLive);

            if ($dados !== null && $dados['rev'] !== $revCliente) {
                echo jsonSaida(array(
                    'ativo'    => true,
                    'revisao'  => $dados['rev'],
                    'id'       => $dados['id'],
                    'titulo'   => $dados['titulo'],
                    'conteudo' => $dados['conteudo']
                ));
                exit;
            }

            if ((microtime(true) - $inicio) >= $maxEspera) {
                echo jsonSaida(array(
                    'unchanged' => true,
                    'ativo'     => ($dados !== null),
                    'revisao'   => ($dados !== null ? $dados['rev'] : 0)
                ));
                exit;
            }

            usleep($tickUs);
        }

    } catch (Throwable $e) {
        error_log('iScreva monitor: ' . $e->getMessage());
        echo jsonSaida(array('erro' => 'Erro interno do servidor.', 'fatal' => false));
    }
    exit;
}

/* ===== Renderização HTML ============================================== */

$estadoInicial = array(
    'ativo'    => false,
    'titulo'   => '',
    'conteudo' => '',
    'revisao'  => 0
);

try {
    $dados = lerLive($arquivoLive);
    if ($dados !== null) {
        $estadoInicial = array(
            'ativo'    => true,
            'titulo'   => $dados['titulo'],
            'conteudo' => $dados['conteudo'],
            'revisao'  => $dados['rev']
        );
    }
} catch (Throwable $e) {
    error_log('iScreva monitor: ' . $e->getMessage());
}

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: " . gmdate("D, d M Y H:i:s", time() - 3600) . " GMT");
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex,nofollow,noimageindex,noarchive">
<title>iScreva</title>
<style>

* {
    -webkit-box-sizing: border-box;
    box-sizing: border-box;
}

html, body {
    margin: 0;
    padding: 0;
    width: 100%;
    min-height: 100%;
    font-family: Georgia, "Times New Roman", serif;
    font-size: 26px;
    line-height: 1.65;
}

#quadro {
    max-width: 640px;
    margin: 0 auto;
    padding: 10px 30px;
}

#titulo {
    font-weight: bold;
    line-height: 1.3;
    text-transform: uppercase;
    margin-bottom: 15px;
    border-bottom: 1px solid;
}

#titulo.vazio {
    display: none;
}

#conteudo {
    white-space: pre-wrap;
    word-wrap: break-word;
    -webkit-hyphens: auto;
    -ms-hyphens: auto;
    hyphens: auto;
    text-align: left;
}

#conteudo::after {
    content: "";
    display: inline-block;
    width: 2px;
    height: 1em;
    margin-left: 2px;
    vertical-align: -0.15em;
    background-color: #000000;
    -webkit-animation: iscreva-piscar 1.04s infinite;
    animation: iscreva-piscar 1.04s infinite;
}

@-webkit-keyframes iscreva-piscar {
    0%, 50%    { opacity: 1; }
    50.01%, 100% { opacity: 0; }
}

@keyframes iscreva-piscar {
    0%, 50%    { opacity: 1; }
    50.01%, 100% { opacity: 0; }
}

#estado {
    position: fixed;
    right: 10px;
    bottom: 10px;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Helvetica, Arial, sans-serif;
    font-size: 11px;
    color: #bbbbbb;
    background: #ffffff;
    padding: 2px 6px;
    border-radius: 3px;
    pointer-events: none;
}

#estado.erro {
    color: #b00000;
}

@media print {
    #estado { display: none; }
    #quadro { padding: 0; max-width: none; }
    #conteudo::after { display: none; }
}

</style>
</head>
<body>

<div id="quadro">
    <div id="titulo"<?php echo $estadoInicial['titulo'] === '' ? ' class="vazio"' : ''; ?>><?php
        echo h($estadoInicial['titulo']);
    ?></div>
    <div id="conteudo"><?php
        echo h($estadoInicial['conteudo']);
    ?></div>
</div>

<div id="estado">conectando…</div>

<noscript>
<div style="max-width:640px;margin:0 auto;padding:0 30px 30px;font-family:sans-serif;font-size:14px;color:#666;">
    O monitor precisa de JavaScript para acompanhar o texto em tempo real.
    A versão acima é o último estado publicado.
</div>
</noscript>

<script>
(function() {

    // Caminho absoluto: funciona tanto em /monitor.php?c=... quanto em /m/CODIGO.
    var ENDPOINT = <?php echo jsonParaScript($_SERVER['SCRIPT_NAME']); ?>;
    var PARAM = <?php echo jsonParaScript($paramPolling); ?>;
    var QUASE_NO_FIM = 120;

    var ultimaRevisao = <?php echo (int)$estadoInicial['revisao']; ?>;
    var ultimoTitulo = <?php echo jsonParaScript($estadoInicial['titulo']); ?>;
    var ultimoConteudo = <?php echo jsonParaScript($estadoInicial['conteudo']); ?>;
    var falhas = 0;
    var ativo = <?php echo $estadoInicial['ativo'] ? 'true' : 'false'; ?>;

    // Controle para existir no máximo UMA requisição em andamento.
    var consultando = false;
    var xhrAtual = null;
    var sequencia = 0;
    var parado = false;

    function $(id) { return document.getElementById(id); }

    function definirEstado(txt, ehErro) {
        var el = $('estado');
        el.textContent = txt;
        el.className = ehErro ? 'erro' : '';
    }

    function quaseNoFim() {
        var doc = document.documentElement;
        var alturaTotal = doc.scrollHeight;
        var alturaVisivel = window.innerHeight || doc.clientHeight;
        var posicao = window.pageYOffset || doc.scrollTop || 0;
        return (alturaTotal - posicao - alturaVisivel) < QUASE_NO_FIM;
    }

    function aplicar(dados) {

        ativo = !!dados.ativo;

        if (!dados.ativo) {
            if (ultimoConteudo !== '') {
                $('titulo').textContent = '';
                $('titulo').className = 'vazio';
                $('conteudo').textContent = '';
                ultimoTitulo = '';
                ultimoConteudo = '';
            }
            definirEstado('aguardando o editor…', false);
            return;
        }

        var novoTitulo = dados.titulo || '';
        var novoConteudo = dados.conteudo || '';

        if (novoTitulo !== ultimoTitulo) {
            var elTitulo = $('titulo');
            elTitulo.textContent = novoTitulo;
            elTitulo.className = (novoTitulo === '') ? 'vazio' : '';
            ultimoTitulo = novoTitulo;
        }

        if (novoConteudo !== ultimoConteudo) {
            var colado = quaseNoFim();
            var posicaoAntes = window.pageYOffset || document.documentElement.scrollTop || 0;

            $('conteudo').textContent = novoConteudo;
            ultimoConteudo = novoConteudo;

            if (colado) {
                window.scrollTo(0, document.documentElement.scrollHeight);
            } else {
                window.scrollTo(0, posicaoAntes);
            }
        }

        definirEstado('espelhando', false);
    }

    function falhar() {
        falhas++;
        definirEstado(falhas > 4 ? 'sem conexão' : 'sem conexão — tentando…', true);
    }

    function tentarDepois(ms) {
        setTimeout(consultar, ms);
    }

    function consultar() {

        if (parado || consultando) return;
        consultando = true;

        var minhaSequencia = ++sequencia;

        var xhr = new XMLHttpRequest();
        xhrAtual = xhr;

        var url = ENDPOINT + '?' + PARAM +
                  '&peek=1&rev=' + encodeURIComponent(ultimaRevisao) +
                  '&_=' + (new Date()).getTime();

        xhr.open('GET', url, true);
        try { xhr.timeout = 30000; } catch (e) {}

        xhr.onreadystatechange = function() {

            if (xhr.readyState !== 4) return;

            // Requisição descartada (abortada ou substituída por outra mais nova).
            if (minhaSequencia !== sequencia) return;

            consultando = false;
            xhrAtual = null;

            if (xhr.status !== 200) {
                falhar();
                tentarDepois(2000);
                return;
            }

            var texto = xhr.responseText || '';
            texto = texto.replace(/^[\uFEFF\uFFFE\xEF\xBB\xBF\s\xA0]+/, '');

            var dados;
            try {
                dados = JSON.parse(texto);
            } catch (e) {
                falhar();
                tentarDepois(2000);
                return;
            }

            if (dados.erro) {
                if (dados.fatal) {
                    // Código inválido, conta apagada ou limite excedido: insistir só piora.
                    parado = true;
                    definirEstado(dados.erro, true);
                    return;
                }
                falhar();
                tentarDepois(2000);
                return;
            }

            falhas = 0;

            if (typeof dados.revisao === 'number') {
                ultimaRevisao = dados.revisao;
            }

            if (dados.unchanged) {
                definirEstado(ativo ? 'espelhando' : 'aguardando o editor…', false);
            } else {
                aplicar(dados);
            }

            consultar();
        };

        xhr.send(null);
    }

    // Ao voltar para a aba/aparelho, descarta a requisição pendente (que pode
    // estar pendurada numa conexão morta) e começa uma nova, sem acumular laços.
    function recomecar() {
        if (parado) return;

        if (consultando && xhrAtual) {
            sequencia++;
            consultando = false;
            try { xhrAtual.abort(); } catch (e) {}
            xhrAtual = null;
        }

        consultar();
    }

    consultar();

    document.addEventListener('visibilitychange', function() {
        if (!document.hidden) recomecar();
    }, false);

    window.addEventListener('focus', recomecar, false);

})();
</script>
</body>
</html>
