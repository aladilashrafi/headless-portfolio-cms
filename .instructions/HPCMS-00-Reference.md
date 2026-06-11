# Headless Portfolio CMS — Shared Reference
## Overview, API Schema, Sanitization & TypeScript Types

**Covers:** v1.1.0 → v1.3.0  
**Plugin Namespace:** `HPCMS\`  
**API Namespace:** `hpcms/v1`  
**WordPress.org Compliant:** Yes

---

## Release Plan

| Version | What Ships |
|---|---|
| **v1.1.0** | Architecture refactor, Settings migration routine, General tab, Social tab, SEO tab, Configuration tab rename, `/main` endpoint (general + social + seo data), `/profile` marked deprecated |
| **v1.2.0** | Home Page tab (all 13 sections), `/main` endpoint fully populated |
| **v1.3.0** | Remove `/profile` endpoint, update API Reference dashboard, updated TypeScript types in docs |

**Golden rule throughout:** every file starts with `defined('ABSPATH') || exit;`, uses `declare(strict_types=1);`, and follows PSR-12.

---

## Option Key Strategy

| Option Key | Contains |
|---|---|
| `hpcms_general` | Name, tagline, email, phone, locations, favicon, header button, footer text |
| `hpcms_homepage` | All 13 Home Page sections (nested array) |
| `hpcms_social` | All social platform URLs |
| `hpcms_seo` | Meta title, meta description, OG image |

The existing `hpcms_api_settings` option (enable_api, enable_cors, allowed_origins, cache_duration) is **untouched**.

---

## Sanitization & Escaping Reference

| Field Type | On Save (Sanitize) | On Output in Admin HTML | On Output in API |
|---|---|---|---|
| Plain text | `sanitize_text_field()` | `esc_attr()` | `esc_html()` |
| Email | `sanitize_email()` | `esc_attr()` | `sanitize_email()` |
| URL | `esc_url_raw()` | `esc_attr()` | `esc_url()` |
| HTML content | `wp_kses_post()` | `wp_kses_post()` | `wp_kses_post()` |
| Integer (attachment ID) | `absint()` | `esc_attr( (string) $int )` | N/A (resolved to URL) |
| Textarea (no HTML) | `sanitize_textarea_field()` | `esc_textarea()` | `esc_html()` |
| Array key / slug | `sanitize_key()` | `esc_attr()` | `sanitize_key()` |
| Icon field (SVG/text/URL) | `wp_kses_post()` | `wp_kses_post()` | `wp_kses_post()` |

---

## Full `/main` Response Schema

```
GET /wp-json/hpcms/v1/main

{
  "general": {
    "name":           string,
    "tagline":        string,
    "email":          string,
    "phone":          string,
    "locations":      [{ "id": string, "value": string }],
    "favicon":        string (URL),
    "header_button":  { "text": string, "url": string },
    "footer_text":    string (may contain HTML)
  },
  "homepage": {
    "hero": {
      "title":       string,
      "subtitle":    string,
      "description": string (HTML),
      "buttons":     [{ "id": string, "text": string, "url": string }],
      "images":      [string (URL), ...],
      "video_url":   string
    },
    "highlighted_cards": {
      "title":    string,
      "subtitle": string,
      "cards":    [{ "id": string, "title": string, "subtitle": string, "icon": string }]
    },
    "about": {
      "title":       string,
      "subtitle":    string,
      "description": string (HTML),
      "skill_tags":  [string, ...],
      "image":       string (URL),
      "video_url":   string
    },
    "services":     { "title": string, "subtitle": string, "description": string (HTML) },
    "tech_stack":   { "title": string, "subtitle": string, "description": string (HTML) },
    "experience":   { "title": string, "subtitle": string, "description": string (HTML) },
    "awards":       { "title": string, "subtitle": string, "description": string (HTML) },
    "industries":   { "title": string, "subtitle": string, "description": string (HTML) },
    "brands":       { "title": string, "subtitle": string, "description": string (HTML) },
    "blogs":        { "title": string, "subtitle": string, "description": string (HTML) },
    "projects":     { "title": string, "subtitle": string, "description": string (HTML), "video_url": string },
    "testimonials": { "title": string, "subtitle": string, "description": string (HTML), "video_url": string },
    "contact": {
      "title":       string,
      "subtitle":    string,
      "description": string (HTML),
      "buttons":     [{ "id": string, "text": string, "url": string }],
      "image":       string (URL)
    }
  },
  "social": {
    "linkedin":      string (URL),
    "github":        string (URL),
    "behance":       string (URL),
    "dribbble":      string (URL),
    "gravatar":      string (URL),
    "wordpress_org": string (URL),
    "youtube":       string (URL),
    "x":             string (URL),
    "facebook":      string (URL),
    "instagram":     string (URL),
    "whatsapp":      string (URL)
  },
  "seo": {
    "meta_title":       string,
    "meta_description": string,
    "og_image":         string (URL)
  }
}
```

---

## TypeScript Interface (Frontend Integration Guide)

Distribute with the Frontend Integration Guide starting from v1.3.0:

```typescript
export interface HPCMSButton {
  id: string;
  text: string;
  url: string;
}

