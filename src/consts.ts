import type { Site, Metadata, Socials } from "@types";
import { SITE_CONFIG } from "./site.config";

export const SITE: Site = {
  NAME: SITE_CONFIG.NAME,
  EMAIL: SITE_CONFIG.EMAIL,
  NUM_POSTS_ON_HOMEPAGE: 3,
  NUM_WORKS_ON_HOMEPAGE: 2,
  NUM_PROJECTS_ON_HOMEPAGE: 3,
};

export const HOME: Metadata = {
  TITLE: SITE_CONFIG.HOME_TITLE,
  DESCRIPTION: SITE_CONFIG.HOME_DESCRIPTION,
};

export const BLOG: Metadata = {
  TITLE: SITE_CONFIG.BLOG_TITLE,
  DESCRIPTION: SITE_CONFIG.BLOG_DESCRIPTION,
};

export const WORK: Metadata = {
  TITLE: SITE_CONFIG.WORK_TITLE,
  DESCRIPTION: SITE_CONFIG.WORK_DESCRIPTION,
};

export const PROJECTS: Metadata = {
  TITLE: SITE_CONFIG.PROJECTS_TITLE,
  DESCRIPTION: SITE_CONFIG.PROJECTS_DESCRIPTION,
};

export const SOCIALS: Socials = [
  ...(Array.isArray(SITE_CONFIG.SOCIALS) ? SITE_CONFIG.SOCIALS : [
    { NAME: "github", HREF: "https://github.com/dev-digitaldream" },
    { NAME: "twitter-x", HREF: "https://x.com/digitaldream_io" },
    { NAME: "linkedin", HREF: "https://linkedin.com/in/mohammed-alaoui-1379a617/" },
  ])
];
