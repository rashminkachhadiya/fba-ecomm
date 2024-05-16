<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class EditSellableUnitRule implements ValidationRule
{
    protected $packOf;
    protected $whQty;
    protected $oldSellableUnit;

    public function __construct($packOf, $whQty, $oldSellableUnit)
    {
        $this->packOf = $packOf;
        $this->whQty = $whQty;
        $this->oldSellableUnit = $oldSellableUnit;
    }
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $index = explode('.', $attribute)[1];

        $oldSellableUnitVal = $this->oldSellableUnit[$index] * $this->packOf[$index];

        $newSellableUnitVal = $value * $this->packOf[$index];

        $diffQty = $newSellableUnitVal - $oldSellableUnitVal;

        $flag = $diffQty <= $this->whQty[$index];

        $sellableUnit = intval($this->whQty[$index] / $this->packOf[$index]);

        if(!$flag)
        {
            $fail("There are only $sellableUnit Sellable unit");
        }
    }
}
