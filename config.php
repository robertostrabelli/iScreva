<?php
require_once __DIR__ . '/comum.php';

cabecalhosSeguranca();
iniciarSessao();
iniciarIdioma();

if (!isset($_SESSION['logado'])) {
    header('Location: index.php');
    exit;
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: " . gmdate("D, d M Y H:i:s", time() - 3600) . " GMT");

$configPadrao = configPadrao();
$config = carregarConfiguracoes();
$logoImagem = logoDaTecla($config['tecla_imagem']);
$existeDB = dbExiste();

    $opcoes_tecla = [
        'tecla_default.png' => 'Default',
        'tecla_osm2.png'    => 'Berlim',
        'tecla_scs55.png'  => 'Esmeralda',
        'tecla_is.png'     => 'Executiva',
        'tecla_osm.png'    => 'Monica',        
        'tecla_ftp1.png'   => 'Nórdica',
        'tecla_ol32.png'   => 'Riviera',
        'tecla_ov.png'     => 'Valentine',        
        'tecla_hb57.png'   => 'Verão 57',
        'tecla_u5.png'     => 'Vintage Ouro', 
    ];

    $descricoes_tecla = array(
    'tecla_default.png' => t('tecla_default_desc'),
    'tecla_osm.png'     => t('tecla_osm_desc'),
    'tecla_osm2.png'    => t('tecla_osm2_desc'),
    'tecla_ftp1.png'    => t('tecla_ftp1_desc'),
    'tecla_hb57.png'    => t('tecla_hb57_desc'),
    'tecla_is.png'      => t('tecla_is_desc'),
    'tecla_ol32.png'    => t('tecla_ol32_desc'),
    'tecla_ov.png'      => t('tecla_ov_desc'),
    'tecla_scs55.png'   => t('tecla_scs55_desc'),
    'tecla_u5.png'      => t('tecla_u5_desc'),
);

    $descricoes_fonte = array(
    'TT2020'      => t('fonte_tt2020_desc'),
    'domitian'    => t('fonte_domitian_desc'),
    'markerfelt'  => t('fonte_markerfelt_desc'),
    'merriweather'=> t('fonte_merriweather_desc'),
    'noteworthy'  => t('fonte_noteworthy_desc'),
    'monospace'   => t('fonte_monospace_desc'),
    'serif'       => t('fonte_serif_desc'),
    'sans-serif'  => t('fonte_sans_desc'),
    'cursive'     => t('fonte_cursive_desc'),
);

$camposCor = array(
    'cor_fundo'        => t('config_cor_fundo'),
    'cor_barra'        => t('config_cor_barra'),
    'cor_texto'        => t('config_cor_texto'),
    'cor_editor_fundo' => t('config_cor_editor_fundo'),
    'cor_editor_texto' => t('config_cor_editor_texto'),
    'cor_tecla_texto'  => t('config_cor_tecla_texto')
);

$opcoes_fonte = array(
    'TT2020'       => 'TT2020',
    'domitian'     => 'Domitian',
    'markerfelt'   => 'Marker Felt',
    'merriweather' => 'Merriweather',
    'noteworthy'   => 'Noteworthy',
    'monospace'    => t('config_fonte_monospace'),
    'serif'        => t('config_fonte_serif'),
    'sans-serif'   => t('config_fonte_sans'),
    'cursive'      => t('config_fonte_cursive')
);

$opcoes_peso = array(
    'normal' => 'Normal',
    '300'    => t('config_peso_leve'),
    '500'    => t('config_peso_medio'),
    '600'    => t('config_peso_seminegrito'),
    'bold'   => t('config_peso_negrito'),
    '800'    => t('config_peso_extranegrito')
);

$desabilitar = $existeDB ? '' : ' disabled';

?><!DOCTYPE html>
<html lang="<?php echo htmlspecialchars(langCodeHtml()); ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex,nofollow,noimageindex,noarchive">
<title><?php echo te('titulo_app'); ?> - <?php echo te('configuracoes'); ?></title>
<style>

@font-face {
  font-family: 'TT2020';
  font-style: normal;
  src: url(fonts/TT2020StyleE-Regular.ttf) format('truetype');
}

* {
    -webkit-box-sizing: border-box;
    box-sizing: border-box;
}

body {
    margin: 0;
    background: #e9e9e9;
    color: #222;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Helvetica, Arial, sans-serif;
}

header {
    padding: 18px;
    text-align: center;
    width: 100%;
}

header h1 {
    font-family: 'TT2020', monospace;
    margin: 10px;
    font-size: 24px;
}

.container {
    max-width: 1100px;
    margin: 0 auto;
    padding: 0 20px;
}

.painel {
    background: white;
    border-radius: 8px;
    padding: 25px;
    margin-bottom: 20px;
    -webkit-box-shadow: 0 2px 8px rgba(0,0,0,.12);
    box-shadow: 0 2px 8px rgba(0,0,0,.12);
}

.duas-colunas:after {
    content: "";
    display: block;
    clear: both;
}

.coluna {
    float: left;
    width: 48%;
}

.coluna + .coluna {
    margin-left: 4%;
}

.coluna-titulo {
    margin: 0 0 15px;
    font-size: 17px;
    color: #444;
}

.campo {
    margin-bottom: 16px;
}

.campo label,
.rotulo {
    display: block;
    font-weight: 600;
    margin-bottom: 6px;
}

.campo input[type="text"],
.campo input[type="number"],
.campo input[type="password"],
.campo select {
    display: block;
    width: 100%;
    height: 38px;
    padding: 7px 10px;
    border: 1px solid #bbb;
    border-radius: 4px;
    font-size: 16px;
    background: white;
}

.cor-controle:after {
    content: "";
    display: block;
    clear: both;
}

.cor-controle input[type="color"] {
    float: left;
    width: 55px;
    height: 42px;
    padding: 2px;
    border: 1px solid #bbb;
    border-radius: 4px;
    background: white;
}

.cor-texto {
    margin-left: 63px;
}

.sem-cor .cor-controle input[type="color"] {
    display: none;
}

.sem-cor .cor-texto {
    margin-left: 0;
}

.teclas {
    margin-top: 5px;
}

.opcao-tecla {
    display: inline-block;
    margin: 0 10px 10px 0;
    padding: 4px 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
    cursor: pointer;
    font-weight: normal;
}

.opcao-tecla img {
    width: 40px;
    height: auto;
    vertical-align: middle;
}

.tecla-desc {
    margin-top: 12px;
    padding: 10px 12px;
    min-height: 12px;
    font-size: 18px;
    font-style: italic;
    color: #555;
    background: #f5f5f5;
    border-left: 3px solid #bbb;
    border-radius: 3px;
    opacity: 0;
    -webkit-transition: opacity 0.35s ease;
    transition: opacity 0.35s ease;
}

.tecla-desc.visivel {
    opacity: 1;
}

.nota {
    margin: 10px 0 0;
    color: #666;
    font-size: 14px;
    line-height: 1.4;
}

.acoes {
    text-align: right;
}

.acoes:after {
    content: "";
    display: block;
    clear: both;
}

button.acao {
    margin: 0 0 0 10px;
    padding: 11px 20px;
    border: 0;
    border-radius: 5px;
    background: #222;
    color: white;
    font-size: 15px;
    cursor: pointer;
}

button.acao.secundario {
    background: #777;
}

button.acao[disabled] {
    opacity: 0.5;
}

.mensagem {
    display: none;
    margin-bottom: 20px;
    padding: 12px;
    background: #dff0d8;
    border: 1px solid #b8dcae;
    border-radius: 5px;
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

.logo img {
    max-width: 100%;
}

@media (max-width: 700px) {

    .coluna {
        float: none;
        width: 100%;
    }

    .coluna + .coluna {
        margin-left: 0;
        margin-top: 25px;
    }

    .container {
        padding: 0 10px;
    }

}

</style>
</head>
<body>
<header>
<div class="logo"><img id="logoPreview" src="images/<?php echo h($logoImagem); ?>" alt="<?php echo te('titulo_app'); ?>"></div>
<h1><?php echo te('configuracoes'); ?></h1>
</header>

<div class="container">
<div class="mensagem" id="mensagem"><?php echo te('config_salva'); ?></div>

<?php if (!$existeDB): ?>
<div class="mensagem" style="display:block;background:#fff4ce;border-color:#e6cf78;">
    <?php echo te('config_sem_db'); ?>
</div>
<?php endif; ?>

<div class="painel">

<div class="duas-colunas">

<div class="coluna">

<h3 class="coluna-titulo"><?php echo te('config_teclas'); ?></h3>

<span class="rotulo"><?php echo te('config_escolha_teclas'); ?></span>

<input type="hidden" id="tecla_imagem" name="tecla_imagem" value="<?php echo h($config['tecla_imagem']); ?>">

<div class="teclas">
<?php
foreach ($opcoes_tecla as $valor => $rotulo) {
    $checked = ($config['tecla_imagem'] === $valor) ? ' checked' : '';
    $id = 'tecla_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $valor);

    echo '<label class="opcao-tecla" for="' . h($id) . '">'
        . '<input type="radio" name="tecla_radio" id="' . h($id) . '" value="' . h($valor) . '"' . $checked . '> '
        . '<img src="images/' . h($valor) . '" alt="' . h($rotulo) . '"> '
        . h($rotulo)
        . '</label>' . "\n";
}
?>
</div>

<div id="tecla_desc" class="tecla-desc"></div>
<div id="fonte_desc" class="tecla-desc"></div><br><br>


<div class="campo" style="border:2px solid #222;padding:12px;border-radius:4px;background:#fff8dc;">
    <label style="display:block;font-weight:700;margin-bottom:6px;cursor:pointer;">
        <input type="checkbox" id="estilo_neobrutalista" value="1"<?php echo ($config['estilo_neobrutalista'] === '1') ? ' checked' : ''; ?>><?php echo te('config_neo'); ?></label>
    <p class="nota" style="margin:0;"><?php echo te('config_neo_nota'); ?></p>
</div>

</div>

<div class="coluna">

<div class="campo">
    <label for="idioma"><?php echo te('config_idioma'); ?></label>
    <select id="idioma">
    <?php foreach (idiomasDisponiveis() as $cod => $rotulo): ?>
        <option value="<?php echo h($cod); ?>"<?php echo ($LANG_CODE === $cod ? ' selected' : ''); ?>>
            <?php echo h($rotulo); ?>
        </option>
    <?php endforeach; ?>
    </select>
    <p class="nota"><?php echo te('config_idioma_nota'); ?></p>
</div>

<h3 class="coluna-titulo"><?php echo te('config_cores_fonte'); ?></h3>

<?php foreach ($camposCor as $chave => $nome): ?>
<div class="campo">
    <label for="<?php echo h($chave); ?>_texto"><?php echo h($nome); ?></label>
    <div class="cor-controle">
        <input type="color" id="<?php echo h($chave); ?>" value="<?php echo h($config[$chave]); ?>">
        <div class="cor-texto">
            <input type="text" id="<?php echo h($chave); ?>_texto" value="<?php echo h($config[$chave]); ?>"
                   maxlength="7" autocapitalize="off" autocorrect="off" spellcheck="false"
                   data-rotulo="<?php echo h($nome); ?>">
        </div>
    </div>
</div>
<?php endforeach; ?>

<div class="campo">
<label for="fonte_familia"><?php echo te('config_fonte'); ?></label>
<select id="fonte_familia">
<?php foreach ($opcoes_fonte as $valor => $rotulo): ?>
    <option value="<?php echo h($valor); ?>"<?php echo ($config['fonte_familia'] === $valor) ? ' selected' : ''; ?>><?php echo h($rotulo); ?></option>
<?php endforeach; ?>
</select>
</div>

<div class="campo">
<label for="fonte_tamanho"><?php echo te('config_tamanho'); ?></label>
<input type="number" id="fonte_tamanho" min="0.8" max="3" step="0.1" value="<?php echo h($config['fonte_tamanho']); ?>">
</div>

<div class="campo">
<label for="fonte_altura_linha"><?php echo te('config_altura_linha'); ?></label>
<input type="number" id="fonte_altura_linha" min="1" max="3" step="0.1" value="<?php echo h($config['fonte_altura_linha']); ?>">
</div>

<div class="campo">
<label for="fonte_espacamento"><?php echo te('config_espacamento'); ?></label>
<input type="number" id="fonte_espacamento" min="-3" max="10" step="0.5" value="<?php echo h($config['fonte_espacamento']); ?>">
</div>

<div class="campo">
<label for="fonte_peso"><?php echo te('config_peso'); ?></label>
<select id="fonte_peso">
<?php foreach ($opcoes_peso as $valor => $rotulo): ?>
    <option value="<?php echo h($valor); ?>"<?php echo ($config['fonte_peso'] === (string)$valor) ? ' selected' : ''; ?>><?php echo h($rotulo); ?></option>
<?php endforeach; ?>
</select>
</div>

<div class="campo">

<h3 class="coluna-titulo"><?php echo te('config_senha'); ?></h3>

<label for="senha_atual"><?php echo te('label_senha_atual'); ?></label>
<input type="password" id="senha_atual" value="" autocomplete="current-password"<?php echo $desabilitar; ?>>

<label for="senha" style="margin-top:10px;"><?php echo te('label_nova_senha'); ?></label>
<input type="password" id="senha" value="" placeholder="<?php echo te('config_senha_min_placeholder', (int)SENHA_MIN); ?>" autocomplete="new-password"<?php echo $desabilitar; ?>>


<label for="senha2" style="margin-top:10px;"><?php echo te('label_repita_nova_senha'); ?></label>
<input type="password" id="senha2" value="" autocomplete="new-password"<?php echo $desabilitar; ?>>

<p class="nota"><?php echo te('config_rodape_senha'); ?></p>
</div>

<div class="campo">

<h3 class="coluna-titulo"><?php echo te('config_conta'); ?></h3>

<button type="button" class="acao secundario" id="botaoApagarConta" onclick="apagarConta()" style="margin-left:0;"<?php echo $desabilitar; ?>><?php echo te('config_apagar_conta'); ?></button>

<p class="nota"><?php echo te('config_rodape_conta'); ?></p>
</div>

</div>

</div>

</div>

<div class="acoes">

<button type="button" class="acao secundario" onclick="restaurarPadrao()"><?php echo te('config_restaurar'); ?></button>

<button type="button" class="acao" onclick="salvarTudo()"<?php echo $desabilitar; ?>><?php echo te('config_salvar'); ?></button>

<button type="button" class="acao" onclick="window.location.href = 'index.php'"><?php echo te('config_voltar'); ?></button>

</div>

</div>

<script>
var T = <?php echo jsonParaScript($LANG); ?>;
var LANG = <?php echo jsonParaScript($LANG_CODE); ?>;
var IDIOMA_ATUAL = <?php echo jsonParaScript($LANG_CODE); ?>;

function tjs(k) {
    var s = (T && T[k]) || k;
    var args = Array.prototype.slice.call(arguments, 1);
    var i = 0;
    return s.replace(/%[sd]/g, function() {
        var v = args[i++];
        return (v === undefined) ? '' : String(v);
    });
}
function tnjs(kSing, kPlur, n) {
    return tjs((n === 1) ? kSing : kPlur, n);
}

function $(id) {
    return document.getElementById(id);
}

var CONFIG_PADRAO = <?php echo jsonParaScript($configPadrao); ?>;
var DESCRICOES_TECLA = <?php echo jsonParaScript($descricoes_tecla); ?>;
var DESCRICOES_FONTE = <?php echo jsonParaScript($descricoes_fonte); ?>;
var PALETAS_RECOMENDADAS = <?php echo jsonParaScript(temasRecomendados()); ?>;
var LOGOS_POR_TECLA = <?php echo jsonParaScript(logosPorTecla()); ?>;
var SENHA_MIN = <?php echo (int)SENHA_MIN; ?>;
var CSRF_TOKEN = <?php echo jsonParaScript($_SESSION['csrf_token']); ?>;

var cores = [
    'cor_fundo',
    'cor_barra',
    'cor_texto',
    'cor_editor_fundo',
    'cor_editor_texto',
    'cor_tecla_texto'
];

var camposFonte = [
    'fonte_familia',
    'fonte_tamanho',
    'fonte_altura_linha',
    'fonte_espacamento',
    'fonte_peso'
];

var camposTecla = [
    'tecla_imagem'
];

var REGEX_COR = /^#[0-9a-fA-F]{6}$/;

(function() {
    var teste = document.createElement('input');
    try { teste.type = 'color'; } catch (e) {}
    if (teste.type !== 'color') {
        document.body.className += ' sem-cor';
    }
})();

function sincronizarCor(chave) {
    $(chave + '_texto').value = $(chave).value;
}

for (var i = 0; i < cores.length; i++) {
    (function(chave) {
        $(chave).addEventListener('input', function() {
            sincronizarCor(chave);
        });
        $(chave).addEventListener('change', function() {
            sincronizarCor(chave);
        });
        $(chave + '_texto').addEventListener('change', function() {
            var valor = this.value;
            if (REGEX_COR.test(valor)) {
                $(chave).value = valor;
            }
        });
    })(cores[i]);
}

function definirBotoes(desabilitado) {
    var botoes = document.querySelectorAll('button.acao');
    for (var j = 0; j < botoes.length; j++) {
        botoes[j].disabled = desabilitado;
    }
}

function salvarTudo() {
    var dados = {};

    for (var i = 0; i < cores.length; i++) {
        var campo = $(cores[i] + '_texto');
        var valorCor = campo.value;

        if (!REGEX_COR.test(valorCor)) {
            alert(tjs('config_cor_invalida', campo.getAttribute('data-rotulo')));
            return;
        }

        dados[cores[i]] = valorCor;
    }

    var outros = camposFonte.concat(camposTecla);

    for (var k = 0; k < outros.length; k++) {
        dados[outros[k]] = $(outros[k]).value;
    }

    dados['idioma'] = $('idioma').value;

    var novaSenha = $('senha').value;

    if (novaSenha !== '' || $('senha2').value !== '') {
        if (novaSenha.length < SENHA_MIN) {
            alert(tjs('config_senha_curta', SENHA_MIN));
            return;
        }

        if (novaSenha !== $('senha2').value) {
            alert(tjs('config_senha_diferente'));
            return;
        }

        if ($('senha_atual').value === '') {
            alert(tjs('config_senha_atual'));
            return;
        }

        dados['senha'] = novaSenha;
        dados['senha_atual'] = $('senha_atual').value;
        
    }

    definirBotoes(true);

    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'index.php?action=salvar_configuracao', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');

    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            definirBotoes(false);

            var resp = null;

            if (xhr.status === 200) {
                try { resp = JSON.parse(xhr.responseText); } catch (e) {}
            }

            if (resp && resp.sucesso) {
                $('senha').value = '';
                $('senha2').value = '';
                $('senha_atual').value = '';
                $('mensagem').style.display = 'block';
                setTimeout(function() {
                    $('mensagem').style.display = 'none';
                }, 2500);
                if (dados['idioma'] && dados['idioma'] !== IDIOMA_ATUAL) {
        setTimeout(function() { window.location.reload(); }, 300);
    }
            } else {
                alert(tjs('config_erro_salvar', resp && resp.erro ? ': ' + resp.erro : '.'));
            }
        }
    };

    var elNeo = document.getElementById('estilo_neobrutalista');
if (elNeo) {
    dados['estilo_neobrutalista'] = elNeo.checked ? '1' : '0';
}

    xhr.send(
        'dados=' + encodeURIComponent(JSON.stringify(dados)) +
        '&csrf=' + encodeURIComponent(CSRF_TOKEN)
    );
}

