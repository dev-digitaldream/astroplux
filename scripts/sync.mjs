#!/usr/bin/env node
// Selects the right sync pipeline based on CMS_SOURCE env
// CMS_SOURCE=ghost | pluxml (default: pluxml)

import { spawn } from 'node:child_process';

const source = (process.env.CMS_SOURCE || 'pluxml').toLowerCase();
let script = 'scripts/sync-pluxml.mjs';
if (source === 'ghost') script = 'scripts/sync-ghost.mjs';
if (source === 'grav') script = 'scripts/sync-grav.mjs';

const child = spawn(process.execPath, [script], { stdio: 'inherit' });
child.on('exit', (code) => process.exit(code ?? 0));
