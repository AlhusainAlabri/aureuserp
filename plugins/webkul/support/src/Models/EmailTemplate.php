<?php

namespace Webkul\Support\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Webkul\Support\Database\Factories\EmailTemplateFactory;

class EmailTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'subject',
        'content',
        'description',
        'layout',
        'variables',
        'is_active',
        'sender_name',
    ];

    protected $casts = [
        'variables' => 'array',
        'is_active' => 'boolean',
    ];

    protected static function newFactory(): EmailTemplateFactory
    {
        return EmailTemplateFactory::new();
    }
}
