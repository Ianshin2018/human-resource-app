@extends('layouts.app')
@section('content')
<div class="d-flex flex-wrap gap-2 align-items-center mb-3">
    <h4 class="me-auto mb-0">人員資料</h4>
    <a class="btn btn-success" href="{{ route('employees.export.excel') }}">匯出 Excel</a>
    <a class="btn btn-secondary" href="{{ route('employees.export.word') }}">匯出 Word</a>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="post" action="{{ route('employees.import') }}" enctype="multipart/form-data" class="row g-2">
            @csrf
            <div class="col-md-8"><input type="file" name="file" class="form-control" accept=".xls,.xlsx" required></div>
            <div class="col-md-4"><button class="btn btn-primary w-100">匯入 Excel</button></div>
        </form>
        <small class="text-warning d-block mt-2">支援 .xls、.xlsx 檔案，大小上限為 10 MB；請確認檔案未損毀後再匯入。</small>
        @if (session('status'))<div class="alert alert-success mt-3 mb-0">{{ session('status') }}</div>@endif
        @if ($errors->any())
            <div class="alert alert-danger mt-3 mb-0">
                @foreach ($errors->all() as $error)<div style="white-space: pre-line">{{ $error }}</div>@endforeach
            </div>
        @endif
        <small class="text-muted d-block mt-2">欄位順序：員工編號、姓名、部門、職稱、電子郵件、電話、到職日期</small>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-striped table-hover mb-0">
            <thead class="table-light"><tr><th>員工編號</th><th>姓名</th><th>部門</th><th>職稱</th><th>電子郵件</th><th>電話</th><th>到職日期</th></tr></thead>
            <tbody>
            @forelse ($employees as $employee)
                <tr>
                    <td>{{ $employee->employee_number }}</td><td>{{ $employee->name }}</td><td>{{ $employee->department }}</td>
                    <td>{{ $employee->position }}</td><td>{{ $employee->email }}</td><td>{{ $employee->phone }}</td><td>{{ $employee->hire_date }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-4">目前沒有資料</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
