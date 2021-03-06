<?php

namespace App\Http\Controllers;

use App\Ad;
use App\Bid;
use App\Validators\BidValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class BidController extends Controller
{
    protected $bidValidator;

    public function __construct(BidValidator $bidValidator)
    {
        $this->bidValidator = $bidValidator;
    }

    protected function bidValidator(array $data)
    {
        return Validator::make($data, [
            'ad_id' => ['exists:ads,id'],
            'body' => ['string', 'max:10000'],
            'cost' => ['required', 'numeric', 'between:0.00,99999999.99'],
        ]);
    }

    public function store(Request $request)
    {
//        TODO: check if ad didn't hire anyone
        $user = Auth::user();
        $requestData = $request->except('_token');

        $validator = $this->bidValidator($requestData);
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $bid = new Bid($requestData);
        $bid->user_id = $user->id;
        $bid->save();

        return redirect()->route('ad.view', $request->ad_id);
    }

    public function destroy(Request $request)
    {
        $user = Auth::user();
        $bid = Bid::where('id', $request->id)->firstOrFail();
        $this->bidValidator->validateOwnership($user, $bid);

        $bid->delete();

        return redirect()->back();
    }

    public function hire(Request $request)
    {
        $user = Auth::user();
        $ad = Ad::where('id', $request->ad_id)->firstOrFail();
        $bid = Bid::where('id', $request->bid_id)->firstOrFail();

        if (!$user->ads->contains($ad)) {
            abort('403', 'Unauthorized action.');
        }

        if (!$ad->bids->contains($bid)) {
            abort('403', 'Unauthorized action.');
        }

        $ad->bid_id = $bid->id;
        $ad->save();

        return redirect()->back();
    }
}
