<?php

namespace App\Http\Controllers;

use App\Ad;
use App\Repositories\UserRepository;
use App\WorkCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AdController extends Controller
{
    protected $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function view(Request $request)
    {
        $ad = Ad::where('id', $request->id)->firstOrFail();
        $user = Auth::user();
        if ($user) {
            $userWork = $this->userRepository->getWork($user->id)->pluck('id')->toArray();
            $isHired = in_array($ad->id, $userWork);
        } else {
            $isHired = false;
        }

        return view('ad.view')
            ->with([
                'ad' => $ad,
                'user' => Auth::user(),
                'isHired' => $isHired,
            ]);
    }

    public function create(Request $request)
    {
        return view('ad.create')
            ->with(
                [
                    'workCategories' => WorkCategory::all()->sortBy('name'),
                ]);
    }

    public function store(Request $request)
    {
        //TODO: move validator outside of the function for reusability
        $validator = Validator::make($request->all(), [
            'title' => 'required|max:100',
            'body' => 'required|max:10000',
            'work_category_id' => 'exists:work_categories,id',
            'price_floor' => 'required|numeric|between:0.00,99999999.99',
            'price_ceiling' => [
                'required',
                'numeric',
                'between:0.00,99999999.99',
                'greater_or_equals_to:price_floor',
        ]]);

        if ($validator->fails()) {
            return redirect(route('ad.create'))
                ->withErrors($validator)
                ->withInput();
        }

        $adData = $request->except('_token');
        $adData['user_id'] = Auth::user()->id;
        Ad::create($adData);

        return redirect(route('home'));
    }

    public function destroy(Request $request)
    {
        $user = Auth::user();
        $ad = Ad::where('id', $request->id)->firstOrFail();

        if ($user->ads->contains($ad)) {
            $ad->delete();
            return redirect()->back();
        } else {
            return abort('403');
        }
    }

    public function remember(Request $request)
    {
        $user = Auth::user();

        $user->rememberedAds()->attach($request->id);

        return redirect()->back();
    }

    public function forget(Request $request)
    {
        $user = Auth::user();

        $user->rememberedAds()->detach($request->id);

        return redirect()->back();
    }

    public function remembered(Request $request)
    {
        $user = Auth::user();
        $ads = $user->rememberedAds;

        return view('ad.remembered')
            ->with([
                'ads' => $ads,
                'user' => $user,
            ]);
    }

    public function myAds(Request $request)
    {
        $user = Auth::user();
        $ads = $user->ads->sortByDesc('created_at');

        return view('ad.my')
            ->with([
                'ads' => $ads,
                'user' => $user,
            ]);
    }
}
