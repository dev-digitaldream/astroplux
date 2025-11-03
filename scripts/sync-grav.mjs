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
const PROJECTS_DIR = path.join(ROOT, 'src', 'content', 'projects', 'grav');
const WORK_DIR = path.join(ROOT, 'src', 'content', 'work', 'grav');
const SITE_CONFIG_TS = path.join(ROOT, 'src', 'site.config.ts');
const HOME_FILE = path.join(PAGES_DIR, 'home.md');

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
function toDateMaybe(d){ if(!d) return ''; const t=new Date(d); return isNaN(t)?d:t.toISOString().slice(0,10); }
function convertLocalImagesToR2(html){ if(!html) return ''; return html.replace(/!\[([^\]]*)\]\((?!https?:\/\/)([^)]+\.(jpg|jpeg|png|gif|webp))\)/gi, (match, alt, filename) => { const webpName = filename.replace(/\.[^.]+$/, '.webp'); return `![${alt}](https://images.alaoui.be/astro-nano-images/${webpName})`; }); }

async function main(){
  console.log(c.cyan('Sync Grav → Astro starting'));
  if(!EXPORT_URL){ console.log(c.yellow('! GRAV_EXPORT_URL not set')); return; }
  ensureDir(BLOG_DIR); ensureDir(PAGES_DIR); ensureDir(PROJECTS_DIR); ensureDir(WORK_DIR);
  cleanDir(BLOG_DIR); cleanDir(PAGES_DIR); cleanDir(PROJECTS_DIR); cleanDir(WORK_DIR);

  try{
    const r = await fetch(EXPORT_URL, { headers:{Accept:'application/json'} });
    if(!r.ok) throw new Error(`HTTP ${r.status}`);
    const data = await r.json();
    const posts = data.posts || data.articles || [];
    const pages = data.pages || [];
    const projects = data.projects || [];
    const work = data.work || [];
    const home = data.home || null;
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
      write(file, fm, convertLocalImagesToR2(p.html || p.content || ''));
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

    let prc = 0; for (const project of projects) {
      const title = project.title || project.header?.title || 'Sans titre';
      const slug = project.slug || project.route || toSlug(title);
      const description = project.description || project.header?.summary || title;
      const date = project.date || project.header?.date;
      const demo = project.demo_url || project.header?.demo_url || project.header?.demo;
      const repo = project.repo_url || project.header?.repo_url || project.header?.repository;
      const tags = Array.isArray(project.tags) ? project.tags : (Array.isArray(project.header?.tags) ? project.header.tags : []);
      const draft = project.draft || project.header?.draft || false;
      const fm = [
        `title: ${y(title)}`,
        `description: ${y(description)}`,
        `date: ${y(toISO(date))}`,
        `draft: ${draft ? 'true' : 'false'}`,
        `demoURL: ${y(demo || '')}`,
        `repoURL: ${y(repo || '')}`,
        `tags: ${JSON.stringify(tags)}`,
      ];
      const file = path.join(PROJECTS_DIR, `${slug}.md`);
      write(file, fm, convertLocalImagesToR2(project.html || project.content || ''));
      prc++;
    }

    let wc = 0; for (const item of work) {
      const title = item.title || item.company || 'Entreprise';
      const slug = item.slug || item.route || toSlug(title);
      const company = item.company || item.header?.company || title;
      const role = item.role || item.header?.role || item.header?.position || '';
      const dateStart = item.date_start || item.header?.date_start || item.header?.from;
      const dateEnd = item.date_end || item.header?.date_end || item.header?.to;
      const fm = [
        `company: ${y(company)}`,
        `role: ${y(role)}`,
        `dateStart: ${y(toDateMaybe(dateStart))}`,
        `dateEnd: ${y(toDateMaybe(dateEnd))}`,
      ];
      const file = path.join(WORK_DIR, `${slug}.md`);
      write(file, fm, convertLocalImagesToR2(item.html || item.content || ''));
      wc++;
    }

    for (const page of pages) {
      const title = page.title || page.header?.title || 'Sans titre';
      const slug = page.slug || page.route || toSlug(title);
      const description = page.header?.summary || page.header?.description || title;
      const fm = [
        `title: ${y(title)}`,
        `description: ${y(description)}`,
        `metaTitle: ${y(page.header?.meta_title || title)}`,
        `metaDescription: ${y(page.header?.meta_description || description)}`,
      ];
      const file = path.join(PAGES_DIR, `${slug}.md`);
      write(file, fm, convertLocalImagesToR2(page.html || page.content || ''));
    }

    if (home) {
      const title = home.title || home.header?.title || 'Accueil';
      const description = home.header?.summary || home.header?.description || title;
      const fm = [
        `title: ${y(title)}`,
        `description: ${y(description)}`,
        `metaTitle: ${y(home.header?.meta_title || title)}`,
        `metaDescription: ${y(home.header?.meta_description || description)}`,
      ];
      write(HOME_FILE, fm, convertLocalImagesToR2(home.html || home.content || ''));
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

    console.log(c.green(`✓ Synced ${pc} posts, ${gc} pages, ${prc} projects, ${wc} work entries from Grav and generated site.config.ts`));
  }catch(e){
    console.log(c.yellow(`! Grav sync skipped: ${e?.message||e}`));
  }
}

main();

