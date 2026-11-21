<?php

namespace App\Http\Controllers;

use App\Services\MidtransWebhookService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use JsonException;
use stdClass;
use Throwable;

class MidtransWebhookController extends Controller
{
    private const MAX_BODY_BYTES = 64 * 1024;

    public function __invoke(Request $request, MidtransWebhookService $webhook): JsonResponse
    {
        $contentType = strtolower(trim(explode(';', $request->header('Content-Type', ''), 2)[0]));
        if ($contentType !== 'application/json') {
            return response()->json(['message' => 'Unsupported media type'], 415);
        }

        $body = $request->getContent();
        if (strlen($body) > self::MAX_BODY_BYTES) {
            return response()->json(['message' => 'Payload too large'], 413);
        }

        try {
            // Preserve signed field values and read only the body, never query parameters.
            $payload = json_decode($body, false, 32, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return response()->json(['message' => 'Invalid payload'], 400);
        }

        if (! $payload instanceof stdClass || (array) $payload === []) {
            return response()->json(['message' => 'Invalid payload'], 400);
        }

        try {
            $result = $webhook->process((array) $payload);
        } catch (QueryException) {
            return response()->json(['message' => 'Temporarily unavailable'], 503);
        } catch (Throwable) {
            return response()->json(['message' => 'Internal error'], 500);
        }

        // A reconciliation result is returned by the service only after its transaction commits.
        return match ($result['status'] ?? null) {
            'applied', 'acknowledged', 'needs_reconciliation' => response()->json(['message' => 'OK']),
            'unavailable' => response()->json(['message' => 'Temporarily unavailable'], 503),
            'rejected' => match ($result['reason'] ?? null) {
                'invalid_payload' => response()->json(['message' => 'Invalid payload'], 400),
                'invalid_signature' => response()->json(['message' => 'Forbidden'], 403),
                'unknown_payment' => response()->json(['message' => 'Not found'], 404),
                default => response()->json(['message' => 'Internal error'], 500),
            },
            default => response()->json(['message' => 'Internal error'], 500),
        };
    }
}
