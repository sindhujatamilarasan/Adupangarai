<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\AiDrafts;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** AI suggestions only. Every endpoint returns a draft for the user to review; nothing is saved. */
class AiController extends Controller
{
    public function pantryParse(Request $request): JsonResponse
    {
        $text = $request->validate(['text' => ['required', 'string', 'max:2000']])['text'];

        $result = AiDrafts::pantryItems($text);

        return response()->json(['data' => $result['items'], 'source' => $result['source']]);
    }

    public function recipeParse(Request $request): JsonResponse
    {
        $text = $request->validate(['text' => ['required', 'string', 'max:6000']])['text'];

        return response()->json(['data' => AiDrafts::recipe($text)]);
    }
}
