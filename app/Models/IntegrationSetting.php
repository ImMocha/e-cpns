<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\IntegrationSettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['key', 'settings', 'version', 'updated_by'])]
#[Hidden(['settings'])]
class IntegrationSetting extends Model
{
    /** @use HasFactory<IntegrationSettingFactory> */
    use HasFactory;

    public $incrementing = false;

    protected $primaryKey = 'key';

    protected $keyType = 'string';

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['settings' => 'encrypted:array', 'version' => 'integer'];
    }
}
