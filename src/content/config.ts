import { z, defineCollection } from "astro:content";

// Blog posts collection
const blog = defineCollection({
  type: "content",
  schema: z.object({
    title: z.string(),
    description: z.string().default(""),
    date: z.coerce.date(), // accept string, expose Date
    draft: z.boolean().optional(),
    // Optional extras (not currently required by pages)
    category: z.string().optional(),
    tags: z.array(z.string()).optional(),
    excerpt: z.string().optional(),
    metaTitle: z.string().optional(),
    metaDescription: z.string().optional(),
    cover: z.string().optional(),
  }),
});

// Projects collection
const projects = defineCollection({
  type: "content",
  schema: z.object({
    title: z.string(),
    description: z.string().default(""),
    date: z.coerce.date(),
    draft: z.boolean().optional(),
    demoURL: z.string().url().optional(),
    repoURL: z.string().url().optional(),
  }),
});

// Work experience collection
const work = defineCollection({
  type: "content",
  schema: z.object({
    company: z.string(),
    role: z.string(),
    dateStart: z.coerce.date(),
    dateEnd: z.union([z.coerce.date(), z.string()]),
  }),
});

// Static pages (optional; for PluXML pages sync)
const pages = defineCollection({
  type: "content",
  schema: z.object({
    title: z.string(),
    description: z.string().default(""),
    metaTitle: z.string().optional(),
    metaDescription: z.string().optional(),
    cover: z.string().optional(),
  }),
});

export const collections = { blog, projects, work, pages };
