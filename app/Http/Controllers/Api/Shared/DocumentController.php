<?php

namespace App\Http\Controllers\Api\Shared;

use App\Http\Controllers\Controller;
use App\Http\Requests\Shared\UploadDocumentRequest;
use App\Services\Shared\DocumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    protected $documentService;

    public function __construct(DocumentService $documentService)
    {
        $this->documentService = $documentService;
    }

    /**
     * Upload a document
     */
    public function upload(UploadDocumentRequest $request): JsonResponse
    {
        try {
            $document = $this->documentService->uploadDocument(
                $request->only(['documentable_type', 'documentable_id', 'document_type', 'description']),
                $request->file('file')
            );

            return response()->json([
                'message' => 'Document uploaded successfully.',
                'data' => $document->load('uploadedBy'),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to upload document.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * List documents (optionally filter by entity)
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'documentable_type' => 'nullable|string',
            'documentable_id' => 'nullable|integer',
        ]);

        if ($request->has('documentable_type') && $request->has('documentable_id')) {
            $documents = $this->documentService->getDocumentsByEntity(
                $request->input('documentable_type'),
                $request->input('documentable_id')
            );
        } else {
            return response()->json([
                'message' => 'Please provide documentable_type and documentable_id to filter documents.',
            ], 400);
        }

        return response()->json([
            'data' => $documents,
        ]);
    }

    /**
     * Show document metadata
     */
    public function show(int $id): JsonResponse
    {
        $document = $this->documentService->getDocumentById($id);

        if (!$document) {
            return response()->json([
                'message' => 'Document not found.',
            ], 404);
        }

        // Authorization check
        $this->authorize('view', $document);

        return response()->json([
            'data' => $document,
        ]);
    }

    /**
     * Download document file
     */
    public function download(int $id)
    {
        try {
            $document = $this->documentService->getDocumentById($id);

            if (!$document) {
                return response()->json([
                    'message' => 'Document not found.',
                ], 404);
            }

            // Authorization check
            $this->authorize('view', $document);

            return $this->documentService->downloadDocument($id);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to download document.',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Delete document (soft delete)
     */
    public function destroy(int $id): JsonResponse
    {
        $document = $this->documentService->getDocumentById($id);

        if (!$document) {
            return response()->json([
                'message' => 'Document not found.',
            ], 404);
        }

        // Authorization check
        $this->authorize('delete', $document);

        $deleted = $this->documentService->deleteDocument($id);

        if ($deleted) {
            return response()->json([
                'message' => 'Document deleted successfully.',
            ]);
        }

        return response()->json([
            'message' => 'Failed to delete document.',
        ], 500);
    }
}
