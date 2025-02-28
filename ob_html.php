<?php // https://github.com/jogemu/ob_html

function ob_array($v) { return is_array($v) ? $v : [$v]; }

function ob_unset(&$array, $key, $fallback=null) {
  if(!is_array($array)) return $fallback;
  $buffer = $array[$key] ?? $fallback;
  unset($array[$key]);
  return $buffer;
}

function ob_unshift(&$a, $tag, $key=null) {
  $key ??= $tag;
  if(isset($a[$key])) array_unshift($a, tag($tag, ...ob_array(ob_unset($a, $key))));
}

function ob_action(&$a) {
  $f = ob_unset($a, 'ob_action');
  $v = isset($a['name']) ? $_POST[$a['name']] ?? $_GET[$a['name']] ?? null : null;
  if(!isset($v)) return;
  if(!is_string($f) && is_callable($f)) $f($v, $a);
  if(!is_string($a['value']) && is_callable($a['value'])) $a['value']($v);
  else $a['value'] = $v;
}

function ob_call(&$a) {
  array_walk_recursive($a, function(&$v, $k) {
    if(is_scalar($v) || !is_callable($v)) return;
    ob_start();
    $i = ob_get_level();
    $v();
    while(ob_get_level() > $i) ob_end_flush();
    $v = ob_get_clean();
  });
}