function apagarConta() {
    if (!confirm(tjs('confirmar_apagar_conta_1'))) return;
    if (!confirm(tjs('confirmar_apagar_conta_2'))) return;

    var botao = $('botaoApagarConta');
    botao.disabled = true;

    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'index.php?action=apagar_conta', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');

    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            var resp = null;

            if (xhr.status === 200) {
                try { resp = JSON.parse(xhr.responseText); } catch (e) {}
            }

            if (resp && resp.sucesso) {
                if (resp.aviso) {
                    alert(resp.aviso);
                }
                window.location.href = 'index.php';
            } else {
                botao.disabled = false;
                alert(tjs('config_erro_apagar_conta', resp && resp.erro ? ': ' + resp.erro : '.'));
            }
        }
    };

    xhr.send('csrf=' + encodeURIComponent(CSRF_TOKEN));
}

function restaurarPadrao() {
    if (!confirm(tjs('config_restaurar_confirm'))) return;

    var chaves = cores.concat(camposFonte, camposTecla);

    for (var i = 0; i < chaves.length; i++) {
        var chave = chaves[i];
        var valorPadrao = CONFIG_PADRAO[chave];

        if ($(chave)) {
            $(chave).value = valorPadrao;
        }

        var campoTexto = document.getElementById(chave + '_texto');
        if (campoTexto) {
            campoTexto.value = valorPadrao;
        }
    }

    var teclaPadrao = CONFIG_PADRAO['tecla_imagem'];
    var radioPadrao = document.querySelector('input[name="tecla_radio"][value="' + teclaPadrao + '"]');

    if (radioPadrao) {
        radioPadrao.checked = true;
    }

    mostrarDescricaoTecla(teclaPadrao);
    atualizarLogoPreview(teclaPadrao);
    mostrarDescricaoFonte($('fonte_familia').value);

    salvarTudo();
    var elNeo = $('estilo_neobrutalista');
    if (elNeo && elNeo.checked) {
        elNeo.checked = false;
        var ev = document.createEvent('HTMLEvents');
        ev.initEvent('change', true, true);
        elNeo.dispatchEvent(ev);
    }
}

