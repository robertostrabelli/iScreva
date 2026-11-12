<?php
require_once __DIR__ . '/comum.php';

cabecalhosSeguranca();
iniciarSessao();

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

    $descricoes_tecla = [
        'tecla_default.png' => 'Tecla Default: a tecla que nunca atrapalha. Neutra e discreta, feita para sumir sob o dedo e deixar a palavra em evidência.',
        'tecla_osm.png' => 'Tecla Monica: amarelo que não pede desculpas. Ela não se esconde na escrivaninha — ela é o motivo de você olhar duas vezes antes de começar.',
        'tecla_osm2.png' => 'Tecla Berlim: roxo profundo com alma de arte. Quem a escolhe não quer só escrever, quer que a escrita pareça uma decisão de estilo.',
        'tecla_ftp1.png'   => 'Tecla Nórdica: um cinza-pedra sóbrio e arquitetônico. Para quem escreve com disciplina e gosta de ferramentas que só apoiam, sem competir com o texto.',
        'tecla_hb57.png'   => 'Tecla Verão 57: verde-menta suave, leve e otimista. Para escrever em qualquer lugar, com a leveza de quem viaja.',
        'tecla_is.png'     => 'Tecla Executiva: preto fosco e presença sólida. Precisão e ritmo para quem escreve com foco e sem enrolação.',
        'tecla_ol32.png'   => 'Tecla Riviera: azul-gelo suave e puro. Elegância funcional, bonita sem ostentação, prática sem ser fria.',
        'tecla_ov.png'     => 'Tecla Valentine: vermelho vibrante e provocador. A tecla-manifesto de quem escreve como quem se expressa.',
        'tecla_scs55.png'  => 'Tecla Esmeralda: verde profundo, quase uma joia. Confiança e permanência para quem escreve pensando em deixar algo que dure.',
        'tecla_u5.png'     => 'Tecla Vintage Ouro: preto profundo com um anel dourado. A raiz de tudo, num detalhe só: solidez e requinte.',
    ];

    $descricoes_fonte = [
        'TT2020'           => 'Fonte TT2020: cada letra sai um pouco diferente da anterior — de propósito. É a fonte que devolve à tela o acaso do impacto real de uma tecla no papel. Criada por Fredrick R. Brennan em 2018, inspirada nas esferas de tipo da IBM Selectric, ela garante que dez "E" seguidos nunca sejam idênticos. Não é imitação de máquina de escrever — é a textura dela, capturada glifo por glifo.',
        'domitian'     => 'Fonte Domitian: uma serifa que carrega décadas de tipografia clássica no corpo do texto — versaletes, algarismos old-style, a cadência de um livro bem composto. Desenvolvida por Daniel Benjamin Miller em 2020, ela reconstrói em código aberto o desenho de Hermann Zapf para a Palatino, uma das serifas mais lidas do século XX. Para quem quer que o texto pareça impresso antes mesmo de ser impresso.',
        'markerfelt'   => 'Fonte Marker Felt: a caligrafia que escapou da caneta. Desenhada por Pat Snyder para a Apple no início dos anos 1990, ela trouxe o traço grosso e imperfeito da caneta hidrográfica para dentro da tela — um respiro de informalidade em meio a tanta fonte séria.',
        'merriweather' => 'Fonte Merriweather: serifa pensada para a era em que ninguém mais lê só em papel. Criada por Eben Sorkin em 2010, com um x-height generoso e formas abertas herdadas de clássicas como a Garamond, ela prova que tradição e tela não são inimigas — só precisavam de alguém disposto a redesenhar a ponte entre as duas.',
        'noteworthy'   => 'Fonte Noteworthy: a letra que parece um bilhete deixado às pressas — e é exatamente essa a intenção. Criada para a Apple por volta de 2009 pelo designer de tipos Patrick Griffin, da Canada Type, a Noteworthy foi concebida como uma fonte manuscrita casual, simulando o traço de um marcador. Foi inspirada na fonte Filmotype Brooklyn. Ela troca a formalidade pela sensação de anotação pessoal, como se alguém tivesse escrito só para você.',
        'monospace'    => 'Fonte Monospace do sistema:  a fonte de largura fixa padrão do seu aparelho. Sem floreios: cada caractere ocupa o mesmo espaço, como numa máquina de escrever comum.',
        'serif'        => 'Fonte Serifada do sistema: a serifa padrão do seu aparelho. Tradicional e confortável para leitura longa, sem depender de uma fonte específica.',
        'sans-serif'   => 'Fonte Sem serifa do sistema: a fonte de traços limpos padrão do seu aparelho. Direta e moderna, sem serifas nas pontas das letras.',
        'cursive'      => 'Fonte Cursiva do sistema: a fonte cursiva padrão do seu aparelho, com traços conectados que lembram a escrita à mão.',
    ];

