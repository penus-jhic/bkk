<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CvResume;
use App\Services\AiCvService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AiCvController extends Controller
{
    public function __construct(
        protected AiCvService $aiService
    ) {}

    /**
     * Feature 2: ATS Readiness Diagnostic & Scoring (POST /api/ai/cv/score)
     */
    public function score(Request $request): JsonResponse
    {
        $reqId = $this->resolveRequestId($request);
        $authUser = $this->resolveUser($request);
        $userId = (string) ($authUser['id'] ?? 'usr-siswa-001');
        $role = strtoupper((string) ($authUser['role'] ?? 'SISWA'));

        $validator = Validator::make($request->all(), [
            'cv_id' => ['nullable'],
        ]);

        if ($validator->fails()) {
            return $this->errorEnvelope(
                status: 422,
                errorCode: 'VALIDATION_ERROR',
                message: 'Validasi input gagal.',
                errors: $validator->errors()->toArray(),
                reqId: $reqId
            );
        }

        $cv = $this->resolveTargetCv($request->input('cv_id'), $userId, $role, allowTemp: true);
        if ($cv instanceof JsonResponse) {
            return $cv;
        }

        $boundaryError = $this->validateMarkdownBoundaries((string) $cv->raw_markdown, $reqId);
        if ($boundaryError) {
            return $boundaryError;
        }

        $token = $this->extractToken($request);
        $envelope = $this->aiService->evaluateAndStoreScore(
            cv: $cv,
            recipientRole: $role,
            token: $token,
            sendNotification: !$cv->temp
        );

        return response()->json($envelope, 200);
    }

    /**
     * Feature 3: Prioritized Actionable Suggestions (POST /api/ai/cv/suggestions)
     */
    public function suggestions(Request $request): JsonResponse
    {
        $reqId = $this->resolveRequestId($request);
        $authUser = $this->resolveUser($request);
        $userId = (string) ($authUser['id'] ?? 'usr-siswa-001');
        $role = strtoupper((string) ($authUser['role'] ?? 'SISWA'));

        $validator = Validator::make($request->all(), [
            'cv_id' => ['nullable'],
        ]);

        if ($validator->fails()) {
            return $this->errorEnvelope(
                status: 422,
                errorCode: 'VALIDATION_ERROR',
                message: 'Validasi input gagal.',
                errors: $validator->errors()->toArray(),
                reqId: $reqId
            );
        }

        $cv = $this->resolveTargetCv($request->input('cv_id'), $userId, $role, allowTemp: true);
        if ($cv instanceof JsonResponse) {
            return $cv;
        }

        $boundaryError = $this->validateMarkdownBoundaries((string) $cv->raw_markdown, $reqId);
        if ($boundaryError) {
            return $boundaryError;
        }

        $token = $this->extractToken($request);
        $envelope = $this->aiService->suggestions($cv, $token, $reqId);

        return response()->json($envelope, 200);
    }

    /**
     * Feature 4: Context-Aware Interactive CV Chat Assistant (POST /api/ai/cv/chat)
     */
    public function chat(Request $request): JsonResponse|StreamedResponse
    {
        $reqId = $this->resolveRequestId($request);
        $authUser = $this->resolveUser($request);
        $userId = (string) ($authUser['id'] ?? 'usr-siswa-001');

        $validator = Validator::make($request->all(), [
            'messages' => ['required', 'array', 'min:1'],
            'messages.*.role' => ['required', 'string', 'in:user,assistant'],
            'messages.*.content' => ['required', 'string', 'min:1', 'max:10000'],
            'context' => ['required', 'array'],
            'context.jurusan' => ['required', 'string', 'min:2', 'max:150'],
            'stream' => ['nullable', 'boolean'],
        ], [
            'messages.required' => 'Riwayat pesan (messages) wajib disertakan.',
            'context.required' => 'Objek context wajib disertakan.',
            'context.jurusan.required' => 'Field context.jurusan wajib diisi.',
        ]);

        if ($validator->fails()) {
            return $this->errorEnvelope(
                status: 422,
                errorCode: 'VALIDATION_ERROR',
                message: 'Validasi input gagal.',
                errors: $validator->errors()->toArray(),
                reqId: $reqId
            );
        }

        $messages = $request->input('messages', []);
        foreach ($messages as $idx => $msg) {
            $content = (string) ($msg['content'] ?? '');
            if (str_contains($content, "\0")) {
                return $this->errorEnvelope(
                    status: 422,
                    errorCode: 'VALIDATION_ERROR',
                    message: 'Validasi input gagal.',
                    errors: ["messages.{$idx}.content" => ['Pesan mengandung karakter null byte yang tidak diizinkan.']],
                    reqId: $reqId
                );
            }
        }

        $context = $request->input('context', []);
        $stream = (bool) $request->boolean('stream', false);
        $token = $this->extractToken($request);

        // Ambil markdown terbaru milik user (temp atau main) sebagai konteks tambahan fallback
        $latestCv = CvResume::where('user_id', $userId)->latest('updated_at')->first();
        $rawMarkdown = $latestCv?->raw_markdown;

        $result = $this->aiService->chat(
            messages: $messages,
            context: $context,
            stream: $stream,
            token: $token,
            requestId: $reqId,
            rawMarkdown: $rawMarkdown
        );

        if (!$stream) {
            return response()->json($result['envelope'], 200);
        }

        // Mode SSE Streaming (Content-Type: text/event-stream)
        return response()->stream(function () use ($result) {
            if (!empty($result['is_raw_sse']) && !empty($result['sse_body'])) {
                echo $result['sse_body'];
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
                return;
            }

            $envelope = $result['envelope'] ?? [];
            $data = $envelope['data'] ?? [];
            $meta = $envelope['meta'] ?? [];
            $reply = (string) ($data['reply'] ?? '');

            // Pecah kalimat menjadi potongan token untuk efek pengetikan SSE
            $chunks = preg_split('/(\s+)/u', $reply, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
            $buffer = '';
            foreach ($chunks as $idx => $word) {
                $buffer .= $word;
                if ($idx % 4 === 3 || $idx === count($chunks) - 1) {
                    $payload = json_encode(['content' => $buffer], JSON_UNESCAPED_UNICODE);
                    echo "event: delta\n";
                    echo "data: {$payload}\n\n";
                    $buffer = '';
                    if (ob_get_level() > 0) {
                        ob_flush();
                    }
                    flush();
                }
            }

            $donePayload = json_encode([
                'execution_time_ms' => $meta['execution_time_ms'] ?? 420,
                'tokens_used' => $meta['tokens_used']['total_tokens'] ?? 145,
                'suggested_actions' => $data['suggested_actions'] ?? [],
                'quick_chips' => $data['quick_chips'] ?? [],
            ], JSON_UNESCAPED_UNICODE);

            echo "event: done\n";
            echo "data: {$donePayload}\n\n";
            if (ob_get_level() > 0) {
                ob_flush();
            }
            flush();
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache, no-transform',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /**
     * Resolve target CvResume berdasarkan cv_id atau fallback ke CV aktif milik user.
     */
    protected function resolveTargetCv(mixed $cvId, string $userId, string $role, bool $allowTemp = true): CvResume|JsonResponse
    {
        $reqId = 'req-' . Str::uuid()->toString();

        if ($cvId !== null && $cvId !== '') {
            if (!is_numeric($cvId)) {
                return $this->errorEnvelope(
                    status: 422,
                    errorCode: 'VALIDATION_ERROR',
                    message: 'Validasi input gagal.',
                    errors: ['cv_id' => ['Parameter cv_id harus berupa angka ID yang valid.']],
                    reqId: $reqId
                );
            }

            $cv = CvResume::find((int) $cvId);
            if (!$cv) {
                return $this->errorEnvelope(
                    status: 422,
                    errorCode: 'VALIDATION_ERROR',
                    message: 'Dokumen CV dengan ID tersebut tidak ditemukan.',
                    errors: ['cv_id' => ['Record cv_resumes tidak ditemukan.']],
                    reqId: $reqId
                );
            }

            if ($role !== 'ADMIN' && (string) $cv->user_id !== $userId) {
                return $this->errorEnvelope(
                    status: 403,
                    errorCode: 'FORBIDDEN',
                    message: 'Anda tidak memiliki akses terhadap dokumen CV ini.',
                    errors: [],
                    reqId: $reqId
                );
            }

            return $cv;
        }

        $query = CvResume::where('user_id', $userId);
        if (!$allowTemp) {
            $query->where('temp', false);
        }

        $cv = (clone $query)->where('is_main', true)->first()
            ?? $query->latest('id')->first();

        if (!$cv) {
            return $this->errorEnvelope(
                status: 422,
                errorCode: 'VALIDATION_ERROR',
                message: 'Validasi input gagal.',
                errors: ['cv_id' => ['Belum ada dokumen CV yang tersimpan untuk dianalisis.']],
                reqId: $reqId
            );
        }

        return $cv;
    }

    /**
     * Validasi batas karakter dan null-byte dokumen Markdown (Section 8.2 AI_CONTRACT.md).
     */
    protected function validateMarkdownBoundaries(string $markdown, string $reqId): ?JsonResponse
    {
        if (str_contains($markdown, "\0")) {
            return $this->errorEnvelope(
                status: 422,
                errorCode: 'VALIDATION_ERROR',
                message: 'Validasi input gagal.',
                errors: ['raw_markdown' => ['Konten CV mengandung karakter null byte yang tidak valid.']],
                reqId: $reqId
            );
        }

        $len = mb_strlen(trim($markdown));
        if ($len < 20) {
            return $this->errorEnvelope(
                status: 422,
                errorCode: 'VALIDATION_ERROR',
                message: 'Validasi input gagal.',
                errors: ['raw_markdown' => ['Konten CV tidak boleh kosong atau kurang dari 20 karakter.']],
                reqId: $reqId
            );
        }

        if ($len > 30000) {
            return $this->errorEnvelope(
                status: 422,
                errorCode: 'VALIDATION_ERROR',
                message: 'Validasi input gagal.',
                errors: ['raw_markdown' => ['Konten CV melebihi batas maksimum 30.000 karakter.']],
                reqId: $reqId
            );
        }

        return null;
    }

    protected function errorEnvelope(
        int $status,
        string $errorCode,
        string $message,
        array $errors = [],
        ?string $reqId = null
    ): JsonResponse {
        $payload = [
            'success' => false,
            'status_code' => $status,
            'message' => $message,
            'error_code' => $errorCode,
        ];

        if (!empty($errors)) {
            $payload['errors'] = $errors;
        }

        $payload['meta'] = [
            'request_id' => $reqId ?: ('req-' . Str::uuid()->toString()),
            'timestamp' => now()->toIso8601ZuluString(),
        ];

        return response()->json($payload, $status);
    }

    protected function resolveRequestId(Request $request): string
    {
        return (string) ($request->header('X-Request-ID') ?: ('req-' . Str::uuid()->toString()));
    }

    protected function resolveUser(Request $request): array
    {
        return (array) ($request->attributes->get('auth_user') ?? $request->auth_user ?? $request->input('auth_user') ?? []);
    }

    protected function extractToken(Request $request): ?string
    {
        $attr = $request->attributes->get('auth_token') ?? $request->input('auth_token');
        if (!empty($attr) && is_string($attr)) {
            return trim($attr);
        }

        $bearer = $request->bearerToken();
        if (!empty($bearer)) {
            return trim($bearer);
        }

        $cookie = $request->cookie('access_token') ?? $request->cookies->get('access_token');
        if (!empty($cookie) && is_string($cookie)) {
            return trim($cookie);
        }

        $rawCookie = $request->header('cookie');
        if (!empty($rawCookie) && is_string($rawCookie)) {
            if (preg_match('/(?:^|;\s*)access_token=([^;]+)/', $rawCookie, $matches)) {
                return trim(urldecode($matches[1]));
            }
        }

        return null;
    }
}
