<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AIService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AIController extends Controller
{
    public function __construct(private AIService $ai)
    {
    }

    public function chat(Request $request)
    {
        $data = $request->validate([
            'message' => 'required|string|max:5000',
            'conversation_id' => 'nullable|string|max:255',
            'history' => 'sometimes|array',
        ]);

        try {
            $user = $request->user();
            $identifier = $user?->id ?: $request->ip();

            $response = $this->ai->sendMessage($data['message'], $data['history'] ?? [], [
                'conversation_id' => $data['conversation_id'] ?? null,
                'user_id' => $identifier,
            ]);

            return response()->json([
                'success' => true,
                'message' => $response['message'] ?? '',
                'meta' => $response['meta'] ?? [],
            ]);
        } catch (\Exception $e) {
            Log::error('AI chat error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'AI Assistant is temporarily unavailable. Please try again later.',
            ], 503);
        }
    }
}
