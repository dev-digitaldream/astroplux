import { defineConfig } from "astro/config";
import mdx from "@astrojs/mdx";
import sitemap from "@astrojs/sitemap";
import tailwind from "@astrojs/tailwind";

export default defineConfig({
  site: "https://votre-domaine-cloudflare.pages.dev", // Mettez à jour avec votre domaine Cloudflare Pages
  integrations: [mdx(), sitemap(), tailwind()],
});
