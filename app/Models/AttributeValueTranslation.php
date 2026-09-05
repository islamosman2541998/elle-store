<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AttributeValueTranslation extends Model
{
    use HasFactory;

    protected $fillable = [
        'attribute_value_id',
        'locale',
        'value',
    ];

    public function attributeValue()
    {
        return $this->belongsTo(AttributeValue::class);
    }
}