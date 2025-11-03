# Images Cloudflare R2 Setup

Optimisation et serveur d'images via Cloudflare R2 avec conversion automatique en WebP.

## 🚀 Setup

### 1. Activer R2 sur Cloudflare

1. Allez dans **Cloudflare Dashboard** → **R2**
2. Cliquez **Create bucket**
3. Nom : `astro-nano-images`
4. Région : auto
5. Cliquez **Create bucket**

### 2. Créer une clé d'accès R2

1. Allez dans **R2** → **Settings** → **API tokens**
2. Cliquez **Create API token**
3. Nom : `astro-nano-upload`
4. Permissions : `Object Read & Write`
5. Copiez :
   - **Access Key ID**
   - **Secret Access Key**

### 3. Configurer les variables d'environnement

Créez un fichier `.env.local` :

```bash
CLOUDFLARE_ACCOUNT_ID=votre_account_id
CLOUDFLARE_R2_ACCESS_KEY_ID=votre_access_key
CLOUDFLARE_R2_SECRET_ACCESS_KEY=votre_secret_key
```

Ou sur Cloudflare Pages (Settings → Environment variables) :

```
CLOUDFLARE_ACCOUNT_ID = xxx
CLOUDFLARE_R2_ACCESS_KEY_ID = xxx
CLOUDFLARE_R2_SECRET_ACCESS_KEY = xxx
```

### 4. Créer un Worker pour servir les images

Déployez `workers/image-optimizer.js` sur Cloudflare Workers :

```bash
npm install -g wrangler
wrangler deploy workers/image-optimizer.js
```

Ou manuellement :
1. Cloudflare Dashboard → **Workers & Pages**
2. Créer un nouveau Worker
3. Copier le contenu de `workers/image-optimizer.js`
4. Déployer

### 5. Configurer le domaine R2

1. Allez dans **R2** → **Settings**
2. **Public access** → **Connect domain**
3. Domaine : `images.alaoui.be` (ou votre domaine)
4. Cliquez **Connect domain**

## 📸 Workflow d'upload

### Depuis Grav

1. **Uploader les images** dans Grav Admin (Pages → Media)
2. Les images se stockent dans `user/pages/01.home/`
3. **Télécharger** les images localement
4. **Uploader** vers R2 :
   ```bash
   node scripts/upload-images-r2.mjs ./downloaded-images
   ```

### Depuis le repo

1. **Ajouter les images** dans `assets/images/`
2. **Uploader** vers R2 :
   ```bash
   node scripts/upload-images-r2.mjs
   ```
3. **Commit** et **push** (optionnel, les images ne sont pas nécessaires dans Git)

## 🖼️ Utiliser les images

### Dans Grav markdown

```markdown
![Hero](https://images.alaoui.be/astro-nano-images/hero.webp)
```

### Avec optimisation (Worker)

```markdown
![Hero](https://images.alaoui.be/astro-nano-images/hero.jpg?w=800&q=80&f=webp)
```

Paramètres :
- `w` : largeur (px)
- `q` : qualité (1-100)
- `f` : format (webp, avif, jpg)

## 📊 Formats supportés

- **Entrée** : JPG, PNG, GIF, WebP
- **Sortie** : WebP (optimisé par défaut)
- **Taille** : ~70-80% plus petite qu'une image originale

## 💡 Bonnes pratiques

1. **Compresser avant d'uploader** : utilisez `cwebp` ou TinyPNG
2. **Nommer les fichiers** : `hero.jpg`, `feature-1.jpg` (pas d'espaces)
3. **Dimensions** : optimisez pour le web (max 2000px de large)
4. **Cache** : les images sont cachées 1 an (immutable)

## 🔗 Ressources

- [Cloudflare R2 Docs](https://developers.cloudflare.com/r2/)
- [cwebp (WebP encoder)](https://developers.google.com/speed/webp/docs/cwebp)
- [Image Optimization Best Practices](https://web.dev/image-optimization/)
