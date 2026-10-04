<?php
return [
    'defaults'=>['guard'=>env('AUTH_GUARD','web'),'passwords'=>env('AUTH_PASSWORD_BROKER','pengguna')],
    'guards'=>['web'=>['driver'=>'session','provider'=>'pengguna']],
    'providers'=>['pengguna'=>['driver'=>'eloquent','model'=>App\Models\Pengguna::class]],
    'passwords'=>['pengguna'=>['provider'=>'pengguna','table'=>env('AUTH_PASSWORD_RESET_TOKEN_TABLE','password_reset_tokens'),'expire'=>60,'throttle'=>60]],
    'password_timeout'=>10800,
];