function mostrarDescricao(id, texto) {
    var el = $(id);

    if (!el) {
        return;
    }

    el.className = 'tecla-desc';

    setTimeout(function() {
        el.innerHTML = '';
        el.appendChild(document.createTextNode(texto));
        el.className = 'tecla-desc visivel';
    }, 250);
}

function mostrarDescricaoTecla(valor) {
    mostrarDescricao('tecla_desc', DESCRICOES_TECLA[valor] || '');
}

function mostrarDescricaoFonte(valor) {
    mostrarDescricao('fonte_desc', DESCRICOES_FONTE[valor] || '');
}

function atualizarLogoPreview(imagemTecla) {
    var logo = LOGOS_POR_TECLA[imagemTecla] || 'logo_default.png';
    var img = $('logoPreview');

    if (img) {
        img.src = 'images/' + logo;
    }
}

function aplicarPaletaRecomendada(imagemTecla) {
    var paleta = PALETAS_RECOMENDADAS[imagemTecla];

    if (!paleta) {
        return;
    }

    for (var chave in paleta) {
        if (paleta.hasOwnProperty(chave)) {
            var campo = $(chave);
            var campoTexto = $(chave + '_texto');

            if (campo) {
                campo.value = paleta[chave];
            }

            if (campoTexto) {
                campoTexto.value = paleta[chave];
            }
        }
    }
}

