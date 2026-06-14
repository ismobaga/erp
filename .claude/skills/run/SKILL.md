---
name: run
description: Start the CROMMIX ERP dev server. Launches php artisan serve and vite in parallel so the app is fully usable in the browser.
---

# Run – CROMMIX ERP Dev Server

Start both the Laravel backend and Vite asset pipeline for local development.

## Steps

1. Make sure dependencies are installed:
   - PHP deps: `composer install` (skip if `vendor/` exists and composer.json unchanged)
   - Node deps: `npm install` (skip if `node_modules/` exists and package.json unchanged)

2. Start both processes in the background using `concurrently` (already a dev dependency):

```bash
npx concurrently \
  "php artisan serve --host=0.0.0.0 --port=8000" \
  "npm run dev" \
  --names "laravel,vite" --prefix-colors "green,blue"
```

Or launch them separately in two terminals:
- Terminal 1: `php artisan serve`
- Terminal 2: `npm run dev`

3. Open the app at **http://localhost:8000**
   - Filament admin panel: **http://localhost:8000/admin**

## Useful flags

| Task | Command |
|------|---------|
| Different port | `php artisan serve --port=8080` |
| Production build | `npm run build` |
| Clear config cache | `php artisan config:clear && php artisan cache:clear` |

## Common issues

- **Port in use**: `lsof -i :8000` then kill the PID, or use `--port=8001`
- **Manifest not found**: run `npm run build` or `npm run dev` first
- **Key not set**: `php artisan key:generate`
- **WSL2 note**: use `--host=0.0.0.0` so Windows browser can reach the server
