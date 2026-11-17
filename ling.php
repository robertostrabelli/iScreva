<?php
if (!defined('ISCREVA_LING')) {
    define('ISCREVA_LING', 1);

    $LANG      = array();
    $FALLBACK  = array();
    $LANG_CODE = 'pt';

function idiomasDisponiveis() {
    return array(
        'pt'     => 'Português',
        'en'     => 'English',
        'eo'     => 'Esperanto',
        'es'     => 'Español',
        'custom' => (function_exists('t') ? t('idioma_custom') : '') ?: 'Custom',
    );
}

    function idiomasValidos() {
        return array_keys(idiomasDisponiveis());
    }

    function carregarArquivoIdioma($codigo) {
        $caminho = __DIR__ . '/lang/' . basename($codigo) . '.php';
        if (!is_file($caminho)) return array();
        $dados = include $caminho;
        return is_array($dados) ? $dados : array();
    }

    function detectarIdiomaPorNavegador() {
        $disponiveis = idiomasValidos();
        $padrao = defined('IDIOMA_PADRAO') ? IDIOMA_PADRAO : 'pt';

        if (!empty($_SERVER['HTTP_ACCEPT_LANGUAGE']) && is_string($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $bruto = strtolower($_SERVER['HTTP_ACCEPT_LANGUAGE']);
            foreach (array('pt', 'en', 'eo', 'es') as $cand) {
                if (strpos($bruto, $cand) !== false) {
                    return $cand;
                }
            }
        }

        return in_array($padrao, $disponiveis, true) ? $padrao : 'en';
    }

    function iniciarIdioma() {
        global $LANG, $FALLBACK, $LANG_CODE;

        $disponiveis = idiomasValidos();
        $codigo = null;

        if (isset($_GET['lang']) && is_string($_GET['lang'])) {
            $l = strtolower($_GET['lang']);
            if (in_array($l, $disponiveis, true)) {
                $codigo = $l;
                @setcookie(
                    defined('IDIOMA_COOKIE') ? IDIOMA_COOKIE : 'iscreva_lang',
                    $l,
                    time() + 60 * 60 * 24 * 365,
                    '/'
                );
                $_COOKIE[defined('IDIOMA_COOKIE') ? IDIOMA_COOKIE : 'iscreva_lang'] = $l;
            }
        }

        if ($codigo === null) {
            $nomeCookie = defined('IDIOMA_COOKIE') ? IDIOMA_COOKIE : 'iscreva_lang';
            if (isset($_COOKIE[$nomeCookie]) && is_string($_COOKIE[$nomeCookie])) {
                $l = strtolower($_COOKIE[$nomeCookie]);
                if (in_array($l, $disponiveis, true)) {
                    $codigo = $l;
                }
            }
        }

        if ($codigo === null
            && function_exists('dbExiste') && dbExiste()
            && function_exists('getDB')) {
            try {
                $stmt = getDB()->prepare("SELECT valor FROM configuracoes WHERE chave = 'idioma'");
                $stmt->execute();
                $v = $stmt->fetchColumn();
                if ($v !== false && in_array($v, $disponiveis, true)) {
                    $codigo = $v;
                }
            } catch (Throwable $e) {
                // silencioso: cai no próximo critério
            }
        }

        if ($codigo === null) {
            $codigo = detectarIdiomaPorNavegador();
        }

        $FALLBACK = carregarArquivoIdioma('en');
        $LANG     = ($codigo === 'en')
            ? $FALLBACK
            : carregarArquivoIdioma($codigo);
        $LANG_CODE = $codigo;

        return $codigo;
    }

    function t($chave, ...$args) {
    global $LANG, $FALLBACK;

    if (array_key_exists($chave, $LANG)
        && $LANG[$chave] !== ''
        && $LANG[$chave] !== null) {
        $s = $LANG[$chave];
    } elseif (array_key_exists($chave, $FALLBACK)
        && $FALLBACK[$chave] !== ''
        && $FALLBACK[$chave] !== null) {
        $s = $FALLBACK[$chave];
    } else {
        $s = $chave;
    }

    if (!$args) return $s;

    $r = @vsprintf($s, $args);
    return ($r === false) ? $s : $r;
}

    function tn($chaveSingular, $chavePlural, $n, ...$args) {
        $chave = ((int)$n === 1) ? $chaveSingular : $chavePlural;
        return t($chave, $n, ...$args);
    }

    function te($chave, ...$args) {
        return htmlspecialchars(t($chave, ...$args), ENT_QUOTES, 'UTF-8');
    }

    function tne($chaveSingular, $chavePlural, $n, ...$args) {
        return htmlspecialchars(tn($chaveSingular, $chavePlural, $n, ...$args), ENT_QUOTES, 'UTF-8');
    }

    function langCodeHtml() {
        global $LANG_CODE;
        $mapa = array(
            'pt'     => 'pt-BR',
            'en'     => 'en',
            'eo'     => 'eo',
            'es'     => 'es',
            'custom' => 'en', // custom é livre; use 'en' como padrão neutro
        );
        return isset($mapa[$LANG_CODE]) ? $mapa[$LANG_CODE] : 'en';
    }
}