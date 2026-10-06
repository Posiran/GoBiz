<?php
namespace App\Http\Controllers;
use App\Models\Category;
use App\Models\City;
use App\Models\Inquiry;
use App\Models\Province;
use App\Models\Rfq;
use App\Support\Fa;
use Illuminate\Http\Request;

class RfqController extends Controller {
    public function create() {
        $prov = old('province_id');
        return view('rfq.create', [
            'categories' => Category::whereNull('parent_id')->with('children')->orderBy('sort_order')->get(),
            'provinces' => Province::orderBy('name')->get(),
            'cities' => $prov ? City::where('province_id', $prov)->orderBy('name')->get() : collect(),
            'sel' => ['province' => $prov, 'city' => old('city_id'), 'town' => null],
        ]);
    }
    public function store(Request $r) {
        $r->merge(collect($r->only(['quantity', 'budget']))->map(fn ($v) => Fa::latin($v))->all());
        $d = $r->validate([
            'title' => 'required|string|max:200', 'details' => 'required|string|min:10|max:5000',
            'category_id' => 'nullable|exists:categories,id', 'city_id' => 'nullable|exists:cities,id',
            'quantity' => 'nullable|integer|min:1', 'budget' => 'nullable|integer|min:0',
        ]);
        Rfq::create($d + ['user_id' => $r->user()->id]);
        return redirect()->route('rfq.mine')->with('msg', 'درخواست شما ثبت شد. فروشندگان پیشنهاد خود را در «پیام‌ها» می‌فرستند.');
    }
    public function mine(Request $r) {
        return view('rfq.mine', ['rfqs' => Rfq::where('user_id', $r->user()->id)->withCount('inquiries as offers')->latest()->paginate(20)]);
    }
    public function close(Request $r, Rfq $rfq) {
        abort_unless($rfq->user_id === $r->user()->id, 403);
        $rfq->update(['status' => 'closed']);
        return back()->with('msg', 'درخواست بسته شد.');
    }

    /** تابلوی درخواست‌های خریداران، برای فروشندگان */
    public function board(Request $r) {
        $q = Rfq::with(['category', 'city'])->where('status', 'open')->where('user_id', '!=', $r->user()->id);
        if ($r->filled('category') && $cat = Category::where('slug', $r->query('category'))->first())
            $q->whereIn('category_id', $cat->selfAndDescendantIds());
        return view('rfq.board', [
            'rfqs' => $q->latest()->paginate(15)->withQueryString(),
            'categories' => Category::whereNull('parent_id')->orderBy('sort_order')->get(),
            'offered' => Inquiry::where('from_user_id', $r->user()->id)->whereNotNull('rfq_id')->whereNull('parent_id')->pluck('rfq_id')->all(),
        ]);
    }
    public function offer(Request $r, Rfq $rfq) {
        $me = $r->user();
        if ($me->company->status !== 'active') return back()->withErrors('برای ارسال پیشنهاد، شرکت شما باید توسط مدیر تأیید شده باشد.');
        if ($rfq->status !== 'open' || $rfq->user_id === $me->id) abort(404);
        if (Inquiry::where(['rfq_id' => $rfq->id, 'from_user_id' => $me->id])->whereNull('parent_id')->exists())
            return back()->withErrors('برای این درخواست قبلاً پیشنهاد داده‌اید.');
        $r->merge(['offered_price' => Fa::latin($r->input('offered_price'))]);
        $d = $r->validate(['message' => 'required|string|min:10|max:2000', 'offered_price' => 'nullable|integer|min:0']);
        Inquiry::create($d + ['rfq_id' => $rfq->id, 'from_user_id' => $me->id, 'to_user_id' => $rfq->user_id]);
        return back()->with('msg', 'پیشنهاد شما برای خریدار ارسال شد.');
    }
}
