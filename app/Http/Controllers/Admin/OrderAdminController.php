<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Support\Procurement;
use Illuminate\Http\Request;

class OrderAdminController extends Controller {
    public function index(Request $r) {
        $status = isset(Order::STATUS[$r->query('status')]) ? $r->query('status') : 'disputed';
        return view('admin.orders', ['status' => $status, 'orders' => Order::where('status', $status)->with(['company:id,name', 'buyer:id,name'])->latest('id')->paginate(20)->withQueryString()]);
    }
    /** حل اختلاف: تکمیل (به نفع فروشنده) یا لغو (به نفع خریدار). استرداد پول خارج از سایت و بین طرفین است. */
    public function resolve(Request $r, Order $order) {
        $d = $r->validate(['to' => 'required|in:completed,cancelled', 'note' => 'required|string|max:300'], ['note.required' => 'توضیح تصمیم را بنویسید.']);
        try { Procurement::move($order, $d['to'], $r->user(), 'admin', $d['note']); }
        catch (\DomainException $e) { return back()->withErrors($e->getMessage()); }
        return back()->with('msg', 'اختلاف بسته شد.');
    }
}
