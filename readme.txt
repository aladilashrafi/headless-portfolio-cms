=== Headless Portfolio CMS ===
Contributors: aladilashrafi
Tags: headless, portfolio, nextjs, cms, rest-api
Requires at least: 6.5
Tested up to: 7.0
Requires PHP: 8.1
Stable tag: 1.1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

API-first headless portfolio CMS for WordPress. Seamlessly power modern frontends like Next.js, React, and Astro via a lightning-fast REST API.

== Description ==

**Headless Portfolio CMS** transforms your WordPress installation into a powerful, API-first backend specifically designed for developers, freelancers, agencies, and creators. Build your dream portfolio using modern frontend frameworks (Next.js, React, Vue, Astro, Gatsby) while enjoying the familiar, user-friendly WordPress dashboard to manage your content.

Stop hardcoding your resume and project data! With Headless Portfolio CMS, you can centrally manage your professional identity and expose it through clean, strictly-typed REST API endpoints.

### 🚀 Core Features

*   **6 Dedicated Content Types:** Manage your **Projects**, **Experience**, **Education**, **Skills**, **Testimonials**, and **Resumes** out of the box.
*   **Custom Meta Fields:** Every content type comes with pre-configured, rich meta boxes (e.g., GitHub URLs, Live Demo links, Tech Stacks, Skill Levels, and Company details).
*   **Built-in CORS Management:** Easily configure Cross-Origin Resource Sharing (CORS) directly from the dashboard to allow your frontend applications to securely fetch data.
*   **Global Profile & SEO Settings:** Manage your bio, social links, and default SEO metadata centrally.
*   **Dynamic Taxonomies:** Organize your projects by **Technologies**, **Categories**, and **Industries**. Group your skills by **Skill Categories**.
*   **API Reference Dashboard:** Includes a beautifully designed, built-in API reference guide right in your WordPress admin area to help you integrate quickly.
*   **Lightning Fast & Lightweight:** Zero bloat, no frontend assets loaded, and highly optimized database queries for instant API responses.

### 💻 Built for Modern Frontends

Whether you are building a static site with Astro, a server-rendered app with Next.js, or a single-page application with React or Vue, this plugin provides the perfect data structure. The JSON responses are deeply nested and cleanly formatted, removing the need for complex data parsing on your frontend.

### 🔒 Secure by Default

All endpoints are strictly read-only for public access. Your private data and administrative settings are fully protected by WordPress's native nonces and capability checks.

== Installation ==

1. Download the `headless-portfolio-cms.zip` file.
2. Go to **Plugins > Add New** in your WordPress admin dashboard.
3. Click **Upload Plugin** and select the downloaded zip file.
4. Click **Install Now** and then **Activate**.
5. Navigate to the **Portfolio CMS** menu item in your sidebar to configure your profile and start adding content!

== Frequently Asked Questions ==

= Do I need a specific WordPress theme to use this? =
No! This is a headless CMS plugin, making it completely theme-agnostic. Your WordPress installation merely acts as the database and API provider.

= How do I connect my frontend application? =
Simply go to **Portfolio CMS > Settings > API & CORS**, add your frontend's URL to the Allowed Origins list, and start making `GET` requests to the endpoints shown in the Dashboard or API Reference.

= Are the API endpoints cached? =
The plugin provides a configuration setting for API Cache Duration, which you can use in conjunction with your frontend caching strategies (like Next.js ISR).

== Changelog ==

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

= 1.0.0 =
* Initial public release!
* Implemented modular PSR-4 architecture.
* Added core content entities (Projects, Experience, Skills, etc.) with custom meta fields.
* Integrated CORS and API security management.
* Built-in API Reference Dashboard.
* Optimized admin menu placement for better UX.

== Upgrade Notice ==

= 1.0.0 =
This is the first stable release. Please install and configure your API settings to begin.
