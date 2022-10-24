<?php

namespace App;

use App\Notifications\PasswordResetUserNotification;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\Session;

/**
 * @method static create(array $array)
 */
class User extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'status',
        'cognito_username',
        'name',
        'email',
        'email_verified'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new PasswordResetUserNotification($token));
    }

    /**
     * セッションへのリレーション設定
     * @return HasMany
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(
            Session::class,
            "cognito_username",
            "cognito_username"
        );
    }
}
