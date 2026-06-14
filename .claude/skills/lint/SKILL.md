---
name: lint
description: Run Laravel Pint (PHP code style fixer) on the CROMMIX ERP codebase.
---

# Lint – CROMMIX ERP

Run Laravel Pint to check and fix PHP code style.

## Commands

```bash
# Check for style issues without fixing (dry run)
./vendor/bin/pint --test

# Fix all style issues
./vendor/bin/pint

# Fix a specific file
./vendor/bin/pint app/Models/Invoice.php

# Fix a specific directory
./vendor/bin/pint app/Filament/Resources/

# Show a diff of what would change (without applying)
./vendor/bin/pint --test --diff
```

## When to run

- Before committing — Pint is fast and fixes most style issues automatically
- After generating new files with `make:*` commands
- When reviewing a PR diff that has style issues

## Config

Pint config lives in `pint.json` (project root) if it exists. The default preset is `laravel`.

## Integration tip

Add to git pre-commit or run as part of CI:
```bash
./vendor/bin/pint --test
```
A non-zero exit code means style violations were found.
