<?php

namespace App\Models;

use App\Models\Concerns\HasCompanyScope;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'name',
    'type',
    'body',
    'variables',
    'is_active',
])]
class WhatsappTemplate extends Model
{
    use HasCompanyScope;

    protected function casts(): array
    {
        return [
            'variables' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Render the template body by replacing {variable} placeholders.
     *
     * @param  array<string, string>  $data
     */
    public function render(array $data): string
    {
        $body = $this->body;

        foreach ($data as $key => $value) {
            $body = str_replace('{' . $key . '}', (string) $value, $body);
        }

        return $body;
    }
}
