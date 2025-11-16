---
title: "Dégooglisation : comment j’ai migré Drive, Photos et Gmail vers des solutions plus privées et souveraines"
description: "Comment je suis passé de Google (Drive, Photos, Gmail) à des services plus privés et souverains."
date: "2025-11-16"
category: "blog"
tags: []
excerpt: ""
metaTitle: "Dégooglisation : migrer Drive, Photos et Gmail vers des alternatives privées"
metaDescription: "Tutoriel détaillé pour quitter Google : backup, migration, nouvelles solutions cloud, DNS, confidentialité..."
cover: ""
---

La dégooglisation n’est plus un phénomène marginal. Beaucoup d’utilisateurs cherchent aujourd’hui à réduire leur dépendance aux GAFAM, pour des raisons de confidentialité, de contrôle des données et de souveraineté numérique.
Dans cet article, je partage mon propre processus : une transition progressive, pragmatique, avec ses limites et ses choix techniques assumés.

⸻

Pourquoi se dégoogliser ?

Sans tomber dans l’idéologie, la question est simple :
Google centralise une grande partie de nos données personnelles (documents, photos, mails, habitudes, localisation…).

La dégooglisation permet :
	•	de reprendre la main sur ses données,
	•	de réduire la dépendance à un acteur unique,
	•	d’adopter des solutions européennes ou auto-hébergées,
	•	d’éviter l’analyse systématique des contenus à des fins commerciales.

Ma transition n’a rien d’extrême. Je continue d’utiliser certains services Google quand aucune alternative crédible ne répond à mon besoin (comme Google Sheets). Mais pour le reste, j’avance étape par étape.

⸻

1. Migrer Google Drive vers kDrive (Infomaniak)

Google Drive stockait plus de 1,5 To de documents personnels et professionnels.
Pour migrer efficacement sans saturer ma connexion locale, j’ai utilisé :

Méthodologie technique
	•	location d’un VPS low-cost quelques heures,
	•	installation de rclone,
	•	configuration des deux remotes (Google Drive → kDrive),
	•	transfert complet via datacenter (haut débit, aucune limite locale),
	•	durée de l’opération : environ 2h–3h.

kDrive offre une alternative européenne sérieuse, hébergée en Suisse, sans exploitation commerciale des données.
Je précise que je ne suis sponsorisé par personne, c’est un choix personnel.

⸻

2. Quitter Google Photos : NAS QNAP + Immich

Google Photos reste objectivement l’un des services les plus performants du marché.
Pour des raisons de confidentialité, j’ai néanmoins choisi de migrer l’ensemble de ma photothèque vers un NAS QNAP, avec Immich comme interface.

Points techniques
	•	export via Google Takeout,
	•	import local dans Immich,
	•	accès externe sécurisé via Cloudflare Tunnel (aucun port ouvert),
	•	NAS bruyant 24/24, mais cohérent avec mon choix d’auto-hébergement,
	•	sauvegarde automatique du NAS vers kDrive via WebDAV (pas d’intégration native QNAP).

Ce n’est ni plus simple ni plus confortable que Google Photos.
C’est juste plus privé.

⸻

3. Migration progressive de Gmail

Changer d’adresse mail est toujours la partie la plus sensible d’une dégooglisation.

J’ai envisagé Infomaniak Mail, mais la limite à 5 adresses ne correspond pas à mes besoins.
Je gère donc mes boîtes mail via un hébergement mutualisé + CPanel, ce qui me donne :
	•	nombre de boîtes illimité,
	•	contrôle total,
	•	configuration IMAP/SMTP standard.

Ce n’est pas encore parfaitement structuré, mais c’est indépendant de Google.

⸻

4. Ce que je garde encore chez Google

La dégooglisation est un processus progressif.
Certaines briques restent chez Google, notamment :

Google Sheets

Aucune alternative actuelle n’offre son niveau :
	•	de collaboration,
	•	d’intégration,
	•	d’automatisation (Apps Script),
	•	d’API.

Google Photos (usage très limité)

Même après migration, je reconnais son efficacité et sa fiabilité.
Je limite donc son usage mais je ne l’ai pas encore totalement abandonné.

⸻

Conclusion : une dégooglisation réaliste et maîtrisée

La dégooglisation n’est pas un abandon soudain de tous les services Google.
C’est une démarche progressive :
	•	Drive → kDrive
	•	Photos → NAS + Immich
	•	Gmail → CPanel
	•	Sauvegardes → NAS + WebDAV
	•	Accès externe → Cloudflare Tunnel

Chaque étape vise le même objectif : garder le contrôle, sans sacrifier totalement le confort.

![degoogl](https://alaoui.be/grav/blog/degooglisation-comment-jai-migre-drive-photos-et-gmail-vers-des-solutions-plus-privees-et-souveraines/degoogl.webp "degoogl")
