<?php

namespace App\Http\Controllers;

use App\Exports\EmployeesExport;
use App\Exports\EmployeeTemplateExport;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Settings as PhpWordSettings;
use PhpOffice\PhpWord\SimpleType\TblWidth;

class EmployeeController extends Controller
{
    private function employeeQuery(string $search, string $department)
    {
        $query = Employee::query();

        if ($search !== '') {
            $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('employee_number', 'like', "%{$search}%");
            });
        }
        if ($department !== '') {
            $query->where('department', $department);
        }

        return $query->orderBy('id');
    }

    public function index(Request $request)
    {
        $search = trim((string) $request->query('search'));
        $department = trim((string) $request->query('department'));
        $query = $this->employeeQuery($search, $department);

        return view('employees.index', [
            'employees' => $query->paginate(20)->withQueryString(),
            'departments' => Employee::query()
                ->whereNotNull('department')
                ->where('department', '!=', '')
                ->distinct()
                ->orderBy('department')
                ->pluck('department'),
            'search' => $search,
            'department' => $department,
            'filteredCount' => $this->employeeQuery($search, $department)->count(),
            'totalCount' => Employee::query()->count(),
        ]);
    }

    public function downloadTemplate()
    {
        return Excel::download(new EmployeeTemplateExport, 'employee-import-template.xlsx');
    }

    public function import(Request $request)
    {
        $file = $request->file('file');
        if ($file instanceof UploadedFile && ! $file->isValid()) {
            $errorCode = $file->getError();
            Log::warning('Employee Excel upload failed.', ['php_upload_error' => $errorCode]);

            return back()->withErrors([
                'file' => $this->uploadErrorMessage($errorCode),
            ])->withInput();
        }

        $request->validate([
            'file' => ['required', 'file', 'mimes:xls,xlsx', 'max:10240'],
        ], [
            'file.required' => '請選擇要匯入的 Excel 檔案。',
            'file.file' => '上傳內容不是有效的檔案，請重新選擇。',
            'file.mimes' => '檔案格式錯誤，僅支援 .xls 或 .xlsx 檔案。',
            'file.max' => '檔案大小不可超過 10 MB，請縮小檔案後再上傳。',
            'file.uploaded' => '伺服器未能接收上傳檔案，請檢查伺服器視窗中的錯誤訊息。這不是 Excel 欄位或檔案格式檢查結果。',
        ]);

        $rows = Excel::toArray(null, $request->file('file'));
        $sheet = $rows[0] ?? [];
        if (count($sheet) < 2) {
            return back()->withErrors(['file' => 'Excel 沒有資料列。'])->withInput();
        }

        $headers = array_map(fn ($v) => trim((string) $v), $sheet[0]);
        while ($headers !== [] && end($headers) === '') {
            array_pop($headers);
        }
        $expected = array_values([
            '員工編號', '姓名', '部門', '職稱', '電子郵件', '電話', '到職日期',
        ]);
        if ($headers !== $expected) {
            return back()->withErrors([
                'file' => '欄位名稱或順序錯誤。請依序使用：員工編號、姓名、部門、職稱、電子郵件、電話、到職日期。',
            ]);
        }

        $existingNumbers = Employee::pluck('employee_number')->map(fn ($v) => trim((string) $v))->all();
        $seen = [];
        $valid = [];
        $errors = [];

        foreach (array_slice($sheet, 1) as $index => $row) {
            $excelRow = $index + 2;
            if (count(array_filter($row, fn ($v) => $v !== null && trim((string) $v) !== '')) === 0) {
                continue;
            }

            $extraValues = array_slice($row, 7);
            if (array_filter($extraValues, fn ($v) => $v !== null && trim((string) $v) !== '') !== []) {
                $errors[] = "第 {$excelRow} 列：七個指定欄位以外仍有資料，請移除多餘欄位內容。";

                continue;
            }

            $row = array_slice($row, 0, 7);
            $row = array_pad($row, 7, null);
            $data = [
                'employee_number' => trim((string) $row[0]),
                'name' => trim((string) $row[1]),
                'department' => trim((string) $row[2]) ?: null,
                'position' => trim((string) $row[3]) ?: null,
                'email' => trim((string) $row[4]) ?: null,
                'phone' => trim((string) $row[5]) ?: null,
                'hire_date' => $this->normalizeDate($row[6]),
            ];

            $validator = Validator::make($data, [
                'employee_number' => ['required', 'string', 'max:255'],
                'name' => ['required', 'string', 'max:255'],
                'email' => ['nullable', 'email', 'max:255'],
                'phone' => ['nullable', 'string', 'max:255'],
                'hire_date' => ['nullable', 'date_format:Y-m-d'],
            ]);

            $rowErrors = $validator->errors()->all();
            if (in_array($data['employee_number'], $existingNumbers, true)) {
                $rowErrors[] = '員工編號已存在於資料庫';
            }
            if (isset($seen[$data['employee_number']])) {
                $rowErrors[] = '員工編號在本批次重複';
            }
            if ($data['employee_number'] !== '') {
                $seen[$data['employee_number']] = true;
            }

            if ($rowErrors) {
                $errors[] = "第 {$excelRow} 列：".implode('；', $rowErrors);
            } else {
                $valid[] = $data;
            }
        }

        if ($errors) {
            return back()->withErrors(['file' => implode("\n", $errors)]);
        }

        DB::transaction(function () use ($valid) {
            foreach ($valid as $employee) {
                Employee::create($employee);
            }
        });

        return back()->with('status', '匯入完成，共成功匯入 '.count($valid).' 筆資料。');
    }

    private function uploadErrorMessage(int $errorCode): string
    {
        $description = match ($errorCode) {
            UPLOAD_ERR_INI_SIZE => '超過 PHP 設定的單檔上傳限制。',
            UPLOAD_ERR_FORM_SIZE => '超過表單設定的檔案大小限制。',
            UPLOAD_ERR_PARTIAL => '伺服器只收到部分檔案資料。',
            UPLOAD_ERR_NO_TMP_DIR => 'PHP 上傳暫存資料夾不存在或未設定。',
            UPLOAD_ERR_CANT_WRITE => 'PHP 無法將檔案寫入暫存資料夾。',
            UPLOAD_ERR_EXTENSION => 'PHP 擴充模組中止了檔案上傳。',
            UPLOAD_ERR_NO_FILE => '沒有收到檔案。',
            default => 'PHP 未提供有效的上傳檔案狀態。',
        };

        return "伺服器未能接收檔案（PHP 上傳錯誤碼 {$errorCode}：{$description}）請檢查伺服器設定或暫存目錄。";
    }

    private function normalizeDate($value): ?string
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }
        $text = trim((string) $value);
        foreach (['Y-m-d', 'Y/m/d', 'Y年m月d日'] as $format) {
            $date = \DateTime::createFromFormat($format, $text);
            if ($date && $date->format($format) === $text) {
                return $date->format('Y-m-d');
            }
        }

        return $text;
    }

    public function exportExcel()
    {
        $search = trim((string) request()->query('search'));
        $department = trim((string) request()->query('department'));
        $count = $this->employeeQuery($search, $department)->count();
        if ($count === 0) {
            return back()->withErrors(['export' => '目前沒有符合匯出條件的資料，未產生空白匯出檔案。']);
        }

        return Excel::download(new EmployeesExport($search, $department), 'human-employees.xlsx');
    }

    public function exportWord()
    {
        $search = trim((string) request()->query('search'));
        $department = trim((string) request()->query('department'));
        $employees = $this->employeeQuery($search, $department)->get();
        if ($employees->isEmpty()) {
            return back()->withErrors(['export' => '目前沒有符合匯出條件的資料，未產生空白匯出檔案。']);
        }

        $phpWord = new PhpWord;
        $phpWord->setDefaultFontName('Microsoft JhengHei');
        $section = $phpWord->addSection();
        $section->addText('人員資料', ['bold' => true, 'size' => 16]);
        $headers = ['員工編號', '姓名', '部門', '職稱', '電子郵件', '電話', '到職日期'];
        $table = $section->addTable(['borderSize' => 6, 'borderColor' => '888888', 'width' => 100 * 50, 'unit' => TblWidth::PERCENT]);
        $table->addRow();
        foreach ($headers as $header) {
            $table->addCell(1500)->addText($header, ['bold' => true]);
        }
        foreach ($employees as $employee) {
            $table->addRow();
            foreach ([
                $employee->employee_number, $employee->name, $employee->department,
                $employee->position, $employee->email, $employee->phone, $employee->hire_date,
            ] as $value) {
                $table->addCell(1500)->addText((string) $value);
            }
        }

        $filename = 'human-employees.docx';
        $tempDirectory = ini_get('upload_tmp_dir');
        if (! is_string($tempDirectory) || $tempDirectory === '' || ! is_dir($tempDirectory) || ! is_writable($tempDirectory)) {
            $tempDirectory = sys_get_temp_dir();
        }
        if (! is_dir($tempDirectory) || ! is_writable($tempDirectory)) {
            throw new \RuntimeException('沒有可供 Word 匯出使用的暫存資料夾。');
        }

        PhpWordSettings::setTempDir($tempDirectory);
        $path = tempnam($tempDirectory, 'human-employees-');
        if ($path === false) {
            throw new \RuntimeException('無法建立 Word 匯出暫存檔。');
        }

        $phpWord->save($path);

        return response()->download($path, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ])->deleteFileAfterSend(true);
    }
}
