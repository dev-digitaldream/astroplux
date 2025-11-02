#!/usr/bin/env node
// Sync Ghost (self-hosted) Content API → Astro content collections
// Requires:
// - GHOST_CONTENT_API_URL (e.g., https://your-ghost.com/ghost/api/content)
// - GHOST_CONTENT_API_KEY (Content API key)

import fs from 'node:fs';
import path from 'node:path';
import process from 'node:process';
import slugify from 'slugify';

const API = process.env.GHOST_CONTENT_API_URL;
const KEY = process.env.GHOST_CONTENT_API_KEY;
const ROOT = process.cwd();
const BLOG_DIR = path.join(ROOT, 'src', 'content', 'blog', 'ghost');
const PAGES_DIR = path.join(ROOT, 'src', 'content', 'pages', 'ghost');

const c = {
  green: (s) => `\x1b[32m${s}\x1b[0m`,
  yellow: (s) => `\x1b[33m${s}\x1b[0m`,
  cyan: (s) => `\x1b[36m${s}\x1b[0m`,
  gray: (s) => `\x1b[90m${s}\x1b[0m`,
};

function ensureDir(d) { fs.mkdirSync(d, { recursive: true }); }
function cleanDir(d) { if (fs.existsSync(d)) for (const f of fs.readdirSync(d)) if (f.endsWith('.md')) fs.unlinkSync(path.join(d,f)); }
function y(v, f='') { const val = (v==null||v==='')?f:v; return JSON.stringify(val); }
function toISO(d,f='2025-01-01'){ if(!d) return f; const t=new Date(d); return isNaN(t)?f:t.toISOString().slice(0,10); }
function toSlug(s,f='untitled'){ return slugify((s||f), { lower:true, strict:true }); }

function write(file, fm, body){ const out = ['---', ...fm, '---', '', body||'', ''].join('\n'); fs.writeFileSync(file,out,{encoding:'utf8'}); }

async function fetchJSON(url){ const r=await fetch(url, { headers:{Accept:'application/json'} }); if(!r.ok) throw new Error(`HTTP ${r.status}`); return r.json(); }

async function main(){
  console.log(c.cyan('Sync Ghost → Astro starting'));
  if(!API||!KEY){ console.log(c.yellow('! Missing GHOST_CONTENT_API_URL or GHOST_CONTENT_API_KEY')); return; }
  ensureDir(BLOG_DIR); ensureDir(PAGES_DIR);
  cleanDir(BLOG_DIR); cleanDir(PAGES_DIR);

  try{
    const postsUrl = `${API}/posts/?key=${KEY}&include=tags,authors&limit=all`;
    const pagesUrl = `${API}/pages/?key=${KEY}&limit=all`;
    const posts = (await fetchJSON(postsUrl)).posts || [];
    const pages = (await fetchJSON(pagesUrl)).pages || [];

    let pc=0; for(const p of posts){
      const slug = p.slug || toSlug(p.title);
      const fm = [
        `title: ${y(p.title)}`,
        `description: ${y(p.excerpt || p.meta_description || p.title)}`,
        `date: ${y(toISO(p.published_at))}`,
        `category: ${y('blog')}`,
        `tags: ${JSON.stringify((p.tags||[]).map(t=>t.name).filter(Boolean))}`,
        `excerpt: ${y(p.excerpt || '')}`,
        `metaTitle: ${y(p.meta_title || p.title)}`,
        `metaDescription: ${y(p.meta_description || p.excerpt || p.title)}`,
        `cover: ${y(p.feature_image || '')}`,
      ];
      const file = path.join(BLOG_DIR, `${slug}.md`);
      write(file, fm, p.html || '');
      pc++;
    }

    let gc=0; for(const g of pages){
      const slug = g.slug || toSlug(g.title);
      const fm = [
        `title: ${y(g.title)}`,
        `description: ${y(g.excerpt || g.meta_description || g.title)}`,
        `metaTitle: ${y(g.meta_title || g.title)}`,
        `metaDescription: ${y(g.meta_description || g.excerpt || g.title)}`,
        `cover: ${y(g.feature_image || '')}`,
      ];
      const file = path.join(PAGES_DIR, `${slug}.md`);
      write(file, fm, g.html || '');
      gc++;
    }

    console.log(c.green(`✓ Synced ${pc} posts, ${gc} pages from Ghost`));
  }catch(e){
    console.log(c.yellow(`! Ghost sync skipped: ${e?.message||e}`));
  }
}

main();

