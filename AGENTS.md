# Repository Guidelines

## Project Structure & Module Organization
This repository is a Typecho theme. Root-level `*.php` files are page templates and partials such as `index.php`, `post.php`, `header.php`, and `footer.php`. Shared backend logic lives in `libs/` (`core.php`, `api.php`, `search.php`, `custom_config.php`), and reusable UI fragments live in `widgets/`. Static assets are split between legacy directories `css/`, `js/`, `img/` and additional packaged resources under `assets/css`, `assets/js`, `assets/fonts`, and `assets/img`. Third-party editor code is vendored in `libs/Vditor/`.

## Build, Test, and Development Commands
There is no build step in this repo; edit files directly and test through a local Typecho install.

- `cp -R . /path/to/typecho/usr/themes/Butterfly` installs the theme into a local Typecho instance.
- `php -l functions.php` checks PHP syntax for a changed file; repeat for edited `libs/*.php` or template files.
- `find . -name '*.php' -print0 | xargs -0 -n1 php -l` runs a quick syntax sweep before opening a PR.
- `git tag v1.2.3 && git push origin v1.2.3` triggers the GitHub release workflow in `.github/workflows/`.

## Coding Style & Naming Conventions
Match the existing theme style: PHP templates with inline HTML, 4-space indentation in markup blocks, and guard clauses such as `if (!defined('__TYPECHO_ROOT_DIR__')) exit;` at file tops. Keep helper functions in `libs/` or `functions.php`; keep page-specific rendering in template files. Use lowercase, descriptive filenames consistent with the repo (`post_header.php`, `category-list.php`). Preserve existing asset organization instead of introducing new tool-generated directories.

## Testing Guidelines
This project does not include an automated test suite yet. Contributors should treat syntax checks and browser validation as the minimum bar. After changing templates, verify the home page, post page, archive page, comments, search, and any affected widget in a local Typecho site. If you touch Pjax, comments, or admin configuration code, test both first load and client-side navigation flows.

## Commit & Pull Request Guidelines
Recent history uses concise conventional prefixes such as `feat:`, `fix:`, `docs:`, `style:`, and `chore:`. Keep commit subjects short and imperative, for example `fix: prevent duplicate Pjax initialization`. PRs should include a brief summary, affected templates or modules, manual test notes, linked issues, and screenshots for visible UI changes.

## Security & Configuration Tips
Sanitize user-controlled output and keep existing protections intact when editing `functions.php` or `libs/api.php`. Do not commit local secrets, analytics IDs, or site-specific Typecho configuration values.
