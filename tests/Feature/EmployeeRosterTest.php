<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeRosterTest extends TestCase
{
    use RefreshDatabase;

    public function test_roster_search_department_filters_and_pagination(): void
    {
        for ($i = 1; $i <= 21; $i++) {
            Employee::create([
                'employee_number' => sprintf('PAGE-%03d', $i),
                'name' => "測試員工{$i}",
                'department' => $i <= 20 ? '人資部' : '財務部',
            ]);
        }

        $response = $this->actingAs(new User)->get(route('employees.index', [
            'search' => 'PAGE',
            'department' => '人資部',
        ]));

        $response->assertOk()
            ->assertSee('PAGE-001')
            ->assertSee('人資部')
            ->assertSee('目前顯示 20 筆，共 21 筆資料');

        $secondPage = $this->actingAs(new User)->get(route('employees.index', [
            'search' => 'PAGE',
            'department' => '人資部',
            'page' => 2,
        ]));

        $secondPage->assertOk()
            ->assertSeeText('目前條件沒有符合的資料');
    }

    public function test_exports_preserve_search_and_department_and_reject_empty_results(): void
    {
        Employee::create([
            'employee_number' => 'EXPORT-001',
            'name' => '匯出員工',
            'department' => '人資部',
        ]);

        $user = new User;
        $this->actingAs($user)
            ->get(route('employees.export.excel', ['department' => '人資部']))
            ->assertOk()
            ->assertDownload('human-employees.xlsx');

        $this->actingAs($user)
            ->get(route('employees.export.excel', ['department' => '不存在部門']))
            ->assertSessionHasErrors(['export' => '目前沒有符合匯出條件的資料，未產生空白匯出檔案。']);

        $this->actingAs($user)
            ->get(route('employees.import.template'))
            ->assertOk()
            ->assertDownload('employee-import-template.xlsx');
    }
}
