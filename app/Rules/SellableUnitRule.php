<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Log;

class SellableUnitRule implements ValidationRule
{
    protected $packOf;
    protected $whQty;

    public function __construct($packOf, $whQty)
    {
        $this->packOf = $packOf;
        $this->whQty = $whQty;
    }
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $index = explode('.', $attribute)[1];

        $sellableUnit = intval($this->whQty[$index] / $this->packOf[$index]);

        if(($this->packOf[$index] * $value) > $this->whQty[$index])
        {
            $fail("There are only $sellableUnit Sellable unit");
        }
    }
}
