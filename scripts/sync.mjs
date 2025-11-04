#!/usr/bin/env node
// Selects the right sync pipeline based on CMS_SOURCE env
// CMS_SOURCE=ghost | pluxml | grav (default: pluxml)

import 'dotenv/config';
import fs from 'node:fs';
import path from 'node:path';
import { spawn } from 'node:child_process';

// When running inside Astro prebuild, .env.local isn't automatically loaded.
// dotenv/config above loads .env; extend it to support .env.local like our other scripts.
const envLocalPath = path.resolve('.env.local');
if (fs.existsSync(envLocalPath)) {
  const lines = fs.readFileSync(envLocalPath, 'utf8').split(/\r?\n/);
  for (const line of lines) {
    if (!line || line.trim().startsWith('#')) continue;
    const [key, ...rest] = line.split('=');
    const value = rest.join('=').trim();
    if (key && !(key in process.env)) {
      process.env[key.trim()] = value;
    }
  }
}

const source = (process.env.CMS_SOURCE || 'pluxml').toLowerCase();
let script = 'scripts/sync-pluxml.mjs';
if (source === 'ghost') script = 'scripts/sync-ghost.mjs';
if (source === 'grav') script = 'scripts/sync-grav.mjs';

const child = spawn(process.execPath, [script], { stdio: 'inherit' });
child.on('exit', (code) => process.exit(code ?? 0));
