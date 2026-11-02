<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditController extends Controller {
    public function index(Request $r) {
        $q = AuditLog::with('user:id,name,mobile')->latest('id');
        if ($r->filled('action')) $q->where('action', 'like', $r->query('action').'%');
        return view('admin.audit', ['rows' => $q->paginate(50)->withQueryString(), 'action' => (string) $r->query('action')]);
    }
}
