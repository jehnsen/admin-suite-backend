# Document Storage & Access Control Strategy

## Overview
This document outlines the secure storage strategy for AdminSuite documents, implementing private storage for sensitive documents and temporary signed URLs for controlled access.

## Security Architecture

### Storage Classification

Documents are classified into two categories based on sensitivity:

#### 🔴 **Sensitive Documents** (Private Storage)
Stored in `storage/app/private/documents/` - **NOT** web-accessible

| Document Type | Entity | Why Sensitive |
|---------------|--------|---------------|
| `official_receipt` | All | Contains financial transaction details |
| `purchase_order` | Procurement | Contains vendor information and pricing |
| `delivery_receipt` | Procurement | Contains item quantities and values |
| `iar` (Inspection Report) | Procurement | Contains approval signatures and acceptance data |
| All types | Liquidation | Financial records with disbursement details |
| All types | CashAdvance | Cash advance requests with amounts |
| All types | Disbursement | Payment vouchers with payee information |

#### 🟢 **Public Documents** (Public Storage)
Stored in `storage/app/public/documents/` - Web-accessible via direct URLs

| Document Type | Entity | Why Public |
|---------------|--------|-----------|
| `property_card_photo` | Inventory | Non-sensitive equipment photos |
| `other` | Inventory | General documentation |

---

## Implementation Details

### Database Schema

**New Columns Added to `documents` table:**

```sql
-- Security classification
is_sensitive BOOLEAN DEFAULT FALSE
    COMMENT 'Whether document contains sensitive/confidential data'

-- Storage location
storage_disk ENUM('public', 'private') DEFAULT 'public'
    COMMENT 'Storage disk: public for non-sensitive, private for sensitive'
```

### Storage Paths

```
storage/
├── app/
│   ├── public/documents/          # Public documents (web-accessible)
│   │   ├── inventory/
│   │   │   └── 2026/
│   │   │       └── 02/
│   │   │           └── property_card_1234567890.jpg
│   │   └── procurement/
│   │       └── ...
│   │
│   └── private/documents/         # Sensitive documents (protected)
│       ├── liquidations/
│       │   └── 2026/
│       │       └── 02/
│       │           └── official_receipt_1234567890.pdf
│       └── procurement/
│           └── 2026/
│               └── 02/
│                   ├── purchase_order_1234567891.pdf
│                   └── delivery_receipt_1234567892.pdf
```

---

## Access Control

### How It Works

#### **For Sensitive Documents:**

1. **Upload** → Stored in private disk
2. **Database Record** → Marked as `is_sensitive = true`, `storage_disk = 'private'`
3. **Access** → Must be authenticated user with proper authorization
4. **Download URL** → Temporary signed URL (expires in 30 minutes)
5. **Audit** → All access logged with user ID, IP address, timestamp

**Example Flow:**
```
User requests document
     ↓
Check: Is user authorized? (DocumentPolicy)
     ↓
Generate temporary signed URL
     ↓
User downloads via signed URL (valid 30 mins)
     ↓
Access logged to audit trail
```

#### **For Public Documents:**

1. **Upload** → Stored in public disk
2. **Database Record** → `is_sensitive = false`, `storage_disk = 'public'`
3. **Access** → Must be authenticated (basic auth check)
4. **Download URL** → Direct storage URL (permanent)
5. **Audit** → Access logged

---

## Code Implementation

### Document Classification

**File:** `app/Repositories/Shared/DocumentRepository.php`

```php
private function isSensitiveDocument(string $documentType, string $entityType): bool
{
    // Sensitive document types
    $sensitiveDocs = [
        'official_receipt',
        'purchase_order',
        'delivery_receipt',
        'iar',
    ];

    // All financial documents are sensitive
    if (in_array($entityType, ['Liquidation', 'CashAdvance', 'Disbursement'])) {
        return true;
    }

    return in_array($documentType, $sensitiveDocs);
}
```

### Storage Logic