function tag(...$a) {
  if(function_exists('tag_')) tag_($a);
  ob_call($a);

  ob_start();

  $tag = array_shift($a);
  echo '<'.$tag;
  foreach($a as $k=>$v) {
    if(is_int($k)) continue;
    if(is_null($v)) continue;
    if(is_array($v)) $v = join(' ', $v);
    if(is_string($v)) $v = htmlentities($v);
    if(is_bool($v)) echo $v ? ' '.$k : '';
    else echo ' '.$k.'="'.$v.'"';
  }
  if(in_array($tag, ['area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'source', 'track', 'wbr'])) echo '/>'; // option, iframe, meter, progress
  else echo '>'.join('', array_filter($a, 'is_int', ARRAY_FILTER_USE_KEY)).'</'.$tag.'>';

  $r = ob_get_clean();

  ob_start(fn($v) => $r.$v);
  return function() use ($r) {
    ob_end_clean();
    echo $r;
  };
}

function tags($tag, $array, ...$a) {
  if(!is_array($array)) return $array;
  ob_call($array);
  if(is_string($a['key'] ?? null)) {
    $a[$a['key']] = fn($v) => $v;
    unset($a['key']);
  }
  if(!$a) $a[] = fn($v) => $v;
  $a = fn($i) => array_map(fn($v) => !is_string($v) && is_callable($v) ? $v($i) : $v, $a);
  return fn() => array_walk($array, fn($v) => tag($tag, ...$a($v))());
}

function a(...$a) { return tag('a', ...$a); }
function abbr(...$a) { return tag('abbr', ...$a); }
function address(...$a) { return tag('address', ...$a); }
// area is part of map
function article(...$a) { return tag('article', ...$a); }
function aside(...$a) { return tag('aside', ...$a); }
function audio(...$a) {
  $sources = array_shift($a);
  $tracks = array_shift($a);
  return tag('audio', tags('source', $sources, key: 'src'), tags('track', $tracks, key: 'src'), ...$a);
}
function b(...$a) { return tag('b', ...$a); }
// base is part of html
function bdi(...$a) { return tag('bdi', ...$a); }
function bdo(...$a) { return tag('bdo', ...$a); }
function blockquote(...$a) { return tag('blockquote', ...$a); }
// body is part of html
function br(...$a) { return tag('br', ...$a); }
function button(...$a) {
  ob_action($a);
  return tag('button', ...$a);
}
function canvas(...$a) { return tag('canvas', ...$a); }
// caption is part of table
function cite(...$a) { return tag('cite', ...$a); }
function code(...$a) { return tag('code', ...$a); }
// col is part of table
// colgroup is part of table
function data(...$a) { return tag('data', ...$a); }
function datalist(...$a) {
  $options = array_shift($a);
  return tag('datalist', tags('option', $options, key: 'value'), ...$a);
}
// dd is part of dl
function del(...$a) { return tag('del', ...$a); }
function details(...$a) {
  $summary = array_shift($a);
  return tag('details', tag('summary', ...ob_array($summary)), ...$a);
}
function dfn(...$a) { return tag('dfn', ...$a); }
function dialog(...$a) { return tag('dialog', ...$a); }
function div(...$a) { return tag('div', ...$a); }
function dl(...$a) {
  $items = array_shift($a);
  ob_call($items);
  return tag('dl', is_array($items) ? function() use ($items) {
    foreach($items as $dt=>$dd) {
      tag('dt', $dt, ...ob_unset($dd, 'dt', []))();
      tags('dd', ...ob_array($dd))();
    }
  } : $items, ...$a);
}
// dt is part of dl
function em(...$a) { return tag('em', ...$a); }
function embed($src, ...$a) {
  $a['src'] = $src;
  return tag('embed', ...$a);
}
function fieldset(...$a) {
  ob_unshift($a, 'legend');
  return tag('fieldset', ...$a);
}
// figcaption is part of figure
function figure(...$a) {
  ob_unshift($a, 'figcaption', 'caption');
  return tag('figure', ...$a);
}
function footer(...$a) { return tag('footer', ...$a); }
function form(...$a) { return tag('form', ...$a); }
function h(...$a) {
  $level = intval(array_shift($a));
  return tag('h'.$level, ...$a);
}
// head is part of html
function intro(...$a) { return tag('header', ...$a); }
function hgroup(...$a) {
  $level = intval(array_shift($a));
  $h = array_shift($a);
  array_unshift($a, h($level, $h));
  array_unshift($a, ...ob_array(ob_unset($a, 'before', [])));
  return tag('hgroup', ...$a);
}
function hr(...$a) { return tag('hr', ...$a); }
function html(...$a) {
  $lang = ob_unset($a, 'lang');
  $head = ob_array(ob_unset($a, 'head', []));
  return tag('html', tag('head', function() use ($a) {
    tag('meta', charset: ob_unset($a, 'charset', 'utf-8'))();
    if(isset($a['title'])) tag('title', ob_unset($a, 'title'))();

    $base = ob_unset($a, 'base');
    if(is_string($base)) $base = ['href'=>$base];

    $links = ob_unset($a, 'links', []);
    foreach(['canonical', 'manifest'] as $rel) {
      $href = ob_unset($a, $rel);
      if(!is_null($href)) $links[] = ['rel'=>$rel, 'href'=>$href];
    }
    foreach(ob_unset($a, 'stylesheets', []) as $href) {
      $links[] = ['rel'=>'stylesheet', 'href'=>$href];
    }
    foreach(ob_unset($a, 'translations', []) as $lang=>$href) {
      $links[] = ['rel'=>'alternate', 'hreflang'=>$lang, 'href'=>$href];
    }

    $scripts = array_map(fn($src) => is_string($src) ? ['src'=>$src] : $src, ob_unset($a, 'scripts', []));

    if(is_array($a['keywords'] ?? null)) $a['keywords'] = join(',', $a['keywords']);
    $a['viewport'] ??= 'width=device-width,initial-scale=1';

    foreach(array_filter($a, fn($k) => !is_int($k), ARRAY_FILTER_USE_KEY) as $name=>$content) tag('meta', name: $name, content: $content)();
    if(!is_null($base)) tag('base', ...$base)();
    foreach($links as $link) tag('link', ...$link)();
    foreach($scripts as $script) tag('script', ...$script)();
  }, ...$head), tag('body', ...array_filter($a, 'is_int', ARRAY_FILTER_USE_KEY)), lang: $lang);
}
function i(...$a) { return tag('i', ...$a); }
function iframe($src, ...$a) {
  $a['src'] = $src;
  return tag('iframe', ...$a);
}
function img($src, $alt, ...$a) {
  $a['src'] = $src;
  $a['alt'] = $alt;
  return tag('img', ...$a);
}
function input(...$a) {
  $label = array_shift($a);
  ob_action($a);
  if(in_array($a['type'] ?? null, ['checkbox', 'radio']) && isset($a['option'])) {
    $v = ob_unset($a, 'option');
    if(in_array($v, ob_array($a['value'] ?? []))) $a['checked'] = true;
    $a['value'] = $v;
  }
  return label($label, tag('input', ...$a));
}
function ins(...$a) { return tag('ins', ...$a); }
function kbd(...$a) { return tag('kbd', ...$a); }
function label(...$a) {
  $label = array_shift($a);
  if(is_array($label)) {
    // copy named attributes of $label to $a
    foreach($label as $k=>$v) is_int($k) ?: $a[$k] = $v;
    // exclude named attributes
    $label = array_filter($label, 'is_int', ARRAY_FILTER_USE_KEY);
    // first entry if just one entry
    if(count($label) == 1) $label = $label[0];
  }
  if(is_scalar($label)) $label = span($label);
  return tag('label', $label, ...$a);
}
// legend is part of fieldset
// li is part of menu, ol and ul
// link is part of html
function main(...$a) { return tag('main', ...$a); }
function map($name, ...$a) {
  $areas = array_shift($a);
  $a['name'] = $name;
  return tag('map', tags('area', $areas, key: 'shape'), ...$a);
}
function mark(...$a) { return tag('mark', ...$a); }
function menu(...$a) {
  $items = array_shift($a);
  return tag('menu', tags('li', $items), ...$a);
}
// meta is part of html
function meter($label, $value, ...$a) {
  $a['value'] = $value;
  return label($label, tag('meter', ...$a));
}
function nav(...$a) { return tag('nav', ...$a); }
function noscript(...$a) { return tag('noscript', ...$a); }
function object(...$a) { return tag('object', ...$a); }
function ol(...$a) {
  $items = array_shift($a);
  return tag('ol', tags('li', $items), ...$a);
}
// optgroup is part of select
// option is part of datalist and select
function output(...$a) { return tag('output', ...$a); }
function p(...$a) { return tag('p', ...$a); }
function picture(...$a) {
  $sources = array_shift($a);
  return tag('picture', tags('source', $sources, key: 'srcset'), ...$a);
}
function pre(...$a) { return tag('pre', ...$a); }
function progress($label, $value, ...$a) {
  $a['value'] = $value;
  return label($label, tag('progress', ...$a));
}
function q(...$a) { return tag('q', ...$a); }
// rp is part of ruby
// rt is part of ruby
function ruby($inner, $before='(', $after=')', ...$a) {
  ob_call($inner);
  return tag('ruby', is_array($inner) ? function() use ($inner, $before, $after) {
    foreach($inner as $i) {
      if(!is_array($i)) $i = [$i];
      echo $i[0] ?? '';
      tag('rp', $before)();
      tag('rt', $i[1] ?? '')();
      tag('rp', $after)();
    }
  } : $inner, ...$a);
}
function s(...$a) { return tag('s', ...$a); }
function samp(...$a) { return tag('samp', ...$a); }
function script(...$a) { return tag('script', ...$a); }
function search(...$a) { return tag('search', ...$a); }
function section(...$a) { return tag('section', ...$a); }
function select(...$a) {
  $label = array_shift($a);
  $options = array_shift($a);
  ob_action($a);
  $value = ob_array($a['value'] ?? []);
  array_walk_recursive($options, fn(&$v, $k) => $v = fn() => tag('option', $v, value: $k, selected: in_array($k, $value)));
  array_walk($options, fn(&$v, $k) => is_array($v) ? $v = fn() => tag('optgroup', ...array_values($v), label: $k) : null);
  label($label, tag('select', ...array_values($options), ...$a));
}
function slot(...$a) { return tag('slot', ...$a); }
function small(...$a) { return tag('small', ...$a); }
// source is part of audio, picture and video
function span(...$a) { return tag('span', ...$a); }
function strong(...$a) { return tag('strong', ...$a); }
function style(...$a) { return tag('style', ...$a); }
function sub(...$a) { return tag('sub', ...$a); }
// summary is part of details
function sup(...$a) { return tag('sup', ...$a); }
function table(...$a) {
  ob_call($a);
  $tbody = array_shift($a);
  $cols = ob_unset($a, 'cols', []);
  $thead = ob_unset($a, 'thead', []);
  $tfoot = ob_unset($a, 'tfoot', []);

  if(is_array($tbody)) {
    $start = is_array($thead) ? 0 : $thead;
    $end = is_array($tfoot) ? null : -$tfoot;
    if($start) $thead = array_slice($tbody, 0, $start);
    if($end) $tfoot = array_slice($tbody, $end);
    $tbody = array_slice($tbody, $start, $end);
  }

  $tags = function(...$b) {
    $v = array_shift($b);
    if(!is_array($v)) return $v;
    if(!$v) return '';
    $c = array_filter($v, fn($k) => !is_int($k), ARRAY_FILTER_USE_KEY);
    $v = array_filter($v, 'is_int', ARRAY_FILTER_USE_KEY);
    return tag(array_shift($b), tags(array_shift($b), $v, ...$b), ...$c);
  };

  array_unshift(
    $a,
    $tags($cols, 'colgroup', 'col', key: 'span'),
    $tags($thead, 'thead', 'tr', fn($l) => fn() => tags('td', $l)()),
    $tags($tbody, 'tbody', 'tr', fn($l) => fn() => tags('td', $l)()),
    $tags($tfoot, 'tfoot', 'tr', fn($l) => fn() => tags('td', $l)())
  );

  ob_unshift($a, 'caption');

  return tag('table', ...$a);
}
// tbody is part of table
// td is part of table
function template(...$a) { return tag('template', ...$a); }
function textarea(...$a) {
  $label = array_shift($a);
  ob_action($a);
  label($label, tag('textarea', htmlentities(ob_unset($a, 'value')), ...$a));
}
// tfoot is part of table
// th is part of table
// thead is part of table
function datetime(...$a) { return tag('time', ...$a); }
// title is part of html
// tr is part of table
// track is part of audio and video
function u(...$a) { return tag('u', ...$a); }
function ul(...$a) {
  $items = array_shift($a);
  return tag('ul', tags('li', $items), ...$a);
}
function variable(...$a) { return tag('var', ...$a); }
function video(...$a) {
  $sources = array_shift($a);
  $tracks = array_shift($a);
  return tag('video', tags('source', $sources, key: 'src'), tags('track', $tracks, key: 'src'), ...$a);
}
function wbr(...$a) { return tag('wbr', ...$a); }

return function(...$a) {
  ob_start();
  echo '<!DOCTYPE html>';
  html(...$a)();
  $r = ob_get_clean();

  ob_start(fn($v) => substr_replace($r, $v, -14, 0));
  return function() use ($r) {
    ob_end_clean();
    echo $r;
  };
};
