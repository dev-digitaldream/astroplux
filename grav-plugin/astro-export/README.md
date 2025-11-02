# Astro Export Plugin for Grav CMS

Ce plugin permet d'exporter le contenu de votre site Grav vers un format JSON compatible avec Astro, facilitant la synchronisation avec votre front-end hébergé sur Cloudflare Pages.

## Installation

1. Copiez le dossier `astro-export` dans `user/plugins/` de votre installation Grav
2. Activez le plugin via l'admin Grav ou en modifiant `user/config/plugins/astro-export.yaml`
3. Configurez les paramètres selon vos besoins

## Configuration

Les options disponibles dans `user/config/plugins/astro-export.yaml` :

```yaml
enabled: true                    # Activer/désactiver le plugin
route: /astro-export.json       # Route d'export JSON
blog_root: /blog                 # Racine des articles de blog
include_html: true               # Inclure le contenu HTML
```

## Utilisation

Une fois installé, le plugin expose un endpoint JSON à l'URL configurée (par défaut : `/astro-export.json`) qui retourne :

```json
{
  "config": {
    "site_title": "Titre du site",
    "site_description": "Description du site",
    "email": "email@example.com"
  },
  "posts": [
    {
      "title": "Titre de l'article",
      "slug": "slug-article",
      "route": "/blog/slug-article",
      "date": "2025-01-01T00:00:00+00:00",
      "tags": ["tag1", "tag2"],
      "header": {...},
      "html": "<p>Contenu HTML...</p>"
    }
  ],
  "pages": [
    {
      "title": "Titre de la page",
      "slug": "slug-page",
      "route": "/slug-page",
      "header": {...},
      "html": "<p>Contenu HTML...</p>"
    }
  ]
}
```

## Configuration pour Cloudflare Pages

Pour connecter votre front Astro sur Cloudflare Pages :

1. **Variable d'environnement** : Définissez `GRAV_EXPORT_URL` dans les settings de votre projet Cloudflare Pages :
   ```
   GRAV_EXPORT_URL=https://votre-domaine-cpanel.com/astro-export.json
   ```

2. **Build command** : Utilisez la commande de build avec synchronisation Grav :
   ```bash
   npm run build
   ```

3. **Script de synchronisation** : Le script `scripts/sync-grav.mjs` sera automatiquement utilisé lors du build pour récupérer le contenu depuis Grav.

## Dépannage

### CORS Errors
Le plugin inclut les en-têtes CORS nécessaires. Si vous rencontrez des erreurs CORS, vérifiez que votre hébergeur cPanel n'ajoute pas de restrictions supplémentaires.

### Erreur 404
- Vérifiez que le plugin est bien activé dans l'admin Grav
- Confirmez que la route configurée est accessible
- Vérifiez les permissions des fichiers

### Erreur 500
- Consultez les logs d'erreurs de Grav dans `logs/grav.log`
- Vérifiez la syntaxe PHP dans vos pages

## Structure des dossiers

```
user/plugins/astro-export/
├── astro-export.php      # Plugin principal
├── astro-export.yaml     # Configuration par défaut
├── blueprints.yaml       # Configuration admin
└── README.md            # Documentation
```

## Support

Pour toute question ou problème, consultez la documentation du projet ou ouvrez une issue sur le dépôt GitHub.

