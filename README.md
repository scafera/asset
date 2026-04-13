# scafera/asset

Asset management for the Scafera framework. Configures Symfony's AssetMapper internally — your code references assets via `asset()` in Twig templates, never through PHP imports.

This is a **capability package** (adoption gate). It adds optional asset support to a Scafera project. It does not define folder structure or architectural rules — those belong to architecture packages.

## Core Idea

Scafera treats the asset pipeline as an implementation detail. This package activates AssetMapper with sensible defaults, provides boundary enforcement, and declares companion bundles for the asset ecosystem — so third-party tools like TailwindBundle work out of the box with zero configuration.

## Installation

```bash
composer require scafera/asset
```

The bundle is auto-discovered via Scafera's `symfony-bundle` type detection. No manual registration needed.

## Requirements

- PHP 8.4+
- `scafera/kernel` ^1.0
- `scafera/frontend` (for template rendering with `asset()`)

## Usage

### Referencing assets in templates

Place CSS, JS, and other static files in `assets/` at your project root. Reference them in Twig templates using the `asset()` function:

```twig
<link rel="stylesheet" href="{{ asset('styles/app.css') }}">
<script src="{{ asset('js/app.js') }}"></script>
<img src="{{ asset('images/logo.png') }}">
```

### Using with Tailwind CSS

Install the TailwindBundle — it is auto-registered as a companion bundle:

```bash
composer require symfonycasts/tailwind-bundle
```

Initialize and build:

```bash
vendor/bin/scafera symfony tailwind:init
vendor/bin/scafera symfony tailwind:build
```

For development with auto-rebuild on file changes:

```bash
vendor/bin/scafera symfony tailwind:build --watch
```

Reference the compiled CSS in your template:

```twig
<link rel="stylesheet" href="{{ asset('styles/app.css') }}">
```

### Production deployment

Compile assets with versioned filenames for cache busting:

```bash
vendor/bin/scafera symfony asset-map:compile
```

This writes versioned files to `public/assets/` for direct serving by the web server.

## Companion Bundles

This package declares companion bundles via `extra.scafera-bundles` in its `composer.json`. When you install a companion package, Scafera registers its bundle automatically — no manual configuration needed.

| Package | Bundle | Purpose |
|---------|--------|---------|
| `symfonycasts/tailwind-bundle` | `SymfonycastsTailwindBundle` | Tailwind CSS compilation via standalone binary |

Companions are only registered when installed. If you don't `composer require` the package, the declaration is ignored.

## Boundary Enforcement

This package includes an `AssetMapperLeakageValidator` that scans your `src/` directory for direct `Symfony\Component\AssetMapper\*` imports. Violations are reported by `scafera validate`:

```
Package checks:
  ✗ No AssetMapper imports in userland FAILED
    - src/Service/AssetHelper.php: imports AssetMapper types directly — use asset() in Twig templates instead
```

Use `asset()` in templates — don't import AssetMapper types in PHP.

## Configuration

The bundle configures AssetMapper automatically:

- **Asset paths**: `assets/` at your project root
- **Public prefix**: `/assets/`

To override defaults, add a `framework:` section to `config/config.yaml`:

```yaml
framework:
    asset_mapper:
        paths:
            - 'assets/'
            - 'vendor/some-package/assets/'
```

## What This Package Does NOT Own

- **Template rendering** — owned by `scafera/frontend`
- **JavaScript bundling** — AssetMapper uses native ES modules, no bundler needed
- **Node.js tooling** — TailwindBundle downloads its own standalone binary

## License

MIT
