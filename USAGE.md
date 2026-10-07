# Illustrated usage guide

Use the [installation guide](INSTALLATION.md) first. This guide explains normal website workflows. The [complete gallery](SCREENSHOTS.md) contains a separate captioned image for each captured page and major tool, including similar screens and compatibility routes.

Screenshots use an isolated Demo Grid with synthetic accounts, regions and content. Native settings and the public browser example are explicitly labelled as examples from the AUSTRALIA grid. Secrets in native settings are covered; no production account or page was edited for these images.

## Public pages, registration and login

Open http://YOUR-GRID-DOMAIN/Other/ in your browser. Use the Login link to sign in with your grid account. Create Account is for registration; Forgot Password and Login Help provide the corresponding workflows. Registration and recovery depend on your grid policy and optional mail setup. Do not assume a demo screenshot confirms your mail server delivers messages.

![Public home](docs/screenshots/details/public-home.png)

*The landing page offers public information and access to login/registration.*


![Create Account](docs/screenshots/pages/other-create-account.png)

*Complete the registration fields and terms required by the destination grid.*


![Login page](docs/screenshots/details/login-page.png)

*Sign in here. Documentation images do not contain passwords.*


![Password recovery](docs/screenshots/pages/other-forgot-password.png)

*Request recovery using the destination grid’s configured handling.*


## User Dashboard

After login, open /Other/FreshUserDashboardExact/user-dashboard.php. Click each left menu item to open the corresponding workspace. Account edits identity/account settings; Profile shows public avatar information; Regions lists associated regions; Inventory and IAR Backups provide inventory/backup workflows; Offline Messages displays queued messages; Map and 3D Map show spatial views; Help Centre opens tutorials and guidance. Some functions depend on running grid services and user permissions.

![Dashboard Account](docs/screenshots/menus/member-account.png)

*Account workspace opened from the actual User Dashboard menu.*


![Dashboard Inventory](docs/screenshots/menus/member-inventory.png)

*Browse inventory. Do not clean or remove inventory without understanding the action.*


![Dashboard IAR Backups](docs/screenshots/menus/member-iar-backups.png)

*Review available inventory backups and the backup workflow.*


![Dashboard Map](docs/screenshots/menus/member-map.png)

*Open the 2D map. The documentation grid is synthetic; a real destination supplies its own regions and map images.*