$camposCor = array(
    'cor_fundo'        => 'Cor do fundo geral',
    'cor_barra'        => 'Cor da barra de ferramentas superior e inferior',
    'cor_texto'        => 'Cor do texto geral',
    'cor_editor_fundo' => 'Cor do fundo do editor',
    'cor_editor_texto' => 'Cor do texto do editor',
    'cor_tecla_texto'  => 'Cor do texto da tecla'
);

$opcoes_fonte = array(
    'TT2020'       => 'TT2020',
    'domitian'     => 'Domitian',
    'markerfelt'   => 'Marker Felt',
    'merriweather' => 'Merriweather',
    'noteworthy'   => 'Noteworthy',
    'monospace'    => 'Monospace do sistema',
    'serif'        => 'Serifada do sistema',
    'sans-serif'   => 'Sem serifa do sistema',
    'cursive'      => 'Cursiva do sistema'
);

$opcoes_peso = array(
    'normal' => 'Normal',
    '300'    => 'Leve (300)',
    '500'    => 'Médio (500)',
    '600'    => 'Semi-negrito (600)',
    'bold'   => 'Negrito',
    '800'    => 'Extra-negrito (800)'
);

$desabilitar = $existeDB ? '' : ' disabled';

?><!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex,nofollow,noimageindex,noarchive">
<title>iScreva - Configurações</title>
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
<div class="logo"><img id="logoPreview" src="images/<?php echo h($logoImagem); ?>" alt="iScreva"></div>
<h1>Configurações</h1>
</header>

<div class="container">
<div class="mensagem" id="mensagem">Configuração salva.</div>

<?php if (!$existeDB): ?>
<div class="mensagem" style="display:block;background:#fff4ce;border-color:#e6cf78;">
    O banco de dados ainda não foi criado. Crie o primeiro texto no iScreva antes de salvar configurações.
</div>
<?php endif; ?>

<div class="painel">

<div class="duas-colunas">

<div class="coluna">

<h3 class="coluna-titulo">Teclas</h3>

<span class="rotulo">Escolha suas teclas</span>

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
        <input type="checkbox" id="estilo_neobrutalista" value="1"<?php echo ($config['estilo_neobrutalista'] === '1') ? ' checked' : ''; ?>>Nada disso, quero ser brutal!</label>
    <p class="nota" style="margin:0;">
        Visual ousado, com cores fortes. Substitui todas as demais opções visuais e somente as configurações de fonte podem ser customizadas.
    </p>
</div>

</div>

<div class="coluna">

<h3 class="coluna-titulo">Cores e fonte</h3>

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
<label for="fonte_familia">Fonte</label>
<select id="fonte_familia">
<?php foreach ($opcoes_fonte as $valor => $rotulo): ?>
    <option value="<?php echo h($valor); ?>"<?php echo ($config['fonte_familia'] === $valor) ? ' selected' : ''; ?>><?php echo h($rotulo); ?></option>
<?php endforeach; ?>
</select>
</div>

<div class="campo">
<label for="fonte_tamanho">Tamanho (em)</label>
<input type="number" id="fonte_tamanho" min="0.8" max="3" step="0.1" value="<?php echo h($config['fonte_tamanho']); ?>">
</div>

<div class="campo">
<label for="fonte_altura_linha">Altura da linha</label>
<input type="number" id="fonte_altura_linha" min="1" max="3" step="0.1" value="<?php echo h($config['fonte_altura_linha']); ?>">
</div>

