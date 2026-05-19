# Tracy Latte Convert

Build-time tool that compiles Tracy's `.latte` panel templates into standalone
`.phtml` files with no Latte runtime dependency. The generated `.phtml` files
are committed to the repo and shipped to end users.

For Tracy maintainers only — end users of `tracy/tracy` never need this.

## Requirements

- PHP 8.2+
- `latte/latte` ^3.1.4 with the following patches (not yet upstreamed):
  - `Compiler\Escaper` non-final
  - `Compiler\PrintContext::__construct()` accepts optional `?Escaper`
  - `Compiler\TagParser::parseType()` accepts `Token::Php_Sr`
  - `Compiler\TemplateParser::applyDedent()` uses regex `/^(\t+| +)/`

## Usage

```bash
# one-time setup
cd tools/latte-convert && composer install

# compile all Tracy templates
composer compile-templates

# compile a single .latte file (output auto-derived to sibling dist/)
php tools/latte-convert/compile.php src/Tracy/Bar/assets/bar.latte

# compile a single file with explicit output
php tools/latte-convert/compile.php in.latte out.phtml

# compile every .latte in a directory tree (recursive)
php tools/latte-convert/compile.php src/Tracy

# tests
cd tools/latte-convert && vendor/bin/tester tests
```

**Output convention:** each `<dir>/<name>.latte` compiles to `<dir>/../dist/<name>.phtml`
(sibling `dist/` of the input directory). Templates whose basename contains `agent`
are compiled in text (markdown) mode.
