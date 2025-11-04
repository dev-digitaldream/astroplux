import type { APIRoute } from "astro";
import { getCollection } from "astro:content";

export const GET: APIRoute = async () => {
  try {
    const [blog, projects, pages] = await Promise.all([
      getCollection("blog").catch(() => []),
      getCollection("projects").catch(() => []),
      getCollection("pages").catch(() => []),
    ]);

    const body = {
      ok: true,
      time: new Date().toISOString(),
      env: {
        PLUXML_EXPORT_URL: Boolean(process.env.PLUXML_EXPORT_URL) || undefined,
        IMAGE_ALLOWLIST: Boolean(process.env.IMAGE_ALLOWLIST) || undefined,
        NODE_VERSION: process.version,
      },
      content: {
        blog: blog.length,
        projects: projects.length,
        pages: pages.length,
      },
    };

    return new Response(JSON.stringify(body), {
      status: 200,
      headers: {
        "content-type": "application/json; charset=utf-8",
        "cache-control": "no-cache, no-store, must-revalidate",
      },
    });
  } catch (err: any) {
    const body = { ok: false, error: err?.message || String(err) };
    return new Response(JSON.stringify(body), {
      status: 500,
      headers: { "content-type": "application/json; charset=utf-8" },
    });
  }
};

