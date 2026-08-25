<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinanceCategory extends Model
{
    use HasFactory;

    public const TYPE_INCOME = 'income';

    public const TYPE_EXPENSE = 'expense';

    public const TYPES = [
        self::TYPE_INCOME => 'Income',
        self::TYPE_EXPENSE => 'Expense',
    ];

    public const CODE_MEMBERSHIPS = 'memberships';

    public const CODE_GROUP_PROGRAMMES = 'group-programmes';

    public const CODE_YOGA = 'yoga-programmes';

    public const CODE_PERSONAL_TRAINING = 'personal-training';

    public const CODE_THERAPY = 'therapy-sessions';

    public const CODE_PRODUCTS = 'product-sales';

    protected $fillable = [
        'name',
        'slug',
        'transaction_type',
        'system_code',
        'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(FinancialTransaction::class);
    }
}
