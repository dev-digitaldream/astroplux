#!/usr/bin/env node
/**
 * Upload et optimise les images vers Cloudflare R2
 * Usage: node scripts/upload-images-r2.mjs [--source ./images]
 */

import fs from 'node:fs';
import path from 'node:path';
import { execSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';

// Charger .env.local
const __dirname = path.dirname(fileURLToPath(import.meta.url));
const envPath = path.join(__dirname, '..', '.env.local');
if (fs.existsSync(envPath)) {
  const envContent = fs.readFileSync(envPath, 'utf-8');
  envContent.split('\n').forEach(line => {
    const [key, value] = line.split('=');
    if (key && value && !process.env[key]) {
      process.env[key] = value.trim();
    }
  });
}

const R2_ACCOUNT_ID = process.env.CLOUDFLARE_ACCOUNT_ID;
const R2_ACCESS_KEY = process.env.CLOUDFLARE_R2_ACCESS_KEY_ID;
const R2_SECRET_KEY = process.env.CLOUDFLARE_R2_SECRET_ACCESS_KEY;
const R2_BUCKET = 'astro-nano-images';
const R2_ENDPOINT = `https://${R2_ACCOUNT_ID}.r2.cloudflarestorage.com`;

const sourceDir = process.argv[2] || './assets/images';

if (!R2_ACCESS_KEY || !R2_SECRET_KEY) {
  console.error('❌ Erreur: CLOUDFLARE_R2_ACCESS_KEY_ID et CLOUDFLARE_R2_SECRET_ACCESS_KEY non définis');
  console.error(`   Vérifiez que .env.local existe et contient:`);
  console.error(`   CLOUDFLARE_ACCOUNT_ID=...`);
  console.error(`   CLOUDFLARE_R2_ACCESS_KEY_ID=...`);
  console.error(`   CLOUDFLARE_R2_SECRET_ACCESS_KEY=...`);
  process.exit(1);
}

async function uploadImage(filePath) {
  const fileName = path.basename(filePath);
  const ext = path.extname(fileName).toLowerCase();

  // Formats supportés
  if (!['.jpg', '.jpeg', '.png', '.gif', '.webp'].includes(ext)) {
    console.log(`⏭️  Ignoré: ${fileName} (format non supporté)`);
    return;
  }

  try {
    // Convertir en WebP si nécessaire
    let uploadPath = filePath;
    let uploadName = fileName;

    if (ext !== '.webp') {
      const webpPath = filePath.replace(ext, '.webp');
      console.log(`🔄 Conversion en WebP: ${fileName}`);
      execSync(`cwebp "${filePath}" -o "${webpPath}" -q 80`, { stdio: 'inherit' });
      uploadPath = webpPath;
      uploadName = fileName.replace(ext, '.webp');
    }

    // Upload vers R2
    const fileContent = fs.readFileSync(uploadPath);
    const uploadUrl = `${R2_ENDPOINT}/${R2_BUCKET}/${uploadName}`;

    const response = await fetch(uploadUrl, {
      method: 'PUT',
      headers: {
        'Authorization': `AWS4-HMAC-SHA256 Credential=${R2_ACCESS_KEY}`,
        'Content-Type': 'image/webp',
      },
      body: fileContent,
    });

    if (response.ok) {
      const cdnUrl = `https://images.alaoui.be/${R2_BUCKET}/${uploadName}`;
      console.log(`✅ Uploadé: ${uploadName}`);
      console.log(`   CDN: ${cdnUrl}`);
    } else {
      console.error(`❌ Erreur upload ${fileName}: ${response.statusText}`);
    }
  } catch (error) {
    console.error(`❌ Erreur traitement ${fileName}: ${error.message}`);
  }
}

async function main() {
  if (!fs.existsSync(sourceDir)) {
    console.error(`❌ Dossier non trouvé: ${sourceDir}`);
    process.exit(1);
  }

  const files = fs.readdirSync(sourceDir).filter(f => !f.startsWith('.'));

  if (files.length === 0) {
    console.log(`ℹ️  Aucune image trouvée dans ${sourceDir}`);
    return;
  }

  console.log(`📸 Upload de ${files.length} image(s) vers R2...\n`);

  for (const file of files) {
    await uploadImage(path.join(sourceDir, file));
  }

  console.log(`\n✨ Upload terminé !`);
}

main().catch(err => {
  console.error('Erreur:', err);
  process.exit(1);
});
