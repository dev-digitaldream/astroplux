# Deploy: PluXML → Astro Nano → Cloudflare Pages

## Cloudflare Pages
- Build command: `npm run build`
- Output dir: `dist`
- Environment variables:
  - `PLUXML_EXPORT_URL=https://alaoui.be/pluxml/export.php`
  - `NODE_VERSION=18`
  - (optional) `IMAGE_ALLOWLIST=alaoui.be,cdn.example.com`

## Sync script
- Prebuild hook runs `scripts/sync-pluxml.mjs` to fetch, convert, and write content.
- Articles → `src/content/blog/pluxml/*.md|mdx`
- Pages → `src/content/pages/pluxml/*.md|mdx`
- Images → `src/assets/pluxml/<slug>/*` and transformed to `<Image />` via astro:assets

## PluXML plugin
- Folder to install on PluXML: `pluxml-plugin/plxAstroSync` → `plugins/plxAstroSync`
- Config (admin):
  - Relay URL: `https://<your-domain>/pluxml/trigger-cloudflare.php`
  - Secret: long random string (e.g., `5c2f9e9a6b3140f0b3a2b1f8b9d7c4aa`)
  - Cloudflare Deploy Hook URL: paste from Pages → Settings → Build hooks
  - Enable auto-trigger on save if desired

## Local development
- `PLUXML_EXPORT_URL=... npm run sync:watch` and in another terminal `npm run dev`
- Edit/publish in PluXML → content regenerates locally (images optimized)

