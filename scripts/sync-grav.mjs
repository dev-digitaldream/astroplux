#!/usr/bin/env node
// Sync Grav export JSON → Astro content collections
// Env: GRAV_EXPORT_URL (e.g., https://your.site/astro-export.json)

import fs from 'node:fs';
import path from 'node:path';
import process from 'node:process';
import slugify from 'slugify';

const EXPORT_URL = process.env.GRAV_EXPORT_URL || process.env.PLUXML_EXPORT_URL || '';
const ROOT = process.cwd();
const BLOG_DIR = path.join(ROOT, 'src', 'content', 'blog', 'grav');
const PAGES_DIR = path.join(ROOT, 'src', 'content', 'pages', 'grav');
const SITE_CONFIG_TS = path.join(ROOT, 'src', 'site.config.ts');

const c = {
  green: (s) => `\x1b[32m${s}\x1b[0m`,
  yellow: (s) => `\x1b[33m${s}\x1b[0m`,
  cyan: (s) => `\x1b[36m${s}\x1b[0m`,
  gray: (s) => `\x1b[90m${s}\x1b[0m`,
};

function ensureDir(d){ fs.mkdirSync(d,{recursive:true}); }
function cleanDir(d){ if(fs.existsSync(d)) for(const f of fs.readdirSync(d)) if(f.endsWith('.md')) fs.unlinkSync(path.join(d,f)); }
function y(v,f=''){ const val=(v==null||v==='')?f:v; return JSON.stringify(val); }
function toISO(d,f='2025-01-01'){ if(!d) return f; const t=new Date(d); return isNaN(t)?f:t.toISOString().slice(0,10); }
function toSlug(s,f='untitled'){ return slugify((s||f), { lower:true, strict:true }); }
function write(file, fm, body){ const out = ['---', ...fm, '---', '', body||'', ''].join('\n'); fs.writeFileSync(file,out,{encoding:'utf8'}); }

async function main(){
  console.log(c.cyan('Sync Grav → Astro starting'));
  if(!EXPORT_URL){ console.log(c.yellow('! GRAV_EXPORT_URL not set')); return; }
  ensureDir(BLOG_DIR); ensureDir(PAGES_DIR);
  cleanDir(BLOG_DIR); cleanDir(PAGES_DIR);

  try{
    const r = await fetch(EXPORT_URL, { headers:{Accept:'application/json'} });
    if(!r.ok) throw new Error(`HTTP ${r.status}`);
    const data = await r.json();
    const posts = data.posts || data.articles || [];
    const pages = data.pages || [];
    const cfg   = data.config || {};

    let pc=0; for(const p of posts){
      const title = p.title || p.header?.title || 'Sans titre';
      const slug = p.slug || p.route || toSlug(title);
      const description = p.excerpt || p.header?.summary || p.header?.description || title;
      const date = p.date || p.header?.date || p.header?.published_at;
      const tags = Array.isArray(p.tags) ? p.tags : (Array.isArray(p.header?.tags) ? p.header.tags : []);
      const cover = p.header?.image?.file || p.image || '';
      const fm = [
        `title: ${y(title)}`,
        `description: ${y(description)}`,
        `date: ${y(toISO(date))}`,
        `category: ${y('blog')}`,
        `tags: ${JSON.stringify(tags)}`,
        `excerpt: ${y(p.excerpt || '')}`,
        `metaTitle: ${y(p.header?.meta_title || title)}`,
        `metaDescription: ${y(p.header?.meta_description || description)}`,
        `cover: ${y(cover)}`,
      ];
      const file = path.join(BLOG_DIR, `${slug}.md`);
      write(file, fm, p.html || p.content || '');
      pc++;
    }

    let gc=0; for(const g of pages){
      const title = g.title || g.header?.title || 'Sans titre';
      const slug = g.slug || g.route || toSlug(title);
      const description = g.header?.summary || g.header?.description || title;
      const cover = g.header?.image?.file || g.image || '';
      const fm = [
        `title: ${y(title)}`,
        `description: ${y(description)}`,
        `metaTitle: ${y(g.header?.meta_title || title)}`,
        `metaDescription: ${y(g.header?.meta_description || description)}`,
        `cover: ${y(cover)}`,
      ];
      const file = path.join(PAGES_DIR, `${slug}.md`);
      write(file, fm, g.html || g.content || '');
      gc++;
    }

    // Generate site config at build time so front can import it
    try {
      const socials = Array.isArray(cfg.socials) ? cfg.socials : [];
      const siteTs = `// Auto-generated from Grav export. Do not commit.
export const SITE_CONFIG = {
  NAME: ${y(cfg.site_title || 'Site')},
  EMAIL: ${y(cfg.email || '')},
  HOME_TITLE: ${y(cfg.home_title || cfg.site_title || 'Accueil')},
  HOME_DESCRIPTION: ${y(cfg.home_description || cfg.site_description || '')},
  BLOG_TITLE: ${y(cfg.blog_title || 'Blog')},
  BLOG_DESCRIPTION: ${y(cfg.blog_description || '')},
  WORK_TITLE: ${y(cfg.work_title || 'Work')},
  WORK_DESCRIPTION: ${y(cfg.work_description || '')},
  PROJECTS_TITLE: ${y(cfg.projects_title || 'Projects')},
  PROJECTS_DESCRIPTION: ${y(cfg.projects_description || '')},
  SOCIALS: ${JSON.stringify(socials, null, 2)}
};
`;
      fs.writeFileSync(SITE_CONFIG_TS, siteTs, {encoding:'utf8'});
      console.log(c.gray(`generated ${path.relative(ROOT, SITE_CONFIG_TS)}`));
    } catch (e) {
      console.log(c.yellow(`! failed to write site.config.ts: ${e?.message||e}`));
    }

    console.log(c.green(`✓ Synced ${pc} posts, ${gc} pages from Grav and generated site.config.ts`));
  }catch(e){
    console.log(c.yellow(`! Grav sync skipped: ${e?.message||e}`));
  }
}

main();

