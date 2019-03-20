<?php
return [
    'adminEmail' => 'admin@example.com',
    // token有效期是否验证 默认验证
    'user.accessTokenValidity' => true,
    // api接口token有效期 默认 2 小时
    'user.accessTokenExpire' => 2 * 60 * 60,
    // 不需要token验证的方法
    'user.optional' => [
        'login'
    ],
    // 速度控制 6 秒内访问 10 次，注意，数组的第一个不要设置1，设置1会出问题，一定要大于2
    'user.rateLimit' => [8, 10],
    // 默认分页数量
    'user.pageSize' => 10,
];
