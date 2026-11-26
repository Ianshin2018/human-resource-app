<!doctype html>
<html lang="zh-Hant">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>人力資料庫系統</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-dark bg-primary mb-4">
    <div class="container">
        <span class="navbar-brand">人力資料庫系統</span>
        @auth
        <form action="{{ route('logout') }}" method="post">
            @csrf
            <button class="btn btn-outline-light btn-sm">登出</button>
        </form>
        @endauth
    </div>
</nav>
<main class="container pb-5">
    @yield('content')
</main>
</body>
</html>
