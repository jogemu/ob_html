# ob_html.php
Seamlessly generate HTML with PHP functions. Use named arguments to set attributes, neatly nest tags and allow HTML forms to update PHP variables. Designed to be compact and easy to understand, even in deeply nested scopes. The native control structures of PHP allow maximum flexibility while keeping the barrier to entry low.

## Quick start
Echo shows the expected output of the previous call.

```php
<?php require 'ob_html.php';
p('Lorem ipsum', id: 'paragraph');
echo '<p id="paragraph">Lorem ipsum</p>';
```

Positional arguments are [content][anatomy] and [named arguments] are [attributes][anatomy]. Some elements allow arrays, where each entry is placed in its own child element like a list item. A more detailed description and further exceptions can be found [here](#special-array-interpretations).

[named arguments]: https://www.php.net/manual/en/functions.arguments.php#functions.named-arguments
[anatomy]: https://developer.mozilla.org/en-US/docs/Learn_web_development/Getting_started/Your_first_website/Creating_the_content#anatomy_of_an_html_element

```php
<?php require 'ob_html.php';
ul(['Lorem', 'ipsum']);
echo '<ul><li>Lorem</li><li>ipsum</li></ul>';
dl(['Lorem'=>'ipsum', 'dolor'=>'sit']);
echo '<dl><dt>Lorem</dt><dd>ipsum</dd><dt>dolor</dt><dd>sit</dd></dl>';
```

Nest tags by using a tag as an argument or provide a function to do loops or exit and re-enter PHP interpretation as needed. Variables from the parent scope can be made accessible with `function() use (...) {}` or [arrow functions](https://www.php.net/manual/en/functions.arrow.php).

```php
<?php require 'ob_html.php';
div(
  p('Lorem ipsum', id: 'paragraph'),
  article(function() {
    p('Lorem ipsum', id: 'paragraph');
    ?><p>Lorem ipsum</p><?php
    for($i = 0; $i < 10; $i++) {}
  })
);
```

If a [combined getter and setter](https://martinfowler.com/bliki/OverloadedGetterSetter.html) is passed as value of a form input, user input will automatically set that value. For this to happen, the `input()`, `select()`, `textarea()` or `button()` must have a `name` and be in a `<form>`. Inputs that are `readonly` or `disabled` do not overwrite their variables. The optional parameter `ob_action` may provide a function that is called on form submission.

```php
<?php require 'ob_html.php';
class User {
  public function __construct(private string $name, private int $age) {}
  public function __call($n, $v) { return $v ? $this->$n = $v[0] : $this->$n; }
  public function __get($n) { return fn($v) => $this->$n($v); }
  public function save() { echo 'Validate and save to database/session'; }
}
$user = new User('John Doe', 0);
form(
  textarea('Name', value: $user->name, name: 'name'),
  // echo '<label><textarea name="name">John Doe</textarea><span>Name</span></label>';
  input('Age', value: $user->age, type: 'number', name: 'age'),
  // echo '<label><input type="number" name="age" value="0"/><span>Age</span></label>';
  button('Submit', type:'submit', name: 'submit', ob_action: $user->save),
  // echo '<button type="submit" name="submit">Submit</button>';
  method: 'post'
);
```

Ultimately, the essential HTML elements are still missing. Call the return value of `require 'ob_html.php'` to add a `<title>`, stylesheets and metadata.

```php
<?php (require 'ob_html.php')(
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

p('I am a child of tag body.');
```

## Functions
A list of supported functions should not be necessary by design. Assuming familiarity with [HTML elements](https://developer.mozilla.org/en-US/docs/Web/HTML/Element), simply use the functions of the respective name.

### Intentionally excluded
* Essentials and metadata (generated via return or `html()`)
  * `<!DOCTYPE html>`, `<head>`, `<body>`
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
* `select('label', options: ["value"=>'option', 'optgroup'=>["value"=>'option'], ...])`
* `datalist(options: ["value", ...])`
* `["href", ...]` for `audio(sources: $, tracks: $)`, `picture(sources: $)`, `video(sources: $, tracks: $)`
* `ruby([[''=>'rt'], ...], 'rp', 'rp')`

### Limitations
The definition of so many functions, some with short names, is likely to cause conflicts. The advantages of simple coding hopefully outweigh the disadvantages. Although some type of injection is prevented, in many places injection prevention would be too restrictive. Therefore, this should happen on a level that is built on top of these functions. The same also applies to validation. In particular, the detection of missing values requires additional logic.
