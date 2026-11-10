# Testing

- **Unit** (no WordPress): `cd plugin-template && composer install && composer test`.
- **Static analysis / style:** `composer phpstan`, `composer phpcs` (WPCS 3.4).
- **In a real WordPress:** from the repo root `npx @wordpress/env start` (uses `.wp-env.json`), then open http://localhost:8888 (admin / password).
- **Plugin Check:** runs on PRs to `dev`/`main` (`.github/workflows/plugin-check.yml`) or manually from the Actions tab.
- **Demo in the browser:** `dev/blueprint.json` for WordPress Playground (replace the zip URL with a public release zip).
- Integration tests: add the WordPress test suite via wp-env (`npx @wordpress/env run tests-cli …`) and a second PHPUnit suite.
