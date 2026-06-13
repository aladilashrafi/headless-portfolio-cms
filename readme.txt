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

*   **9 Dedicated Content Types:** Manage your **Projects**, **Experience**, **Education**, **Skills**, **Testimonials**, **Resumes**, **Services**, **Clients**, and an internal **Contact Inbox** out of the box.
*   **Custom Meta Fields:** Every content type comes with pre-configured, rich meta boxes (e.g., GitHub URLs, Live Demo links, Tech Stacks, Skill Levels, and Company details).
*   **Built-in CORS Management:** Easily configure Cross-Origin Resource Sharing (CORS) directly from the dashboard to allow your frontend applications to securely fetch data.
*   **Contact Form API:** Accept contact form submissions via a dedicated `POST /contact` endpoint with built-in rate limiting, inbox storage, and email notifications.
*   **On-Demand ISR Revalidation:** Automatically pings your Next.js frontend's revalidation endpoint whenever content is published, keeping your static pages fresh without manual deploys.
*   **Global Profile & SEO Settings:** Manage your bio, social links, and default SEO metadata centrally.
*   **Dynamic Taxonomies:** Organize your projects by **Technologies**, **Categories**, and **Industries**. Group your skills by **Skill Categories**.
*   **API Reference Dashboard:** Includes a beautifully designed, built-in API reference guide right in your WordPress admin area to help you integrate quickly.
*   **Lightning Fast & Lightweight:** Zero bloat, no frontend assets loaded, and highly optimized database queries for instant API responses.

### 💻 Built for Modern Frontends

Whether you are building a static site with Astro, a server-rendered app with Next.js, or a single-page application with React or Vue, this plugin provides the perfect data structure. The JSON responses are deeply nested and cleanly formatted, removing the need for complex data parsing on your frontend.

### 🔒 Secure by Default

Read-only endpoints are publicly accessible. The contact form endpoint is rate-limited (5 requests per IP per hour) and all submissions are sanitized. Your administrative settings are fully protected by WordPress's native nonces and capability checks.

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

= How does on-demand revalidation work? =
Go to **Portfolio CMS > Settings**, enter your frontend's URL and a revalidation secret token. Whenever you publish or update content, the plugin will automatically call your frontend's `/api/revalidate` endpoint to purge the relevant cached pages.

= How does the contact form work? =
Send a `POST` request to `wp-json/hpcms/v1/contact` with a JSON body containing `name`, `email`, `subject`, `message`, and optionally `budget`. Submissions are saved to the Contact Inbox in the dashboard and forwarded to your configured notification email. The endpoint is rate-limited to 5 requests per IP per hour.

== Screenshots ==

1. Portfolio CMS Dashboard overview.
2. General Settings panel for profile and branding configuration.
3. Social Links Settings to add social profile links.
4. SEO Settings for meta title, meta description and og image.
5. Configuration Panel For API and CORS settings.
6. Built-in API Reference documentation dashboard.
7. Project Management interface with custom portfolio fields.
8. Skills Management with custom categories and proficiency levels.
9. Experience content editor with company details and metadata.
10. Service content editor with service details and metadata.
11. Testimonial content editor with testimonial details and metadata.
12. Resume Management with resume file url and metadata.
13. Education History Management with certificate url and metadata.

== Changelog ==

= 1.1.0 =
* Improved settings architecture and automatic data migration.
* Renamed Profile Info tab to General.
* Added favicon, header button, and footer text settings.
* Added support for multiple locations.
* Expanded social profile options.
* Renamed API & CORS tab to Configuration.
* Added new REST API endpoint: /wp-json/hpcms/v1/main
* Deprecated the /profile endpoint

= 1.0.0 =
* Initial public release!
* Implemented modular PSR-4 architecture.
* Added core content entities (Projects, Experience, Skills, etc.) with custom meta fields.
* Integrated CORS and API security management.
* Built-in API Reference Dashboard.
* Optimized admin menu placement for better UX.

== Upgrade Notice ==

= 1.1.0 =
Recommended update. Includes settings improvements, new customization options, automatic data migration, and a new REST API endpoint. The legacy /profile endpoint has been deprecated.