Use [the User Dashboard gallery](SCREENSHOTS.md#user-dashboard-menu) for every menu screen separately. Logout ends the website session; it does not shut down your grid.

## Admin Control Center

Open /Other/admin-home.php with an authorized administrator account. The left menu offers account/group management, console, Control Panel, statistics, region management, maps and website tools. Administrative service controls affect your running grid; read each action before executing it. Documentation capture opened screens but did not run production service actions.

![Admin Control Center](docs/screenshots/pages/other-admin-home.png)

*The administrative shell groups account, region, control and website tools.*


![Manage Accounts](docs/screenshots/menus/admin-manage-accounts.png)

*Find an account and open its details before editing. Demonstration accounts are synthetic.*


![Control Panel](docs/screenshots/menus/admin-control-panel.png)

*Native grid control and backup workspaces depend on the destination services.*


![Region Manager](docs/screenshots/menus/admin-region-manager.png)

*Select a region before opening its edit, console, log, map or statistics tools.*


The [Control Center gallery](SCREENSHOTS.md#admin-control-center-menu) shows every menu entry, and the additional-pages gallery includes create/edit/delete and backup screens. An empty list in a demo is not evidence of a missing destination database.

## Build your first page

1. Open **Page Designer** from the Control Center.
2. Open **Pages → New page**, or select a saved page. Choose a layout in **Layouts**, or start blank.
3. Give the page a title and slug in **Page** settings. Configure access and menu placement.
4. Use **Add** to insert sections, columns, headings, text, images, buttons, forms and other available elements.
5. Click an element on the canvas or select it in **Layers**. Use **Content**, **Style** and **Layout** in the inspector. Double-click supported text to edit directly.
6. Click **Save draft**. Saving a draft does not replace an already published version.
7. Preview Desktop, Tablet and Phone. Run **Check**, resolve relevant findings, and click **Publish** when ready.

![Page layouts](docs/screenshots/builder/builder-templates.png)

*Choose a starting layout. Applying a layout can replace unsaved canvas content; review the warning and use Undo when appropriate.*


![Builder canvas](docs/screenshots/builder/builder-canvas.png)

*The visual canvas and element library show the native editable page structure.*


![Element content inspector](docs/screenshots/builder/builder-element-content.png)

*Change the selected element’s content and links.*


![Element style inspector](docs/screenshots/builder/builder-element-style.png)

*Set appearance for the selected element.*


![Element layout inspector](docs/screenshots/builder/builder-element-layout.png)

*Set spacing, width, alignment, column sizing and other available layout properties.*


## Find nested elements, copy and protect them

Use **Layers** and the bottom breadcrumbs to select nested sections and children. Multi-select selects several elements. Copy and Paste use this installation's browser-local builder clipboard, including independent image copies where supported. Lock finished elements to prevent accidental editing; unlock them before moving, deleting or replacing protected content.

![Layers navigator](docs/screenshots/builder/builder-panel-layers.png)

*Expand the structure and select the precise nested element.*


![Multi-select and clipboard](docs/screenshots/builder/builder-multi-select.png)

*The editing toolbar supports copy, paste, locks and recovery.*


## Desktop, Tablet and Phone

Select a device above the canvas. Use Layout/Style controls for device overrides and Visibility to hide selected elements at a breakpoint. Always check all three views after editing; hidden elements can remain in the page structure. The editor preview does not replace testing on the devices your visitors use.

![Desktop preview](docs/screenshots/builder/builder-device-desktop.png)

*Desktop breakpoint preview.*


![Tablet preview](docs/screenshots/builder/builder-device-tablet.png)

*Tablet breakpoint preview.*


![Phone preview](docs/screenshots/builder/builder-device-phone.png)

*Phone breakpoint preview.*


![Device visibility](docs/screenshots/builder/builder-visibility.png)

*Configure the selected element’s device visibility and layout.*


## Reuse sections, images and templates

Select content and open **Sections → Save selected element**, then give it a library name. Insert copies from the Sections library into other pages. Copies remain individually editable. **Images** uploads and reuses supported image files; provide an image description on each meaningful image. **Layouts → Save this page as a template** creates a reusable page template; **My saved templates** opens that library. Archiving a library item does not remove copies already inserted in saved pages.

![Reusable sections](docs/screenshots/builder/builder-sections.png)

*Preview and insert independently editable copies.*


![Save reusable section dialog](docs/screenshots/builder/builder-save-section.png)

*Name the selected element for reuse.*


![Image library](docs/screenshots/builder/builder-images.png)

*Manage images and inspect where saved images are used.*


![Save page template](docs/screenshots/builder/builder-save-template.png)

*Save a named reusable page layout.*


## Global styles, header/footer and navigation

Open **Site** to edit Global Styles, Header & Footer and Navigation. Save a site draft to keep changes private; publish site changes to update pages using them. In each page's settings, select Use global site styles and whether to show the shared header/footer. Explicit element styles take priority. Choose a saved reusable section for shared content where offered.

Use **Menu** to build navigation. Pick custom pages, DreamGrid routes, external URLs or anchors. Use section anchors such as #about for jump links. Check the destination before publishing; copied external URLs are not automatically made portable.

![Global Site Styles](docs/screenshots/builder/builder-site-styles.png)

*Set common colours, typography, width, spacing and button defaults.*


![Global header and footer](docs/screenshots/builder/builder-site-header-footer.png)

*Configure shared header/footer content for pages that enable it.*


![Navigation builder](docs/screenshots/builder/builder-site-navigation.png)

*Build the shared navigation, including page and anchor destinations.*


## Import free HTML templates

Open **Layouts → Free templates & ZIP import**. The tool links to template providers; downloads happen on their websites. Use an HTML website template ZIP, not a WordPress theme. Check the licence and image rights yourself. Upload the ZIP, choose an HTML file and click **Preview conversion**. Review the editable preview, conversion notices and licence, acknowledge the review, then create a **new private draft**.

Scripts/server code are not installed or executed by the importer. Complex source layouts may simplify during conversion. Review all individual elements, images, links and device views before publishing. Original source/licence material is retained privately by the importer.

![Template ZIP importer](docs/screenshots/builder/builder-template-import.png)

*Choose a supported HTML template ZIP and review the provider’s licence.*


![Template conversion preview](docs/screenshots/builder/builder-template-conversion-preview.png)

*Synthetic HTML template preview with editable-element conversion and licence review.*


![Imported template draft](docs/screenshots/builder/builder-template-imported.png)

*Import creates a new private draft for further editing.*


## Convert an existing V10 page

Existing saved pages remain compatible and are not automatically migrated during installation. Select its **Existing page** element and choose **Convert to native elements**. Review the resulting headings, images, text, buttons and containers. Conversion is unsaved until you save; Undo can restore the prior layout. Save as a draft and check device views before publishing. Keep revision history/backups when replacing important pages.

![Existing page conversion control](docs/screenshots/builder/builder-legacy-conversion.png)

*Select the existing content and use explicit conversion; installation never migrates it automatically.*

![Converted editable content](docs/screenshots/builder/builder-legacy-converted.png)

*The converted structure contains individual native elements. Save and publish separately.*

## Forms and submissions

Insert a **Form**, then configure its fields in Content: type, label, required status, help and validation. Choose local inbox handling or configured email handling as offered. Registration-style fields build a form; they do not automatically create a DreamGrid account. Real email delivery requires your destination's mail configuration. Publish the page before testing a visitor submission.

![Visual form editor](docs/screenshots/builder/builder-form-editor.png)

*Set fields, validation, button/success text and submission handling.*


![Form field editor](docs/screenshots/builder/builder-form-field.png)

*Edit one field’s type, label and required/validation options.*


![Private form inbox](docs/screenshots/builder/builder-forms-inbox.png)

*Administrators read messages here, mark read, archive/restore, export the view or retry applicable email delivery. This demo inbox contains no live submissions.*


![Synthetic form message](docs/screenshots/builder/builder-demo-submission.png)

*Normal inbox controls with a synthetic message; no live submission was copied.*

## Check, publish and maintain

**Check** reviews the current canvas for supported image/link/anchor/description findings. **Tools → Saved-page link scan** reviews saved drafts or published pages; unsaved canvas changes are outside that scan. External websites are not fetched. **Tools → Search / replace** previews matches before saving replacement drafts; existing published pages remain live until separately published. Shared site content and templates are outside this replacement operation.

![Publish checker](docs/screenshots/builder/builder-publish-check.png)

*Review errors and warnings before publishing.*


![Saved-page link scan](docs/screenshots/builder/builder-link-scan-results.png)

*Review saved page findings and open the corresponding page.*


![Search and replace preview](docs/screenshots/builder/builder-replace-preview.png)

*Inspect the proposed changes before saving replacement drafts.*


Set page metadata/indexing in **Page** settings. Available animation/hover controls are in the inspector; review reduced-motion and mobile behavior. A shop-style layout is a product showcase with links, not a payment or order-processing system.

![SEO page settings](docs/screenshots/builder/builder-seo.png)

*Set the available page metadata and indexing options.*


![Animations and interactions](docs/screenshots/builder/builder-animations.png)

*Configure available motion and hover behavior for the selected native element.*


## Drafts, history, recovery and transfers

Save draft regularly. **History** previews previous saved versions and restores one as a private draft; publishing is a separate action. **Recovery** uses browser-local unsaved snapshots, scoped to this installation/account. It is not a server backup and may be lost when browser storage is cleared. **Transfer** exports a saved DreamGrid page package with supported images; import creates a new private draft. External URLs/videos remain links.

![Revision history preview](docs/screenshots/builder/builder-history-preview.png)

*Select a saved version and preview it before restoring as a draft.*


![Recovery dialog](docs/screenshots/builder/builder-recovery.png)

*Inspect an available browser recovery snapshot.*


![Page transfer](docs/screenshots/builder/builder-import-export.png)

*Download a page package or import one as a new private draft.*


To delete a saved page, open **Pages**, select the page, open its **Page settings**, and use the available Delete page action after reviewing the confirmation. Deletion removes the page from website/menu access. Do not use a canvas element's delete control when you mean to delete the whole saved page.

## Built-in tutorials and further help

Click **Help** in the Page Designer or press F1. Follow a lesson in a practice draft, then apply the steps to your own page. The website Help Centre also has topic pages for normal grid tasks. The gallery includes these topic screens individually.

![Built-in tutorials](docs/screenshots/builder/builder-tutorials.png)

*Guided lessons are available inside the Page Designer.*


## Coverage and known limitations

See [screenshot coverage](SCREENSHOT-COVERAGE.md) for exact capture scope, older compatibility-route problems and demo-service limits. The gallery is a visual guide, not a new claim that every legacy route or optional external service passed production testing. No new website features or production fixes were installed during this documentation pass.
