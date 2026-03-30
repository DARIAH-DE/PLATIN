# Security and Dependency Findings Report

This report summarizes security-relevant and risk-relevant findings identified in the repository review.

## Scope
- Source review of JavaScript and PHP application code.
- Dependency review based on `Rakefile` and vendored library headers.
- No dynamic testing was performed.

## Findings (Ordered by Severity)

### 1) High: Server-side request forgery (SSRF) and open proxy behavior
- Affected files:
  - `php/proxy.php` (notably lines 24-61)
- Evidence:
  - Accepts arbitrary `$_REQUEST['address']` and parses it.
  - Host allowlisting is regex-based (`$validHosts`) and defaults to permissive behavior when empty.
  - Request parameters are appended directly to the outbound URL.
  - Performs outbound fetch via `file_get_contents($address)` for GET and POST flows.
- Security impact:
  - Can be used to trigger server-side outbound requests to attacker-controlled targets.
  - Potential access path to internal services and metadata endpoints depending on deployment/network posture.

### 2) High: Stored/reflected XSS via unsanitized HTML insertion in table rendering
- Affected files:
  - `js/Table/Table.js`
- Evidence:
  - Untrusted values are inserted with jQuery `.html(...)`:
    - line 652 (`$(textDiv).html(subtext);`)
    - line 658 (`$(textDiv).html(text);`)
    - line 661 (`$(textDiv).html(subtext);`)
    - line 688 (`$(cell).html(text);`)
- Security impact:
  - Script-capable payloads in dataset content can execute in users' browsers.
  - Session compromise, content tampering, and UI redress are possible.

### 3) High: XSS in map popup descriptions
- Affected files:
  - `js/Map/PlacenamePopup.js`
- Evidence:
  - line 165 inserts description with `div.innerHTML = this.labels[i].elements[j].description;`.
- Security impact:
  - User-supplied or external dataset descriptions can execute script in popup context.

### 4) High: XSS in map tag cloud labels
- Affected files:
  - `js/Map/PlacenameTags.js`
- Evidence:
  - line 209 concatenates label text into HTML and writes via `innerHTML`.
  - Similar pattern repeated at lines 510 and 525.
- Security impact:
  - Place labels from datasets can inject active HTML/JS into map UI.

### 5) Medium: Dynamic code execution through `eval`
- Affected files:
  - `js/Build/Loader/DynaJsLoader.js`
- Evidence:
  - line 55 evaluates loader test function name with `eval(testFunction)`.
- Security impact:
  - Increases exploitability and audit complexity.
  - If any attacker-influenced path reaches `testFunction`, this becomes direct code execution in browser context.

### 6) Medium: Header/content injection and abuse surface in download endpoint
- Affected files:
  - `php/download.php`
- Evidence:
  - line 26 reads untrusted `$_POST['filename']`.
  - line 33 places it into `Content-Disposition` without normalization.
  - line 47 echoes arbitrary posted content as file response.
- Security impact:
  - Response header manipulation risk via crafted filename values.
  - Endpoint can be abused to return unbounded attacker-provided payloads.

### 7) Medium: Parameter-driven JSON parsing can trigger application-level DoS/errors
- Affected files:
  - `js/Dataloader/DataloaderWidget.js`
- Evidence:
  - URL parameters are iterated and parsed (`$.url().param()` at lines 92, 182, 227, 274).
  - Untrusted values are passed into `JSON.parse(...)` (e.g., line 155 and line 198) without local error handling.
- Security impact:
  - Malformed or oversized parameter payloads can break loading flows and degrade availability.

### 8) Medium: Insecure HTTP external map/overlay sources
- Affected files:
  - `js/Map/MapWidget.js` (lines 677-680)
  - `js/Overlayloader/Overlayloader.js` (line 334)
- Evidence:
  - Hardcoded `http://` tile and overlay URLs.
- Security impact:
  - Enables MITM tampering of map tiles/overlays on non-isolated networks.
  - Causes mixed-content exposure in HTTPS deployments.

### 9) Medium: Legacy/outdated third-party dependency surface
- Affected files:
  - `Rakefile` (dependency list)
  - Vendored libs under `lib/`
- Evidence (examples from headers and filenames):
  - jQuery 1.11.0 (`lib/jquery/jquery.min.js`)
  - jQuery UI 1.10.3 (`lib/jquery-ui/jquery-ui-1.10.3.custom.js`)
  - OpenLayers (2006-2013 generation, `lib/openlayers/OpenLayers.js`)
  - moment.js 2.5.1 (`lib/momentjs/moment.js`)
  - SheetJS `xlsx.js` 0.7.8 and `xls.min.js` 0.7.1 (`lib/sheetjs/...`)
  - Flot 0.8.3-alpha (`lib/flot/jquery.flot.js`)
  - Legacy build compressor `yuicompressor-2.4.2.jar` (`Rakefile`)
- Security impact:
  - Increased probability of known CVEs and legacy parser/browser edge-case vulnerabilities.
  - Higher long-term maintenance and patching risk.

## Notes
- This document intentionally excludes remediation guidance, per request.
- Severity reflects likely impact in common web deployments and should be validated against your runtime environment.