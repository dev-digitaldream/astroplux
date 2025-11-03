# Astro Nano Config Plugin

Plugin Grav pour administrer Astro Nano depuis l'interface Grav.

## Installation

1. Uploadez le dossier `astro-nano-config` vers `user/plugins/astro-nano-config/`.
2. Vider le cache Grav (Admin → Tools → Clear Cache).
3. Activez le plugin (Admin → Plugins → Astro Nano Config → Enable).

## Utilisation

### Configuration du site

1. Allez dans **Admin → Plugins → Astro Nano Config**.
2. Remplissez les champs :
   - **Titre du site** : nom de votre site
   - **Description** : baseline du site
   - **Email** : contact principal
   - **Titres & descriptions** : pour chaque section (Home, Blog, Work, Projects)
   - **Réseaux sociaux** : ajoutez vos liens (GitHub, LinkedIn, Twitter, etc.)
3. Cliquez **Save**.

### Synchronisation Astro

Après avoir modifié des contenus dans Grav (articles, projets, pages, etc.) :

1. Allez dans **Admin → Plugins → Astro Nano Config**.
2. Descendez jusqu'à la section **Synchronisation Astro**.
3. Cliquez sur le bouton **🔄 Sync Astro Now**.
4. Attendez 1-2 minutes.
5. Vérifiez dans **Cloudflare Pages → Deployments** que le build est en cours.

### Structures Grav

Pour que tout soit administrable depuis Grav, créez ces dossiers dans `user/pages/` :

- **Blog** : `03.blog/01.mon-article/default.md`
- **Projects** : `04.projects/01.mon-projet/default.md`
- **Work** : `05.work/01.mon-experience/default.md`
- **Pages statiques** : `06.pages/01.about/default.md`
- **Accueil** : `01.home/default.md` (optionnel)

Chaque dossier doit contenir un fichier `default.md` avec le frontmatter approprié.

## Frontmatter

### Blog (articles)
```yaml
---
title: Mon article
description: Courte description
date: 2025-01-15
tags: [astro, grav]
---
Contenu de l'article...
```

### Projects (projets)
```yaml
---
title: Mon projet
description: Description du projet
date: 2025-01-15
demo_url: https://demo.example.com
repo_url: https://github.com/user/project
tags: [astro, react]
---
Contenu du projet...
```

### Work (expériences)
```yaml
---
company: Nom de l'entreprise
role: Votre rôle
date_start: 2023-01-01
date_end: 2025-01-15
location: Ville, Pays
---
Description de l'expérience...
```

### Pages statiques
```yaml
---
title: À propos
description: Page à propos
---
Contenu de la page...
```

## Configuration avancée

Le plugin génère automatiquement un fichier `user/data/astro-nano/config.json` contenant tous les paramètres. Ce fichier est consommé par le script d'export Grav et utilisé pour générer `src/site.config.ts` côté Astro.

## Dépannage

- **Le bouton Sync ne fonctionne pas** : vérifiez que `trigger-deploy.php` est accessible sur votre serveur.
- **Les changements ne s'affichent pas** : attendez la fin du build Cloudflare (vérifiez dans Deployments).
- **Le cache Grav bloque les changements** : videz le cache (Admin → Tools → Clear Cache).

## Support

Pour toute question, consultez la documentation Astro Nano ou le guide Grav.
