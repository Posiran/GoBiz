<?php
namespace App\Http\Controllers\Seller;
use App\Http\Controllers\Controller;
use App\Models\SalesLead;
use Illuminate\Http\Request;

class LeadController extends Controller {
    public function index(Request $r) {
        $status = $r->query('status');
        $q = SalesLead::where('company_id', $r->user()->company->id)->with('rfq:id,title,quantity,budget,status')->orderByDesc('score')->orderByDesc('id');
        if (in_array($status, ['new', 'contacted', 'quoted', 'negotiating', 'won', 'lost'], true)) $q->where('status', $status);
        return view('seller.leads', ['leads' => $q->paginate(20)->withQueryString(), 'status' => $status]);
    }
    public function update(Request $r, SalesLead $lead) {
        abort_unless($lead->company_id === $r->user()->company->id, 403);
        $d = $r->validate(['status' => 'required|in:new,contacted,quoted,negotiating,won,lost', 'notes' => 'nullable|string|max:1000']);
        $lead->update($d + ($d['status'] !== 'new' ? ['last_contacted_at' => now()] : []));
        return back()->with('msg', 'سرنخ به‌روز شد.');
    }
}
