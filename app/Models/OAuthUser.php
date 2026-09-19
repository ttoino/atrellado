<?php

namespace App\Models;

use App\Enums\ProviderType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OAuthUser extends Model
{
    public $timestamps = false;

    protected $casts = [
        'provider_type' => ProviderType::class,
        'provider_token' => 'encrypted',
        'provider_refresh_token' => 'encrypted',
    ];

    protected $fillable = [
        'provider_type',
        'provider_user_id',
        'provider_token',
        'provider_refresh_token',
        'user_id',
    ];

    /** @return BelongsTo<User, $this> */
    public function user()
    {
        return $this->belongsTo(
            User::class,
            'user_id'
        );
    }

    protected $table = 'oauth_user';
}
