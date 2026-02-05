# File Upload Security - Implementation Guide

## Overview
This document details the security measures implemented to protect the AdminSuite document upload system from filename-based attacks and file upload vulnerabilities.

## Security Vulnerabilities Fixed

### 1. **Path Traversal Attack Prevention**
**Risk:** Attackers could upload files with names like `../../etc/passwd.pdf` to escape the intended storage directory.

**Fix:** Implemented comprehensive filename sanitization that:
- Removes all path traversal sequences (`../`, `..\`, etc.)
- Strips directory separators (`/`, `\`, `:`)
- Validates filenames before storage

**Location:** `app/Repositories/Shared/DocumentRepository.php::sanitizeFilename()`

### 2. **XSS via Filename**
**Risk:** Malicious filenames containing JavaScript could execute when displayed in the browser.

**Example Attack:**
```
filename="<script>alert('XSS')</script>.pdf"
```

**Fix:**
- Sanitizes all special characters from filenames
- Removes control characters and Unicode direction overrides
- Validates filename on upload and download

### 3. **Null Byte Injection**
**Risk:** Null bytes in filenames could bypass extension validation.

**Example Attack:**
```
filename="malicious.php%00.pdf"
```

**Fix:**
- Explicitly checks for and removes null bytes in validation
- Secondary check in sanitization layer
- Double validation of file extensions

### 4. **File Extension Spoofing**
**Risk:** Attackers could disguise malicious files by mismatching extension and MIME type.

**Fix:**
- Validates both file extension and MIME type
- Verifies MIME type matches expected extension
- Uses server-side extension determination

**Location:** `app/Repositories/Shared/DocumentRepository.php::validateAndGetExtension()`

### 5. **Unicode Direction Override Attacks**
**Risk:** Unicode control characters could disguise file extensions.

**Example Attack:**
```
filename="harmless[U+202E]fdp.exe"  // Displays as "harmless.exe.pdf"
```

**Fix:**
- Removes all Unicode direction override characters (U+202A through U+202E, U+2066 through U+2069)
- Sanitizes both on upload and download

## Implementation Details

### Filename Sanitization Process

The `sanitizeFilename()` method in `DocumentRepository.php` performs the following operations:

```php
private function sanitizeFilename(string $filename): string
{
    // 1. Remove null bytes
    $filename = str_replace(chr(0), '', $filename);

    // 2. Remove path traversal sequences
    $filename = str_replace(['../', '..\\', '../', '..\\'], '', $filename);

    // 3. Replace dangerous characters with underscores
    $filename = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '_', $filename);

    // 4. Remove Unicode direction override characters
    $filename = preg_replace('/[\x{202A}-\x{202E}\x{2066}-\x{2069}]/u', '', $filename);

    // 5. Remove control characters
    $filename = preg_replace('/[\x00-\x1F\x7F]/u', '', $filename);

    // 6. Trim dots and spaces
    $filename = trim($filename, '. ');

    // 7. Limit length to 100 characters
    // 8. Provide default if empty

    return $sanitizedFilename;
}
```

### Extension Validation

The `validateAndGetExtension()` method provides multi-layer validation:

1. **Client Extension Check** - Validates the extension from the filename
2. **MIME Type Check** - Verifies the actual file content matches
3. **Allowed List** - Only permits: `pdf`, `jpg`, `jpeg`, `png`
4. **Cross-Reference** - Ensures MIME type matches extension

```php
private function validateAndGetExtension(UploadedFile $file): string
{
    $clientExtension = strtolower($file->getClientOriginalExtension());
    $mimeType = $file->getMimeType();
    $mimeExtension = $this->getExtensionFromMimeType($mimeType);

    // Validate against allowed list
    $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png'];
    if (!in_array($clientExtension, $allowedExtensions)) {
        throw new \Exception('Invalid file extension');
    }

    // Verify MIME type matches extension
    if ($mimeExtension && $mimeExtension !== $clientExtension) {
        // Special handling for JPEG/JPG variants
        if (!($mimeExtension === 'jpeg' && $clientExtension === 'jpg') &&
            !($mimeExtension === 'jpg' && $clientExtension === 'jpeg')) {
            throw new \Exception('File extension does not match file content');
        }
    }

    return $clientExtension;
}
```

### Request-Level Validation

The `UploadDocumentRequest` class provides first-line defense:

```php
'file' => [
    'required',
    'file',
    'mimes:pdf,jpg,jpeg,png',
    'mimetypes:application/pdf,image/jpeg,image/jpg,image/png',
    'max:10240', // 10MB
    function ($attribute, $value, $fail) {
        // Custom validations
        if (strlen($value->getClientOriginalName()) > 255) {
            $fail('Filename too long');
        }
        if (preg_match('/\.\.[\/\\\\]/', $value->getClientOriginalName())) {
            $fail('Invalid path traversal characters');
        }
        if (strpos($value->getClientOriginalName(), chr(0)) !== false) {
            $fail('Invalid null byte characters');
        }
        if (preg_match('/[\x00-\x1F\x7F]/', $value->getClientOriginalName())) {
            $fail('Invalid control characters');
        }
    },
],
```

## Defense in Depth Strategy

The system implements multiple layers of security:

### Layer 1: Request Validation
- File type validation (mimes + mimetypes)
- Size limits (10MB max)
- Filename pattern checks
- Control character detection

### Layer 2: Repository Sanitization
- Comprehensive filename sanitization
- Path traversal prevention
- Unicode attack mitigation
- Null byte removal

### Layer 3: Extension Validation
- MIME type verification
- Extension whitelist enforcement
- Content-type matching

### Layer 4: Storage Isolation
- Files stored in isolated `public/documents/` directory
- Organized by module, year, and month
- Unique timestamp-based filenames

### Layer 5: Download Protection
- Re-sanitization before download
- Content-Disposition headers
- Authorization checks

## File Storage Structure

```
storage/app/public/documents/
├── liquidations/
│   ├── 2026/
│   │   ├── 01/
│   │   │   ├── official_receipt_1738000000.pdf
│   │   │   └── other_document_1738000001.jpg
│   │   └── 02/
│   └── 2025/
├── procurement/
│   └── 2026/
│       └── 02/
│           ├── purchase_order_1738000002.pdf
│           └── iar_1738000003.pdf
└── inventory/
    └── 2026/
        └── 02/
            └── property_card_photo_1738000004.jpg
