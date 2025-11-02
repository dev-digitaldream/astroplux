# Configuration Cloudflare Pages pour Grav CMS

Guide complet pour connecter votre front Astro hébergé sur Cloudflare Pages à votre Grav CMS sur cPanel.

## Étapes préliminaires

### 1. Installation du plugin Grav

1. **Copiez le plugin** :
   ```bash
   # Depuis votre projet local, copiez vers votre installation Grav
   cp -r grav-plugin/astro-export/ /chemin/vers/grav/user/plugins/
   ```

2. **Activez le plugin** :
   - Via l'admin Grav : Plugins → astro-export → Enable
   - Ou manuellement : Créez `user/config/plugins/astro-export.yaml` :
     ```yaml
     enabled: true
     route: /astro-export.json
     blog_root: /blog
     include_html: true
     ```

3. **Vérifiez l'endpoint** :
   ```bash
   curl https://votre-domaine-cpanel.com/astro-export.json
   ```

### 2. Configuration du projet Cloudflare Pages

1. **Variables d'environnement** :
   Dans votre dashboard Cloudflare Pages → Settings → Environment variables :
   
   ```
   GRAV_EXPORT_URL=https://votre-domaine-cpanel.com/astro-export.json
   CMS_SOURCE=grav
   ```

2. **Configuration du build** :
   - **Build command** : `npm run build`
   - **Build output directory** : `dist`
   - **Node.js version** : `18` ou supérieur

### 3. Déploiement et test

1. **Déployez votre projet** :
   ```bash
   # Connectez votre repo à Cloudflare Pages
   # Ou utilisez Wrangler pour le déploiement manuel
   npm run build
   npx wrangler pages deploy dist
   ```

2. **Testez la synchronisation** :
   ```bash
   # Test local avec la variable d'environnement
   export GRAV_EXPORT_URL=https://votre-domaine-cpanel.com/astro-export.json
   export CMS_SOURCE=grav
   npm run sync
   ```

## Dépannage

### Erreurs courantes

#### CORS Error
Si vous obtenez une erreur CORS :
1. Vérifiez que le plugin Grav est bien installé
2. Confirmez que les headers CORS sont présents :
   ```bash
   curl -I https://votre-domaine-cpanel.com/astro-export.json
   # Doit contenir : Access-Control-Allow-Origin: *
   ```

#### 404 Not Found
1. Vérifiez que le plugin est activé dans l'admin Grav
2. Confirmez l'URL de l'endpoint
3. Vérifiez les permissions des fichiers sur cPanel

#### 500 Server Error
1. Consultez les logs Grav : `logs/grav.log`
2. Vérifiez la version PHP (minimum 7.4 recommandé)
3. Testez avec un appel direct depuis le navigateur

### Monitoring

1. **Logs de build Cloudflare** :
   - Vérifiez les logs dans votre dashboard Cloudflare Pages
   - Cherchez les erreurs dans le script `sync-grav.mjs`

2. **Tests manuels** :
   ```bash
   # Test de l'API Grav
   curl -H "Accept: application/json" \
        https://votre-domaine-cpanel.com/astro-export.json
   
   # Test de synchronisation locale
   GRAV_EXPORT_URL=https://votre-domaine-cpanel.com/astro-export.json \
   CMS_SOURCE=grav \
   node scripts/sync-grav.mjs
   ```

## Bonnes pratiques

1. **Sécurité** :
   - Limitez l'accès à l'endpoint si nécessaire
   - Utilisez HTTPS obligatoirement
   - Surveillez les logs d'accès

2. **Performance** :
   - Activez la mise en cache sur Cloudflare Pages
   - Optimisez les images dans Grav
   - Utilisez le build hook pour les mises à jour automatiques

3. **Déploiement continu** :
   ```yaml
   # .github/workflows/deploy.yml (optionnel)
   name: Deploy to Cloudflare Pages
   on:
     push:
       branches: [main]
   jobs:
     deploy:
       runs-on: ubuntu-latest
       steps:
         - uses: actions/checkout@v3
         - name: Setup Node.js
           uses: actions/setup-node@v3
           with:
             node-version: '18'
         - name: Install dependencies
           run: npm ci
         - name: Build and deploy
           run: |
             npm run build
             # Déploiement vers Cloudflare Pages
   ```

## Support

- **Documentation Grav** : https://learn.getgrav.org/
- **Documentation Cloudflare Pages** : https://developers.cloudflare.com/pages/
- **Issues du projet** : https://github.com/dev-digitaldream/astroplux/issues
