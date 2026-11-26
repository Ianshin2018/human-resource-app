<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeWordExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_word_export_returns_a_generated_download(): void
    {
        Employee::create([
            'employee_number' => 'WORD-001',
            'name' => 'Word 測試員工',
            'department' => '測試部門',
        ]);

        $path = null;

        try {
            $response = $this->actingAs(new User)->get(route('employees.export.word'));

            $response->assertOk()->assertDownload('human-employees.docx');
            $path = $response->baseResponse->getFile()->getPathname();
            $this->assertFileExists($path);
        } finally {
            if (is_string($path) && is_file($path)) {
                unlink($path);
            }
        }
    }
}
