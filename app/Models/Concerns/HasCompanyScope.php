<?php
namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait HasCompanyScope
{
    protected static function bootHasCompanyScope(): void
    {
        static::addGlobalScope('company', function (Builder $builder) {
            if (app()->bound('currentCompany') && app('currentCompany')) {
                $builder->where(
                    $builder->getModel()->getTable() . '.company_id',
                    app('currentCompany')->id
                );
            }
        });

        static::creating(function ($model) {
            if (
                empty($model->company_id) &&
                app()->bound('currentCompany') &&
                app('currentCompany')
            ) {
                $model->company_id = app('currentCompany')->id;
            }
        });
    }

    public function company()
    {
        return $this->belongsTo(\App\Models\Company::class);
    }
}
