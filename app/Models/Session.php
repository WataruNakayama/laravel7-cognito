<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Session extends Model
{
    protected $table = "sessions";

    protected $primaryKey = 'uuid';

    protected $keyType = 'string';

    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        "uuid",
        'cognito_username',
        'id_token',
        'access_token',
        'refresh_token',
        'ip_address',
        'user_agent',
    ];

    /**
     * ユーザーへのリレーション設定
     * @return HasOne
     */
    public function user(): HasOne
    {
        return $this->hasOne(
            User::class,
            "cognito_username",
            "cognito_username"
        );
    }
}
