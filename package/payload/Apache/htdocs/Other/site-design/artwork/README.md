# Built-in artwork: source and redistribution

These fifteen backgrounds are original AI-generated images commissioned for this project through the built-in OpenAI imagegen tool on 9 October 2026. No input/reference images, stock photos, web downloads, personal uploads, logos or existing characters were used. The requested scenes are generic; they contain no receiving-grid identity or installation details.

The project permits using, modifying and redistributing these files with DreamGrid websites and design packages, including public distribution. Attribution is welcome but not required. This permission covers any rights held by the project; it does not assert exclusive copyright or that generated output is unique.

OpenAI's [Terms of Use](https://openai.com/policies/terms-of-use/) assign output ownership to the user, as between OpenAI and the user and to the extent permitted by applicable law. AI-generated provenance is disclosed here rather than describing the images as human-made photographs.

`provenance.json` records the art direction, dimensions, runtime byte counts, encoding and SHA-256 for every asset. All runtime files are 1440 × 900 PNG. Classic Gold and the two modern architectural scenes retain full RGB gradients; the other scenes use an adaptive palette with dithering and optimized PNG compression. No original full-size generation files or machine paths ship in the runtime package.

`tools/generate-design-artwork.py` is an offline optimization helper, not an application dependency or image-generation client. It requires an explicit private manifest of locally generated sources and never reads the user's website uploads. The application continues using the existing portable relative asset paths and PNG package logic. System artwork remains separate from user uploads.
