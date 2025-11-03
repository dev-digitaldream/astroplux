# R2 Image Sync Plugin

Plugin Grav pour synchroniser automatiquement les images vers Cloudflare R2 avec conversion WebP et remplacement d'URLs.

## Installation

1. Uploadez le dossier `r2-image-sync` vers `user/plugins/r2-image-sync/`.
2. Vider le cache Grav (Admin → Tools → Clear Cache).
3. Activez le plugin (Admin → Plugins → R2 Image Sync → Enable).

## Configuration

### 1. Créer les credentials R2

1. Allez dans **Cloudflare Dashboard** → **R2**
2. Cliquez **Manage R2 API Tokens**
3. Cliquez **Create API token**
4. **Permissions** : `Object Read and Write`
5. **Bucket** : `astro-nano-images`
6. Copiez :
   - **Access Key ID**
   - **Secret Access Key**
   - **Account ID** (visible dans R2 Settings)

### 2. Configurer le plugin

1. Allez dans **Admin → Plugins → R2 Image Sync**
2. Remplissez les champs :
   - **Account ID** : votre ID Cloudflare
   - **Access Key ID** : la clé d'accès
   - **Secret Access Key** : la clé secrète
   - **Bucket Name** : `astro-nano-images`
   - **CDN Domain** : `images.alaoui.be` (ou votre domaine)
3. Cliquez **Save**

## Utilisation

### Workflow automatique

1. **Uploadez une image** dans Grav Admin (Pages → Media)
2. **Référencez-la** dans le contenu markdown :
   ```markdown
   ![Mon image](mon-image.png)
   ```
3. **Sauvegardez** la page
4. **Le plugin** :
   - ✅ Détecte l'image locale
   - ✅ Convertit en WebP (80% plus léger)
   - ✅ Upload vers R2
   - ✅ Remplace l'URL locale par l'URL CDN

### Résultat

L'image est maintenant servie via CDN Cloudflare :

```markdown
![Mon image](https://images.alaoui.be/astro-nano-images/mon-image.webp)
```

## Options

- **Auto-convert to WebP** : convertit automatiquement les images en WebP (recommandé)
- **Image Quality** : qualité JPEG/WebP (1-100, défaut: 80)

## Formats supportés

- **Entrée** : JPG, PNG, GIF, WebP
- **Sortie** : WebP (optimisé par défaut)

## Avantages

- ✅ Images **70-80% plus légères**
- ✅ **CDN global** (Cloudflare)
- ✅ **Cache 1 an** (immutable)
- ✅ **Automatique** (pas de manipulation manuelle)
- ✅ **Gratuit** (R2 + Cloudflare)

## Dépannage

### Les images ne sont pas uploadées

1. Vérifiez les credentials R2 dans la config du plugin
2. Vérifiez que le bucket `astro-nano-images` existe
3. Vérifiez les permissions du token (doit être `Object Read & Write`)

### Les URLs ne sont pas remplacées

1. Vérifiez que le CDN Domain est correct
2. Vérifiez que l'image est référencée en markdown (pas en HTML)
3. Vérifiez les logs Grav (Admin → Tools → Logs)

### Erreur "Imagick not found"

Le plugin utilise ImageMagick pour la conversion WebP si disponible. Si non disponible, il copie simplement le fichier original. Installez ImageMagick pour une meilleure compression :

```bash
# macOS
brew install imagemagick

# Ubuntu/Debian
sudo apt-get install imagemagick

# CentOS/RHEL
sudo yum install ImageMagick
```

## Support

Pour toute question, consultez la documentation Grav ou le guide Astro Nano.
