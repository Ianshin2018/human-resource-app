<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class EmployeeImportUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_preserves_leading_zero_employee_number_and_phone_as_strings(): void
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            ['員工編號', '姓名', '部門', '職稱', '電子郵件', '電話', '到職日期'],
        ]);
        $sheet->setCellValueExplicit('A2', '000456', DataType::TYPE_STRING);
        $sheet->setCellValue('B2', '前導零員工');
        $sheet->setCellValueExplicit('F2', '0912345678', DataType::TYPE_STRING);

        $path = tempnam(sys_get_temp_dir(), 'employees');
        $this->assertNotFalse($path);

        try {
            (new Xlsx($spreadsheet))->save($path);
            $file = new UploadedFile($path, 'employees.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', UPLOAD_ERR_OK, true);

            $response = $this->actingAs(new User)->post(route('employees.import'), ['file' => $file]);

            $response->assertSessionHas('status', '匯入完成，共成功匯入 1 筆資料。');
            $this->assertDatabaseHas('employees', [
                'employee_number' => '000456',
                'phone' => '0912345678',
            ]);
        } finally {
            $spreadsheet->disconnectWorksheets();
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    public function test_mixed_valid_and_invalid_batch_is_not_partially_imported(): void
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            ['員工編號', '姓名', '部門', '職稱', '電子郵件', '電話', '到職日期'],
            ['BATCH-001', '有效員工', '測試部門', '測試職稱', 'valid@example.com', '0911111111', '2024-01-15'],
            ['BATCH-001', '重複員工', '測試部門', '測試職稱', 'duplicate@example.com', '0922222222', '2024-01-16'],
        ]);

        $path = tempnam(sys_get_temp_dir(), 'employees');
        $this->assertNotFalse($path);

        try {
            (new Xlsx($spreadsheet))->save($path);
            $file = new UploadedFile($path, 'employees.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', UPLOAD_ERR_OK, true);

            $response = $this->actingAs(new User)->post(route('employees.import'), ['file' => $file]);

            $response->assertSessionHasErrors();
            $this->assertDatabaseMissing('employees', ['employee_number' => 'BATCH-001']);
        } finally {
            $spreadsheet->disconnectWorksheets();
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    public function test_existing_employee_number_and_leading_zero_values_are_supported(): void
    {
        Employee::create([
            'employee_number' => '000123',
            'name' => '既有員工',
            'department' => '測試部門',
            'phone' => '0912345678',
        ]);

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            ['員工編號', '姓名', '部門', '職稱', '電子郵件', '電話', '到職日期'],
        ]);
        $sheet->setCellValueExplicit('A2', '000123', DataType::TYPE_STRING);
        $sheet->setCellValue('B2', '重複員工');
        $sheet->setCellValueExplicit('F2', '0900000000', DataType::TYPE_STRING);

        $path = tempnam(sys_get_temp_dir(), 'employees');
        $this->assertNotFalse($path);

        try {
            (new Xlsx($spreadsheet))->save($path);
            $file = new UploadedFile($path, 'employees.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', UPLOAD_ERR_OK, true);

            $response = $this->actingAs(new User)->post(route('employees.import'), ['file' => $file]);

            $response->assertSessionHasErrors();
            $this->assertDatabaseCount('employees', 1);
        } finally {
            $spreadsheet->disconnectWorksheets();
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    public function test_php_upload_error_is_reported_with_its_actual_cause(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'upload');
        $this->assertNotFalse($path);

        try {
            $file = new UploadedFile(
                $path,
                'employees.xlsx',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                UPLOAD_ERR_CANT_WRITE,
                true,
            );

            $response = $this->actingAs(new User)->post(route('employees.import'), [
                'file' => $file,
            ]);

            $response->assertSessionHasErrors([
                'file' => '伺服器未能接收檔案（PHP 上傳錯誤碼 7：PHP 無法將檔案寫入暫存資料夾。）請檢查伺服器設定或暫存目錄。',
            ]);
        } finally {
            if (is_string($path) && is_file($path)) {
                unlink($path);
            }
        }
    }

    public function test_import_ignores_empty_formatted_columns_after_the_expected_headers(): void
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            ['員工編號', '姓名', '部門', '職稱', '電子郵件', '電話', '到職日期'],
            ['TEST-IMPORT-001', '測試員工', '測試部門', '測試職稱', 'test@example.com', '0912345678', '2024-01-15'],
        ]);
        $sheet->getStyle('H5')->getFont()->setBold(true);

        $path = tempnam(sys_get_temp_dir(), 'employees');
        $this->assertNotFalse($path);

        try {
            (new Xlsx($spreadsheet))->save($path);
            $file = new UploadedFile(
                $path,
                'employees.xlsx',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                UPLOAD_ERR_OK,
                true,
            );

            $response = $this->actingAs(new User)->post(route('employees.import'), [
                'file' => $file,
            ]);

            $response->assertSessionHas('status', '匯入完成，共成功匯入 1 筆資料。');
            $this->assertDatabaseHas('employees', ['employee_number' => 'TEST-IMPORT-001']);
        } finally {
            $spreadsheet->disconnectWorksheets();
            if (is_string($path) && is_file($path)) {
                unlink($path);
            }
        }
    }

    public function test_import_rejects_data_in_an_unlabeled_column_after_the_expected_fields(): void
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            ['員工編號', '姓名', '部門', '職稱', '電子郵件', '電話', '到職日期'],
            ['TEST-IMPORT-002', '測試員工', '測試部門', '測試職稱', 'test@example.com', '0912345678', '2024-01-15', '多餘資料'],
        ]);
        $sheet->getStyle('H5')->getFont()->setBold(true);

        $path = tempnam(sys_get_temp_dir(), 'employees');
        $this->assertNotFalse($path);

        try {
            (new Xlsx($spreadsheet))->save($path);
            $file = new UploadedFile(
                $path,
                'employees.xlsx',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                UPLOAD_ERR_OK,
                true,
            );

            $response = $this->actingAs(new User)->post(route('employees.import'), [
                'file' => $file,
            ]);

            $response->assertSessionHasErrors([
                'file' => '第 2 列：七個指定欄位以外仍有資料，請移除多餘欄位內容。',
            ]);
            $this->assertDatabaseMissing('employees', ['employee_number' => 'TEST-IMPORT-002']);
        } finally {
            $spreadsheet->disconnectWorksheets();
            if (is_string($path) && is_file($path)) {
                unlink($path);
            }
        }
    }
}
