<?php // https://github.com/jogemu/ob_html/

// returns unset value of array or fallback
function ob_unset($key, &$array, $fallback=null) {
  if(!is_array($array)) return $fallback;
  $buffer = $array[$key] ?? $fallback;
  $array = array_diff_key($array, array_fill_keys([$key], ''));
  return $buffer;
}

// returns array of functions
function ob_map($fn, $array) {
  if(!is_array($array)) return $array;
  $mapfn = fn($key) => fn() => $fn($array[$key], $key, $array);
  return array_map(fn($key) => is_int($key) ? $mapfn($key) : $array[$key], array_keys($array));
}

function ob_call($inner) {
  if(is_null($inner)) return '';
  if(is_array($inner)) return join('', array_map('ob_call', $inner));
  if(is_scalar($inner) || !is_callable($inner)) return $inner;
  ob_start();
  $inner();
  return ob_get_clean();
}

function array_move_named(&$from, &$to) {
  if(!is_array($from)) return;
  $keys = array_filter(array_keys($from), fn($v) => !is_int($v));
  $named = array_intersect_key($from, array_fill_keys($keys, ''));
  $from = array_diff_key($from, $named);
  $to = array_merge($to, $named);
}

function tag($tag, $inner='', ...$attr) {
  array_move_named($inner, $attr);
  echo '<'.$tag;
  foreach($attr as $k=>$v) {
    if(is_null($v)) continue;
    if(is_array($v)) $v = join(' ', $v);
    if(is_string($v)) {
      $v = str_replace('&', '&amp;', $v);
      $v = str_replace('"', '&quot;', $v);
    }
    if(is_bool($v)) echo $v ? ' '.$k : '';
    else echo ' '.$k.'="'.$v.'"';
  }
  if(in_array($tag, ['area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'source', 'track', 'wbr'])) echo '/>'; // option, iframe, meter, progress
  else echo '>'.ob_call($inner).'</'.$tag.'>';
}

// specify attributes through functions or constant values
function tags($tag, $array, $key='inner', ...$attr) {
  $attr[$key] ??= fn($i) => $i;
  $f = fn($i) => array_combine(
    array_keys($attr),
    array_map(fn($fn) => is_scalar($fn) || is_array($fn) || !is_callable($fn) ? $fn : $fn($i), $attr)
  );
  return ob_map(fn($i) => $key != 'inner' && is_array($i) ? tag($tag, ...$i) : tag($tag, ...$f($i)), $array);
}

function ob_action($action, $attr, &$value) {
  if(!isset($attr['name'])) return;
  if(isset($attr['readonly'])) return;
  if(isset($attr['disabled'])) return;
  if($_SERVER['REQUEST_METHOD'] != 'POST') return;
  if(!isset($_POST[$attr['name']])) return;

  if(!is_callable($action) || $action()) $value = $_POST[$attr['name']];
}








function a($inner, ...$attr) { tag('a', $inner, ...$attr); }
function abbr($inner, ...$attr) { tag('abbr', $inner, ...$attr); }
function address($inner, ...$attr) { tag('address', $inner, ...$attr); }
// area is part of map
function article($inner, ...$attr) { tag('article', $inner, ...$attr); }
function aside($inner, ...$attr) { tag('aside', $inner, ...$attr); }
function audio($sources, $tracks=[], ...$attr) {
  tag('audio', [
    ...tags('source', $sources, 'src'),
    ...tags('track', $tracks, 'src'),
    fn() => p(ob_map(fn($source) => a('Audio', href: is_string($source) ? $source : $source['src']), $sources))
  ], ...$attr);
}
function b($inner, ...$attr) { tag('b', $inner, ...$attr); }
// base is part of ob_html
function bdi($inner, ...$attr) { tag('bdi', $inner, ...$attr); }
function bdo($inner, $dir, ...$attr) {
  $attr['dir'] = $dir;
  tag('bdo', $inner, ...$attr);
}
function blockquote($inner, ...$attr) { tag('blockquote', $inner, ...$attr); }
// body is part of ob_html
function br(...$attr) { tag('br', ...$attr); }
function button($inner, $ob_action=null, ...$attr) {
  $value = null;
  ob_action($ob_action, $attr, $value);
  tag('button', $inner, ...$attr);
}
function canvas($inner, ...$attr) { tag('canvas', $inner, ...$attr); }
// caption is part of table
function cite($inner, ...$attr) { tag('cite', $inner, ...$attr); }
function code($inner, ...$attr) { tag('code', $inner, ...$attr); }
// col is part of table
// colgroup is part of table
function data($inner, $value, ...$attr) {
  $attr['value'] = $value;
  tag('data', $inner, ...$attr);
}
function datalist($options, ...$attr) {
  tag('datalist', tags('option', $options, 'value'), ...$attr);
}
// dd is part of dl
function del($inner, ...$attr) { tag('del', $inner, ...$attr); }
function details($summary, $inner, ...$attr) {
  tag('details', [
    fn() => tag('summary', $summary),
    $inner
  ], ...$attr);
}
function dfn($inner, ...$attr) { tag('dfn', $inner, ...$attr); }
function dialog($inner, ...$attr) { tag('dialog', $inner, ...$attr); }
function div($inner, ...$attr) { tag('div', $inner, ...$attr); }
function dl($items, ...$attr) {
  tag('dl', is_array($items) ? function() use ($items) {
    foreach($items as $dt=>$dd) {
      tag('dt', $dt, ob_unset('attr', $dd));
      tag('dd', $dd);
    }
  } : $items, ...$attr);
}
// dt is part of dl
function em($inner, ...$attr) { tag('em', $inner, ...$attr); }
function embed(...$attr) { tag('embed', ...$attr); }
function fieldset($inner, $legend=null, ...$attr) {
  tag('fieldset', [
    fn() => is_null($legend) ? '' : tag('legend', $legend),
    $inner
  ], ...$attr);
}
// figcaption is part of figure
function figure($inner, $caption=null, ...$attr) {
  tag('figure', [
    $inner,
    fn() => is_null($caption) ? '' : tag('figcaption', $caption)
  ], ...$attr);
}
function footer($inner, ...$attr) { tag('footer', $inner, ...$attr); }
function form($inner, ...$attr) { tag('form', $inner, ...$attr); }
function h($level, $inner, ...$attr) {
  tag('h'.$level, $inner, ...$attr);
}
// head is part of ob_html
function intro($inner, ...$attr) { tag('header', $inner, ...$attr); }
function hgroup($level, $inner, $after=[], $before=[], ...$attr) {
  tag('hgroup', [
    ...tags('p', $before),
    fn() => h($level, $inner),
    ...tags('p', $after)
  ], ...$attr);
}
function hr(...$attr) { tag('hr', ...$attr); }
// html is part of ob_html
function i($inner, ...$attr) { tag('i', $inner, ...$attr); }
function iframe($src, ...$attr) {
  $attr['src'] = $src;
  tag('iframe', ...$attr);
}
function img($src, $alt, ...$attr) {
  $attr['src'] = $src;
  $attr['alt'] = $alt;
  tag('img', ...$attr);
}
function input($label, &$value=null, $ob_action=null, ...$attr) {
  ob_action($ob_action, $attr, $value);
  if(in_array($attr['type'] ?? null, ['checkbox', 'radio'])) {
    $v = ob_unset('option', $attr, 'true');
    $attr['value'] = $v;
    if(in_array($v, is_array($value) ? $value : [$value])) $attr['checked'] = true;
  } else $attr['value'] = $value;
  label($label, fn() => tag('input', ...$attr));
}
function ins($inner, ...$attr) { tag('ins', $inner, ...$attr); }
function kbd($inner, ...$attr) { tag('kbd', $inner, ...$attr); }
function label($label, $inner='', ...$attr) {
  $label_tag = ob_unset('tag', $label, 'span');
  $attr = array_merge($attr, ob_unset('attr', $label, []));
  tag('label', [
    $inner,
    fn() => tag($label_tag, $label)
  ], ...$attr);
}
// legend is part of fieldset
// li is part of menu, ol and ul
// link is part of ob_html
function main($inner, ...$attr) { tag('main', $inner, ...$attr); }
function map($name, $areas=[], ...$attr) {
  $attr['name'] = $name;
  tag('map', tags('area', $areas, 'shape'), ...$attr);
}
function mark($inner, ...$attr) { tag('mark', $inner, ...$attr); }
function menu($items, ...$attr) {
  tag('menu', tags('li', $items), ...$attr);
}
// meta is part of ob_html
function meter($label, $value, ...$attr) {
  $attr['value'] = $value;
  label($label, fn() => tag('meter', ...$attr));
}
function nav($inner, ...$attr) { tag('nav', $inner, ...$attr); }
function noscript($inner, ...$attr) { tag('noscript', $inner, ...$attr); }
function object($inner, ...$attr) { tag('object', $inner, ...$attr); }
function ol($items, ...$attr) {
  tag('ol', tags('li', $items), ...$attr);
}
// optgroup is part of select
// option is part of datalist and select
function output($inner, ...$attr) { tag('output', $inner, ...$attr); }
function p($inner, ...$attr) { tag('p', $inner, ...$attr); }
function picture($sources, $img, ...$attr) {
  tag('picture', [
    ...tags('source', $sources, 'srcset'),
    fn() => tag('img', ...$img)
  ], ...$attr);
}
function pre($inner, ...$attr) { tag('pre', $inner, ...$attr); }
function progress($label, $value, ...$attr) {
  $attr['value'] = $value;
  label($label, fn() => tag('progress', ...$attr));
}
function q($inner, ...$attr) { tag('q', $inner, ...$attr); }
// rp is part of ruby
// rt is part of ruby
function ruby($inner, $before='(', $after=')', ...$attr) {
  tag('ruby', is_array($inner) ? function() use ($inner, $before, $after) {
    foreach($inner as $i) {
      if(!is_array($i)) $i = [$i];
      echo $i[0] ?? '';
      tag('rp', $before);
      tag('rt', $i[1] ?? '');
      tag('rp', $after);
    }
  } : $inner, ...$attr);
}
function s($inner, ...$attr) { tag('s', $inner, ...$attr); }
function samp($inner, ...$attr) { tag('samp', $inner, ...$attr); }
function script($inner, ...$attr) { tag('script', $inner, ...$attr); }
function search($inner, ...$attr) { tag('search', $inner, ...$attr); }
function section($inner, ...$attr) { tag('section', $inner, ...$attr); }
function select($label, $options, &$value=null, $optgroup=[], $ob_action=null, ...$attr) {
  ob_action($ob_action, $attr, $value);

  $fn = fn($options) => array_map(fn($label, $value) => is_array($value) ? [$label, ...$value] : [$label, 'value'=>$value], $options, array_keys($options));
  $fn2 = fn($options) => array_map(fn($o) => array_merge([
    'selected' => in_array($o['value'], is_array($value) ? $value : [$value])
  ], $o), $fn($options));
  label($label, fn() => tag('select', [
    ...tags('option', $fn2($options)),
    function() use ($optgroup, $fn2) {
      foreach($optgroup as $label=>$options) {
        tag('optgroup', tags('option', $fn2($options)), label: $label);
      }
    }
  ], ...$attr));
}
function slot($inner, ...$attr) { tag('slot', $inner, ...$attr); }
function small($inner, ...$attr) { tag('small', $inner, ...$attr); }
// source is part of audio, picture and video
function span($inner, ...$attr) { tag('span', $inner, ...$attr); }
function strong($inner, ...$attr) { tag('strong', $inner, ...$attr); }
function style($inner, ...$attr) { tag('style', $inner, ...$attr); }
function sub($inner, ...$attr) { tag('sub', $inner, ...$attr); }
// summary is part of details
function sup($inner, ...$attr) { tag('sup', $inner, ...$attr); }
function table($inner, $caption=null, $cols=[], $thead=[], $tfoot=[], $caption_attr=[], $cols_attr=[], $thead_attr=[], $tbody_attr=[], $tfoot_attr=[], ...$attr) {
  if(is_array($inner)) {
    $start = is_array($thead) ? 0 : $thead;
    $end = is_array($tfoot) ? null : -$tfoot;
    if($start) $thead = array_slice($inner, 0, $start);
    if($end) $tfoot = array_slice($inner, $end);
    $inner = array_slice($inner, $start, $end);
  }
  $tr = fn($array) => tags('tr', $array, inner: fn($row) => tags('td', $row));
  tag('table', [
    fn() => is_null($caption) ? '' : tag('caption', $caption, ...$caption_attr),
    fn() => $cols ? tag('colgroup', tags('col', $cols, 'span'), ...$cols_attr) : '',
    fn() => $thead ? tag('thead', $tr($thead), ...$thead_attr) : '',
    fn() => is_array($inner) ? tag('tbody', $tr($inner), ...$tbody_attr) : $inner,
    fn() => $tfoot ? tag('tfoot', $tr($tfoot), ...$tfoot_attr) : ''
  ], ...$attr);
}
// tbody is part of table
// td is part of table
function template($inner, ...$attr) { tag('template', $inner, ...$attr); }
function textarea($label, &$value='', $ob_action=null, ...$attr) {
  ob_action($ob_action, $attr, $value);
  $v = str_replace('&', '&amp;', $value);
  $v = str_replace('<', '&lt;', $v);
  $v = str_replace('>', '&gt;', $v);
  label($label, fn() => tag('textarea', $v, ...$attr));
}
// tfoot is part of table
// th is part of table
// thead is part of table
function datetime($inner, ...$attr) { tag('time', $inner, ...$attr); }
// title is part of ob_html
// tr is part of table
// track is part of audio and video
function u($inner, ...$attr) { tag('u', $inner, ...$attr); }
function ul($items, ...$attr) {
  tag('ul', tags('li', $items), ...$attr);
}
function variable($inner, ...$attr) { tag('var', $inner, ...$attr); }
function video($sources, $tracks=[], ...$attr) {
  tag('video', [
    ...tags('source', $sources, 'src'),
    ...tags('track', $tracks, 'src'),
    fn() => p(ob_map(fn($source) => a('Video', href: is_string($source) ? $source : $source['src']), $sources))
  ], ...$attr);
}
function wbr(...$attr) { tag('wbr', ...$attr); }








function ob_html($title, $head='', $lang='en', $charset='utf-8', ...$attr) {
  // assumes body content is already in output buffer
  $body = ob_get_clean();

  // adding content of head to new output buffer
  ob_start();
  tag('meta', charset: $charset);
  tag('title', $title);

  // cleanup $attr so that only metas remain
  $base = ob_unset('base', $attr);
  if(is_string($base)) $base = ['href'=>$base];

  // some values in $attr are links and not metas
  $links = ob_unset('links', $attr, []);
  foreach(['canonical', 'manifest'] as $rel) {
    $href = ob_unset($rel, $attr);
    if(!is_null($href)) $links[] = ['rel'=>$rel, 'href'=>$href];
  }
  foreach(ob_unset('stylesheets', $attr, []) as $href) {
    $links[] = ['rel'=>'stylesheet', 'href'=>$href];
  }
  foreach(ob_unset('translations', $attr, []) as $lang=>$href) {
    $links[] = ['rel'=>'alternate', 'hreflang'=>$lang, 'href'=>$href];
  }

  $scripts = array_map(fn($src) => is_string($src) ? ['src'=>$src] : $src, ob_unset('scripts', $attr, []));

  if(is_array($attr['keywords'] ?? null)) $attr['keywords'] = join(',', $attr['keywords']);
  $attr['viewport'] ??= 'width=device-width,initial-scale=1';

  foreach($attr as $name=>$content) tag('meta', name: $name, content: $content);
  if(!is_null($base)) tag('base', ...$base);
  foreach($links as $link) tag('link', ...$link);
  foreach($scripts as $script) tag('script', ...$script);

  $head = [ob_get_clean(), $head];

  echo '<!DOCTYPE html>';
  echo tag('html', [
    fn() => tag('head', $head),
    fn() => tag('body', $body)
  ], lang: $lang);
}

function ob_json($body=null) {
  header('Content-Type: application/json');
  if(is_null($body)) $body = ob_get_clean();
  else if(!is_string($body)) $body = json_encode($body);
  echo $body;
}
