<?php

$out = "";

$out .= '<!DOCTYPE html>';
$out .= '<html dir="ltr">';
$out .= '<head>';
$out .= '<meta name="viewport" content="width=device-width">';
$out .= '<meta charset="utf-8">';
$out .= '<title>' . i('Dependency Inspector') . '</title>';
$out .= '<link href="' . $url . '/favicon.ico" rel="icon">';
$out .= '<style>';
$out .= '*{margin:0;padding:0;list-style:none;font:inherit}:focus{outline:0}html{border-top:4px solid;background:#fff;color:#000;font:normal normal 13px/1.25 sans-serif}body{padding:2em}a{color:inherit;text-decoration:none}h1,h2,h3,h4,h5,h6{line-height:1}*+p,*+ul{margin-top:1em}h1{font-size:180%}h1+p{font-size:110%}h1+p a{color:#00f}details{display:block;padding:1em;margin:0 -1em;border-bottom:1px solid #eee}details:target{background:#ffa}h1+p+details{border-top:1px solid #eee;margin-top:1em}summary{display:block;font-size:140%;overflow:hidden;cursor:pointer}summary b{color:#999;float:right}ul{color:#7f7f7f}.error{color:#ed1c24}.info{color:#00a2e8}.success{color:#22b14c}';
$out .= '</style>';
$out .= '</head>';
$out .= '<body>';

$markdown = null !== State::get('x.markdown');
$count = 0;
$error = 0;

$r = "";

foreach (glob(__DIR__ . DS . '..' . DS . '*' . DS . 'about.page', GLOB_NOSORT) as $about) {
    if (!is_file($about)) {
        continue;
    }
    ++$count;
    $header = "";
    $soh = defined("YAML\\SOH") ? YAML\SOH : '---';
    $eot = defined("YAML\\EOT") ? YAML\EOT : '...';
    foreach (stream($about) as $k => $v) {
        // No header marker means no property at all
        if (0 === $k && $soh . "\n" !== $v) {
            break;
        }
        // Skip header marker!
        if (0 === $k && $soh . "\n" === $v) {
            continue;
        }
        // End header marker means no `use` property found
        if ($eot . "\n" === $v) {
            break;
        }
        $header .= "\n" . $v;
    }
    $data = From::YAML($header);
    $id = str_replace(ROOT, '.', strtr(realpath(dirname($about)), '/', DS));
    $r .= '<details id="x:' . basename($id) . '" open>';
    $r .= '<summary>';
    $r .= $id . ' <b>';
    if (!empty($data['title'])) {
        if ($markdown) {
            $r .= (new Parsedown)->line($data['title']);
        } else {
            $r .= $data['title'];
        }
    }
    $r .= '</b></summary>';
    if (!empty($data['use'])) {
        $r .= '<ul>';
        foreach ($data['use'] as $k => $v) {
            $r .= '<li>';
            $r .= '<a href="#x:' . basename($k) . '">';
            $r .= $k;
            if (0 === $v) {
                $r .= ' <span class="info">&#x2981;</span>';
            } else if (1 === $v) {
                if (is_file(ROOT . strtr(substr($k, 1), '\\', DS) . DS . 'index.php')) {
                    $r .= ' <span class="success">&#x2714;</span>';
                } else {
                    $r .= ' <span class="error">&#x2718;</span>';
                    ++$error;
                }
            } else if (is_string($v)) {
                preg_match('/^([!=<>]+)(.*)/', $v, $m);
                // TODO
            }
            $r .= '</a>';
            $r .= '</li>';
        }
        $r .= '</ul>';
    } else {
        $r .= '<p>' . i('This extension has no dependencies.') . '</p>';
    }
    $r .= '</details>';
}

$out .= '<h1>' . i('Dependency Inspector') . '</h1>';
$out .= '<p class="error">' . i('%d extension' . (1 === $i ? "" : 's') . ' installed.', $count) . ' ' . i('Found %d error' . (1 === $error ? "" : 's') . '.', $error) . ' <a href="https://mecha-cms.com/store" target="_blank">' . i('Search for missing extensions') . '&#x2026;</a></p>';
$out .= $r;
$out .= '</body>';
$out .= '</html>';

if ($error > 0) {
    Hook::let('get');
    http_response_code(200);
    echo $out;
    exit;
}
