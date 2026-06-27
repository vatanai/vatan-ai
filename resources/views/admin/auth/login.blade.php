<!DOCTYPE html>
<html dir="rtl" lang="fa">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>ورود ادمین — وطن استودیو</title>
<link href="{{ asset('css/fonts.css') }}" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'IRANSansXFaNum',sans-serif;background:#0c0c10;color:#fff;direction:rtl;min-height:100vh;display:flex;align-items:center;justify-content:center;}
.box{background:#111116;border:1px solid #222230;border-radius:20px;padding:36px 32px;width:360px;}
.logo{width:48px;height:48px;border-radius:13px;background:#0BBF53;display:flex;align-items:center;justify-content:center;font-size:22px;font-weight:900;color:#fff;margin:0 auto 16px;box-shadow:0 0 20px rgba(11,191,83,.3);}
h1{text-align:center;font-size:18px;font-weight:800;margin-bottom:4px;}
.sub{text-align:center;font-size:12px;color:#4d7a56;margin-bottom:28px;}
label{font-size:12px;font-weight:600;color:#a8c4a8;display:block;margin-bottom:5px;}
input{width:100%;background:#0c0c10;border:1px solid #222230;border-radius:9px;padding:10px 14px;font-size:13px;color:#fff;font-family:'IRANSansXFaNum',sans-serif;outline:none;direction:rtl;margin-bottom:14px;transition:border-color .15s;}
input:focus{border-color:#a07af5;}
.btn{width:100%;padding:11px;border-radius:10px;border:none;background:#a07af5;color:#fff;font-family:'IRANSansXFaNum',sans-serif;font-size:14px;font-weight:700;cursor:pointer;margin-top:4px;transition:background .15s;}
.btn:hover{background:#8f68e0;}
.error{background:rgba(240,92,92,.1);border:1px solid rgba(240,92,92,.2);color:#f05c5c;border-radius:8px;padding:10px 14px;font-size:12px;margin-bottom:14px;}
</style>
</head>
<body>
<div class="box">
  <div class="logo">و</div>
  <h1>پنل مدیریت</h1>
  <p class="sub">AIPIX Admin Panel</p>
  @if($errors->any())
    <div class="error">{{ $errors->first() }}</div>
  @endif
  <form method="POST" action="{{ route('admin.login.submit') }}">
    @csrf
    <label>ایمیل</label>
    <input type="email" name="email" value="{{ old('email') }}" placeholder="admin@example.com" required autofocus>
    <label>رمز عبور</label>
    <input type="password" name="password" placeholder="••••••••" required>
    <button class="btn" type="submit">ورود به پنل</button>
  </form>
</div>
</body>
</html>
