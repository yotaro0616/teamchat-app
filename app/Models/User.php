<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'password' => 'hashed',
    ];

    /**
     * 自分がメンバーになっているチャンネル（channel_user）。
     *
     * data.md 2-3 のとおり channel_user は updated_at を持たないので withTimestamps() は使わない。
     */
    public function channels(): BelongsToMany
    {
        return $this->belongsToMany(Channel::class)->withPivot('created_at');
    }

    /**
     * 自分が投稿したメッセージ（返信を含む）。
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }
}