export interface HPCMSCard {
  id: string;
  title: string;
  subtitle: string;
  icon: string; // Lucide name, inline SVG, or URL — render on frontend.
}

export interface HPCMSLocation {
  id: string;
  value: string;
}

export interface HPCMSMainResponse {
  general: {
    name: string;
    tagline: string;
    email: string;
    phone: string;
    locations: HPCMSLocation[];
    favicon: string;
    header_button: HPCMSButton;
    footer_text: string; // May contain HTML.
  };
  homepage: {
    hero: {
      title: string;
      subtitle: string;
      description: string; // May contain HTML.
      buttons: HPCMSButton[];
      images: string[]; // Resolved URLs.
      video_url: string;
    };
    highlighted_cards: {
      title: string;
      subtitle: string;
      cards: HPCMSCard[];
    };
    about: {
      title: string;
      subtitle: string;
      description: string;
      skill_tags: string[];
      image: string;
      video_url: string;
    };
    services:     { title: string; subtitle: string; description: string; };
    tech_stack:   { title: string; subtitle: string; description: string; };
    experience:   { title: string; subtitle: string; description: string; };
    awards:       { title: string; subtitle: string; description: string; };
    industries:   { title: string; subtitle: string; description: string; };
    brands:       { title: string; subtitle: string; description: string; };
    blogs:        { title: string; subtitle: string; description: string; };
    projects:     { title: string; subtitle: string; description: string; video_url: string; };
    testimonials: { title: string; subtitle: string; description: string; video_url: string; };
    contact: {
      title: string;
      subtitle: string;
      description: string;
      buttons: HPCMSButton[];
      image: string;
    };
  };
  social: {
    linkedin: string;
    github: string;
    behance: string;
    dribbble: string;
    gravatar: string;
    wordpress_org: string;
    youtube: string;
    x: string;
    facebook: string;
    instagram: string;
    whatsapp: string;
  };
  seo: {
    meta_title: string;
    meta_description: string;
    og_image: string;
  };
}
```

---

## Changelog Entries (All Versions)

Add these to `readme.txt`:

```
== Changelog ==

= 1.3.0 =
* Removed deprecated /profile endpoint. Migrate to /main (deprecated since 1.1.0).
* Updated built-in API Reference dashboard to document /main endpoint.
* Updated TypeScript interface documentation in Frontend Integration Guide.

= 1.2.0 =
* Added Home Page tab with 13 configurable sections (Hero, About, Services, etc.).
* /main endpoint now returns full homepage section data.
* Added repeatable Highlighted Cards with icon support (Lucide name, SVG, or URL).
* Added repeatable Skill Tags field in the About section.
* Added gallery/slider image support for the Hero section.

= 1.1.0 =
* Settings storage refactored to grouped option keys (hpcms_general, hpcms_social, hpcms_seo, hpcms_homepage).
* One-time migration routine moves existing data to new structure automatically on upgrade.
* Profile Info tab renamed to General. Field label Full Name renamed to Name.
* Added Favicon, Header Button, and Footer Text (HTML) fields to General tab.
* Location field is now repeatable with unique IDs.
* Removed Bio, Hero Description, and Avatar URL fields.
* Social Links tab expanded: Dribbble, Gravatar, WordPress.org, Facebook, Instagram, WhatsApp added.
* API & CORS tab renamed to Configuration.
* New REST API endpoint: GET /wp-json/hpcms/v1/main
* /profile endpoint deprecated (returns X-HPCMS-Deprecated header). Will be removed in 1.3.0.
```