<div class="campo">
<label for="fonte_espacamento">Espaçamento entre letras (px)</label>
<input type="number" id="fonte_espacamento" min="-3" max="10" step="0.5" value="<?php echo h($config['fonte_espacamento']); ?>">
</div>

<div class="campo">
<label for="fonte_peso">Peso da fonte</label>
<select id="fonte_peso">
<?php foreach ($opcoes_peso as $valor => $rotulo): ?>
    <option value="<?php echo h($valor); ?>"<?php echo ($config['fonte_peso'] === (string)$valor) ? ' selected' : ''; ?>><?php echo h($rotulo); ?></option>
<?php endforeach; ?>
</select>
</div>

<div class="campo">

<h3 class="coluna-titulo">Senha</h3>

<label for="senha_atual">Senha atual</label>
<input type="password" id="senha_atual" value="" autocomplete="current-password"<?php echo $desabilitar; ?>>

<label for="senha" style="margin-top:10px;">Nova senha</label>
<input type="password" id="senha" value="" placeholder="mínimo <?php echo (int)SENHA_MIN; ?> caracteres"
       autocomplete="new-password"<?php echo $desabilitar; ?>>

<label for="senha2" style="margin-top:10px;">Repita a nova senha</label>
<input type="password" id="senha2" value="" autocomplete="new-password"<?php echo $desabilitar; ?>>

<p class="nota">
Defina uma senha própria pra facilitar o acesso, no lugar da senha numérica de 6 dígitos
gerada automaticamente. Deixe os campos vazios para manter a senha atual. Para trocá-la,
é preciso confirmar a senha atual.
</p>
</div>

<div class="campo">

<h3 class="coluna-titulo">Conta</h3>

<button type="button" class="acao secundario" id="botaoApagarConta" onclick="apagarConta()" style="margin-left:0;"<?php echo $desabilitar; ?>>
    Apagar minha conta
</button>

<p class="nota">
Isso apaga todos os seus textos e configurações de forma permanente e não pode ser desfeito.
</p>
</div>

</div>

</div>

</div>

<div class="acoes">

<button type="button" class="acao secundario" onclick="restaurarPadrao()">
    Restaurar padrão
</button>

<button type="button" class="acao" onclick="salvarTudo()"<?php echo $desabilitar; ?>>
    Salvar configuração
</button>

<button type="button" class="acao" onclick="window.location.href = 'index.php'">
    Voltar
</button>

</div>

</div>

<script>

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
            alert('Cor inválida em "' + campo.getAttribute('data-rotulo') + '". Use o formato #rrggbb.');
            return;
        }

        dados[cores[i]] = valorCor;
    }

    var outros = camposFonte.concat(camposTecla);

    for (var k = 0; k < outros.length; k++) {
        dados[outros[k]] = $(outros[k]).value;
    }

    var novaSenha = $('senha').value;

    if (novaSenha !== '' || $('senha2').value !== '') {
        if (novaSenha.length < SENHA_MIN) {
            alert('A senha deve ter pelo menos ' + SENHA_MIN + ' caracteres.');
            return;
        }

        if (novaSenha !== $('senha2').value) {
            alert('As senhas não conferem.');
            return;
        }

        if ($('senha_atual').value === '') {
            alert('Informe a senha atual para trocá-la.');
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
            } else {
                alert('Erro ao salvar as configurações' + (resp && resp.erro ? ': ' + resp.erro : '.'));
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
    if (!confirm(
        'Apagar sua conta? Todos os seus textos e configurações serão perdidos para sempre. Essa ação não pode ser desfeita.'
    )) {
        return;
    }

    if (!confirm(
        'Tem certeza? Não existe como recuperar seus textos depois disso.'
    )) {
        return;
    }

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
                alert('Erro ao apagar a conta' + (resp && resp.erro ? ': ' + resp.erro : '.'));
            }
        }
    };

    xhr.send('csrf=' + encodeURIComponent(CSRF_TOKEN));
}

function restaurarPadrao() {
    if (!confirm(
        'Restaurar todas as configurações para os valores originais?'
    )) {
        return;
    }

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