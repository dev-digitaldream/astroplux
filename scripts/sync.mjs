#!/usr/bin/env node
// Selects the right sync pipeline based on CMS_SOURCE env
// CMS_SOURCE=ghost | pluxml | grav (default: pluxml)

import fs from 'node:fs';
import path from 'node:path';
import { spawn } from 'node:child_process';

// Load environment variables from .env and .env.local when running in Node.
const envFiles = ['.env', '.env.local'];
for (const file of envFiles) {
  const filePath = path.resolve(file);
  if (!fs.existsSync(filePath)) continue;
  const lines = fs.readFileSync(filePath, 'utf8').split(/\r?\n/);
  for (const line of lines) {
    if (!line || line.trim().startsWith('#')) continue;
    const idx = line.indexOf('=');
    if (idx === -1) continue;
    const key = line.slice(0, idx).trim();
    const value = line.slice(idx + 1).trim();
    if (key && !(key in process.env)) {
      process.env[key] = value;
    }
  }
}

const source = (process.env.CMS_SOURCE || 'pluxml').toLowerCase();
let script = 'scripts/sync-pluxml.mjs';
if (source === 'ghost') script = 'scripts/sync-ghost.mjs';
if (source === 'grav') script = 'scripts/sync-grav.mjs';

const child = spawn(process.execPath, [script], { stdio: 'inherit' });
child.on('exit', (code) => process.exit(code ?? 0));
