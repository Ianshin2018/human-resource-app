@extends('layouts.app')
@section('content')
<div class="d-flex flex-wrap gap-2 align-items-center mb-3">
    <h4 class="me-auto mb-0">人員資料</h4>
    <a class="btn btn-success" href="{{ route('employees.export.excel', ['search' => $search, 'department' => $department]) }}">匯出目前結果 Excel（{{ $filteredCount }} 筆）</a>
    <a class="btn btn-secondary" href="{{ route('employees.export.word', ['search' => $search, 'department' => $department]) }}">匯出目前結果 Word（{{ $filteredCount }} 筆）</a>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="get" action="{{ route('employees.index') }}" class="row g-2 align-items-end">
            <div class="col-md-5">
                <label for="search" class="form-label">搜尋姓名或員工編號</label>
                <input id="search" type="text" name="search" value="{{ $search }}" class="form-control" placeholder="例如：王小明或 EMP-001">
            </div>
            <div class="col-md-4">
                <label for="department" class="form-label">部門</label>
                <select id="department" name="department" class="form-select">
                    <option value="">全部部門</option>
                    @foreach ($departments as $departmentName)
                        <option value="{{ $departmentName }}" @selected($department === $departmentName)>{{ $departmentName }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button class="btn btn-primary flex-fill">套用條件</button>
                @if ($search !== '' || $department !== '')
                    <a class="btn btn-outline-secondary flex-fill" href="{{ route('employees.index') }}">清除條件</a>
                @endif
            </div>
        </form>
        <div class="text-muted mt-2">
            目前顯示 {{ $filteredCount }} 筆，共 {{ $totalCount }} 筆資料。
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="post" action="{{ route('employees.import') }}" enctype="multipart/form-data" class="row g-2">
            @csrf
            <div class="col-md-8"><input type="file" name="file" class="form-control" accept=".xls,.xlsx" required></div>
            <div class="col-md-4"><button class="btn btn-primary w-100">匯入 Excel</button></div>
        </form>
        <div class="d-flex flex-wrap gap-3 mt-2">
            <a class="link-primary" href="{{ route('employees.import.template') }}">下載 Excel 匯入範本</a>
            <small class="text-warning">支援 .xls、.xlsx，大小上限 10 MB。</small>
        </div>
        @if (session('status'))<div class="alert alert-success mt-3 mb-0">{{ session('status') }}</div>@endif
        @if ($errors->any())
            <div class="alert alert-danger mt-3 mb-0">
                @foreach ($errors->all() as $error)<div style="white-space: pre-line">{{ $error }}</div>@endforeach
            </div>
        @endif
        <small class="text-muted d-block mt-2">欄位順序：員工編號、姓名、部門、職稱、電子郵件、電話、到職日期。只要本批有任何錯誤，整批不寫入；全部通過才一次匯入。</small>
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
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">
                        @if ($totalCount === 0)
                            資料庫目前沒有資料。
                        @else
                            目前條件沒有符合的資料。
                            @if ($search !== '' || $department !== '')
                                <a href="{{ route('employees.index') }}" class="ms-1">清除搜尋條件</a>
                            @endif
                        @endif
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">
    {{ $employees->links() }}
</div>
@endsection
