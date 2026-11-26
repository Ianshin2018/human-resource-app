<?php

return [
    'confirmed' => ':attribute 與確認欄位不一致。',
    'date_format' => ':attribute：日期格式不正確，請使用年-月-日（例如 2026-11-26）。',
    'email' => ':attribute：格式不正確，請輸入有效的電子郵件地址。',
    'file' => ':attribute 必須是有效檔案。',
    'max' => [
        'file' => ':attribute 檔案大小不可超過 :max KB。',
        'string' => ':attribute 不可超過 :max 個字元。',
    ],
    'min' => [
        'string' => ':attribute 至少需要 :min 個字元。',
    ],
    'mimes' => ':attribute 僅接受 :values 格式的檔案。',
    'required' => ':attribute 為必填欄位。',
    'string' => ':attribute 必須是文字。',
    'unique' => ':attribute 已經被使用。',
    'uploaded' => ':attribute 上傳失敗，請檢查伺服器設定後重試。',
    'attributes' => [
        'name' => '姓名',
        'email' => '電子郵件',
        'password' => '密碼',
        'password_confirmation' => '確認密碼',
        'file' => '檔案',
        'employee_number' => '員工編號',
        'department' => '部門',
        'position' => '職稱',
        'phone' => '電話',
        'hire_date' => '到職日期',
    ],
];