```

## Allowed File Types

| Extension | MIME Type | Use Case |
|-----------|-----------|----------|
| `.pdf` | `application/pdf` | Official receipts, purchase orders, forms |
| `.jpg` | `image/jpeg` | Photos, scanned documents |
| `.jpeg` | `image/jpeg` | Photos, scanned documents |
| `.png` | `image/png` | Screenshots, property cards |

## Security Best Practices

### For Developers

1. **Never trust client input**
   - Always sanitize filenames from `getClientOriginalName()`
   - Validate MIME types from actual file content
   - Use server-side extension determination

2. **Use whitelists, not blacklists**
   - Only allow known-safe extensions
   - Explicitly list allowed MIME types
   - Reject anything not explicitly permitted

3. **Implement defense in depth**
   - Multiple layers of validation
   - Sanitize on upload AND download
   - Store files outside web root when possible

4. **Limit file sizes**
   - Current limit: 10MB
   - Prevents DoS via large uploads
   - Adjust based on server capacity

5. **Log upload attempts**
   - Monitor for suspicious patterns
   - Track failed validations
   - Alert on repeated attacks

### For System Administrators

1. **Configure file permissions**
   ```bash
   # Ensure upload directory is not executable
   chmod 755 storage/app/public/documents
   find storage/app/public/documents -type f -exec chmod 644 {} \;
   ```

2. **Web server configuration**
   ```apache
   # Apache .htaccess in uploads directory
   <FilesMatch "\.(php|phtml|php3|php4|php5|pl|py|jsp|asp|html|htm|shtml|sh|cgi)$">
       Order Deny,Allow
       Deny from all
   </FilesMatch>
   ```

3. **Monitor storage usage**
   - Set up disk space alerts
   - Implement upload quotas if needed
   - Regular cleanup of old files

4. **Enable virus scanning**
   - Integrate ClamAV or similar
   - Scan files before storage
   - Quarantine suspicious files

## Testing Security

### Manual Testing

Test these attack scenarios to verify protection:

```bash
# 1. Path traversal
curl -F "file=@../../etc/passwd" http://api/documents/upload

# 2. Null byte injection
curl -F "file=@malicious.php%00.pdf" http://api/documents/upload

# 3. Extension mismatch
# Upload a PHP file renamed to .pdf

# 4. Long filename
# Create a file with 300+ character name

# 5. Special characters
curl -F "file=@test<script>.pdf" http://api/documents/upload
```

### Automated Testing

Create PHPUnit tests:

```php
public function test_rejects_path_traversal_filename()
{
    $response = $this->postJson('/api/documents/upload', [
        'file' => UploadedFile::fake()->create('../../../test.pdf'),
        // ... other fields
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('file');
}
```

## Incident Response

If a malicious upload is detected:

1. **Immediate Actions**
   - Delete the malicious file
   - Identify the uploader (check `uploaded_by`)
   - Review audit logs for pattern

2. **Investigation**
   - Check for similar uploads by same user
   - Review recent downloads of the file
   - Scan server for compromise

3. **Prevention**
   - Update validation rules if needed
   - Consider temporary suspension of uploads
   - Notify security team

## Related Files

- `app/Repositories/Shared/DocumentRepository.php` - Core sanitization logic
- `app/Http/Requests/Shared/UploadDocumentRequest.php` - Request validation
- `app/Services/Shared/DocumentService.php` - Business logic
- `app/Http/Controllers/Api/Shared/DocumentController.php` - Upload endpoint
- `app/Policies/DocumentPolicy.php` - Authorization rules

## Security Changelog

### 2026-02-05: Major Security Update
- ✅ Added comprehensive filename sanitization
- ✅ Implemented MIME type validation
- ✅ Added Unicode attack prevention
- ✅ Enhanced path traversal protection
- ✅ Added null byte detection
- ✅ Implemented extension verification
- ✅ Added download-time re-sanitization

### Previous State (Before 2026-02-05)
- ❌ No filename sanitization
- ❌ Direct use of client-provided names
- ❌ No MIME type verification
- ❌ Vulnerable to path traversal
- ❌ Vulnerable to XSS via filename
- ❌ Vulnerable to null byte injection

---

**Classification:** Security Documentation
**Last Updated:** 2026-02-05
**Review Schedule:** Quarterly
**Owner:** Development Team
