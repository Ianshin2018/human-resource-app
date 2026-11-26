<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ValidationLocalizationTest extends TestCase
{
    public function test_email_and_date_validation_errors_are_traditional_chinese(): void
    {
        $validator = Validator::make([
            'email' => 'not-an-email',
            'hire_date' => '1',
        ], [
            'email' => ['email'],
            'hire_date' => ['date_format:Y-m-d'],
        ]);

        $this->assertSame([
            '電子郵件：格式不正確，請輸入有效的電子郵件地址。',
            '到職日期：日期格式不正確，請使用年-月-日（例如 2026-11-26）。',
        ], $validator->errors()->all());
    }

    public function test_common_http_error_messages_are_traditional_chinese(): void
    {
        $this->assertSame('頁面已過期，請重新載入後再試。', __('Page Expired'));
        $this->assertSame('找不到您要查看的頁面。', __('Not Found'));
        $this->assertSame('伺服器發生錯誤，請稍後再試。', __('Server Error'));
    }
}
