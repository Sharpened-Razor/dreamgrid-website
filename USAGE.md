> **Screenshot set withdrawn on 7 October 2026.** The previous captures failed identity/loading and duplication review. Replacement images will remain unpublished until the entire set is checked against a capture manifest.

# usage guide

Use the [installation guide](INSTALLATION.md) first. This guide explains normal website workflows. The [complete gallery](SCREENSHOTS.md) contains a separate captioned image for each captured page and major tool, including similar screens and compatibility routes.

Screenshots use an isolated Demo Grid with synthetic accounts, regions and content. Native settings and the public browser example are explicitly labelled as examples from the AUSTRALIA grid. Secrets in native settings are covered; no production account or page was edited for these images.

## Public pages, registration and login

Open http://YOUR-GRID-DOMAIN/Other/ in your browser. Use the Login link to sign in with your grid account. Create Account is for registration; Forgot Password and Login Help provide the corresponding workflows. Registration and recovery depend on your grid policy and optional mail setup. Do not assume a demo screenshot confirms your mail server delivers messages.

## User Dashboard

After login, open /Other/FreshUserDashboardExact/user-dashboard.php. Click each left menu item to open the corresponding workspace. Account edits identity/account settings; Profile shows public avatar information; Regions lists associated regions; Inventory and IAR Backups provide inventory/backup workflows; Offline Messages displays queued messages; Map and 3D Map show spatial views; Help Centre opens tutorials and guidance. Some functions depend on running grid services and user permissions.

Use [the User Dashboard gallery](SCREENSHOTS.md#user-dashboard-menu) for every menu screen separately. Logout ends the website session; it does not shut down your grid.

## Admin Control Center

Open /Other/admin-home.php with an authorized administrator account. The left menu offers account/group management, console, Control Panel, statistics, region management, maps and website tools. Administrative service controls affect your running grid; read each action before executing it. Documentation capture opened screens but did not run production service actions.

The [Control Center gallery](SCREENSHOTS.md#admin-control-center-menu) shows every menu entry, and the additional-pages gallery includes create/edit/delete and backup screens. An empty list in a demo is not evidence of a missing destination database.

## Build your first page

1. Open **Page Designer** from the Control Center.
2. Open **Pages → New page**, or select a saved page. Choose a layout in **Layouts**, or start blank.
3. Give the page a title and slug in **Page** settings. Configure access and menu placement.
4. Use **Add** to insert sections, columns, headings, text, images, buttons, forms and other available elements.
5. Click an element on the canvas or select it in **Layers**. Use **Content**, **Style** and **Layout** in the inspector. Double-click supported text to edit directly.
6. Click **Save draft**. Saving a draft does not replace an already published version.
7. Preview Desktop, Tablet and Phone. Run **Check**, resolve relevant findings, and click **Publish** when ready.

## Find nested elements, copy and protect them

Use **Layers** and the bottom breadcrumbs to select nested sections and children. Multi-select selects several elements. Copy and Paste use this installation's browser-local builder clipboard, including independent image copies where supported. Lock finished elements to prevent accidental editing; unlock them before moving, deleting or replacing protected content.

## Desktop, Tablet and Phone

Select a device above the canvas. Use Layout/Style controls for device overrides and Visibility to hide selected elements at a breakpoint. Always check all three views after editing; hidden elements can remain in the page structure. The editor preview does not replace testing on the devices your visitors use.

## Reuse sections, images and templates

Select content and open **Sections → Save selected element**, then give it a library name. Insert copies from the Sections library into other pages. Copies remain individually editable. **Images** uploads and reuses supported image files; provide an image description on each meaningful image. **Layouts → Save this page as a template** creates a reusable page template; **My saved templates** opens that library. Archiving a library item does not remove copies already inserted in saved pages.

## Global styles, header/footer and navigation

Open **Site** to edit Global Styles, Header & Footer and Navigation. Save a site draft to keep changes private; publish site changes to update pages using them. In each page's settings, select Use global site styles and whether to show the shared header/footer. Explicit element styles take priority. Choose a saved reusable section for shared content where offered.

Use **Menu** to build navigation. Pick custom pages, DreamGrid routes, external URLs or anchors. Use section anchors such as #about for jump links. Check the destination before publishing; copied external URLs are not automatically made portable.

## Import free HTML templates

Open **Layouts → Free templates & ZIP import**. The tool links to template providers; downloads happen on their websites. Use an HTML website template ZIP, not a WordPress theme. Check the licence and image rights yourself. Upload the ZIP, choose an HTML file and click **Preview conversion**. Review the editable preview, conversion notices and licence, acknowledge the review, then create a **new private draft**.

Scripts/server code are not installed or executed by the importer. Complex source layouts may simplify during conversion. Review all individual elements, images, links and device views before publishing. Original source/licence material is retained privately by the importer.

## Convert an existing V10 page

Existing saved pages remain compatible and are not automatically migrated during installation. Select its **Existing page** element and choose **Convert to native elements**. Review the resulting headings, images, text, buttons and containers. Conversion is unsaved until you save; Undo can restore the prior layout. Save as a draft and check device views before publishing. Keep revision history/backups when replacing important pages.

## Forms and submissions

Insert a **Form**, then configure its fields in Content: type, label, required status, help and validation. Choose local inbox handling or configured email handling as offered. Registration-style fields build a form; they do not automatically create a DreamGrid account. Real email delivery requires your destination's mail configuration. Publish the page before testing a visitor submission.

## Check, publish and maintain

**Check** reviews the current canvas for supported image/link/anchor/description findings. **Tools → Saved-page link scan** reviews saved drafts or published pages; unsaved canvas changes are outside that scan. External websites are not fetched. **Tools → Search / replace** previews matches before saving replacement drafts; existing published pages remain live until separately published. Shared site content and templates are outside this replacement operation.

Set page metadata/indexing in **Page** settings. Available animation/hover controls are in the inspector; review reduced-motion and mobile behavior. A shop-style layout is a product showcase with links, not a payment or order-processing system.

## Drafts, history, recovery and transfers

Save draft regularly. **History** previews previous saved versions and restores one as a private draft; publishing is a separate action. **Recovery** uses browser-local unsaved snapshots, scoped to this installation/account. It is not a server backup and may be lost when browser storage is cleared. **Transfer** exports a saved DreamGrid page package with supported images; import creates a new private draft. External URLs/videos remain links.

To delete a saved page, open **Pages**, select the page, open its **Page settings**, and use the available Delete page action after reviewing the confirmation. Deletion removes the page from website/menu access. Do not use a canvas element's delete control when you mean to delete the whole saved page.

## Built-in tutorials and further help

Click **Help** in the Page Designer or press F1. Follow a lesson in a practice draft, then apply the steps to your own page. The website Help Centre also has topic pages for normal grid tasks. The gallery includes these topic screens individually.

## Coverage and known limitations

See [screenshot coverage](SCREENSHOT-COVERAGE.md) for exact capture scope, older compatibility-route problems and demo-service limits. The gallery is a visual guide, not a new claim that every legacy route or optional external service passed production testing. No new website features or production fixes were installed during this documentation pass.
