@extends('layouts.app')
@section('content')
<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card shadow-sm">
            <div class="card-body">
                <h4 class="mb-3">註冊本機帳號</h4>
                @if ($errors->any())
                    <div class="alert alert-danger">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>
                @endif
                <form method="post" action="{{ route('register') }}">
                    @csrf
                    <div class="mb-3"><label class="form-label">姓名</label><input name="name" class="form-control" value="{{ old('name') }}" required></div>
                    <div class="mb-3"><label class="form-label">電子郵件</label><input type="email" name="email" class="form-control" value="{{ old('email') }}" required></div>
                    <div class="mb-3"><label class="form-label">密碼</label><input type="password" name="password" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">確認密碼</label><input type="password" name="password_confirmation" class="form-control" required></div>
                    <button class="btn btn-primary w-100">註冊</button>
                </form>
                <div class="text-center mt-3"><a href="{{ route('login') }}">已有帳號？前往登入</a></div>
            </div>
        </div>
    </div>
</div>
@endsection
