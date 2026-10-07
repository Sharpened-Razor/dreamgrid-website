<?php
/** Pure validation for importing a single region definition. No filesystem writes. */
function ag_region_import_parse(string $raw): array
{
    if ($raw === '' || strlen($raw) > 262144 || strpos($raw, "\0") !== false || !preg_match('//u', $raw)) {
        throw new RuntimeException('Choose a UTF-8 Region INI file up to 256 KB.');
    }
    $raw = preg_replace('/^\xEF\xBB\xBF/', '', $raw);
    $section = null;
    $values = [];
    $lines = [];
    foreach (preg_split('/\r\n|\n|\r/', $raw) as $line) {
        $text = trim($line);
        if ($text === '' || $text[0] === ';' || $text[0] === '#') continue;
        if (preg_match('/^\[([^\[\]\r\n]+)\]\s*(?:;.*)?$/', $text, $match)) {
            if ($section !== null) throw new RuntimeException('Import one Region section at a time, not Opensim.ini or a multi-region file.');
            $section = trim($match[1]);
            if ($section === '') throw new RuntimeException('The Region section name is blank.');
            continue;
        }
        if ($section === null || !preg_match('/^([A-Za-z][A-Za-z0-9_.-]*)\s*=\s*(.*)$/', $text, $match)) {
            throw new RuntimeException('The INI contains an invalid setting or a setting outside its Region section.');
        }
        $key = strtolower($match[1]);
        if (preg_match('/^(include|config|extends)(?:$|[_.-])/', $key)) {
            throw new RuntimeException('Import a self-contained Region INI without include directives.');
        }
        if (array_key_exists($key, $values)) throw new RuntimeException('Duplicate INI setting: ' . $match[1]);
        $parsed = @parse_ini_string('value = ' . $match[2], false, INI_SCANNER_RAW);
        if (!is_array($parsed) || !array_key_exists('value', $parsed) || is_array($parsed['value'])) {
            throw new RuntimeException('Invalid value for ' . $match[1] . '.');
        }
        $values[$key] = (string)$parsed['value'];
        $lines[$key] = $match[1] . ' = ' . $match[2];
    }
    if ($section === null || !isset($values['regionuuid'], $values['internalport'])) {
        throw new RuntimeException('A Region INI needs a Region section, RegionUUID and InternalPort.');
    }
    if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $values['regionuuid'])) {
        throw new RuntimeException('RegionUUID is invalid.');
    }
    $location = explode(',', $values['location'] ?? '');
    $x = $values['coordx'] ?? trim($location[0] ?? '');
    $y = $values['coordy'] ?? trim($location[1] ?? '');
    if (!ctype_digit($x) || !ctype_digit($y)) throw new RuntimeException('Provide CoordX/CoordY or a numeric Location=x,y.');
    $sx = $values['sizex'] ?? '256';
    $sy = $values['sizey'] ?? '256';
    if (!ctype_digit($sx) || !ctype_digit($sy) || (int)$sx !== (int)$sy || (int)$sx < 256 || (int)$sx > 4096 || (int)$sx % 256 !== 0) {
        throw new RuntimeException('This creator supports square regions from 256 to 4096 metres. The file was not changed.');
    }
    $truth = static fn(string $v): bool => in_array(strtolower(trim($v)), ['true','1','yes','on'], true);
    $fields = [
        'regionName' => $section, 'estateName' => $values['estate'] ?? $section,
        'regionUuid' => $values['regionuuid'], 'port' => $values['internalport'],
        'coordX' => $x, 'coordY' => $y, 'regionSize' => (string)((int)$sx / 256),
        'smartStart' => 'off', 'enabled' => false,
        'locked' => $truth($values['locked'] ?? 'false'),
        'clampPrimSize' => $truth($values['clampprimsize'] ?? 'false'),
    ];
    foreach (['nonphysicalprimmax'=>'nonPhysicalPrimMax','physicalprimmax'=>'physicalPrimMax','maxprims'=>'maxPrims','maxagents'=>'maxAgents'] as $key=>$field) {
        if (isset($values[$key])) $fields[$field] = $values[$key];
    }
    return ['name'=>$section, 'values'=>$values, 'lines'=>$lines, 'fields'=>$fields, 'raw'=>$raw];
}

function ag_region_import_merge(string $template, array $import): string
{
    foreach ($import['lines'] as $key=>$line) {
        $pattern = '/^[ \t]*' . preg_quote($key, '/') . '[ \t]*=.*$/mi';
        $count = preg_match_all($pattern, $template);
        if ($count > 1) throw new RuntimeException('The native template contains a duplicate setting: ' . $key);
        if ($count === 1) {
            $template = preg_replace_callback($pattern, static fn() => $line, $template);
        } else {
            $template = rtrim($template) . "\r\n" . $line . "\r\n";
        }
    }
    return $template;
}