```php
public function uploadDocument(array $data, UploadedFile $file): Document
{
    // Classify document
    $isSensitive = $this->isSensitiveDocument($data['document_type'], $data['documentable_type']);
    $disk = $isSensitive ? 'private' : 'public';

    // Store in appropriate disk
    $filePath = $file->storeAs($storagePath, $fileName, $disk);

    // Save with metadata
    return Document::create([
        // ... other fields
        'is_sensitive' => $isSensitive,
        'storage_disk' => $disk,
    ]);
}
```

### Temporary Signed URLs

```php
public function generateTemporaryUrl(int $documentId, int $expiresInMinutes = 30): string
{
    $document = Document::findOrFail($documentId);

    // Sensitive documents get signed URLs
    if ($document->is_sensitive || $document->storage_disk === 'private') {
        return \URL::temporarySignedRoute(
            'documents.download',
            now()->addMinutes($expiresInMinutes),
            ['id' => $documentId]
        );
    }

    // Public documents get direct URLs
    return Storage::disk('public')->url($document->file_path);
}
```

### Download with Access Control

```php
public function downloadDocument(int $id): StreamedResponse
{
    $document = Document::findOrFail($id);
    $disk = $document->storage_disk ?? 'public';

    // Check file exists
    if (!Storage::disk($disk)->exists($document->file_path)) {
        abort(404, 'File not found on storage');
    }

    // Audit log
    \Log::info('Document downloaded', [
        'document_id' => $document->id,
        'user_id' => auth()->id(),
        'ip_address' => request()->ip(),
        'is_sensitive' => $document->is_sensitive,
    ]);

    // Download from appropriate disk
    return Storage::disk($disk)->download($document->file_path, $safeFilename);
}
```

---

## API Usage

### Getting Document with Temporary URL

**Request:**
```http
GET /api/documents/123
Authorization: Bearer {token}
```

**Response:**
```json
{
  "id": 123,
  "document_type": "official_receipt",
  "file_name": "OR-12345.pdf",
  "is_sensitive": true,
  "storage_disk": "private",
  "download_url": "https://api.example.com/api/documents/123/download?expires=1738000000&signature=abc123...",
  "url_expires_in": "30 minutes",
  "uploaded_at": "2026-02-05T10:00:00Z"
}
```

### Downloading Document

**For Sensitive Documents:**
```http
GET /api/documents/123/download?expires=1738000000&signature=abc123...
Authorization: Bearer {token}

# URL is valid for 30 minutes
# Must match signature
# Requires authentication
```

**For Public Documents:**
```http
GET /api/documents/456/download
Authorization: Bearer {token}

# Direct download
# No expiration
# Still requires authentication
```

---

## Migration Guide

### Step 1: Run Migration

```bash
php artisan migrate
```

This adds `is_sensitive` and `storage_disk` columns to `documents` table.

### Step 2: Classify Existing Documents

The migration automatically marks existing sensitive documents:
- All `official_receipt`, `purchase_order`, `delivery_receipt`, `iar` → `is_sensitive = true`
- All documents for Liquidation/CashAdvance/Disbursement entities → `is_sensitive = true`
- Existing files remain in public storage for backward compatibility

### Step 3: Move Sensitive Files to Private Storage (Optional)

If you want to move existing sensitive documents to private storage:

```bash
php artisan tinker
```

```php
use App\Models\Document;
use Illuminate\Support\Facades\Storage;

Document::where('is_sensitive', true)
    ->where('storage_disk', 'public')
    ->chunk(100, function ($documents) {
        foreach ($documents as $doc) {
            $oldPath = $doc->file_path;

            // Copy to private disk
            $content = Storage::disk('public')->get($oldPath);
            Storage::disk('private')->put($oldPath, $content);

            // Update database
            $doc->update(['storage_disk' => 'private']);

            // Optionally delete from public
            // Storage::disk('public')->delete($oldPath);

            echo "Moved: {$doc->id} - {$doc->file_name}\n";
        }
    });
```

### Step 4: Update Filesystem Config

Ensure `config/filesystems.php` has the private disk configured:

```php
'disks' => [
    'public' => [
        'driver' => 'local',
        'root' => storage_path('app/public'),
        'url' => env('APP_URL').'/storage',
        'visibility' => 'public',
    ],

    'private' => [
        'driver' => 'local',
        'root' => storage_path('app/private'),
        'visibility' => 'private',  // Not web-accessible
    ],
],
```

