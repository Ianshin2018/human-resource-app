@extends('layouts.app')

@section('content')
<div class="card">
    <div class="card-body text-center">
        <h1 class="h3">人力資料庫系統</h1>
        <p class="text-muted">請登入或建立本機帳號以繼續使用。</p>
        <a class="btn btn-primary" href="{{ route('login') }}">登入</a>
        <a class="btn btn-outline-secondary" href="{{ route('register') }}">建立帳號</a>
    </div>
</div>
@endsection
