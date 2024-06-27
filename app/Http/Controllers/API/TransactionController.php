<?php

namespace App\Http\Controllers\API;

use App\Models\Listing;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Transaction\Store;
use Illuminate\Http\Exceptions\HttpResponseException;

class TransactionController extends Controller
{
    public function index()
    {
        $transactions = Transaction::with('listing')->whereUserId(auth()->id())->paginate();

        return response()->json([
            'success' => true,
            'message' => 'Get all transactions',
            'data' => $transactions,
        ]);
    }

    private function _fullyBooekedChecker(Store $request)
    {
        $listing = Listing::find($request->listing_id);
        $runningTransactionCount = Transaction::whereListingId($listing->id)
            ->whereNot('status', 'canceled')
            ->where(function ($query) use ($request) {
                $query
                    ->whereBetween('start_date', [$request->start_date, $request->end_date])
                    ->orWhereBetween('end_date', [$request->start_date, $request->end_date])
                    ->orWhere(function ($subquery) use ($request) {
                        $subquery->where('start_date', '<', $request->start_date)->where('end_date', '>', $request->end_date);
                    });
            })
            ->count();

        if ($runningTransactionCount >= $listing->max_person) {
            throw new HttpResponseException(
                response()->json(
                    [
                        'success' => false,
                        'message' => 'Listing is fully booked',
                    ],
                    JsonResponse::HTTP_UNPROCESSABLE_ENTITY,
                ),
            );
        }

        return true;
    }

    function isAvailable(Store $request)
    {
        $this->_fullyBooekedChecker($request);

        return response()->json([
            'success' => true,
            'message' => 'Listing is ready to book',
        ]);
    }

    public function store(Store $request)
    {
        $this->_fullyBooekedChecker($request);

        $transaction = Transaction::create([
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'listing_id' => $request->listing_id,
            'user_id' => auth()->id(),
        ]);

        $transaction->Listing;

        return response()->json([
            'success' => true,
            'message' => 'New transaction created successfully',
            'data' => $transaction,
        ]);
    }

    public function show(Transaction $transaction): JsonResponse
    {
        if ($transaction->user_id !== auth()->id()) {
            return response()->json(
                [
                    'success' => true,
                    'message' => 'Unauthorized',
                ],
                JsonResponse::HTTP_UNAUTHORIZED,
            );
        }

        $transaction->Listing;

        return response()->json([
            'success' => true,
            'message' => 'Get all transactions',
            'data' => $transaction,
        ]);
    }
}