var radiosTecla = document.querySelectorAll('input[name="tecla_radio"]');

for (var r = 0; r < radiosTecla.length; r++) {
    (function(radio) {
        radio.addEventListener('change', function() {
            if (this.checked) {
                $('tecla_imagem').value = this.value;
                mostrarDescricaoTecla(this.value);
                aplicarPaletaRecomendada(this.value);
                atualizarLogoPreview(this.value);
                mostrarDescricaoFonte($('fonte_familia').value);
            }
        });
    })(radiosTecla[r]);
}

$('fonte_familia').addEventListener('change', function() {
    mostrarDescricaoFonte(this.value);
});

mostrarDescricaoTecla($('tecla_imagem').value);
mostrarDescricaoFonte($('fonte_familia').value);


(function() {
    var elNeo = document.getElementById('estilo_neobrutalista');
    if (!elNeo) return;

    // Só as cores e a tecla são fixas no neobrutalismo.
    // Fonte, tamanho, altura de linha, espaçamento e peso continuam livres.
    var alvos = ['cor_fundo','cor_barra','cor_texto','cor_editor_fundo','cor_editor_texto','cor_tecla_texto'];

    function atualizarEstadoNeobrutalista() {
        var ativo = elNeo.checked;
        for (var i = 0; i < alvos.length; i++) {
            var el = document.getElementById(alvos[i]);
            if (el) el.disabled = ativo;
            var el2 = document.getElementById(alvos[i] + '_texto');
            if (el2) el2.disabled = ativo;
        }
        var radios = document.querySelectorAll('input[name="tecla_radio"]');
        for (var r = 0; r < radios.length; r++) {
            radios[r].disabled = ativo;
        }
    }

    elNeo.addEventListener('change', atualizarEstadoNeobrutalista);
    atualizarEstadoNeobrutalista();
})();

</script>
</body>
</html>