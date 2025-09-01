<?php

namespace App\Models;
use Illuminate\Notifications\Notifiable;

class Administrator extends \Encore\Admin\Auth\Database\Administrator
{
    use Notifiable;
    protected $guarded = [];

    protected $fillable = ['email', 'products', 'site_configs', 'username', 'password', 'name', 'avatar', 'agent_id'];


    protected $casts = [
        'products' => 'array',
        'site_configs' => 'array',
    ];

    public function getProductsAttribute($value)
    {
        if (empty($value)) {
            return $value;
        }

        return \json_decode($value, true);
    }
    public function getSiteConfigsAttribute($value)
    {
        if (empty($value)) {
            return $value;
        }

        return \json_decode($value, true);
    }
}
