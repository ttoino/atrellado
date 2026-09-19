<?php

namespace App\Models;

use App\Enums\ProviderType;
use Illuminate\Database\Eloquent\Model;

class OAuthUser extends Model
{
    public $timestamps = false;

    protected $casts = [
        'provider_type' => ProviderType::class,
    ];

    protected $fillable = [
        'provider_type',
        'provider_token',
        'user_id',
    ];

    public function user()
    {
        return $this->belongsTo(
            User::class,
            'user_id'
        );
    }

    protected $table = 'oauth_user';
}
