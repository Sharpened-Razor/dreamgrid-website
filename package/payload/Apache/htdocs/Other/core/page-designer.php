<?php
// Compatibility entry point used by the DreamGrid Control Center and public routes.
require_once __DIR__ . '/dreamgrid-env.php';
require_once __DIR__ . '/page-designer/model.php';
require_once __DIR__ . '/page-designer/storage.php';
require_once __DIR__ . '/page-designer/lifecycle.php';
require_once __DIR__ . '/page-designer/render.php';

require_once __DIR__.'/page-designer/publish-check.php';

require_once __DIR__.'/page-designer/template-zip.php';
require_once __DIR__.'/page-designer/template-archive.php';
require_once __DIR__.'/page-designer/template-convert.php';
require_once __DIR__.'/page-designer/template-import.php';

require_once __DIR__.'/page-designer/forms-model.php';
require_once __DIR__.'/page-designer/forms.php';
require_once __DIR__.'/page-designer/site-tools.php';
