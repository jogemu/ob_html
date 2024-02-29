# ob_html.php
Seamlessly generate HTML with PHP functions. Use named arguments to set attributes, neatly nest tags and allow HTML forms to update PHP variables.

## Quick start
Echo shows the expected output of the previous call.

```php
<?php require('ob_html.php');
p('Lorem ipsum', id: 'paragraph');
echo '<p id="paragraph">Lorem ipsum</p>';
```

Instead of a string, an array can be provided, which is normally concatenated. Some exceptions apply, such as lists, where each entry is placed in its own child element. A more detailed description and further exceptions can be found [here](#special-array-interpretations).

```php
<?php require('ob_html.php');
ul(['Lorem', 'ipsum']);
echo '<ul><li>Lorem</li><li>ipsum</li></ul>';
dl(['Lorem'=>'ipsum', 'dolor'=>'sit']);
echo '<dl><dt>Lorem</dt><dd>ipsum</dd><dt>dolor</dt><dd>sit</dd></dl>';
```

Provide a function to nest tags without the need to pre-generate content. The PHP interpretation can be exited and re-entered as usual without having to worry about the output buffer. Variables from the parent scope can be made accessible with `function() use (...) {}` or [arrow functions](https://www.php.net/manual/en/functions.arrow.php).

```php
<?php require('ob_html.php');
div(function() {
  p('Lorem ipsum', id: 'paragraph');
  ?><p>Lorem ipsum</p><?php
  for($i = 0; $i < 10; $i++) {}
});
```

The values of form inputs are passed by reference so that user input can be automatically applied. For this to happen, the `input()`, `select()`, `textarea()` or `button()` must have a `name` and be in a `<form method="post">`. Inputs that are `readonly` or `disabled` do not overwrite their variables. The optional parameter `ob_action` may provide a function which can cancel or modify immediately before overwriting.

```php
<?php require('ob_html.php');
class User {
  public function __construct(public string $name, public int $age) {}
  public function save() { echo 'Validate and save to database/session'; }
}
$user = new User('John Doe', 0);
form(function() use ($user) {
  textarea('Name', $user->name, name: 'name');
  // echo '<label><textarea name="name">John Doe</textarea><span>Name</span></label>';
  input('Age', $user->age, type: 'number', name: 'age');
  // echo '<label><input type="number" name="age" value="0"/><span>Age</span></label>';
  button('Submit', name: 'submit', type:'submit', ob_action: fn() => $user->save());
}, method: 'post');
```

Ultimately, the essential HTML elements are still missing. The native PHP function `ob_start()` should be executed before the first output. At the end, `ob_html()` reads the body from the output buffer, closes it and adds important metadata to the head.

```php
<?php require('ob_html.php');
ob_start();
// ...
ob_html(
  title: 'title',
  lang: 'en',

  // optional metas
  description: 'meta description',
  keywords: ['Lorem', 'ipsum'],
  author: 'author',
  // ...: ...

  base: '.',
  // common links
  manifest: 'manifest.json',
  stylesheets: ['stylesheet.css'],
  scripts: ['script.js'],
  translations: ['de'=>'de.php'],
  // other links
  links: [
    ['rel'=>'icon', 'href'=>'favicon.svg']
  ]
);
```

## Functions
A list of supported functions should not be necessary by design. Assuming familiarity with [HTML elements](https://developer.mozilla.org/en-US/docs/Web/HTML/Element), simply use the functions of the respective name.

### Intentionally excluded
* Essentials and metadata (generated via `ob_html()`)
  * `<!DOCTYPE html>`, `<html>`, `<head>`, `<body>`
  * `<title>`, `<meta>`, `<link>`, `<base>`
* Items and captions (generated via permitted parent)
  * `<li>`, `<dt>`, `<dd>`
  * `<option>`, `<optgroup>`
  * `<caption>`, `<figcaption>`, `<legend>`, `<summary>`
  * `<tr>`, `<td>`, `<th>`, `<tbody>`, `<thead>`, `<tfoot>` `<col>`, `<colgroup>`
  * `<source>`, `<track>`, `<area>`
* Conflicts with native PHP functions or reserved keywords
  * `<header>` => `intro()`
  * `<var>` => `variable()`
  * `<time>` => `datetime()`
* Obsolete and deprecated elements

### Special array interpretations
In this compact list, the meaning of a string depends on the quotation marks used. The current content of a string is the placeholder and represents a place where content can be placed. For single quotes (`'`) the placeholder is the tag of the element that contains the content. With double quotes (`"`) the tag must be inferred from the context and has an attribute with the placeholder as the name and the content as the value.

* `ul(['li', ...])`, `ol(['li', ...])`, `menu(['li', ...])`
* `dl(['dt'=>'dd', ...])`
* `select('label', options: ["value"=>'option', ...])`
* `datalist(options: ["value", ...])`
* `["href", ...]` for `audio(sources: $, tracks: $)`, `picture(sources: $)`, `video(sources: $, tracks: $)`
* `ruby([[''=>'rt'], ...], 'rp', 'rp')`

If the content is an array all named entries will be automatically applied to the respective tag.

### Limitations
The definition of so many functions, some with short names, is likely to cause conflicts. The advantages of simple coding hopefully outweigh the disadvantages. Although some type of injection is prevented, in many places injection prevention would be too restrictive. Therefore, this should happen on a level that is built on top of these functions. The same also applies to validation. In particular, the detection of missing values requires additional logic.