<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreInquiryRequest;
use App\Models\Inquiry;
use Illuminate\Http\JsonResponse;

class InquiryController extends Controller
{
    public function store(StoreInquiryRequest $request): JsonResponse
    {
        $inquiry = Inquiry::query()->create($request->safe()->except(StoreInquiryRequest::HONEYPOT));

        // Nothing about the inquiry goes back over the wire. The sender learns
        // that it arrived, and that is all they need.
        return response()->json(['id' => $inquiry->getKey()], JsonResponse::HTTP_CREATED);
    }
}