### Step 5: Test Access Control

```bash
# Test sensitive document access
curl -H "Authorization: Bearer {token}" \
  https://api.example.com/api/documents/123

# Verify signed URL works
curl -H "Authorization: Bearer {token}" \
  "https://api.example.com/api/documents/123/download?expires=X&signature=Y"

# Verify expired URL fails
# Wait 30 minutes or manipulate timestamp
curl -H "Authorization: Bearer {token}" \
  "https://api.example.com/api/documents/123/download?expires=OLD&signature=Y"
# Should return 403 Forbidden
```

---

## Security Benefits

### Before (Vulnerable)

✗ All documents stored publicly
✗ Direct file URLs never expire
✗ Anyone with URL can access (if they guess it)
✗ No audit trail for file access
✗ Financial documents exposed

**Attack Scenario:**
```
Attacker finds: /storage/documents/liquidations/2026/02/official_receipt_1234.pdf
     ↓
Direct access via web browser (no auth required)
     ↓
Downloads sensitive financial data
```

### After (Secure)

✅ Sensitive documents in private storage
✅ Temporary signed URLs (30 min expiration)
✅ Authentication required for all access
✅ Authorization checked via DocumentPolicy
✅ Complete audit trail with user ID + IP
✅ File path guessing ineffective (private disk not web-accessible)

**Secure Flow:**
```
User requests document
     ↓
Auth check: Valid bearer token?
     ↓
Policy check: Can user access this document?
     ↓
Generate signed URL with 30-min expiration
     ↓
User downloads via signed URL
     ↓
Access logged with user ID, IP, timestamp
     ↓
URL expires after 30 minutes
```

---

## Audit Logging

All document downloads are logged:

```php
\Log::info('Document downloaded', [
    'document_id' => 123,
    'document_type' => 'official_receipt',
    'user_id' => 5,
    'user_email' => 'admin@deped.gov.ph',
    'ip_address' => '192.168.1.100',
    'is_sensitive' => true,
    'timestamp' => '2026-02-05 14:30:00'
]);
```

### Querying Audit Logs

```bash
# View recent document downloads
tail -f storage/logs/laravel.log | grep "Document downloaded"

# Count downloads by user
grep "Document downloaded" storage/logs/laravel.log | \
  jq -r '.context.user_id' | sort | uniq -c

# Find sensitive document access
grep "Document downloaded" storage/logs/laravel.log | \
  grep '"is_sensitive":true'
```

---

## Best Practices

### For Developers

1. **Always classify new document types**
   - Add to `isSensitiveDocument()` method
   - Default to sensitive if unsure

2. **Use repository methods**
   - Don't bypass repository for file operations
   - Use `generateTemporaryUrl()` for links

3. **Respect storage disk**
   - Always use `$document->storage_disk` when accessing files
   - Never assume all files are in public disk

4. **Log access attempts**
   - Include user ID, IP, document type
   - Monitor for suspicious patterns

### For System Administrators

1. **Protect private storage**
   ```apache
   # .htaccess in storage/app/private
   Deny from all
   ```

2. **Monitor access logs**
   - Set up alerts for bulk downloads
   - Review access patterns monthly

3. **Backup both disks**
   ```bash
   # Backup script
   tar -czf documents_backup_$(date +%Y%m%d).tar.gz \
     storage/app/public/documents \
     storage/app/private/documents
   ```

4. **Set file permissions**
   ```bash
   chmod 755 storage/app/private
   chmod 644 storage/app/private/documents/**/*
   ```

---

## Related Files

- `app/Repositories/Shared/DocumentRepository.php` - Storage and classification logic
- `app/Models/Document.php` - Model with signed URL generation
- `app/Policies/DocumentPolicy.php` - Access control rules
- `routes/api.php` - Signed route configuration
- `database/migrations/2026_02_05_*_add_storage_security_columns_to_documents_table.php` - Migration
- `docs/FILE_UPLOAD_SECURITY.md` - File upload security measures

---

**Last Updated:** 2026-02-05
**Version:** 1.0.0
**Status:** Production Ready
