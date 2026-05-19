<?php

namespace App\Http\Controllers\Api\Shared;

use App\Http\Controllers\Controller;
use App\Http\Requests\Shared\UploadDocumentRequest;
use App\Http\Resources\Shared\DocumentResource;
use App\Services\Shared\DocumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    protected DocumentService $documentService;

    public function __construct(DocumentService $documentService)
    {
        $this->documentService = $documentService;
    }

    public function upload(UploadDocumentRequest $request): JsonResponse
    {
        try {
            $document = $this->documentService->uploadDocument(
                $request->only(['documentable_type', 'documentable_id', 'document_type', 'description']),
                $request->file('file')
            );

            return response()->json([
                'message' => 'Document uploaded successfully.',
                'data'    => new DocumentResource($document->load('uploadedBy')),
            ], 201);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => 'Failed to upload document.'], 500);
        }
    }

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'documentable_type' => 'nullable|string',
            'documentable_id'   => 'nullable|integer',
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

        return response()->json(['data' => DocumentResource::collection($documents)]);
    }

    public function show(string $uuid): JsonResponse
    {
        $id = \App\Models\Document::where('uuid', $uuid)->value('id') ?? 0;
        $document = $this->documentService->getDocumentById($id);

        if (!$document) {
            return response()->json(['message' => 'Document not found.'], 404);
        }

        $this->authorize('view', $document);

        return response()->json(['data' => new DocumentResource($document)]);
    }

    public function download(string $uuid)
    {
        $id = \App\Models\Document::where('uuid', $uuid)->value('id') ?? 0;
        try {
            $document = $this->documentService->getDocumentById($id);

            if (!$document) {
                return response()->json(['message' => 'Document not found.'], 404);
            }

            $this->authorize('view', $document);

            return $this->documentService->downloadDocument($id);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => 'Failed to download document.'], 500);
        }
    }

    public function destroy(string $uuid): JsonResponse
    {
        $id = \App\Models\Document::where('uuid', $uuid)->value('id') ?? 0;
        $document = $this->documentService->getDocumentById($id);

        if (!$document) {
            return response()->json(['message' => 'Document not found.'], 404);
        }

        $this->authorize('delete', $document);

        $deleted = $this->documentService->deleteDocument($id);

        if ($deleted) {
            return response()->json(['message' => 'Document deleted successfully.']);
        }

        return response()->json(['message' => 'Failed to delete document.'], 500);
    }
}
