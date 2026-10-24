=== Resgata Pet ===
Contributors: robertodutra
Tags: animals, adoption, rescue, donations, nonprofit
Requires at least: 6.9
Tested up to: 7.1
Requires PHP: 8.1
Stable tag: 0.6.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A toolkit for independent animal rescuers: list animals for adoption, receive adoption requests and collect monthly sponsorships.

== Description ==

Resgata Pet helps independent animal rescuers publish the animals they have rescued, receive adoption requests, raise funds through monthly sponsorships and report on how the money is used.

It was built for rescuers in Brazil: the plugin interface is written in Brazilian Portuguese, phone numbers and the Pix key follow Brazilian formats, and sponsorships are charged in BRL.

Modules (the rescuer turns each one on or off in the panel, and the site and the panel show only what is on):

* **Animals** (available): records with profile, health information and a photo gallery (before and after), a public list with filters and an individual page for each animal.
* **Adoption requests** (available): a questionnaire on the site, sent through the rescuer's WhatsApp, with a queue of requests in the panel.
* **Sponsorship** (available): monthly plans paid by card through Stripe, a thank-you page, a password-free sponsor area (with the total donated so far), a record of every payment in the panel and a sponsor counter on the site.
* **Accountability** (coming soon)

The plugin also includes its own mobile-first panel at `/painel`, the rescuer's settings (contact details, social networks, location and Pix key), a "How to help" page with a Pix QR code and banners for the home page.

The plugin works with any theme. A theme can replace the templates by creating a `resgata-pet/` folder and take over the styling by declaring `add_theme_support( 'resgata-pet' )`.

== Installation ==

1. Upload the `resgata-pet` folder to `wp-content/plugins/`.
2. Activate the plugin in "Plugins".
3. Add the animals at yoursite.org/painel. The site must use pretty permalinks (any option other than "Plain").
4. The public list is at `/animais/`. To show animals on another page, use the "Lista de animais" block or the `[resgata_animais]` shortcode.

== Frequently Asked Questions ==

= Which animals appear in the public list? =

By default, those with the status "Disponível para adoção" (available) or "Reservado" (reserved). The "Lista de animais" block lets you choose other statuses (for example, "Adotado" for a happy-endings page).

= How do I use the shortcode? =

`[resgata_animais especie="gato" quantidade="6" colunas="3" filtros="nao" paginacao="nao" status="disponivel,reservado"]`

= What happens to the data if I delete the plugin? =

Nothing: the data is kept. To erase it, uncheck "Manter os dados" in "Resgata Pet → Configurações" before deleting the plugin.

= Where does the sponsors' data go? =

Payment happens on Stripe's pages (stripe.com), which store the card details. The site stores only the name, the e-mail address, the Stripe identifiers of the customer, the subscription and the payments, the plan, the status and the amount and date of each payment (including refunds and disputes). Tax ID, address and card details are not stored on the site.

= Where is the source code of the panel? =

The panel, the editor scripts and part of the site scripts are written in TypeScript and compiled into the `build/` folder. The source code, the build instructions and the tests are in the public repository: https://github.com/resgata-pet/resgata-pet

== External services ==

The sponsorship module, which is off by default, uses Stripe (https://stripe.com) to receive payments. With the module on and the keys set:

* when a visitor chooses a plan, they are taken to Stripe's payment page, where they enter their name, e-mail address and card;
* the site sends Stripe the chosen plan and the return address, and asks Stripe to open the customer portal when a sponsor wants to change the card, change the amount or cancel;
* Stripe notifies the site (webhook) about subscriptions, payments, refunds and disputes.

Terms of service: https://stripe.com/legal. Privacy policy: https://stripe.com/privacy.

The Pix QR code is built in the visitor's browser from the key registered by the rescuer, without sending data to any service.

== Source code ==

The files in `build/` are generated from the TypeScript sources in the `resources/` folder of the public repository, using `@wordpress/scripts`:

* Repository: https://github.com/resgata-pet/resgata-pet
* Sources of the compiled files: https://github.com/resgata-pet/resgata-pet/tree/main/plugins/resgata-pet/resources
* To build: run `make build` at the root of the repository (or `npm install && npm run build` in `plugins/resgata-pet`).

The `vendor/` folder contains the official Stripe PHP library, installed with Composer (`composer.json` is included).

== Changelog ==

= 0.6.0 =
* "Recursos do site" tab in the panel settings: the rescuer chooses which modules to use (animals, adoption requests, sponsorship). A site can run with sponsorship and Pix only, without animals or adoption.
* The panel menu, screens and home page follow the modules that are on.
* Theme sections that depend on a module (classes `resgata-needs-<module>` and `resgata-without-<module>`) are hidden on the site when the module is off, and grids adjust their columns.
* Fix: sponsorship and Pix blocks now work in the editor even with the Animals module off.
* The "Números da protetora" block is always available and shows only the numbers of the modules that are on.

= 0.5.0 =
* Sponsorship: monthly card plans through Stripe Checkout, sponsor area through a link sent by e-mail (Stripe portal), sponsor counter on the site and list of sponsors in the panel.
* Sponsorship: record of every payment, with refunds and disputes; revenue and list of payments in the panel; total donated in the sponsor area.
* Sponsorship: thank-you page after payment, thank-you e-mail to the sponsor and e-mail notices to the rescuer.
* Settings in two tabs, with the Stripe keys under "Pagamentos e apadrinhamento".
* Pix donation with QR code, "How to help" page, banners and the rescuer's numbers on the home page.
* Animals are now managed only in the panel (the WordPress admin screens were removed).

= 0.4.0 =
* Adoption requests: questionnaire on the site with consent (LGPD), sent through the rescuer's WhatsApp, and a queue of requests in the panel.

= 0.3.0 =
* Rescuer's settings in the panel (contact details, social networks, location, adoption, privacy and Pix).
* "Contato da protetora" block and a contact page created automatically.
* Search and filters in the panel's list of animals.

= 0.2.0 =
* Own panel at /painel, built for mobile, with a four-step form to add animals.
* New "Protetor admin" role, which uses only the panel.
* Own API (resgata-pet/v1) with a permission check on every route.

= 0.1.0 =
* First version: plugin skeleton, settings and the Animals module.

== Credits ==

* stripe-php (MIT): https://github.com/stripe/stripe-php
* React, React Router, Base UI, TanStack Query and Table, Tiptap, react-day-picker, date-fns, sonner, qrcode-pix and Tailwind CSS (MIT); lucide (ISC).
