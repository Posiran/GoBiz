<!DOCTYPE html>
<html lang="fa" dir="rtl"><head><meta charset="utf-8"><meta name="robots" content="noindex,nofollow"><title>انتقال به درگاه پرداخت</title></head>
<body style="font-family:Tahoma,sans-serif;text-align:center;padding:60px">
<p>در حال انتقال به درگاه پرداخت...</p>
<form id="f" method="post" action="{{ $url }}">
@foreach($fields as $k => $v)<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endforeach
<noscript><button>ادامه به درگاه پرداخت</button></noscript>
</form>
<script>document.getElementById('f').submit()</script>
</body></html>
